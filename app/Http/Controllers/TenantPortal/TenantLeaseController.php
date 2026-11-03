<?php

namespace App\Http\Controllers\TenantPortal;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenantLeaseController extends Controller
{
    /**
     * Display leases belonging to the signed-in tenant.
     */
    public function index(Request $request)
    {
        $tenant = Tenant::where('user_id', $request->user()->id)->first();

        $leases = Lease::query()
            ->with(['unit.building', 'application'])
            ->where('tenant_id', $tenant?->id ?? 0)
            ->latest()
            ->paginate(10);

        return view('tenant-portal.leases.index', compact('leases'));
    }

    /**
     * Display one lease, after verifying tenant ownership.
     */
    public function show(Request $request, Lease $lease)
    {
        $tenant = Tenant::where('user_id', $request->user()->id)->first();

        abort_unless(
            $tenant && (int) $lease->tenant_id === (int) $tenant->id,
            404
        );

        $lease->load(['unit.building', 'application']);

        return view('tenant-portal.leases.show', compact('lease'));
    }

    /**
     * Accept a lease awaiting tenant acceptance.
     */
    public function accept(Request $request, Lease $lease)
    {
        $tenant = Tenant::where('user_id', $request->user()->id)->first();

        abort_unless(
            $tenant && (int) $lease->tenant_id === (int) $tenant->id,
            404
        );

        $validated = $request->validate([
            'accept_terms' => ['required', 'accepted'],
        ]);

        DB::transaction(function () use ($lease) {
            $lockedLease = Lease::query()
                ->whereKey($lease->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLease->status !== 'draft') {
                throw ValidationException::withMessages([
                    'lease' => 'This lease is not currently awaiting your acceptance.',
                ]);
            }

            if ($lockedLease->tenant_accepted_at !== null) {
                throw ValidationException::withMessages([
                    'lease' => 'You have already accepted this lease.',
                ]);
            }

            $lockedLease->update([
                'tenant_accepted_at' => now(),
                'status' => 'pending_activation',
            ]);
        });

        return redirect()
            ->route('tenant-portal.leases.show', $lease)
            ->with(
                'success',
                'You have accepted the lease. The property manager must confirm the move-in conditions and required payments before activation.'
            );
    }
}