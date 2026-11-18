<?php

namespace App\Http\Controllers\Admin;

use App\Rules\InternationalPhone;
use App\Support\Phone;
use App\Http\Controllers\Controller;
use App\Mail\OwnerAccountCreated;
use App\Models\PropertyOwner;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class PropertyOwnerController extends Controller
{
    public function index(Request $request): View
    {
        $owners = PropertyOwner::query()
            ->with('user:id,name,email')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('owner_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) =>
                $query->where('status', $request->input('status'))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.owners.index', compact('owners'));
    }

    public function create(): View
    {
        return view('admin.owners.create', [
            'owner' => new PropertyOwner(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $data['owner_number'] = $this->generateOwnerNumber();
        $data['created_by'] = $request->user()->id;

        $password = $this->generatePassword();

        // The user account and the owner record are created together, or not at all.
        try {
            $owner = DB::transaction(function () use ($data, $password) {
                $user = $this->createUserAccount($data, $password);

                return PropertyOwner::create($data + ['user_id' => $user->id]);
            });
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'The owner could not be created. Nothing was saved, please try again.');
        }

        $mailSent = $this->sendCredentials($owner, $password);
        $this->registered($owner);

        return redirect()
            ->to($this->afterStoreUrl($owner))
            ->with('success', $mailSent
                ? "Property owner created. Login credentials were emailed to {$owner->email}."
                : 'Property owner created, but the credentials email could not be sent. '
                    . 'Use "Resend credentials" once the mail settings are fixed.');
    }

    public function show(PropertyOwner $owner): View
    {
        return view('admin.owners.show', compact('owner'));
    }

    public function edit(PropertyOwner $owner): View
    {
        return view('admin.owners.edit', compact('owner'));
    }

    public function update(Request $request, PropertyOwner $owner): RedirectResponse
    {
        $data = $this->validatedData($request, $owner);

        $newPassword = null;

        try {
            DB::transaction(function () use ($owner, $data, &$newPassword) {
                if ($owner->user) {
                    // Keep the login account in sync with the owner profile.
                    $owner->user->forceFill([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'] ?? null,
                    ])->save();
                } elseif (! empty($data['email'])) {
                    // Legacy owner without a login yet: create one now.
                    $newPassword = $this->generatePassword();
                    $data['user_id'] = $this->createUserAccount($data, $newPassword)->id;
                }

                $owner->update($data);
            });
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'The owner could not be updated. Nothing was saved, please try again.');
        }

        $message = 'Property owner updated successfully.';

        if ($newPassword !== null) {
            $message = $this->sendCredentials($owner->fresh(), $newPassword)
                ? "Property owner updated. A login account was created and credentials were emailed to {$owner->email}."
                : 'Property owner updated and a login account was created, but the credentials email '
                    . 'could not be sent. Use "Resend credentials".';
        }

        return redirect()
            ->route('admin.owners.index')
            ->with('success', $message);
    }

    public function destroy(PropertyOwner $owner): RedirectResponse
    {
        // Preserve owner history for future property and finance records.
        $owner->update(['status' => 'inactive']);

        return redirect()
            ->route('admin.owners.index')
            ->with('success', 'Property owner deactivated successfully.');
    }

    /**
     * Generate a fresh password for the linked account and email it to the owner.
     */
    public function resendCredentials(PropertyOwner $owner): RedirectResponse
    {
        if (! $owner->user || ! $owner->email) {
            return back()->with('error', 'This owner has no linked user account or email address.');
        }

        $password = $this->generatePassword();

        $owner->user->forceFill(['password' => Hash::make($password)])->save();

        $sent = $this->sendCredentials($owner, $password, reset: true);

        return back()->with(
            $sent ? 'success' : 'error',
            $sent
                ? "New credentials were emailed to {$owner->email}."
                : 'The password was reset, but the email could not be sent. Check your mail settings.'
        );
    }

    /** Where to go after registering an owner (the field staff portal overrides this). */
    protected function afterStoreUrl(PropertyOwner $owner): string
    {
        return route('admin.owners.index');
    }

    /** Hook run after an owner account has been created. */
    protected function registered(PropertyOwner $owner): void
    {
    }

    protected function validatedData(Request $request, ?PropertyOwner $owner = null): array
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

        // Every owner gets a login, so an email is required whenever one exists or is being created.
        $emailRules = ($owner === null || $owner->user_id)
            ? ['required', 'email', 'max:255']
            : ['nullable', 'email', 'max:255'];

        $emailRules[] = Rule::unique('users', 'email')->ignore($owner?->user_id);

        Phone::normalizeRequest($request, ['phone', 'alternative_phone', 'emergency_contact_phone']);
        return $request->validate([
            'owner_type' => ['required', Rule::in(['individual', 'company'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'max:30', new InternationalPhone()],
            'national_id' => ['nullable', 'string', 'max:100'],
            'company_name' => [
                Rule::requiredIf($request->input('owner_type') === 'company'),
                'nullable', 'string', 'max:255',
            ],
            'country' => ['required', 'string', 'max:100'],
            'province' => $provinceRules,
            'district' => $districtRules,
            'address' => ['nullable', 'string', 'max:255'],
            'verification_status' => [
                'required',
                Rule::in(['pending', 'verified', 'rejected']),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'email.required' => 'An email address is required because the owner receives their login credentials by email.',
            'email.unique' => 'A user account with this email already exists.',
            'company_name.required' => 'Enter the company name for a company owner.',
            'district.in' => 'The selected district does not belong to the selected province.',
        ]);
    }

    protected function createUserAccount(array $data, string $password): User
    {
        $attributes = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ];

        // Optional columns: only filled when your users table has them.
        if (Schema::hasColumn('users', 'role')) {
            $attributes['role'] = 'owner'; // adapt to your role names
        }

        if (Schema::hasColumn('users', 'phone') && ! empty($data['phone'])) {
            $attributes['phone'] = $data['phone'];
        }

        // forceFill avoids depending on the User::$fillable list.
        $user = new User();
        $user->forceFill($attributes)->save();

        // If you use spatie/laravel-permission instead of a role column:
        // $user->assignRole('owner');

        return $user;
    }

    protected function sendCredentials(PropertyOwner $owner, string $password, bool $reset = false): bool
    {
        try {
            Mail::to($owner->email)->send(new OwnerAccountCreated(
                ownerName: $owner->name,
                ownerNumber: $owner->owner_number,
                email: $owner->email,
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

    protected function generatePassword(): string
    {
        // Readable, no ambiguous characters (0/O, 1/l/I).
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $password = '';

        for ($i = 0; $i < 12; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $password;
    }

    protected function generateOwnerNumber(): string
    {
        do {
            $number = 'OWN-' . now()->format('Ymd') . '-'
                . strtoupper(Str::random(6));
        } while (PropertyOwner::where('owner_number', $number)->exists());

        return $number;
    }
}