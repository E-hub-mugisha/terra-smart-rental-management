<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\PropertyExpense;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\ServiceProvider;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Report categories from brief §28. Every report returns
 * ['title', 'description', 'columns' => [key => label], 'rows' => Collection, 'totals' => ?array]
 * scoped to a set of property ids (null = whole platform).
 */
class ReportBuilder
{
    public const REPORTS = [
        'Property' => [
            'occupancy' => 'Occupancy & vacancy',
            'tenants' => 'Tenant list',
            'property_performance' => 'Property performance',
        ],
        'Financial' => [
            'rent_collection' => 'Rent collection by month',
            'outstanding' => 'Outstanding rent',
            'income_expenses' => 'Income, expenses & net income',
        ],
        'Lease' => [
            'active_leases' => 'Active leases',
            'expiring_leases' => 'Expiring leases (next 90 days)',
            'renewals_terminations' => 'Renewals & terminations',
        ],
        'Maintenance' => [
            'maintenance_requests' => 'Open & completed requests',
            'maintenance_costs' => 'Maintenance costs by property',
            'technician_performance' => 'Technician performance',
        ],
    ];

    public static function title(string $key): ?string
    {
        foreach (self::REPORTS as $group) {
            if (isset($group[$key])) {
                return $group[$key];
            }
        }

        return null;
    }

    public function build(string $key, ?Collection $propertyIds, Carbon $from, Carbon $to): array
    {
        $report = match ($key) {
            'occupancy' => $this->occupancy($propertyIds),
            'tenants' => $this->tenants($propertyIds),
            'property_performance' => $this->propertyPerformance($propertyIds, $from, $to),
            'rent_collection' => $this->rentCollection($propertyIds, $from, $to),
            'outstanding' => $this->outstanding($propertyIds),
            'income_expenses' => $this->propertyPerformance($propertyIds, $from, $to, true),
            'active_leases' => $this->leases($propertyIds, 'active'),
            'expiring_leases' => $this->leases($propertyIds, 'expiring'),
            'renewals_terminations' => $this->renewalsTerminations($propertyIds, $from, $to),
            'maintenance_requests' => $this->maintenanceRequests($propertyIds, $from, $to),
            'maintenance_costs' => $this->maintenanceCosts($propertyIds, $from, $to),
            'technician_performance' => $this->technicianPerformance($propertyIds, $from, $to),
            default => abort(404),
        };

        return $report + ['title' => self::title($key), 'totals' => $report['totals'] ?? null];
    }

    /* ------------------------------ Property ------------------------------ */

    private function occupancy(?Collection $ids): array
    {
        $rows = $this->properties($ids)->map(function ($p) {
            $units = Unit::whereHas('building', fn ($b) => $b->where('property_id', $p->id))->where('status', '!=', 'inactive')->get(['status', 'rent_amount']);
            $occupied = $units->where('status', 'occupied')->count();
            $total = $units->count();

            return [
                'property' => $p->name,
                'units' => $total,
                'occupied' => $occupied,
                'available' => $units->where('status', 'available')->count(),
                'reserved' => $units->where('status', 'reserved')->count(),
                'other' => $total - $occupied - $units->whereIn('status', ['available', 'reserved'])->count(),
                'occupancy' => $total ? round($occupied / $total * 100, 1) . '%' : '—',
                'potential_rent' => (float) $units->sum('rent_amount'),
            ];
        });
        $t = $rows->sum('units');

        return [
            'description' => 'Unit status per property, excluding inactive units.',
            'columns' => ['property' => 'Property', 'units' => 'Units', 'occupied' => 'Occupied', 'available' => 'Available', 'reserved' => 'Reserved', 'other' => 'Maintenance / vacant', 'occupancy' => 'Occupancy', 'potential_rent' => 'Potential rent / month'],
            'rows' => $rows,
            'totals' => ['property' => 'Total', 'units' => $t, 'occupied' => $rows->sum('occupied'), 'available' => $rows->sum('available'), 'reserved' => $rows->sum('reserved'), 'other' => $rows->sum('other'),
                'occupancy' => $t ? round($rows->sum('occupied') / $t * 100, 1) . '%' : '—', 'potential_rent' => $rows->sum('potential_rent')],
        ];
    }

    private function tenants(?Collection $ids): array
    {
        $rows = $this->leaseQuery($ids)->whereIn('status', ['active', 'pending_activation'])->with(['tenant', 'unit.building.property'])->get()
            ->map(fn ($l) => [
                'tenant' => $l->tenant?->full_name, 'phone' => $l->tenant?->phone, 'email' => $l->tenant?->email,
                'property' => $l->unit?->building?->property?->name, 'unit' => $l->unit?->unit_number, 'lease' => $l->lease_number,
                'rent' => (float) $l->monthly_rent, 'end_date' => $l->end_date?->format('Y-m-d'), 'kyc' => ucfirst((string) ($l->tenant?->kyc_status ?? 'unverified')),
            ])->sortBy('property')->values();

        return [
            'description' => 'Tenants with an active or about-to-start lease.',
            'columns' => ['tenant' => 'Tenant', 'phone' => 'Phone', 'email' => 'Email', 'property' => 'Property', 'unit' => 'Unit', 'lease' => 'Lease', 'rent' => 'Monthly rent', 'end_date' => 'Lease ends', 'kyc' => 'ID check'],
            'rows' => $rows,
        ];
    }

    private function propertyPerformance(?Collection $ids, Carbon $from, Carbon $to, bool $financeOnly = false): array
    {
        $rows = $this->properties($ids)->map(function ($p) use ($from, $to) {
            $unitIds = Unit::whereHas('building', fn ($b) => $b->where('property_id', $p->id))->pluck('id');
            $billed = (float) RentInvoice::whereIn('unit_id', $unitIds)->whereNotIn('status', ['cancelled', 'void'])->whereBetween('issue_date', [$from, $to])->sum('total_amount');
            $collected = (float) RentPayment::whereIn('unit_id', $unitIds)->where('status', 'confirmed')->whereBetween('payment_date', [$from, $to])->sum('amount');
            $expenses = (float) PropertyExpense::where('property_id', $p->id)->whereBetween('expense_date', [$from, $to])->sum('amount');
            $outstanding = (float) RentInvoice::whereIn('unit_id', $unitIds)->whereNotIn('status', ['cancelled', 'void'])->sum('balance_due');
            $units = Unit::whereIn('id', $unitIds)->where('status', '!=', 'inactive')->get(['status']);

            return [
                'property' => $p->name,
                'billed' => $billed,
                'collected' => $collected,
                'collection_rate' => $billed > 0 ? round(min($collected, $billed) / $billed * 100, 1) . '%' : '—',
                'expenses' => $expenses,
                'net_income' => $collected - $expenses,
                'outstanding' => $outstanding,
                'occupancy' => $units->count() ? round($units->where('status', 'occupied')->count() / $units->count() * 100, 1) . '%' : '—',
            ];
        });

        $columns = ['property' => 'Property', 'billed' => 'Rent billed', 'collected' => 'Collected (gross income)', 'collection_rate' => 'Collection rate', 'expenses' => 'Expenses', 'net_income' => 'Net income', 'outstanding' => 'Outstanding now', 'occupancy' => 'Occupancy'];
        if ($financeOnly) {
            unset($columns['occupancy'], $columns['collection_rate']);
        }
        $billed = $rows->sum('billed');

        return [
            'description' => 'Rent billed and collected, expenses and net income between the selected dates.',
            'columns' => $columns,
            'rows' => $rows,
            'totals' => ['property' => 'Total', 'billed' => $billed, 'collected' => $rows->sum('collected'),
                'collection_rate' => $billed > 0 ? round(min($rows->sum('collected'), $billed) / $billed * 100, 1) . '%' : '—',
                'expenses' => $rows->sum('expenses'), 'net_income' => $rows->sum('net_income'), 'outstanding' => $rows->sum('outstanding'), 'occupancy' => ''],
        ];
    }

    /* ------------------------------ Financial ------------------------------ */

    private function rentCollection(?Collection $ids, Carbon $from, Carbon $to): array
    {
        $unitIds = $this->unitIds($ids);
        $rows = collect();
        for ($month = $from->copy()->startOfMonth(); $month->lte($to); $month->addMonth()) {
            $end = $month->copy()->endOfMonth();
            $billed = (float) RentInvoice::when($unitIds, fn ($q) => $q->whereIn('unit_id', $unitIds))->whereNotIn('status', ['cancelled', 'void'])->whereBetween('issue_date', [$month, $end])->sum('total_amount');
            $collected = (float) RentPayment::when($unitIds, fn ($q) => $q->whereIn('unit_id', $unitIds))->where('status', 'confirmed')->whereBetween('payment_date', [$month, $end])->sum('amount');
            $rows->push(['month' => $month->format('M Y'), 'billed' => $billed, 'collected' => $collected,
                'rate' => $billed > 0 ? round(min($collected, $billed) / $billed * 100, 1) . '%' : '—']);
        }

        return [
            'description' => 'Rent invoiced and payments received per calendar month.',
            'columns' => ['month' => 'Month', 'billed' => 'Billed', 'collected' => 'Collected', 'rate' => 'Collection rate'],
            'rows' => $rows,
            'totals' => ['month' => 'Total', 'billed' => $rows->sum('billed'), 'collected' => $rows->sum('collected'),
                'rate' => $rows->sum('billed') > 0 ? round(min($rows->sum('collected'), $rows->sum('billed')) / $rows->sum('billed') * 100, 1) . '%' : '—'],
        ];
    }

    private function outstanding(?Collection $ids): array
    {
        $unitIds = $this->unitIds($ids);
        $rows = RentInvoice::when($unitIds, fn ($q) => $q->whereIn('unit_id', $unitIds))
            ->where('balance_due', '>', 0)->whereNotIn('status', ['cancelled', 'void', 'paid'])
            ->with(['tenant', 'unit.building.property'])->orderBy('due_date')->get()
            ->map(fn ($i) => [
                'tenant' => $i->tenant?->full_name, 'phone' => $i->tenant?->phone, 'property' => $i->unit?->building?->property?->name, 'unit' => $i->unit?->unit_number,
                'invoice' => $i->invoice_number, 'due_date' => $i->due_date?->format('Y-m-d'),
                'days_overdue' => $i->due_date && $i->due_date->isPast() ? (int) $i->due_date->diffInDays(now()) : 0,
                'balance' => (float) $i->balance_due,
            ]);

        return [
            'description' => 'Unpaid and partly paid invoices, oldest first.',
            'columns' => ['tenant' => 'Tenant', 'phone' => 'Phone', 'property' => 'Property', 'unit' => 'Unit', 'invoice' => 'Invoice', 'due_date' => 'Due', 'days_overdue' => 'Days overdue', 'balance' => 'Balance'],
            'rows' => $rows,
            'totals' => ['tenant' => 'Total', 'balance' => $rows->sum('balance')],
        ];
    }

    /* -------------------------------- Lease -------------------------------- */

    private function leases(?Collection $ids, string $which): array
    {
        $q = $this->leaseQuery($ids)->where('status', 'active')->with(['tenant', 'unit.building.property']);
        if ($which === 'expiring') {
            $q->whereBetween('end_date', [now()->startOfDay(), now()->addDays(90)]);
        }
        $rows = $q->orderBy('end_date')->get()->map(fn ($l) => [
            'lease' => $l->lease_number, 'tenant' => $l->tenant?->full_name, 'property' => $l->unit?->building?->property?->name, 'unit' => $l->unit?->unit_number,
            'start' => $l->start_date?->format('Y-m-d'), 'end' => $l->end_date?->format('Y-m-d'),
            'days_left' => $l->end_date ? max(0, (int) now()->startOfDay()->diffInDays($l->end_date, false)) : null, 'rent' => (float) $l->monthly_rent,
        ]);

        return [
            'description' => $which === 'expiring' ? 'Active leases ending within 90 days.' : 'All active leases.',
            'columns' => ['lease' => 'Lease', 'tenant' => 'Tenant', 'property' => 'Property', 'unit' => 'Unit', 'start' => 'Start', 'end' => 'End', 'days_left' => 'Days left', 'rent' => 'Monthly rent'],
            'rows' => $rows,
            'totals' => ['lease' => 'Total', 'rent' => $rows->sum('rent')],
        ];
    }

    private function renewalsTerminations(?Collection $ids, Carbon $from, Carbon $to): array
    {
        $leaseIds = $this->leaseQuery($ids)->pluck('id');
        $renewals = \App\Models\LeaseRequest::whereIn('lease_id', $leaseIds)->where('type', 'renewal')->where('status', 'accepted')
            ->whereBetween('closed_at', [$from, $to->copy()->endOfDay()])->with(['lease.tenant', 'lease.unit.building.property'])->get()
            ->map(fn ($r) => ['date' => $r->closed_at?->format('Y-m-d'), 'type' => 'Renewal', 'lease' => $r->lease?->lease_number, 'tenant' => $r->lease?->tenant?->full_name,
                'property' => $r->lease?->unit?->building?->property?->name, 'unit' => $r->lease?->unit?->unit_number,
                'detail' => 'Extended to ' . $r->offered_end_date?->format('Y-m-d') . ' at ' . number_format((float) $r->offered_rent)]);
        $terminations = $this->leaseQuery($ids)->where('status', 'terminated')->whereBetween('termination_date', [$from, $to])->with(['tenant', 'unit.building.property'])->get()
            ->map(fn ($l) => ['date' => $l->termination_date?->format('Y-m-d'), 'type' => 'Termination', 'lease' => $l->lease_number, 'tenant' => $l->tenant?->full_name,
                'property' => $l->unit?->building?->property?->name, 'unit' => $l->unit?->unit_number, 'detail' => str((string) $l->termination_reason)->limit(80)->toString()]);

        return [
            'description' => 'Lease renewals accepted and leases terminated between the selected dates.',
            'columns' => ['date' => 'Date', 'type' => 'Type', 'lease' => 'Lease', 'tenant' => 'Tenant', 'property' => 'Property', 'unit' => 'Unit', 'detail' => 'Detail'],
            'rows' => $renewals->concat($terminations)->sortBy('date')->values(),
        ];
    }

    /* ----------------------------- Maintenance ----------------------------- */

    private function maintenanceQuery(?Collection $ids, Carbon $from, Carbon $to)
    {
        $unitIds = $this->unitIds($ids);

        return MaintenanceRequest::when($unitIds, fn ($q) => $q->whereIn('unit_id', $unitIds))->whereBetween('created_at', [$from, $to->copy()->endOfDay()]);
    }

    private function maintenanceRequests(?Collection $ids, Carbon $from, Carbon $to): array
    {
        $rows = $this->maintenanceQuery($ids, $from, $to)->with(['unit.building.property', 'serviceProvider', 'tenant'])->latest()->get()
            ->map(fn ($m) => [
                'number' => $m->request_number, 'title' => $m->title, 'property' => $m->unit?->building?->property?->name, 'unit' => $m->unit?->unit_number,
                'priority' => ucfirst((string) $m->priority), 'status' => ucwords(str_replace('_', ' ', (string) $m->status)),
                'provider' => $m->serviceProvider?->company_name, 'opened' => $m->created_at?->format('Y-m-d'),
                'days_to_resolve' => $m->resolved_at ? (int) $m->created_at->diffInDays($m->resolved_at) : null,
                'cost' => (float) $m->actual_cost, 'rating' => $m->tenant_rating,
            ]);

        return [
            'description' => 'Requests opened between the selected dates, with current status.',
            'columns' => ['number' => 'Request', 'title' => 'Title', 'property' => 'Property', 'unit' => 'Unit', 'priority' => 'Priority', 'status' => 'Status', 'provider' => 'Provider', 'opened' => 'Opened', 'days_to_resolve' => 'Days to resolve', 'cost' => 'Cost', 'rating' => 'Tenant rating'],
            'rows' => $rows,
            'totals' => ['number' => 'Total: ' . $rows->count() . ' (' . $rows->whereIn('status', ['Resolved', 'Closed'])->count() . ' completed)', 'cost' => $rows->sum('cost')],
        ];
    }

    private function maintenanceCosts(?Collection $ids, Carbon $from, Carbon $to): array
    {
        $rows = $this->properties($ids)->map(function ($p) use ($from, $to) {
            $q = MaintenanceRequest::whereHas('unit.building', fn ($b) => $b->where('property_id', $p->id))->whereBetween('created_at', [$from, $to->copy()->endOfDay()]);
            $expenses = (float) PropertyExpense::where('property_id', $p->id)->where('category', 'like', '%maint%')->whereBetween('expense_date', [$from, $to])->sum('amount');

            return ['property' => $p->name, 'requests' => (clone $q)->count(), 'completed' => (clone $q)->whereIn('status', ['resolved', 'closed'])->count(),
                'request_costs' => (float) (clone $q)->sum('actual_cost'), 'maintenance_expenses' => $expenses];
        });

        return [
            'description' => 'Repair costs recorded on requests, and maintenance expenses recorded against each property.',
            'columns' => ['property' => 'Property', 'requests' => 'Requests', 'completed' => 'Completed', 'request_costs' => 'Costs on requests', 'maintenance_expenses' => 'Maintenance expenses'],
            'rows' => $rows,
            'totals' => ['property' => 'Total', 'requests' => $rows->sum('requests'), 'completed' => $rows->sum('completed'), 'request_costs' => $rows->sum('request_costs'), 'maintenance_expenses' => $rows->sum('maintenance_expenses')],
        ];
    }

    private function technicianPerformance(?Collection $ids, Carbon $from, Carbon $to): array
    {
        $jobs = $this->maintenanceQuery($ids, $from, $to)->whereNotNull('service_provider_id')->get();
        $rows = $jobs->groupBy('service_provider_id')->map(function ($group, $providerId) {
            $done = $group->whereIn('status', ['resolved', 'closed']);
            $provider = ServiceProvider::find($providerId);

            return [
                'provider' => $provider?->company_name ?? '#' . $providerId,
                'categories' => implode(', ', $provider?->categoryLabels() ?? []),
                'assigned' => $group->count(),
                'completed' => $done->count(),
                'reopened' => $group->filter(fn ($m) => $m->tenant_feedback && ! $m->tenant_confirmed_at)->count(),
                'avg_days' => $done->filter->resolved_at->count() ? round($done->filter->resolved_at->avg(fn ($m) => $m->created_at->diffInDays($m->resolved_at)), 1) : null,
                'avg_rating' => $group->whereNotNull('tenant_rating')->count() ? round($group->whereNotNull('tenant_rating')->avg('tenant_rating'), 1) : null,
                'cost' => (float) $group->sum('actual_cost'),
            ];
        })->sortByDesc('completed')->values();

        return [
            'description' => 'Jobs assigned to each service provider between the selected dates.',
            'columns' => ['provider' => 'Provider', 'categories' => 'Services', 'assigned' => 'Assigned', 'completed' => 'Completed', 'reopened' => 'Reopened by tenant', 'avg_days' => 'Avg. days to resolve', 'avg_rating' => 'Avg. rating', 'cost' => 'Total cost'],
            'rows' => $rows,
        ];
    }

    /* ------------------------------ Helpers ------------------------------ */

    private function properties(?Collection $ids)
    {
        return Property::when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->orderBy('name')->get(['id', 'name']);
    }

    private function unitIds(?Collection $ids): ?Collection
    {
        return $ids === null ? null : Unit::whereHas('building', fn ($b) => $b->whereIn('property_id', $ids))->pluck('id');
    }

    private function leaseQuery(?Collection $ids)
    {
        return Lease::when($ids !== null, fn ($q) => $q->whereHas('unit.building', fn ($b) => $b->whereIn('property_id', $ids)));
    }
}
