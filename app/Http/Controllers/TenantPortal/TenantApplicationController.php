<?php

namespace App\Http\Controllers\TenantPortal;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantApplication;
use Illuminate\Http\Request;

class TenantApplicationController extends Controller
{
    public function index(Request $request)
    {
        $tenant = Tenant::where('user_id', $request->user()->id)->first();

        $applications = $tenant
            ? TenantApplication::with(['unit.building', 'lease'])
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(10)
            : TenantApplication::whereRaw('1 = 0')->paginate(10);

        return view(
            'tenant-portal.applications.index',
            compact('applications', 'tenant')
        );
    }

    public function show(Request $request, TenantApplication $tenantApplication)
    {
        $tenant = Tenant::where('user_id', $request->user()->id)->first();

        abort_unless(
            $tenant && $tenantApplication->tenant_id === $tenant->id,
            404
        );

        $tenantApplication->load([
            'unit.building',
            'unit.floor',
            'reviewer',
            'lease',
        ]);

        return view(
            'tenant-portal.applications.show',
            compact('tenantApplication')
        );
    }

    public function accept(
        \Illuminate\Http\Request $request,
        \App\Models\Lease $lease
    ) {
        $tenant = \App\Models\Tenant::where(
            'user_id',
            $request->user()->id
        )->first();

        abort_unless(
            $tenant && (int) $lease->tenant_id === (int) $tenant->id,
            404
        );

        $request->validate([
            'accept_terms' => ['required', 'accepted'],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($lease) {
            $lockedLease = \App\Models\Lease::whereKey($lease->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLease->status !== 'pending_tenant_acceptance') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'lease' => 'This lease is not awaiting acceptance.',
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
                'Lease accepted. The property manager must confirm the move-in conditions and required payments before activation.'
            );
    }
}
