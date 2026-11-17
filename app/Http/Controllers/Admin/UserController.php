<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    private const ROLES = ['admin', 'manager', 'tenant', 'accountant', 'agent', 'technician'];

    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->where('role', $request->input('role'));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $request->only('search', 'role'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLES)],
            'email_verified' => ['sometimes', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'email_verified_at' => $request->boolean('email_verified')
                ? now()
                : null,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$user->name} was created successfully.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(self::ROLES)],
            'email_verified' => ['sometimes', 'boolean'],
        ]);

        if ($request->user()->is($user) && $validated['role'] !== $user->role) {
            return back()->withErrors([
                'role' => 'You cannot change your own role.',
            ]);
        }

        if ($user->isAdmin() && $validated['role'] !== 'admin') {
            if (! User::where('role', 'admin')->whereKeyNot($user->id)->exists()) {
                return back()->withErrors([
                    'role' => 'At least one administrator must remain.',
                ]);
            }
        }

        $emailChanged = strcasecmp($user->email, $validated['email']) !== 0;

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            // The User model's hashed password cast hashes this automatically.
            $data['password'] = $validated['password'];
        }

        if ($request->boolean('email_verified')) {
            $data['email_verified_at'] = $user->email_verified_at ?? now();
        } elseif ($emailChanged) {
            // A changed email should be verified again.
            $data['email_verified_at'] = null;
        } elseif ($request->exists('email_verified')) {
            $data['email_verified_at'] = null;
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$user->name} was updated successfully.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        if ($request->user()->is($user)) {
            return back()->withErrors([
                'role' => 'You cannot change your own role.',
            ]);
        }

        if ($user->isAdmin() && $validated['role'] !== 'admin') {
            $otherAdminsExist = User::where('role', 'admin')
                ->whereKeyNot($user->id)
                ->exists();

            if (! $otherAdminsExist) {
                return back()->withErrors([
                    'role' => 'At least one administrator must remain.',
                ]);
            }
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('success', 'User role updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors([
                'user' => 'You cannot delete your own account from user management.',
            ]);
        }

        if ($user->isAdmin()) {
            $otherAdminsExist = User::where('role', 'admin')
                ->whereKeyNot($user->id)
                ->exists();

            if (! $otherAdminsExist) {
                return back()->withErrors([
                    'user' => 'You cannot delete the last administrator.',
                ]);
            }
        }

        try {
            DB::transaction(function () use ($user) {
                $user->delete();
            });
        } catch (\Illuminate\Database\QueryException $exception) {
            report($exception);

            return back()->withErrors([
                'user' => 'This account could not be deleted because other records may depend on it. Review linked tenant, lease, or property records first.',
            ]);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User account deleted successfully.');
    }
}
