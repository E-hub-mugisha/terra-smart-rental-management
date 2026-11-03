<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ManagerAccountCreated;
use App\Models\PropertyManager;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class PropertyManagerController extends Controller
{
    public function index(Request $request): View
    {
        $managers = PropertyManager::with('user:id,name,email')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('manager_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when(
                $request->filled('status'),
                fn($query) =>
                $query->where('status', $request->input('status'))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.managers.index', compact('managers'));
    }

    public function create(): View
    {
        return view('admin.managers.create', [
            'manager' => new PropertyManager(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            ...$this->profileRules($request),
        ], $this->messages());

        // No password is typed by the admin: one is generated and emailed.
        $password = $this->generatePassword();

        try {
            $manager = DB::transaction(function () use ($request, $data, $password) {
                $user = new User();
                $user->forceFill([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'role' => 'manager',
                ])->save();

                return PropertyManager::create([
                    ...$this->profileData($data),
                    'user_id' => $user->id,
                    'manager_number' => $this->generateManagerNumber(),
                    'created_by' => $request->user()->id,
                ]);
            });
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'The manager could not be created. Nothing was saved, please try again.');
        }

        $manager->load('user:id,name,email');

        return redirect()
            ->route('admin.managers.index')
            ->with('success', $this->sendCredentials($manager, $password)
                ? "Property manager created. Login credentials were emailed to {$manager->user->email}."
                : 'Property manager created, but the credentials email could not be sent. '
                    . 'Use "Resend credentials" once the mail settings are fixed.');
    }

    public function show(PropertyManager $manager): View
    {
        $manager->load('user:id,name,email');

        return view('admin.managers.show', compact('manager'));
    }

    public function edit(PropertyManager $manager): View
    {
        $manager->load('user:id,name,email');

        return view('admin.managers.edit', compact('manager'));
    }

    public function update(Request $request, PropertyManager $manager): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($manager->user_id),
            ],
            ...$this->profileRules($request),
        ], $this->messages());

        DB::transaction(function () use ($manager, $data) {
            $manager->user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
            ])->save();

            $manager->update($this->profileData($data));
        });

        return redirect()
            ->route('admin.managers.index')
            ->with('success', 'Property manager updated successfully.');
    }

    public function destroy(PropertyManager $manager): RedirectResponse
    {
        // Deactivate rather than delete the account or historical profile.
        DB::transaction(function () use ($manager) {
            $manager->update(['status' => 'inactive']);
        });

        return redirect()
            ->route('admin.managers.index')
            ->with('success', 'Manager deactivated and access downgraded.');
    }

    /**
     * Generate a fresh password for the manager's login and email it.
     */
    public function resendCredentials(PropertyManager $manager): RedirectResponse
    {
        $manager->load('user:id,name,email');

        if (! $manager->user) {
            return back()->with('error', 'This manager has no linked user account.');
        }

        $password = $this->generatePassword();

        $manager->user->forceFill(['password' => Hash::make($password)])->save();

        $sent = $this->sendCredentials($manager, $password, reset: true);

        return back()->with(
            $sent ? 'success' : 'error',
            $sent
                ? "New credentials were emailed to {$manager->user->email}."
                : 'The password was reset, but the email could not be sent. Check your mail settings.'
        );
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
            'phone' => ['nullable', 'string', 'max:30'],
            'country' => ['required', 'string', 'max:100'],
            'province' => $provinceRules,
            'district' => $districtRules,
            'address' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'employment_type' => [
                'required',
                Rule::in(['full_time', 'part_time', 'contract']),
            ],
            'start_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function profileData(array $data): array
    {
        return [
            'phone' => $data['phone'] ?? null,
            'country' => $data['country'],
            'province' => $data['province'] ?? null,
            'district' => $data['district'] ?? null,
            'address' => $data['address'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'employment_type' => $data['employment_type'],
            'start_date' => $data['start_date'] ?? null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function messages(): array
    {
        return [
            'email.unique' => 'A user account with this email already exists.',
            'district.in' => 'The selected district does not belong to the selected province.',
        ];
    }

    private function sendCredentials(PropertyManager $manager, string $password, bool $reset = false): bool
    {
        try {
            Mail::to($manager->user->email)->send(new ManagerAccountCreated(
                managerName: $manager->user->name,
                managerNumber: $manager->manager_number,
                email: $manager->user->email,
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

        for ($i = 0; $i < 12; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $password;
    }

    private function generateManagerNumber(): string
    {
        do {
            $number = 'MGR-' . now()->format('Ymd') . '-'
                . strtoupper(Str::random(6));
        } while (PropertyManager::where('manager_number', $number)->exists());

        return $number;
    }
}