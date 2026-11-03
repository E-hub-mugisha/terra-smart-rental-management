<?php

namespace App\Http\Controllers\TenantPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lease;

class RentalController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()
            ->tenant()
            ->where('status', 'active')
            ->firstOrFail();

        $leases = Lease::query()
            ->where('tenant_id', $tenant->id)
            ->with([
                'unit.images',
                'unit.floor',
                'unit.building.property',
            ])
            ->latest()
            ->paginate(10);

        return view('tenant-portal.rentals.index', compact('leases'));
    }
}
