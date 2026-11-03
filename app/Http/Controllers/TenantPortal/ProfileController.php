<?php

namespace App\Http\Controllers\TenantPortal;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    private function tenant(): Tenant
    {
        return Tenant::query()
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    public function show()
    {
        $tenant = \App\Models\Tenant::where('user_id', auth()->id())
            ->firstOrFail();

        return view('tenant-portal.profile.index', compact('tenant'));
    }

    public function edit()
    {
        $tenant = $this->tenant();

        return view('tenant-portal.profile.edit', compact('tenant'));
    }

    public function update(Request $request)
    {
        $tenant = $this->tenant();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('tenants', 'email')->ignore($tenant->id),
            ],
            'phone' => ['required', 'string', 'max:30'],
            'alternative_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],

            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],

            'current_password' => [
                'nullable',
                'required_with:password',
                'current_password',
            ],
            'password' => [
                'nullable',
                'confirmed',
                Password::min(8),
            ],
        ]);

        $tenant->fill([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'alternative_phone' => $validated['alternative_phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? null,
        ]);

        $tenant->save();

        // Keep the linked user account synchronized.
        $user = $request->user();

        if ($user) {
            $user->name = $validated['full_name'];
            $user->email = $validated['email'];

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();
        }

        return redirect()
            ->route('tenant-portal.profile.edit')
            ->with('success', 'Your profile has been updated successfully.');
    }
}
