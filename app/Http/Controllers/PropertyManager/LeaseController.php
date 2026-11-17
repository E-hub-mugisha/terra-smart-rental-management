<?php

namespace App\Http\Controllers\PropertyManager;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Services\LeaseLifecycle;
use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\RentPayment;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\RentInvoice;
use App\Models\RentPaymentAllocation;
use Illuminate\Support\Str;

class LeaseController extends Controller
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
     * Ensure the authenticated user is either an active manager
     * or a registered property owner.
     */
    private function authorizeLeaseAccess(): void
    {
        abort_unless(
            $this->manager() || $this->owner(),
            403,
            'An active property manager or property owner account is required.'
        );
    }

    /**
     * Properties accessible to the authenticated user.
     *
     * Property owners:
     * - Can access properties they own.
     *
     * Property managers:
     * - Can access properties actively assigned to them.
     */
    private function accessibleProperties(): Builder
    {
        $this->authorizeLeaseAccess();

        $manager = $this->manager();
        $owner = $this->owner();

        return Property::query()->where(function (Builder $query) use (
            $manager,
            $owner
        ) {
            // Property owner access.
            if ($owner) {
                $query->where('property_owner_id', $owner->id);
            }

            // Assigned property manager access.
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
     * Retrieve leases belonging to properties accessible
     * to the authenticated owner or manager.
     */
    private function leasesQuery(): Builder
    {
        $propertyIds = $this->accessibleProperties()
            ->select('properties.id');

        return Lease::query()
            ->whereHas('unit.building', function (Builder $building) use (
                $propertyIds
            ) {
                $building->whereIn('property_id', $propertyIds);
            });
    }

    /**
     * Retrieve a lease only if it belongs to an accessible property.
     */
    private function scopedLease(Lease $lease): Lease
    {
        return $this->leasesQuery()
            ->whereKey($lease->id)
            ->firstOrFail();
    }


    public function index(Request $request)
    {
        $statuses = [
            'draft',
            'pending_tenant_acceptance',
            'pending_activation',
            'active',
            'expired',
            'terminated',
            'cancelled',
        ];

        $query = $this->leasesQuery()
            ->with(['tenant', 'unit.building.property']);

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());

            $query->where(function (Builder $q) use ($search) {
                $q->where('lease_number', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function (Builder $tenantQuery) use ($search) {
                        $tenantQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('unit', function (Builder $unitQuery) use ($search) {
                        $unitQuery->where('unit_number', 'like', "%{$search}%");
                    });
            });
        }

        if (in_array($request->input('status'), $statuses, true)) {
            $query->where('status', $request->input('status'));
        }

        $base = $this->leasesQuery();

        $counts = [
            'all' => (clone $base)->count(),
            'draft' => (clone $base)->where('status', 'draft')->count(),
            'pending_tenant_acceptance' => (clone $base)->where('status', 'pending_tenant_acceptance')->count(),
            'pending_activation' => (clone $base)->where('status', 'pending_activation')->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'expired' => (clone $base)->where('status', 'expired')->count(),
            'terminated' => (clone $base)->where('status', 'terminated')->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
        ];

        $leases = $query->latest()->paginate(15)->withQueryString();

        return view('property-portal.leases.index', compact(
            'leases',
            'counts',
            'statuses'
        ));
    }

    public function show(Lease $lease)
    {
        $lease = $this->scopedLease($lease);

        $lease->load([
            'tenant.user',
            'unit.floor',
            'unit.building.property',
            'invoices.allocations.payment',
            'payments',
            'application',
        ]);

        $completedDepositPayments = $this->confirmedDepositPaid($lease);

        return view('property-portal.leases.show', compact(
            'lease',
            'completedDepositPayments'
        ));
    }
    public function edit(Lease $lease)
    {
        $lease = $this->scopedLease($lease);

        abort_unless(
            $lease->status === 'draft',
            403,
            'Only draft leases can be edited.'
        );

        // Once accepted, the terms should no longer be edited.
        abort_if(
            $lease->tenant_accepted_at,
            403,
            'An accepted lease cannot be edited.'
        );

        $lease->load(['tenant', 'unit.building.property']);

        return view('property-portal.leases.edit', compact('lease'));
    }

    public function update(Request $request, Lease $lease)
    {
        $lease = $this->scopedLease($lease);

        abort_unless(
            $lease->status === 'draft' && !$lease->tenant_accepted_at,
            403,
            'Only unaccepted draft leases can be edited.'
        );

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(['RWF', 'USD', 'EUR'])],
            'rent_due_day' => ['required', 'integer', 'between:1,28'],
            'payment_frequency' => [
                'required',
                Rule::in(['monthly', 'quarterly', 'yearly']),
            ],
            'terms' => ['nullable', 'string', 'max:10000'],
        ]);

        DB::transaction(function () use ($lease, $validated) {
            $lockedLease = $this->leasesQuery()
                ->whereKey($lease->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedLease->status !== 'draft'
                || $lockedLease->tenant_accepted_at
            ) {
                throw ValidationException::withMessages([
                    'lease' => 'This lease is no longer an editable draft.',
                ]);
            }

            $this->assertNoOverlappingLease(
                $lockedLease,
                $validated['start_date'],
                $validated['end_date']
            );

            $lockedLease->update($validated);
        });

        return redirect()
            ->route('property-manager.leases.show', $lease)
            ->with('success', 'Draft lease updated successfully.');
    }

    /**
     * Send the draft to the tenant (draft → pending_tenant_acceptance) and reserve the unit.
     */
    public function sendForSignature(Lease $lease)
    {
        $lease = $this->scopedLease($lease);
        app(LeaseLifecycle::class)->sendToTenant($lease);

        return redirect()
            ->route('property-manager.leases.show', $lease)
            ->with('success', 'Lease sent. The tenant can now review and accept it in their portal.');
    }

    /**
     * Take an unaccepted lease back to draft to change its terms.
     */
    public function recall(Lease $lease)
    {
        $lease = $this->scopedLease($lease);
        app(LeaseLifecycle::class)->recall($lease);

        return redirect()
            ->route('property-manager.leases.show', $lease)
            ->with('success', 'Lease recalled to draft. Edit the terms, then send it again.');
    }

    /**
     * Record that the tenant has moved in.
     */
    public function confirmMoveIn(Lease $lease)
    {
        $lease = $this->scopedLease($lease);
        app(LeaseLifecycle::class)->confirmMoveIn($lease);

        return back()->with('success', 'Move-in confirmed.');
    }
    /**
     * Activate (pending_activation → active) once the tenant has accepted,
     * the first invoices are paid and move-in is confirmed. Ticking
     * "move-in confirmed" on the activation form records move-in as well.
     */
    public function activate(Request $request, Lease $lease)
    {
        $lease = $this->scopedLease($lease);
        $lifecycle = app(LeaseLifecycle::class);

        if ($request->boolean('move_in_confirmed') && ! $lease->move_in_confirmed_at) {
            $lifecycle->confirmMoveIn($lease);
        }
        $lifecycle->activate($lease);

        return redirect()
            ->route('property-manager.leases.show', $lease)
            ->with('success', 'Lease activated. The unit is now marked as occupied.');
    }
    public function terminate(Request $request, Lease $lease)
    {
        $lease = $this->scopedLease($lease);

        $validated = $request->validate([
            'termination_reason' => ['required', 'string', 'max:5000'],
            'termination_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
        ]);

        abort_unless(
            $lease->status === 'active',
            403,
            'Only active leases can be terminated.'
        );

        DB::transaction(function () use ($lease, $validated) {
            $lockedLease = $this->leasesQuery()
                ->whereKey($lease->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLease->status !== 'active') {
                throw ValidationException::withMessages([
                    'lease' => 'This lease is no longer active.',
                ]);
            }

            $unit = Unit::query()
                ->whereKey($lockedLease->unit_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedLease->termination_date = $validated['termination_date'];
            $lockedLease->termination_reason = $validated['termination_reason'];
            $lockedLease->status = 'terminated';
            $lockedLease->save();

            $today = now()->toDateString();

            $otherActiveLease = Lease::query()
                ->where('unit_id', $unit->id)
                ->where('id', '!=', $lockedLease->id)
                ->where('status', 'active')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->exists();

            if (!$otherActiveLease) {
                $unit->status = 'available';
                $unit->save();
            }
        });

        return redirect()
            ->route('property-manager.leases.show', $lease)
            ->with('success', 'Lease terminated successfully.');
    }

    /**
     * Deposit actually received: confirmed payments allocated to the lease's
     * deposit invoices (the same rule activation uses), plus any legacy
     * confirmed payments recorded with the "security_deposit" method.
     */
    private function confirmedDepositPaid(Lease $lease): float
    {
        $allocated = \App\Models\RentPaymentAllocation::query()
            ->whereHas('invoice', fn (Builder $q) => $q->where('lease_id', $lease->id)->where('invoice_type', 'deposit'))
            ->whereHas('payment', fn (Builder $q) => $q->where('status', 'confirmed'))
            ->sum('amount');

        $legacy = RentPayment::query()
            ->where('lease_id', $lease->id)
            ->where('status', 'confirmed')
            ->where('payment_method', 'security_deposit')
            ->sum('amount');

        return (float) $allocated + (float) $legacy;
    }

    private function assertNoOverlappingLease(Lease $lease, ?string $startDate = null, ?string $endDate = null): void
    {
        app(LeaseLifecycle::class)->assertNoOverlap($lease, $startDate, $endDate);
    }
    /**
     * Generate the security deposit and initial rent invoices.
     */
    public function generateRequiredInvoices(Lease $lease)
    {
        $lease = $this->scopedLease($lease);
        app(LeaseLifecycle::class)->generateRequiredInvoices($lease);

        return redirect()
            ->route('property-manager.leases.show', $lease)
            ->with('success', 'Required invoices have been generated, or the existing invoices were retained.');
    }
    /**
     * Calculate the amount allocated to an invoice through confirmed payments.
     */
    private function confirmedAllocatedAmount(RentInvoice $invoice): float
    {
        return (float) RentPaymentAllocation::query()
            ->where('rent_invoice_id', $invoice->id)
            ->whereHas('payment', function ($query) {
                $query->where('status', 'confirmed');
            })
            ->sum('amount');
    }
}
