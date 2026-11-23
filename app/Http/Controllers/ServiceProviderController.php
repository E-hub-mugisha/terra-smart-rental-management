<?php

namespace App\Http\Controllers;

use App\Rules\InternationalPhone;
use App\Support\Phone;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Services\PropertyAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Service provider network (brief §15): plumbers, electricians, cleaners…
 * Property staff add providers; Terra admins verify them. A provider with an
 * email gets a technician login to receive and update assigned jobs.
 */
class ServiceProviderController extends Controller
{
    public function __construct(private PropertyAudience $audience)
    {
    }

    public function index(Request $request)
    {
        $this->authorizeStaff($request);

        $providers = ServiceProvider::query()
            ->withCount([
                'jobs as open_jobs_count' => fn ($q) => $q->whereNotIn('status', ['resolved', 'closed', 'rejected']),
                'jobs as done_jobs_count' => fn ($q) => $q->whereIn('status', ['resolved', 'closed']),
            ])
            ->withAvg('jobs as rating_avg', 'tenant_rating')
            ->when($request->filled('category'), fn ($q) => $q->whereJsonContains('categories', $request->query('category')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('company_name', 'like', '%' . $request->query('q') . '%')
                ->orWhere('contact_name', 'like', '%' . $request->query('q') . '%')->orWhere('district', 'like', '%' . $request->query('q') . '%')))
            ->orderByRaw('verified_at is null')->orderBy('company_name')
            ->paginate(20)->withQueryString();

        return view('service-providers.index', ['providers' => $providers, 'isAdmin' => $request->user()->isAdmin()]);
    }

    public function store(Request $request)
    {
        $this->authorizeStaff($request);
        $data = $this->validated($request);

        DB::transaction(function () use ($data, $request) {
            $provider = ServiceProvider::create(collect($data)->except('create_login')->all() + ['created_by' => $request->user()->id]);
            if (! empty($data['create_login']) && ! empty($data['email'])) {
                $this->createLogin($provider);
            }
        });

        return back()->with('success', 'Service provider added.' . (! empty($data['create_login']) && ! empty($data['email']) ? ' A password-setup email was sent to ' . $data['email'] . '.' : ''));
    }

    public function update(Request $request, ServiceProvider $serviceProvider)
    {
        $this->authorizeStaff($request);
        $data = $this->validated($request, $serviceProvider);

        $serviceProvider->update(collect($data)->except('create_login')->all());
        if (! empty($data['create_login']) && $serviceProvider->email && ! $serviceProvider->user_id) {
            $this->createLogin($serviceProvider);
        }

        return back()->with('success', 'Service provider updated.');
    }

    public function verify(Request $request, ServiceProvider $serviceProvider)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $action = $request->validate(['action' => ['required', Rule::in(['verify', 'unverify', 'suspend', 'activate'])]])['action'];

        $serviceProvider->update(match ($action) {
            'verify' => ['verified_at' => now(), 'verified_by' => $request->user()->id],
            'unverify' => ['verified_at' => null, 'verified_by' => null],
            'suspend' => ['status' => 'suspended'],
            'activate' => ['status' => 'active'],
        });

        return back()->with('success', 'Provider ' . $action . ($action === 'verify' ? 'd' : ($action === 'suspend' ? 'ed' : 'd')) . '.');
    }

    /**
     * Admin: delete a provider completely, with their technician login.
     * Their maintenance jobs stay on the property record, unassigned; jobs
     * still in progress go back to "open" so they can be reassigned.
     */
    public function destroy(Request $request, ServiceProvider $serviceProvider)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $name = $serviceProvider->company_name;

        DB::transaction(function () use ($serviceProvider) {
            \App\Models\MaintenanceRequest::withTrashed()
                ->where('service_provider_id', $serviceProvider->id)
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->update(['status' => 'open']);

            \App\Models\MaintenanceRequest::withTrashed()
                ->where('service_provider_id', $serviceProvider->id)
                ->update(['service_provider_id' => null, 'assigned_to' => null]);

            $login = $serviceProvider->user;
            $serviceProvider->delete();

            // The technician login exists only for this provider.
            if ($login && $login->role === 'technician' && ! ServiceProvider::where('user_id', $login->id)->exists()) {
                $login->delete();
            }
        });

        return redirect()->route('service-providers.index')->with('success', "{$name} was deleted.");
    }

    private function createLogin(ServiceProvider $provider): void
    {
        $user = User::where('email', $provider->email)->first();
        if ($user && $user->role !== 'technician') {
            throw \Illuminate\Validation\ValidationException::withMessages(['email' => 'That email already belongs to another Terra account.']);
        }

        $user ??= User::create([
            'name' => $provider->contact_name ?: $provider->company_name,
            'email' => $provider->email,
            'password' => Str::password(32),
            'role' => 'technician',
        ]);

        $provider->update(['user_id' => $user->id]);
        Password::sendResetLink(['email' => $user->email]);
    }

    private function validated(Request $request, ?ServiceProvider $provider = null): array
    {
        Phone::normalizeRequest($request, ['phone', 'alternative_phone', 'emergency_contact_phone']);
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30', new InternationalPhone()],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('service_providers', 'email')->ignore($provider?->id)],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => [Rule::in(array_keys(ServiceProvider::CATEGORIES))],
            'district' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'create_login' => ['nullable', 'boolean'],
        ]);
        $data['categories'] = array_values(array_unique($data['categories']));

        return $data;
    }

    private function authorizeStaff(Request $request): void
    {
        abort_unless($this->audience->isStaff($request->user()), 403);
    }
}
