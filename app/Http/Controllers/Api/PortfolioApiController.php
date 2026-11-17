<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\PropertyExpense;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\Unit;
use App\Services\PropertyAudience;
use Illuminate\Http\Request;

/** Landlord / manager mobile experience (brief §18, §22): portfolio, properties, leases, maintenance. */
class PortfolioApiController extends Controller
{
    public function __construct(private PropertyAudience $audience)
    {
    }

    /** The portfolio dashboard numbers from brief §18. */
    public function summary(Request $request)
    {
        $ids = $this->ids($request);
        $unitIds = $this->unitIds($ids);
        $from = now()->startOfMonth();
        $units = Unit::when($unitIds !== null, fn ($q) => $q->whereIn('id', $unitIds))->where('status', '!=', 'inactive');
        $occupied = (clone $units)->where('status', 'occupied')->count();
        $expected = (float) Lease::whereIn('status', ['active'])->when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds))->sum('monthly_rent');
        $collected = (float) RentPayment::where('status', 'confirmed')->when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds))->where('payment_date', '>=', $from)->sum('amount');
        $expenses = (float) PropertyExpense::when($ids !== null, fn ($q) => $q->whereIn('property_id', $ids))->where('expense_date', '>=', $from)->sum('amount');

        return ['data' => [
            'period' => $from->format('Y-m'),
            'properties' => $ids === null ? Property::count() : $ids->count(),
            'units' => (clone $units)->count(),
            'occupied' => $occupied,
            'vacant' => (clone $units)->count() - $occupied,
            'occupancy_rate' => (clone $units)->count() ? round($occupied / (clone $units)->count() * 100, 1) : 0,
            'expected_rent' => $expected,
            'collected' => $collected,
            'outstanding' => (float) RentInvoice::when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds))->whereNotIn('status', ['cancelled', 'void'])->sum('balance_due'),
            'expenses' => $expenses,
            'net_income' => $collected - $expenses,
            'open_maintenance' => MaintenanceRequest::when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds))->whereNotIn('status', ['closed', 'rejected', 'resolved'])->count(),
            'leases_expiring_60_days' => Lease::where('status', 'active')->when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds))->whereBetween('end_date', [now(), now()->addDays(60)])->count(),
        ]];
    }

    public function properties(Request $request)
    {
        $ids = $this->ids($request);

        return Property::when($ids !== null, fn ($q) => $q->whereIn('id', $ids))
            ->withCount(['units as units_total' => fn ($q) => $q->where('units.status', '!=', 'inactive'),
                'units as units_occupied' => fn ($q) => $q->where('units.status', 'occupied')])
            ->orderBy('name')->paginate(50)
            ->through(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'code' => $p->property_code, 'type' => $p->property_type, 'status' => $p->status,
                'location' => $p->location_line, 'latitude' => $p->latitude !== null ? (float) $p->latitude : null, 'longitude' => $p->longitude !== null ? (float) $p->longitude : null,
                'units_total' => $p->units_total, 'units_occupied' => $p->units_occupied,
            ]);
    }

    public function property(Request $request, Property $property)
    {
        abort_unless($this->audience->canViewFinance($request->user(), $property->id), 404);
        $property->load(['buildings' => fn ($q) => $q->orderBy('name'), 'buildings.units' => fn ($q) => $q->where('status', '!=', 'inactive')->orderBy('unit_number'),'buildings.units.leases' => fn ($q) => $q->where('status', 'active')->with('tenant')]);

        return ['data' => [
            'id' => $property->id, 'name' => $property->name, 'upi' => $property->upi, 'location' => $property->location_line,
            'amenities' => array_column($property->amenityList(), 'label'),
            'buildings' => $property->buildings->map(fn ($b) => [
                'id' => $b->id, 'name' => $b->name,
                'units' => $b->units->map(fn ($u) => [
                    'id' => $u->id, 'number' => $u->unit_number, 'status' => $u->status, 'bedrooms' => $u->bedrooms, 'bathrooms' => $u->bathrooms,
                    'rent' => (float) $u->rent_amount, 'currency' => $u->currency,
                    'tenant' => $u->leases->first()?->tenant?->full_name, 'lease_id' => $u->leases->first()?->id,
                ])->values(),
            ])->values(),
        ]];
    }

    public function maintenance(Request $request)
    {
        $unitIds = $this->unitIds($this->ids($request));

        return MaintenanceRequest::when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds))
            ->when($request->query('status') === 'open', fn ($q) => $q->whereNotIn('status', ['closed', 'rejected', 'resolved']))
            ->with(['unit.building.property', 'tenant', 'serviceProvider'])->latest('id')->paginate(20)
            ->through(fn ($m) => [
                'id' => $m->id, 'number' => $m->request_number, 'title' => $m->title, 'priority' => $m->priority, 'status' => $m->status,
                'property' => $m->unit?->building?->property?->name, 'unit' => $m->unit?->unit_number, 'tenant' => $m->tenant?->full_name,
                'provider' => $m->serviceProvider?->company_name, 'created_at' => $m->created_at?->toIso8601String(),
            ]);
    }

    public function payments(Request $request)
    {
        $unitIds = $this->unitIds($this->ids($request));

        return RentPayment::when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds))->with(['tenant', 'unit'])->latest('payment_date')->latest('id')->paginate(20)
            ->through(fn ($p) => [
                'id' => $p->id, 'number' => $p->payment_number, 'tenant' => $p->tenant?->full_name, 'unit' => $p->unit?->unit_number,
                'amount' => (float) $p->amount, 'currency' => $p->currency, 'date' => $p->payment_date?->toDateString(), 'status' => $p->status, 'method' => $p->payment_method,
            ]);
    }

    private function ids(Request $request)
    {
        $ids = $this->audience->financePropertyIds($request->user());
        abort_unless($ids === null || $ids->isNotEmpty(), 403, 'No properties are linked to this account.');

        return $ids;
    }

    private function unitIds($ids)
    {
        return $ids === null ? null : Unit::whereHas('building', fn ($b) => $b->whereIn('property_id', $ids))->pluck('id');
    }
}
