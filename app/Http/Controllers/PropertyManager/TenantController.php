<?php

namespace App\Http\Controllers\PropertyManager;

use App\Rules\InternationalPhone;
use App\Support\Phone;
use App\Http\Controllers\Controller;
use App\Mail\TenantAccountCreated;
use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class TenantController extends Controller
{
    /**
     * Get the authenticated user's active property manager account.
     */
    private function manager(): ?PropertyManager
    {
        return auth()->user()
            ?->propertyManager()
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get the authenticated user's property owner account.
     */
    private function owner()
    {
        return auth()->user()?->propertyOwner;
    }

    /**
     * Ensure the authenticated user is either an active manager
     * or a registered property owner.
     */
    private function authorizeTenantAccess(): void
    {
        abort_unless(
            $this->manager() || $this->owner(),
            403,
            'An active property manager or property owner account is required.'
        );
    }

    /**
     * Properties the authenticated user is allowed to manage.
     *
     * Includes:
     * - Properties owned by the authenticated property owner.
     * - Properties assigned to the authenticated property manager.
     */
    private function accessibleProperties(): Builder
    {
        $this->authorizeTenantAccess();

        $manager = $this->manager();
        $owner = $this->owner();

        return Property::query()->where(function (Builder $query) use ($manager, $owner) {

            // Properties belonging to the authenticated owner.
            if ($owner) {
                $query->where('property_owner_id', $owner->id);
            }

            // Properties assigned to the authenticated manager.
            if ($manager) {
                $method = $owner ? 'orWhereHas' : 'whereHas';

                $query->{$method}(
                    'activeManagers',
                    fn(Builder $managerQuery) => $managerQuery->where(
                        'property_managers.id',
                        $manager->id
                    )
                );
            }
        });
    }

    /**
     * Tenants created by the authenticated user or associated
     * with properties they own or manage.
     */
    private function tenantQuery(): Builder
    {
        $userId = auth()->id();

        return Tenant::query()
            ->where(function (Builder $query) use ($userId) {

                // Tenants created by the authenticated user.
                $query->where('created_by', $userId)

                    // Tenants connected through leases.
                    ->orWhereHas(
                        'leases.unit.building',
                        function (Builder $building) {
                            $building->whereIn(
                                'property_id',
                                $this->accessibleProperties()->select('properties.id')
                            );
                        }
                    )

                    // Tenants connected through applications.
                    ->orWhereHas(
                        'applications.unit.building',
                        function (Builder $building) {
                            $building->whereIn(
                                'property_id',
                                $this->accessibleProperties()->select('properties.id')
                            );
                        }
                    );
            });
    }

    /**
     * Retrieve a tenant only if the authenticated user
     * is authorized to access that tenant.
     */
    private function scopedTenant(Tenant $tenant): Tenant
    {
        return $this->tenantQuery()
            ->whereKey($tenant->id)
            ->firstOrFail();
    }

    /**
     * Generate a unique tenant number.
     */
    private function generateTenantNumber(): string
    {
        do {
            $number = 'TEN-' . now()->format('Ymd') . '-'
                . Str::upper(Str::random(5));
        } while (
            Tenant::withTrashed()
            ->where('tenant_number', $number)
            ->exists()
        );

        return $number;
    }

    /**
     * Display tenants.
     */
    public function index(Request $request)
    {
        $this->authorizeTenantAccess();

        $query = $this->tenantQuery()->with('user');

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());

            $query->where(function (Builder $q) use ($search) {
                $q->where('tenant_number', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $tenants = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('property-portal.tenants.index', compact('tenants'));
    }

    /**
     * Show tenant creation form.
     */
    public function create()
    {
        $this->authorizeTenantAccess();

        return view('property-portal.tenants.create');
    }

    /**
     * Create a tenant and their login account.
     */

    public function store(Request $request)
    {
        $this->authorizeTenantAccess();

        Phone::normalizeRequest($request, ['phone', 'alternative_phone', 'emergency_contact_phone']);
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
                'unique:tenants,email',
            ],

            'phone' => ['required', 'string', 'max:30', new InternationalPhone()],
            'alternative_phone' => ['nullable', 'string', 'max:30', new InternationalPhone()],
            'address' => ['nullable', 'string', 'max:2000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30', new InternationalPhone()],

            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],

            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $plainPassword = Str::password(16);

        $tenant = DB::transaction(function () use ($validated, $plainPassword) {

            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => $plainPassword,
                'role' => 'tenant',
            ]);

            return Tenant::create([
                ...$validated,
                'user_id' => $user->id,
                'created_by' => auth()->id(),
                'tenant_number' => $this->generateTenantNumber(),
            ]);
        });

        // Send login credentials to the tenant.
        $emailSent = $this->sendCredentials($tenant, $plainPassword);

        // Prepare redirect.
        $redirect = redirect()
            ->route('property-manager.tenants.show', $tenant);

        if ($emailSent) {
            return $redirect->with(
                'success',
                "Tenant and login account created successfully. Credentials were emailed to {$tenant->email}."
            );
        }

        // Email failed: expose the temporary password once through flash data.
        return $redirect
            ->with(
                'warning',
                'Tenant and login account created, but the credentials email could not be sent. '
                    . 'Share the temporary password securely with the tenant.'
            )
            ->with('generated_password', $plainPassword)
            ->with('generated_email', $tenant->email);
    }



    private function sendCredentials(
        Tenant $tenant,
        string $password,
        bool $reset = false
    ): bool {
        try {
            Mail::to($tenant->email)->send(
                new TenantAccountCreated(
                    tenantName: $tenant->full_name,
                    tenantNumber: $tenant->tenant_number,
                    email: $tenant->email,
                    password: $password,
                    loginUrl: route('login'),
                    isReset: $reset,
                )
            );

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }


    /**
     * Display tenant details.
     */
    public function show(Tenant $tenant)
    {
        $tenant = $this->scopedTenant($tenant);

        $tenant->load([
            'user',
            'leases.unit.building.property',
            'applications.unit.building.property',
            'invoices',
            'payments',
        ]);

        return view('property-portal.tenants.show', compact('tenant'));
    }

    /**
     * Show tenant edit form.
     */
    public function edit(Tenant $tenant)
    {
        $tenant = $this->scopedTenant($tenant);

        return view('property-portal.tenants.edit', compact('tenant'));
    }

    /**
     * Update tenant details.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $tenant = $this->scopedTenant($tenant);

        Phone::normalizeRequest($request, ['phone', 'alternative_phone', 'emergency_contact_phone']);
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('tenants', 'email')->ignore($tenant->id),
                Rule::unique('users', 'email')->ignore($tenant->user_id),
            ],

            'phone' => ['required', 'string', 'max:30', new InternationalPhone()],
            'alternative_phone' => ['nullable', 'string', 'max:30', new InternationalPhone()],
            'address' => ['nullable', 'string', 'max:2000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30', new InternationalPhone()],

            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],

            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($tenant, $validated) {

            $tenant->update($validated);

            if ($tenant->user) {
                $tenant->user->update([
                    'name' => $validated['full_name'],
                    'email' => $validated['email'],
                ]);
            }
        });

        return redirect()
            ->route('property-manager.tenants.show', $tenant)
            ->with('success', 'Tenant updated successfully.');
    }

    /**
     * Archive tenant without destroying rental history.
     */
    public function destroy(Tenant $tenant)
    {
        $tenant = $this->scopedTenant($tenant);

        DB::transaction(function () use ($tenant) {

            // Remove tenant portal access while preserving the user account.
            if ($tenant->user) {
                $tenant->user->update([
                    'role' => 'user',
                ]);
            }

            $tenant->update([
                'status' => 'inactive',
            ]);

            $tenant->delete();
        });

        return redirect()
            ->route('property-manager.tenants.index')
            ->with('success', 'Tenant archived successfully.');
    }
}
