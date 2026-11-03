<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\TenantApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenantApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = TenantApplication::with([
            'tenant',
            'unit',
            'reviewer',
            'lease',
        ]);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function ($tenantQuery) use ($search) {
                        $tenantQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('unit', function ($unitQuery) use ($search) {
                        $unitQuery->where('unit_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'all' => TenantApplication::count(),
            'pending' => TenantApplication::where('status', 'pending')->count(),
            'approved' => TenantApplication::where('status', 'approved')->count(),
            'rejected' => TenantApplication::where('status', 'rejected')->count(),
        ];

        return view('admin.tenant-applications.index', compact(
            'applications',
            'counts'
        ));
    }

    public function show(TenantApplication $tenantApplication)
    {
        $tenantApplication->load([
            'tenant.user',
            'unit.building',
            'unit.floor',
            'reviewer',
            'lease',
        ]);

        return view(
            'admin.tenant-applications.show',
            compact('tenantApplication')
        );
    }

    public function approve(Request $request, TenantApplication $tenantApplication)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'monthly_rent' => ['required', 'numeric', 'min:0.01'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'rent_due_day' => ['required', 'integer', 'between:1,28'],
            'payment_frequency' => [
                'required',
                'in:monthly,quarterly,annually',
            ],
            'decision_notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string'],
        ]);

        $lease = DB::transaction(function () use (
            $tenantApplication,
            $validated,
            $request
        ) {
            $application = TenantApplication::whereKey($tenantApplication->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($application->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending applications can be approved.',
                ]);
            }

            if ($application->lease()->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'A lease already exists for this application.',
                ]);
            }

            $unit = $application->unit()
                ->lockForUpdate()
                ->firstOrFail();

            $startDate = $validated['start_date'];
            $endDate = $validated['end_date'] ?? null;

            // Prevent a conflicting lease for the same unit.
            $conflict = Lease::where('unit_id', $unit->id)
                ->whereIn('status', ['active', 'draft'])
                ->whereDate('start_date', '<=', $endDate ?? '9999-12-31')
                ->where(function ($query) use ($startDate) {
                    $query->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $startDate);
                })
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'start_date' => 'This unit has a conflicting lease for the selected dates.',
                ]);
            }

            $lease = Lease::create([
                'tenant_id' => $application->tenant_id,
                'unit_id' => $application->unit_id,
                'tenant_application_id' => $application->id,
                'lease_number' => $this->generateLeaseNumber(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'monthly_rent' => $validated['monthly_rent'],
                'deposit_amount' => $validated['deposit_amount'],
                'currency' => $application->currency ?: 'RWF',
                'rent_due_day' => $validated['rent_due_day'],
                'payment_frequency' => $validated['payment_frequency'],
                'status' => $startDate <= now()->toDateString()
                    ? 'active'
                    : 'draft',
                'terms' => $validated['terms'] ?? null,
            ]);

            $application->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'decision_notes' => $validated['decision_notes'] ?? null,
            ]);

            return $lease;
        });

        return redirect()
            ->route('admin.tenant-applications.show', $tenantApplication)
            ->with('success', "Application approved. Lease {$lease->lease_number} was created.");
    }

    public function reject(Request $request, TenantApplication $tenantApplication)
    {
        $validated = $request->validate([
            'decision_notes' => ['required', 'string', 'max:5000'],
        ]);

        if ($tenantApplication->status !== 'pending') {
            return back()->withErrors([
                'status' => 'Only pending applications can be rejected.',
            ]);
        }

        $tenantApplication->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'decision_notes' => $validated['decision_notes'],
        ]);

        return redirect()
            ->route('admin.tenant-applications.show', $tenantApplication)
            ->with('success', 'Application rejected.');
    }

    private function generateLeaseNumber(): string
    {
        do {
            $number = 'LSE-' . now()->format('YmdHis')
                . '-' . strtoupper(bin2hex(random_bytes(2)));
        } while (Lease::withTrashed()->where('lease_number', $number)->exists());

        return $number;
    }
}