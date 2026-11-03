<?php

namespace App\Http\Controllers\PropertyManager;

use App\Http\Controllers\Controller;
use App\Models\Lease;
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

        $completedDepositPayments = RentPayment::query()
            ->where('lease_id', $lease->id)
            ->where('status', 'completed')
            ->where('payment_method', 'security_deposit')
            ->sum('amount');

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
     * Keep the lease as draft so the tenant portal can accept it.
     * The model has no sent_at field, so this action does not invent one.
     */
    public function sendForSignature(Lease $lease)
    {
        $lease = $this->scopedLease($lease);

        if ($lease->status !== 'draft') {
            return back()->with('error', 'Only draft leases can be prepared for tenant acceptance.');
        }

        if ($lease->tenant_accepted_at) {
            return back()->with('error', 'The tenant has already accepted this lease.');
        }

        DB::transaction(function () use ($lease) {
            $lockedLease = $this->leasesQuery()
                ->whereKey($lease->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedLease->status !== 'draft'
                || $lockedLease->tenant_accepted_at
            ) {
                throw ValidationException::withMessages([
                    'lease' => 'This lease can no longer be prepared for acceptance.',
                ]);
            }

            $unit = Unit::query()
                ->whereKey($lockedLease->unit_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($unit->status, ['available', 'reserved'], true)) {
                throw ValidationException::withMessages([
                    'lease' => 'This unit is not available for a new lease.',
                ]);
            }

            $this->assertNoOverlappingLease($lockedLease);

            $unit->status = 'reserved';
            $unit->save();
        });

        return redirect()
            ->route('property-manager.leases.show', $lease)
            ->with(
                'success',
                'The draft is ready for tenant acceptance in the tenant portal.'
            );
    }

    /**
     * Activate only after tenant acceptance, security deposit payment,
     * and manager move-in confirmation.
     */
    public function activate(Request $request, Lease $lease)
    {
        $lease = $this->scopedLease($lease);

        $request->validate([
            'move_in_confirmed' => ['required', 'accepted'],
        ]);

        DB::transaction(function () use ($lease) {
            $lockedLease = $this->leasesQuery()
                ->whereKey($lease->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLease->status !== 'draft') {
                throw ValidationException::withMessages([
                    'lease' => 'Only draft leases can be activated.',
                ]);
            }

            if (!$lockedLease->tenant_accepted_at) {
                throw ValidationException::withMessages([
                    'lease' => 'The tenant must accept the lease before activation.',
                ]);
            }

            $unit = Unit::query()
                ->whereKey($lockedLease->unit_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($unit->status !== 'reserved') {
                throw ValidationException::withMessages([
                    'lease' => 'The unit must be reserved for this lease.',
                ]);
            }

            $this->assertNoOverlappingLease($lockedLease);

            $paidDeposit = RentPayment::query()
                ->where('lease_id', $lockedLease->id)
                ->where('status', 'completed')
                ->where('payment_method', 'security_deposit')
                ->sum('amount');

            if ((float) $paidDeposit < (float) $lockedLease->deposit_amount) {
                throw ValidationException::withMessages([
                    'lease' => 'The required security deposit has not been fully paid.',
                ]);
            }

            $lockedLease->move_in_confirmed_at = now()->toDateString();
            $lockedLease->activated_at = now()->toDateString();
            $lockedLease->status = 'active';
            $lockedLease->save();

            $unit->status = 'occupied';
            $unit->save();
        });

        return redirect()
            ->route('property-manager.leases.show', $lease)
            ->with('success', 'Lease activated successfully.');
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

    private function assertNoOverlappingLease(
        Lease $lease,
        ?string $startDate = null,
        ?string $endDate = null
    ): void {
        $startDate ??= $lease->start_date?->toDateString();
        $endDate ??= $lease->end_date?->toDateString();

        $overlap = Lease::query()
            ->where('unit_id', $lease->unit_id)
            ->where('id', '!=', $lease->id)
            ->whereNotIn('status', ['rejected', 'terminated', 'cancelled'])
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'lease' => 'Another lease overlaps these dates for this unit.',
            ]);
        }
    }

    /**
     * Generate the security deposit and initial rent invoices.
     *
     * Managers can only generate invoices for leases belonging to
     * properties currently assigned to them.
     */
    public function generateRequiredInvoices(Lease $lease)
    {
        $lease = $this->scopedLease($lease);

        if ($lease->status !== 'draft' || ! $lease->tenant_accepted_at) {
            throw ValidationException::withMessages([
                'lease' => 'The tenant must accept the draft lease before required invoices can be generated.',
            ]);
        }

        DB::transaction(function () use ($lease) {
            // Recheck the manager's access and lock the lease.
            $lockedLease = $this->leasesQuery()
                ->whereKey($lease->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedLease->status !== 'draft'
                || ! $lockedLease->tenant_accepted_at
            ) {
                throw ValidationException::withMessages([
                    'lease' => 'Invoices can only be generated for a tenant-accepted draft lease.',
                ]);
            }

            // Generate the deposit invoice if a deposit is required.
            if ((float) $lockedLease->deposit_amount > 0) {
                $this->createInvoiceIfMissing(
                    $lockedLease,
                    'deposit',
                    (float) $lockedLease->deposit_amount,
                    'Security deposit - ' . $lockedLease->lease_number
                );
            }

            // Generate the initial rent invoice for the first billing period.
            $periodStart = $lockedLease->start_date->copy();

            $periodEnd = $periodStart
                ->copy()
                ->addMonthNoOverflow()
                ->subDay();

            if ($periodEnd->gt($lockedLease->end_date)) {
                $periodEnd = $lockedLease->end_date->copy();
            }

            $this->createInvoiceIfMissing(
                $lockedLease,
                'rent',
                (float) $lockedLease->monthly_rent,
                'Initial rent - ' . $lockedLease->lease_number,
                $periodStart,
                $periodEnd
            );
        });

        return redirect()
            ->route('property-manager.leases.show', $lease)
            ->with(
                'success',
                'Required invoices have been generated, or the existing invoices were retained.'
            );
    }

    /**
     * Create an invoice only if the same invoice type and billing period
     * do not already exist for this lease.
     */
    private function createInvoiceIfMissing(
        Lease $lease,
        string $type,
        float $amount,
        string $description,
        $periodStart = null,
        $periodEnd = null
    ): void {
        if ($type === 'deposit' && $amount <= 0) {
            return;
        }

        $existingInvoice = RentInvoice::query()
            ->where('lease_id', $lease->id)
            ->where('invoice_type', $type)
            ->when(
                $periodStart,
                fn($query) => $query->whereDate(
                    'billing_period_start',
                    $periodStart
                )
            )
            ->when(
                $periodEnd,
                fn($query) => $query->whereDate(
                    'billing_period_end',
                    $periodEnd
                )
            )
            ->exists();

        if ($existingInvoice) {
            return;
        }

        RentInvoice::create([
            'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'unit_id' => $lease->unit_id,
            'invoice_type' => $type,
            'billing_period_start' => $periodStart ?? $lease->start_date,
            'billing_period_end' => $periodEnd ?? $lease->start_date,
            'issue_date' => now()->toDateString(),
            'due_date' => $lease->start_date,
            'subtotal' => $amount,
            'tax_amount' => 0,
            'total_amount' => $amount,
            'amount_paid' => 0,
            'balance_due' => $amount,
            'currency' => $lease->currency,
            'status' => 'unpaid',
            'description' => $description,
        ]);
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
