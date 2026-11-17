<?php

namespace App\Http\Controllers\TenantPortal;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Tenant;
use App\Services\LeaseLifecycle;
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
     * Accept a lease that staff have sent (pending_tenant_acceptance → pending_activation).
     */
    public function accept(Request $request, Lease $lease)
    {
        $tenant = Tenant::where('user_id', $request->user()->id)->first();

        abort_unless(
            $tenant && (int) $lease->tenant_id === (int) $tenant->id,
            404
        );

        $request->validate([
            'accept_terms' => ['required', 'accepted'],
        ]);

        app(LeaseLifecycle::class)->accept($lease, $tenant);

        return redirect()
            ->route('tenant-portal.leases.show', $lease)
            ->with(
                'success',
                'You have accepted the lease. Next, pay the deposit and first rent; the property manager then confirms your move-in and activates the lease.'
            );
    }
}