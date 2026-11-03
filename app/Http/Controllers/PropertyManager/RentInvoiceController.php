<?php

namespace App\Http\Controllers\PropertyManager;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\RentInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RentInvoiceController extends Controller
{
    /**
     * Get the authenticated user's active property manager account.
     */
    private function manager(): ?PropertyManager
    {
        return auth()->user()
            ?->propertyManager()
            ->where('status', 'active')
            ->first();
    }

    /**
     * Get the authenticated user's property owner account.
     */
    private function owner()
    {
        return auth()->user()?->propertyOwner;
    }

    /**
     * Ensure the user is an active manager or property owner.
     */
    private function authorizeInvoiceAccess(): void
    {
        abort_unless(
            $this->manager() || $this->owner(),
            403,
            'An active property manager or property owner account is required.'
        );
    }

    /**
     * Get properties accessible to the authenticated user.
     *
     * Owners can access their own properties.
     * Managers can access properties assigned to them.
     */
    private function accessibleProperties(): Builder
    {
        $this->authorizeInvoiceAccess();

        $manager = $this->manager();
        $owner = $this->owner();

        return Property::query()->where(function (Builder $query) use ($manager, $owner) {

            // Properties belonging to the authenticated owner.
            if ($owner) {
                $query->where('property_owner_id', $owner->id);
            }

            // Properties assigned to the authenticated manager.
            if ($manager) {
                $method = $owner ? 'orWhereHas' : 'whereHas';

                $query->{$method}(
                    'activeManagers',
                    function (Builder $managerQuery) use ($manager) {
                        $managerQuery->where(
                            'property_managers.id',
                            $manager->id
                        );
                    }
                );
            }
        });
    }

    /**
     * Get rent invoices belonging to accessible properties.
     */
    private function invoicesQuery(): Builder
    {
        $propertyIds = $this->accessibleProperties()
            ->select('properties.id');

        return RentInvoice::query()
            ->with([
                'tenant',
                'lease',
                'unit.building.property',
                'allocations.payment',
            ])
            ->whereHas('unit.building', function (Builder $building) use ($propertyIds) {
                $building->whereIn('property_id', $propertyIds);
            });
    }

    /**
     * Retrieve an invoice only if it belongs to an accessible property.
     */
    private function scopedInvoice(int $id): RentInvoice
    {
        return $this->invoicesQuery()->findOrFail($id);
    }

    /**
     * Display rent invoices.
     */
    public function index(Request $request)
    {
        $query = $this->invoicesQuery();

        // Search.
        if ($search = trim($request->input('search', ''))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function (Builder $tenant) use ($search) {
                        $tenant->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('unit', function (Builder $unit) use ($search) {
                        $unit->where('unit_number', 'like', "%{$search}%");
                    });
            });
        }

        // Invoice status filter.
        if ($request->filled('status')) {
            $status = $request->string('status')->toString();

            if (in_array($status, [
                'draft',
                'issued',
                'partially_paid',
                'paid',
                'overdue',
                'cancelled',
            ], true)) {
                $query->where('status', $status);
            }
        }

        // Invoice type filter.
        if ($request->filled('invoice_type')) {
            $query->where('invoice_type', $request->input('invoice_type'));
        }

        // Due date filters.
        if ($request->filled('due_from')) {
            $query->whereDate('due_date', '>=', $request->input('due_from'));
        }

        if ($request->filled('due_to')) {
            $query->whereDate('due_date', '<=', $request->input('due_to'));
        }

        // Paginated invoices.
        $invoices = $query
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Summary statistics.
        $summaryQuery = $this->invoicesQuery();

        $summary = [
            'all' => (clone $summaryQuery)->count(),

            'outstanding' => (clone $summaryQuery)
                ->whereIn('status', [
                    'issued',
                    'partially_paid',
                    'overdue',
                ])
                ->count(),

            'paid' => (clone $summaryQuery)
                ->where('status', 'paid')
                ->count(),

            'overdue' => (clone $summaryQuery)
                ->where(function (Builder $q) {
                    $q->where('status', 'overdue')
                        ->orWhere(function (Builder $sub) {
                            $sub->whereIn('status', [
                                'issued',
                                'partially_paid',
                            ])
                                ->whereDate('due_date', '<', now()->toDateString());
                        });
                })
                ->count(),

            'outstanding_amount' => (clone $summaryQuery)
                ->whereIn('status', [
                    'issued',
                    'partially_paid',
                    'overdue',
                ])
                ->sum('balance_due'),
        ];

        return view('property-portal.rent-invoices.index', compact(
            'invoices',
            'summary'
        ));
    }

    /**
     * Display a rent invoice.
     */
    public function show(int $invoice)
    {
        $invoice = $this->scopedInvoice($invoice);

        $invoice->load([
            'tenant',
            'lease',
            'unit.building.property',
            'allocations.payment',
        ]);

        return view('property-portal.rent-invoices.show', compact('invoice'));
    }
}