<?php

namespace App\Http\Controllers\Admin;

use App\Rules\InternationalPhone;
use App\Support\Phone;
use App\Http\Controllers\Controller;
use App\Mail\TenantAccountCreated;
use App\Models\Tenant;
use App\Models\User;
use App\Support\IssuedCredentials;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::with('user');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('tenant_number', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $tenants = $query->latest()->paginate(15)->withQueryString();

        return view('admin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        return view('admin.tenants.create');
    }

    /**
     * Create a tenant and their login account in one transaction,
     * then email the credentials to the tenant.
     */
    public function store(Request $request)
    {
        Phone::normalizeRequest($request, ['phone', 'alternative_phone', 'emergency_contact_phone']);
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
                'unique:tenants,email',
            ],
            ...$this->profileRules($request),
        ], $this->messages());

        // Temporary password, generated in memory and emailed once.
        $plainPassword = $this->generatePassword();

        try {
            $tenant = DB::transaction(function () use ($request, $validated, $plainPassword) {
                // The User model's "hashed" cast hashes this password automatically.
                $user = User::create([
                    'name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'password' => $plainPassword,
                    'role' => 'tenant',
                ]);

                $user->forceFill(['email_verified_at' => now()])->save();

                return Tenant::create([
                    ...$validated,
                    'user_id' => $user->id,
                    'tenant_number' => $this->generateTenantNumber(),
                    'created_by' => $request->user()?->id,
                ]);
            });
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'The tenant could not be created. Nothing was saved, please try again.');
        }

        $redirect = redirect()->route('admin.tenants.show', $tenant);

        if ($this->sendCredentials($tenant, $plainPassword)) {
            return $redirect->with(
                'success',
                "Tenant and login account created. Credentials were emailed to {$tenant->email}."
            );
        }

        // Fallback: the email failed, so show the password once on the next page
        // (same flash keys the show view already uses) so the admin can hand it over.
        return $redirect
            ->with('success', 'Tenant and login account created, but the credentials email could not be sent. '
                . 'Share the temporary password below securely, or use "Resend credentials" later.')
            ->with('generated_password', $plainPassword)
            ->with('generated_email', $tenant->email);
    }

    public function show(Tenant $tenant)
    {
        $tenant->load('user');

        return view('admin.tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant)
    {
        $tenant->load('user');

        return view('admin.tenants.edit', compact('tenant'));
    }

    /**
     * Update the tenant and synchronize their linked account.
     */
    public function update(Request $request, Tenant $tenant)
    {
        Phone::normalizeRequest($request, ['phone', 'alternative_phone', 'emergency_contact_phone']);
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('tenants', 'email')->ignore($tenant->id),
                Rule::unique('users', 'email')->ignore($tenant->user_id),
            ],
            ...$this->profileRules($request),
        ], $this->messages());

        DB::transaction(function () use ($tenant, $validated) {
            $tenant->update($validated);

            // Load the linked user, including when it was not loaded beforehand.
            $user = $tenant->user;

            if ($user) {
                $user->update([
                    'name' => $validated['full_name'],
                    'email' => $validated['email'],
                ]);
            }
        });

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant updated successfully.');
    }

    /**
     * Archive the tenant and remove tenant-level account access.
     */
    public function destroy(Tenant $tenant)
    {
        DB::transaction(function () use ($tenant) {
            $user = $tenant->user;

            if ($user) {
                // Preserve the login account and its rental/payment history.
                // Change the role so it no longer has tenant-level access.
                $user->update([
                    'role' => 'user',
                ]);
            }

            $tenant->update([
                'status' => 'inactive',
            ]);

            $tenant->delete();
        });

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', 'Tenant archived successfully.');
    }

    /**
     * Generate a fresh password for the tenant's login and email it.
     */
    public function resendCredentials(Tenant $tenant)
    {
        $tenant->load('user');

        if (! $tenant->user) {
            return back()->with('error', 'This tenant has no linked user account.');
        }

        $password = $this->generatePassword();

        $tenant->user->forceFill(['password' => Hash::make($password)])->save();

        $sent = $this->sendCredentials($tenant, $password, reset: true);
        IssuedCredentials::flash($tenant->full_name ?? $tenant->user->name, $tenant->user->email, $password, $sent, 'tenant');

        if ($sent) {
            return back()->with('success', "New credentials were emailed to {$tenant->email}.");
        }

        return back()
            ->with('error', 'The password was reset, but the email could not be sent. '
                . 'Share the login details below directly.');
    }

    private function profileRules(Request $request): array
    {
        $isRwanda = strcasecmp(trim((string) $request->input('country')), 'Rwanda') === 0;
        $provinces = config('rwanda.provinces', []);

        $provinceRules = ['nullable', 'string', 'max:100'];
        $districtRules = ['nullable', 'string', 'max:100'];

        if ($isRwanda) {
            $provinceRules[] = Rule::in(array_keys($provinces));
            // A district must belong to the selected province.
            $districtRules[] = Rule::in($provinces[$request->input('province')] ?? []);
        }

        return [
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30', new InternationalPhone()],
            'alternative_phone' => ['nullable', 'string', 'max:30', new InternationalPhone()],
            'country' => ['required', 'string', 'max:100'],
            'province' => $provinceRules,
            'district' => $districtRules,
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30', new InternationalPhone()],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function messages(): array
    {
        return [
            'email.unique' => 'An account with this email already exists.',
            'district.in' => 'The selected district does not belong to the selected province.',
        ];
    }

    private function sendCredentials(Tenant $tenant, string $password, bool $reset = false): bool
    {
        try {
            Mail::to($tenant->email)->send(new TenantAccountCreated(
                tenantName: $tenant->full_name,
                tenantNumber: $tenant->tenant_number,
                email: $tenant->email,
                password: $password,
                loginUrl: route('login'),
                isReset: $reset,
            ));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    private function generatePassword(): string
    {
        // Readable, no ambiguous characters (0/O, 1/l/I).
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $password = '';

        for ($i = 0; $i < 14; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $password;
    }

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
}