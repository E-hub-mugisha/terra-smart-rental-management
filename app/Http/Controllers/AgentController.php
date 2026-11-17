<?php

namespace App\Http\Controllers;

use App\Models\AgentCommission;
use App\Models\Property;
use App\Models\TenantApplication;
use App\Models\Unit;
use App\Services\PropertyAudience;
use Illuminate\Http\Request;

/**
 * Real estate agent workspace (brief §4D): assigned properties and their
 * vacancies to market, applications from tenants they help acquire, and
 * the commissions recorded for them.
 */
class AgentController extends Controller
{
    public function __construct(private PropertyAudience $audience)
    {
    }

    public function dashboard(Request $request)
    {
        $ids = $this->ids($request);
        $commissions = AgentCommission::where('user_id', $request->user()->id);

        return view('agent.dashboard', [
            'stats' => [
                'properties' => $ids->count(),
                'available_units' => Unit::whereHas('building', fn ($b) => $b->whereIn('property_id', $ids))->where('status', 'available')->count(),
                'open_applications' => TenantApplication::whereHas('unit.building', fn ($b) => $b->whereIn('property_id', $ids))->whereIn('status', ['pending', 'under_review'])->count(),
                'commission_pending' => (float) (clone $commissions)->whereIn('status', ['pending', 'approved'])->sum('amount'),
                'commission_paid' => (float) (clone $commissions)->where('status', 'paid')->sum('amount'),
            ],
            'properties' => $this->propertyList($ids),
            'recentApplications' => $this->applicationQuery($ids)->limit(6)->get(),
        ]);
    }

    public function properties(Request $request)
    {
        return view('agent.properties', ['properties' => $this->propertyList($this->ids($request))]);
    }

    public function applications(Request $request)
    {
        $applications = $this->applicationQuery($this->ids($request))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->paginate(20)->withQueryString();

        return view('agent.applications', compact('applications'));
    }

    public function commissions(Request $request)
    {
        $this->ids($request);

        return view('agent.commissions', [
            'commissions' => AgentCommission::where('user_id', $request->user()->id)->with(['property:id,name', 'lease.tenant'])->latest()->paginate(20),
        ]);
    }

    private function propertyList($ids)
    {
        return Property::whereIn('id', $ids)->with('images')
            ->withCount([
                'units as units_total' => fn ($q) => $q->where('units.status', '!=', 'inactive'),
                'units as units_available' => fn ($q) => $q->where('units.status', 'available'),
            ])
            ->withMin(['units as rent_from' => fn ($q) => $q->where('units.status', 'available')], 'rent_amount')
            ->orderBy('name')->get();
    }

    private function applicationQuery($ids)
    {
        return TenantApplication::whereHas('unit.building', fn ($b) => $b->whereIn('property_id', $ids))
            ->with(['tenant', 'unit.building.property'])->latest('id');
    }

    private function ids(Request $request)
    {
        abort_unless($request->user()->isAgent() || $request->user()->isAdmin(), 403);
        $ids = $request->user()->isAdmin() ? Property::pluck('id') : $this->audience->agentPropertyIds($request->user());

        return $ids;
    }
}
