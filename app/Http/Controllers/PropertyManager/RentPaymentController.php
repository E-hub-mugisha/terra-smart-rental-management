<?php

namespace App\Http\Controllers\PropertyManager;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Property;
use App\Models\PropertyManager;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\RentPaymentAllocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RentPaymentController extends Controller
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
     * Ensure the user is an active manager or registered property owner.
     */
    private function authorizePaymentAccess(): void
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
     * Managers can access properties actively assigned to them.
     */
    private function accessibleProperties(): Builder
    {
        $this->authorizePaymentAccess();

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
     * Get payments belonging to accessible properties.
     */
    private function paymentsQuery(): Builder
    {
        $propertyIds = $this->accessibleProperties()
            ->select('properties.id');

        return RentPayment::query()
            ->with([
                'tenant',
                'lease',
                'unit.building.property',
                'allocations.invoice',
            ])
            ->whereHas('unit.building', function (Builder $building) use ($propertyIds) {
                $building->whereIn('property_id', $propertyIds);
            });
    }

    /**
     * Get a payment only if it belongs to an accessible property.
     */
    private function scopedPayment(int $id): RentPayment
    {
        return $this->paymentsQuery()->findOrFail($id);
    }

    /**
     * Get outstanding invoices belonging to accessible properties.
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
            ])
            ->whereIn('status', [
                'issued',
                'partially_paid',
                'overdue',
            ])
            ->where('balance_due', '>', 0)
            ->whereHas('unit.building', function (Builder $building) use ($propertyIds) {
                $building->whereIn('property_id', $propertyIds);
            });
    }

    /**
     * Display rent payments.
     */
    public function index(Request $request)
    {
        $query = $this->paymentsQuery();

        // Search.
        if ($search = trim($request->input('search', ''))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('transaction_reference', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function (Builder $tenant) use ($search) {
                        $tenant->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Payment status filter.
        if ($request->filled('status')) {
            $status = $request->input('status');

            if (in_array($status, [
                'pending',
                'confirmed',
                'failed',
                'refunded',
            ], true)) {
                $query->where('status', $status);
            }
        }

        // Payment method filter.
        if ($request->filled('payment_method')) {
            $query->where(
                'payment_method',
                $request->input('payment_method')
            );
        }

        // Payment date filters.
        if ($request->filled('date_from')) {
            $query->whereDate(
                'payment_date',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'payment_date',
                '<=',
                $request->input('date_to')
            );
        }

        $payments = $query
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Payment summaries.
        $summaryQuery = $this->paymentsQuery();

        $summary = [
            'all' => (clone $summaryQuery)->count(),

            'completed' => (clone $summaryQuery)
                ->where('status', 'confirmed')
                ->count(),

            'pending' => (clone $summaryQuery)
                ->where('status', 'pending')
                ->count(),

            'completed_amount' => (clone $summaryQuery)
                ->where('status', 'confirmed')
                ->sum('amount'),
        ];

        return view('property-portal.payments.index', compact(
            'payments',
            'summary'
        ));
    }

    /**
     * Show the payment creation form.
     */
    public function create()
    {
        $invoices = $this->invoicesQuery()
            ->orderBy('due_date')
            ->get();

        return view('property-portal.payments.create', compact('invoices'));
    }

    /**
     * Record a new payment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => [
                'required',
                'integer',
                'exists:tenants,id',
            ],

            'lease_id' => [
                'required',
                'integer',
                'exists:leases,id',
            ],

            'unit_id' => [
                'required',
                'integer',
                'exists:units,id',
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999999999',
            ],

            'currency' => [
                'required',
                Rule::in(['RWF', 'USD']),
            ],

            'payment_method' => [
                'required',
                Rule::in([
                    'cash',
                    'bank_transfer',
                    'mobile_money',
                    'card',
                    'payment_gateway',
                    'other',
                ]),
            ],

            'provider' => [
                'nullable',
                'string',
                'max:100',
            ],

            'transaction_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'payment_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        /*
         * Only allow leases belonging to properties accessible
         * to the authenticated owner or active manager.
         */
        $propertyIds = $this->accessibleProperties()
            ->select('properties.id');

        $lease = Lease::query()
            ->with('unit.building.property')
            ->whereKey($validated['lease_id'])
            ->whereIn('status', [
                'active',
                'pending_activation',
            ])
            ->where('tenant_id', $validated['tenant_id'])
            ->where('unit_id', $validated['unit_id'])
            ->whereHas('unit.building', function (Builder $building) use ($propertyIds) {
                $building->whereIn('property_id', $propertyIds);
            })
            ->firstOrFail();

        // Ensure payment currency matches the lease currency.
        if (
            strtoupper($validated['currency']) !==
            strtoupper($lease->currency)
        ) {
            throw ValidationException::withMessages([
                'currency' => 'The payment currency must match the lease currency.',
            ]);
        }

        $payment = DB::transaction(function () use ($validated, $lease, $request) {
            return RentPayment::create([
                'payment_number' => $this->generatePaymentNumber(),

                'tenant_id' => $lease->tenant_id,
                'lease_id' => $lease->id,
                'unit_id' => $lease->unit_id,

                'recorded_by' => $request->user()->id,

                'payment_method' => $validated['payment_method'],
                'provider' => $validated['provider'] ?? null,

                'transaction_reference' =>
                    $validated['transaction_reference'] ?? null,

                'amount' => $validated['amount'],
                'currency' => strtoupper($validated['currency']),
                'payment_date' => $validated['payment_date'],

                'status' => 'pending',

                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('property-manager.payments.show', $payment->id)
            ->with(
                'success',
                'Payment recorded as pending. Verify receipt of funds before confirming it.'
            );
    }

    /**
     * Generate a unique payment number.
     */
    private function generatePaymentNumber(): string
    {
        do {
            $number = 'PAY-' . strtoupper(Str::random(10));
        } while (
            RentPayment::where('payment_number', $number)->exists()
        );

        return $number;
    }

    /**
     * Display a payment.
     */
    public function show(int $payment)
    {
        $payment = $this->scopedPayment($payment);

        $payment->load([
            'tenant',
            'lease',
            'unit.building.property',
            'allocations.invoice',
        ]);

        return view('property-portal.payments.show', compact('payment'));
    }

    /**
     * Confirm a payment after verifying receipt of funds.
     */
    public function confirm(int $payment)
    {
        $payment = $this->scopedPayment($payment);

        DB::transaction(function () use ($payment) {

            // Recheck property access inside the transaction.
            $lockedPayment = $this->paymentsQuery()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status === 'confirmed') {
                return;
            }

            if ($lockedPayment->status !== 'pending') {
                throw ValidationException::withMessages([
                    'payment' => 'Only pending payments can be confirmed.',
                ]);
            }

            // Confirm only after verifying that the funds were received.
            $lockedPayment->update([
                'status' => 'confirmed',
            ]);
        });

        return redirect()
            ->route('property-manager.payments.show', $payment->id)
            ->with(
                'success',
                'Payment confirmed. You can now allocate it to outstanding invoices.'
            );
    }

    /**
     * Allocate a confirmed payment to an outstanding invoice.
     */
    public function allocate(Request $request, int $payment)
    {
        $validated = $request->validate([
            'rent_invoice_id' => [
                'required',
                'integer',
                'exists:rent_invoices,id',
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999999999',
            ],

            'allocated_at' => [
                'required',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $payment = $this->scopedPayment($payment);

        DB::transaction(function () use ($payment, $validated) {

            // Lock and recheck the payment.
            $lockedPayment = $this->paymentsQuery()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'payment' => 'Confirm the payment before allocating it.',
                ]);
            }

            // Scope the invoice to accessible properties.
            $invoice = $this->invoicesQuery()
                ->whereKey($validated['rent_invoice_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Ensure payment and invoice belong to the same rental.
            if (
                (int) $invoice->tenant_id !== (int) $lockedPayment->tenant_id ||
                (int) $invoice->lease_id !== (int) $lockedPayment->lease_id ||
                (int) $invoice->unit_id !== (int) $lockedPayment->unit_id ||
                strtoupper($invoice->currency) !==
                    strtoupper($lockedPayment->currency)
            ) {
                throw ValidationException::withMessages([
                    'rent_invoice_id' =>
                        'The invoice must belong to the same tenant, lease, unit and currency as the payment.',
                ]);
            }

            // Recalculate the invoice balance before allocating.
            $this->refreshInvoiceBalance($invoice);

            $alreadyAllocated = (float) RentPaymentAllocation::query()
                ->where('rent_payment_id', $lockedPayment->id)
                ->whereHas(
                    'payment',
                    fn (Builder $query) => $query->where(
                        'status',
                        'confirmed'
                    )
                )
                ->sum('amount');

            $remainingPayment = round(
                (float) $lockedPayment->amount - $alreadyAllocated,
                2
            );

            $invoiceBalance = round(
                (float) $invoice->balance_due,
                2
            );

            $allocationAmount = round(
                (float) $validated['amount'],
                2
            );

            if ($allocationAmount > $remainingPayment) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'The allocation exceeds the payment amount still available.',
                ]);
            }

            if ($allocationAmount > $invoiceBalance) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'The allocation exceeds the invoice outstanding balance.',
                ]);
            }

            RentPaymentAllocation::create([
                'rent_payment_id' => $lockedPayment->id,
                'rent_invoice_id' => $invoice->id,
                'amount' => $allocationAmount,
                'allocated_at' => $validated['allocated_at'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update the invoice after successful allocation.
            $this->refreshInvoiceBalance($invoice);
        });

        return redirect()
            ->route('property-manager.payments.show', $payment->id)
            ->with(
                'success',
                'Payment allocated and invoice balance updated.'
            );
    }

    /**
     * Recalculate the paid amount and outstanding balance of an invoice.
     */
    private function refreshInvoiceBalance(RentInvoice $invoice): void
    {
        $paid = (float) RentPaymentAllocation::query()
            ->where('rent_invoice_id', $invoice->id)
            ->whereHas(
                'payment',
                fn (Builder $query) => $query->where(
                    'status',
                    'confirmed'
                )
            )
            ->sum('amount');

        $total = (float) $invoice->total_amount;

        $paid = round(min($paid, $total), 2);
        $balance = round(max(0, $total - $paid), 2);

        if ($balance <= 0) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partially_paid';
        } else {
            $status = 'issued';
        }

        $invoice->update([
            'amount_paid' => $paid,
            'balance_due' => $balance,
            'status' => $status,
        ]);
    }
}