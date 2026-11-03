<?php

namespace App\Http\Controllers\PropertyManager;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\PropertyManager as PropertyManagerModel;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    private function manager(): ?PropertyManagerModel
    {
        return auth()->user()
            ?->propertyManager()
            ->where('status', 'active')
            ->first();
    }

    private function owner()
    {
        return auth()->user()?->propertyOwner;
    }

    private function authorizeAccess(): void
    {
        abort_unless(
            $this->manager() || $this->owner(),
            403,
            'An active property manager or property owner account is required.'
        );
    }

    /**
     * Properties owned by the current user or assigned to their active
     * property manager account.
     */
    private function accessibleProperties(): Builder
    {
        $this->authorizeAccess();

        $manager = $this->manager();
        $owner = $this->owner();

        return Property::query()->where(function (Builder $query) use ($manager, $owner) {
            if ($owner) {
                $query->where('property_owner_id', $owner->id);
            }

            if ($manager) {
                $method = $owner ? 'orWhereHas' : 'whereHas';

                $query->{$method}(
                    'activeManagers',
                    fn(Builder $managerQuery) => $managerQuery->where(
                        'property_managers.id',
                        $manager->id
                    )
                );
            }
        });
    }

    private function propertyIds(): Builder
    {
        return $this->accessibleProperties()->select('properties.id');
    }

    private function unitsQuery(): Builder
    {
        $propertyIds = $this->propertyIds();

        return Unit::query()->whereHas(
            'building',
            fn(Builder $query) => $query->whereIn('property_id', $propertyIds)
        );
    }

    private function leasesQuery(): Builder
    {
        $propertyIds = $this->propertyIds();

        return Lease::query()
            ->with(['tenant', 'unit.building.property'])
            ->whereHas('unit.building', function (Builder $query) use ($propertyIds) {
                $query->whereIn('property_id', $propertyIds);
            });
    }

    private function invoicesQuery(): Builder
    {
        $propertyIds = $this->propertyIds();

        return RentInvoice::query()
            ->with(['unit.building.property'])
            ->whereHas('unit.building', function (Builder $query) use ($propertyIds) {
                $query->whereIn('property_id', $propertyIds);
            });
    }

    private function paymentsQuery(): Builder
    {
        $propertyIds = $this->propertyIds();

        return RentPayment::query()
            ->with(['unit.building.property'])
            ->whereHas('unit.building', function (Builder $query) use ($propertyIds) {
                $query->whereIn('property_id', $propertyIds);
            });
    }

    private function maintenanceQuery(): Builder
    {
        $propertyIds = $this->propertyIds();

        return MaintenanceRequest::query()
            ->with(['tenant', 'unit.building.property'])
            ->whereHas('unit.building', function (Builder $query) use ($propertyIds) {
                $query->whereIn('property_id', $propertyIds);
            });
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();

        $properties = $this->accessibleProperties()
            ->withCount('buildings')
            ->orderBy('name')
            ->get();

        $units = $this->unitsQuery();

        $totalUnits = (clone $units)->count();

        $unitStatuses = (clone $units)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $occupiedUnits = (int) ($unitStatuses['occupied'] ?? 0);
        $availableUnits = (int) ($unitStatuses['available'] ?? 0);
        $otherUnits = max(0, $totalUnits - $occupiedUnits - $availableUnits);

        $occupancyRate = $totalUnits > 0
            ? round(($occupiedUnits / $totalUnits) * 100, 1)
            : 0;

        $leases = $this->leasesQuery();

        $activeLeases = (clone $leases)
            ->where('status', 'active')
            ->count();

        $pendingLeases = (clone $leases)
            ->whereIn('status', ['draft', 'pending'])
            ->count();

        $tenantCount = (clone $leases)
            ->whereNotNull('tenant_id')
            ->distinct()
            ->count('tenant_id');

        $monthlyRentByCurrency = (clone $leases)
            ->where('status', 'active')
            ->selectRaw('currency, SUM(monthly_rent) as total')
            ->groupBy('currency')
            ->get();

        $invoices = $this->invoicesQuery();

        $outstandingInvoices = (clone $invoices)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->count();

        $overdueInvoices = (clone $invoices)
            ->where('status', 'overdue')
            ->count();

        $maintenance = $this->maintenanceQuery();

        $openMaintenance = (clone $maintenance)
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->count();

        $urgentMaintenance = (clone $maintenance)
            ->where('priority', 'urgent')
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->count();

        /*
         * Monthly rent collections for the last six months.
         * RWF and USD remain separate and are never combined.
         */
        $periodStart = now()->startOfMonth()->subMonths(5);
        $periodEnd = now()->endOfMonth();

        $months = collect();
        $monthKeys = [];

        for ($i = 0; $i < 6; $i++) {
            $date = $periodStart->copy()->addMonths($i);
            $key = $date->format('Y-m');

            $monthKeys[] = $key;
            $months->put($key, $date->format('M Y'));
        }

        $paymentRecords = $this->paymentsQuery()
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->get(['id', 'amount', 'currency', 'created_at']);

        $currencies = $paymentRecords
            ->pluck('currency')
            ->filter()
            ->unique()
            ->values();

        $collectionSeries = [];

        foreach ($currencies as $currency) {
            $collectionSeries[$currency] = array_fill(0, 6, 0);
        }

        foreach ($paymentRecords as $payment) {
            $key = $payment->created_at?->format('Y-m');

            if (!$key || !in_array($key, $monthKeys, true)) {
                continue;
            }

            $currency = $payment->currency ?: 'RWF';
            $index = array_search($key, $monthKeys, true);

            if (!isset($collectionSeries[$currency])) {
                $collectionSeries[$currency] = array_fill(0, 6, 0);
            }

            $collectionSeries[$currency][$index] += (float) $payment->amount;
        }

        $monthlyCollections = collect($collectionSeries)
            ->map(fn($values, $currency) => [
                'currency' => $currency,
                'values' => array_map(
                    fn($value) => round($value, 2),
                    array_values($values)
                ),
            ])
            ->values();

        $currentMonthPayments = $paymentRecords->filter(
            fn($payment) => $payment->created_at?->isSameMonth(now())
        );

        $collectionsThisMonth = $currentMonthPayments
            ->groupBy(fn($payment) => $payment->currency ?: 'RWF')
            ->map(fn($records) => round($records->sum('amount'), 2));

        $invoiceStatuses = (clone $invoices)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $maintenanceStatuses = (clone $maintenance)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentLeases = (clone $leases)
            ->latest()
            ->limit(5)
            ->get();

        $recentPayments = $this->paymentsQuery()
            ->latest()
            ->limit(5)
            ->get();

        $recentMaintenance = $this->maintenanceQuery()
            ->latest()
            ->limit(5)
            ->get();

        return view('property-portal.dashboard.index', compact(
            'properties',
            'totalUnits',
            'occupiedUnits',
            'availableUnits',
            'otherUnits',
            'occupancyRate',
            'activeLeases',
            'pendingLeases',
            'tenantCount',
            'monthlyRentByCurrency',
            'outstandingInvoices',
            'overdueInvoices',
            'openMaintenance',
            'urgentMaintenance',
            'months',
            'monthlyCollections',
            'currentMonthPayments', // Added
            'collectionsThisMonth',
            'invoiceStatuses',
            'maintenanceStatuses',
            'recentLeases',
            'recentPayments',
            'recentMaintenance'
        ));
    }
}
