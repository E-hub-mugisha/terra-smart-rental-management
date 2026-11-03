<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicTenantApplicationController extends Controller
{
    public function store(Request $request, Unit $unit)
    {
        $user = $request->user();

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'alternative_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
            'desired_move_in_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'occupants' => ['required', 'integer', 'min:1', 'max:50'],
            'employment_status' => [
                'nullable',
                'string',
                'max:100',
            ],
            'offered_rent' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999999999',
            ],
        ]);

        $application = DB::transaction(function () use (
            $unit,
            $user,
            $validated
        ) {
            // Serialize applications for this unit to reduce duplicate
            // pending applications during simultaneous submissions.
            $lockedUnit = Unit::whereKey($unit->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUnit->status !== 'available') {
                throw ValidationException::withMessages([
                    'unit' => 'This unit is no longer available.',
                ]);
            }

            $tenant = Tenant::where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$tenant) {
                // Reuse an existing tenant profile with the same email
                // only when it is not linked to another account.
                $tenant = Tenant::where('email', $user->email)
                    ->whereNull('user_id')
                    ->lockForUpdate()
                    ->first();

                if ($tenant) {
                    $tenant->update([
                        'user_id' => $user->id,
                        'full_name' => $user->name,
                        'phone' => $validated['phone'],
                        'alternative_phone' => $validated['alternative_phone'] ?? null,
                        'address' => $validated['address'] ?? null,
                    ]);
                } else {
                    $tenant = Tenant::create([
                        'user_id' => $user->id,
                        'tenant_number' => $this->generateTenantNumber(),
                        'full_name' => $user->name,
                        'email' => $user->email,
                        'phone' => $validated['phone'],
                        'alternative_phone' => $validated['alternative_phone'] ?? null,
                        'address' => $validated['address'] ?? null,
                        'status' => 'active',
                    ]);
                }
            } else {
                $tenant->update([
                    'phone' => $validated['phone'],
                    'alternative_phone' => $validated['alternative_phone'] ?? $tenant->alternative_phone,
                    'address' => $validated['address'] ?? $tenant->address,
                ]);
            }

            $duplicate = TenantApplication::where('tenant_id', $tenant->id)
                ->where('unit_id', $lockedUnit->id)
                ->where('status', 'pending')
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'unit' => 'You already have a pending application for this unit.',
                ]);
            }

            return TenantApplication::create([
                'tenant_id' => $tenant->id,
                'unit_id' => $lockedUnit->id,
                'application_number' => $this->generateApplicationNumber(),
                'application_date' => now()->toDateString(),
                'desired_move_in_date' => $validated['desired_move_in_date'],
                'occupants' => $validated['occupants'],
                'offered_rent' => $validated['offered_rent'],
                'currency' => $lockedUnit->currency ?: 'RWF',
                'employment_status' => $validated['employment_status'] ?? null,
                'status' => 'pending',
            ]);
        });

        session()->forget('apply_unit_id');

        return redirect()
            ->route('tenant-portal.applications.show', $application)
            ->with('success', 'Your rental application has been submitted successfully.');
    }

    private function generateTenantNumber(): string
    {
        do {
            $number = 'TEN-' . now()->format('Ymd') . '-'
                . Str::upper(Str::random(5));
        } while (Tenant::withTrashed()->where('tenant_number', $number)->exists());

        return $number;
    }

    private function generateApplicationNumber(): string
    {
        do {
            $number = 'APP-' . now()->format('Ymd') . '-'
                . Str::upper(Str::random(6));
        } while (
            TenantApplication::withTrashed()
                ->where('application_number', $number)
                ->exists()
        );

        return $number;
    }
}