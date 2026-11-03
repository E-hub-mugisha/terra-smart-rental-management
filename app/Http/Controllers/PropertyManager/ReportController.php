<?php

namespace App\Http\Controllers\PropertyManager;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\PropertyExpense;
use App\Models\PropertyManager as PropertyManagerModel;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
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
     * Properties owned by the user or assigned to their active manager account.
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
                    fn (Builder $managerQuery) => $managerQuery->where(
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

    private function expensesQuery(): Builder
    {
        $propertyIds = $this->propertyIds();

        return PropertyExpense::query()
            ->with('property')
            ->whereIn('property_id', $propertyIds);
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

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        $propertyIds = $this->propertyIds();

        // Property and unit statistics.
        $properties = $this->accessibleProperties()
            ->withCount(['buildings'])
            ->orderBy('name')
            ->get();

        $unitsQuery = Unit::query()
            ->whereHas('building', function (Builder $query) use ($propertyIds) {
                $query->whereIn('property_id', $propertyIds);
            });

        $totalUnits = (clone $unitsQuery)->count();
        $occupiedUnits = (clone $unitsQuery)
            ->where('status', 'occupied')
            ->count();

        $availableUnits = (clone $unitsQuery)
            ->where('status', 'available')
            ->count();

        $occupancyRate = $totalUnits > 0
            ? round(($occupiedUnits / $totalUnits) * 100, 1)
            : 0;

        // Lease statistics.
        $leases = $this->leasesQuery();

        $totalLeases = (clone $leases)->count();
        $activeLeases = (clone $leases)
            ->where('status', 'active')
            ->count();

        $draftLeases = (clone $leases)
            ->where('status', 'draft')
            ->count();

        // Tenant count based on distinct tenants with leases in accessible properties.
        $tenantCount = (clone $this->leasesQuery())
            ->whereNotNull('tenant_id')
            ->distinct('tenant_id')
            ->count('tenant_id');

        // Rent invoices within the selected period.
        $invoices = $this->invoicesQuery()
            ->whereBetween('created_at', [$from, $to]);

        $invoiceCount = (clone $invoices)->count();

        $outstandingInvoices = (clone $invoices)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->count();

        $overdueInvoices = (clone $invoices)
            ->where('status', 'overdue')
            ->count();

        // Payments within the selected period.
        $payments = $this->paymentsQuery()
            ->whereBetween('created_at', [$from, $to]);

        $paymentCount = (clone $payments)->count();

        $collectionsByCurrency = (clone $payments)
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        // Expenses within the selected period.
        $expenses = $this->expensesQuery()
            ->whereBetween('expense_date', [
                $from->toDateString(),
                $to->toDateString(),
            ]);

        $expenseCount = (clone $expenses)->count();

        $expensesByCurrency = (clone $expenses)
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        // Maintenance performance within the selected period.
        $maintenance = $this->maintenanceQuery()
            ->whereBetween('created_at', [$from, $to]);

        $maintenanceCount = (clone $maintenance)->count();

        $openMaintenance = (clone $maintenance)
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->count();

        $resolvedMaintenance = (clone $maintenance)
            ->whereIn('status', ['resolved', 'closed'])
            ->count();

        $urgentMaintenance = (clone $maintenance)
            ->where('priority', 'urgent')
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->count();

        // Latest operational records.
        $recentPayments = (clone $payments)
            ->latest()
            ->limit(5)
            ->get();

        $recentExpenses = (clone $expenses)
            ->latest('expense_date')
            ->limit(5)
            ->get();

        $recentMaintenance = (clone $maintenance)
            ->latest()
            ->limit(5)
            ->get();

        return view('property-portal.reports.index', compact(
            'from',
            'to',
            'properties',
            'totalUnits',
            'occupiedUnits',
            'availableUnits',
            'occupancyRate',
            'totalLeases',
            'activeLeases',
            'draftLeases',
            'tenantCount',
            'invoiceCount',
            'outstandingInvoices',
            'overdueInvoices',
            'paymentCount',
            'collectionsByCurrency',
            'expenseCount',
            'expensesByCurrency',
            'maintenanceCount',
            'openMaintenance',
            'resolvedMaintenance',
            'urgentMaintenance',
            'recentPayments',
            'recentExpenses',
            'recentMaintenance'
        ));
    }
}