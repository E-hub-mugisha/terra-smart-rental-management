<?php

namespace App\Http\Controllers\TenantPortal;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Retrieve the tenant linked to the authenticated account.
        $tenant = Tenant::query()
            ->with('user')
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Only retrieve leases belonging to this tenant.
        $leaseQuery = Lease::query()
            ->where('tenant_id', $tenant->id);

        $activeLeaseCount = (clone $leaseQuery)
            ->where('status', 'active')
            ->count();

        $pendingLeaseCount = (clone $leaseQuery)
            ->where('status', 'draft')
            ->count();

        $activeLeases = (clone $leaseQuery)
            ->where('status', 'active')
            ->get(['id', 'monthly_rent', 'currency']);

        // Separate currencies so RWF and USD are not added together.
        $monthlyRentRwf = $activeLeases
            ->filter(fn($lease) => strtoupper($lease->currency ?: 'RWF') === 'RWF')
            ->sum('monthly_rent');

        $monthlyRentUsd = $activeLeases
            ->filter(fn($lease) => strtoupper($lease->currency ?: 'RWF') === 'USD')
            ->sum('monthly_rent');

        $recentLeases = (clone $leaseQuery)
            ->with([
                'unit.floor',
                'unit.building.property',
            ])
            ->latest('id')
            ->take(5)
            ->get();

        // Only show maintenance requests belonging to this tenant.
        $maintenanceQuery = MaintenanceRequest::query()
            ->where('tenant_id', $tenant->id);

        $openMaintenanceCount = (clone $maintenanceQuery)
            ->whereNotIn('status', [
                'resolved',
                'closed',
                'rejected',
            ])
            ->count();

        $recentMaintenanceRequests = (clone $maintenanceQuery)
            ->with([
                'unit.building',
            ])
            ->latest()
            ->take(5)
            ->get();

        $leases = (clone $leaseQuery)
            ->with(['unit.floor', 'unit.building.property'])
            ->latest('id')
            ->get();

        return view('tenant-portal.dashboards.index', compact(
            'tenant',
            'leases',
            'activeLeaseCount',
            'pendingLeaseCount',
            'monthlyRentRwf',
            'monthlyRentUsd',
            'recentLeases',
            'openMaintenanceCount',
            'recentMaintenanceRequests'
        ));
    }
}
