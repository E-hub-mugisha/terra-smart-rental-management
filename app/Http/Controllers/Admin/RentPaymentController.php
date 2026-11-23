<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\RentPaymentAllocation;
use App\Models\Tenant;
use App\Models\Lease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RentPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = RentPayment::with(['tenant', 'lease', 'unit', 'recorder'])
            ->withSum([
                'allocations as confirmed_allocated_amount' => function ($q) {
                    $q->whereHas('payment', function ($paymentQuery) {
                        $paymentQuery->where('status', 'confirmed');
                    });
                },
            ], 'amount')
            ->latest('payment_date');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('transaction_reference', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function ($tenantQuery) use ($search) {
                        $tenantQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('tenant_number', 'like', "%{$search}%");
                    });
            });
        }

        $payments = $query->paginate(15)->withQueryString();

        $counts = [
            'all' => RentPayment::count(),
            'pending' => RentPayment::where('status', 'pending')->count(),
            'confirmed' => RentPayment::where('status', 'confirmed')->count(),
            'failed' => RentPayment::where('status', 'failed')->count(),
        ];

        return view('admin.payments.index', compact('payments', 'counts'));
    }

    public function create()
    {
        $tenants = Tenant::orderBy('full_name')->get();
        $leases = Lease::with(['tenant', 'unit'])
            ->whereIn('status', ['active', 'pending_activation'])
            ->orderByDesc('id')
            ->get();

        return view('admin.payments.create', compact('tenants', 'leases'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'lease_id' => ['required', 'integer', 'exists:leases,id'],
            'payment_method' => [
                'required',
                'in:cash,bank_transfer,mobile_money,card,payment_gateway,other',
            ],
            'provider' => ['nullable', 'string', 'max:100'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $lease = Lease::with('unit')->findOrFail($validated['lease_id']);

        if ((int) $lease->tenant_id !== (int) $validated['tenant_id']) {
            throw ValidationException::withMessages([
                'tenant_id' => 'The selected tenant does not belong to this lease.',
            ]);
        }

        if (!in_array($lease->status, ['active', 'pending_activation'], true)) {
            throw ValidationException::withMessages([
                'lease_id' => 'Payments can only be recorded for active or pending-activation leases.',
            ]);
        }

        if (strtoupper($validated['currency']) !== strtoupper($lease->currency)) {
            throw ValidationException::withMessages([
                'currency' => 'The payment currency must match the lease currency.',
            ]);
        }

        $payment = DB::transaction(function () use ($validated, $lease, $request) {
            return RentPayment::create([
                'payment_number' => $this->generatePaymentNumber(),
                'tenant_id' => $validated['tenant_id'],
                'lease_id' => $lease->id,
                'unit_id' => $lease->unit_id,
                'recorded_by' => $request->user()->id,
                'payment_method' => $validated['payment_method'],
                'provider' => $validated['provider'] ?? null,
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'amount' => $validated['amount'],
                'currency' => strtoupper($validated['currency']),
                'payment_date' => $validated['payment_date'],
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('success', 'Payment recorded as pending. Verify receipt of funds before confirming it.');
    }

    public function show(RentPayment $payment)
    {
        $payment->load([
            'tenant',
            'lease.unit',
            'unit',
            'recorder',
            'allocations.invoice',
        ]);

        $invoices = RentInvoice::where('tenant_id', $payment->tenant_id)
            ->where('lease_id', $payment->lease_id)
            ->where('currency', $payment->currency)
            ->whereIn('status', ['unpaid', 'partially_paid', 'paid'])
            ->orderBy('due_date')
            ->get();

        $allocatedAmount = (float) $payment->allocations()
            ->whereHas('payment', fn ($q) => $q->where('status', 'confirmed'))
            ->sum('amount');

        $availableAmount = $payment->status === 'confirmed'
            ? max(0, (float) $payment->amount - $allocatedAmount)
            : 0;

        return view('admin.payments.show', compact(
            'payment',
            'invoices',
            'allocatedAmount',
            'availableAmount'
        ));
    }

    public function confirm(RentPayment $payment)
    {
        DB::transaction(function () use ($payment) {
            $lockedPayment = RentPayment::whereKey($payment->id)
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

            // Confirm only after verifying the money was received.
            $lockedPayment->update(['status' => 'confirmed']);
        });

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('success', 'Payment confirmed. You can now allocate it to invoices.');
    }

    /**
     * Delete a payment that has not been allocated to any invoice.
     */
    public function destroy(RentPayment $payment)
    {
        DB::transaction(function () use ($payment) {
            $locked = RentPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->allocations()->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'This payment is allocated to invoices, so it cannot be deleted.',
                ]);
            }

            $locked->delete();
        });

        return redirect()
            ->route('admin.payments.index')
            ->with('success', 'Payment deleted successfully.');
    }

    public function allocate(Request $request, RentPayment $payment)
    {
        $validated = $request->validate([
            'rent_invoice_id' => ['required', 'integer', 'exists:rent_invoices,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'allocated_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($payment, $validated) {
            $lockedPayment = RentPayment::whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'payment' => 'Confirm the payment before allocating it.',
                ]);
            }

            $invoice = RentInvoice::whereKey($validated['rent_invoice_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Restrict allocations to invoices belonging to this payment's
            // tenant, lease and currency.
            if (
                (int) $invoice->tenant_id !== (int) $lockedPayment->tenant_id ||
                (int) $invoice->lease_id !== (int) $lockedPayment->lease_id ||
                strtoupper($invoice->currency) !== strtoupper($lockedPayment->currency)
            ) {
                throw ValidationException::withMessages([
                    'rent_invoice_id' => 'This invoice does not belong to the payment tenant and lease, or its currency does not match.',
                ]);
            }

            $this->refreshInvoiceBalance($invoice);

            $alreadyAllocated = (float) RentPaymentAllocation::where(
                'rent_payment_id',
                $lockedPayment->id
            )->whereHas('payment', fn ($q) => $q->where('status', 'confirmed'))
                ->sum('amount');

            $remainingPayment = round(
                (float) $lockedPayment->amount - $alreadyAllocated,
                2
            );

            $invoiceBalance = round((float) $invoice->balance_due, 2);
            $allocationAmount = round((float) $validated['amount'], 2);

            if ($allocationAmount > $remainingPayment) {
                throw ValidationException::withMessages([
                    'amount' => 'The allocation exceeds the payment amount still available.',
                ]);
            }

            if ($allocationAmount > $invoiceBalance) {
                throw ValidationException::withMessages([
                    'amount' => 'The allocation exceeds this invoice’s outstanding balance.',
                ]);
            }

            if ($allocationAmount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'The allocation amount must be greater than zero.',
                ]);
            }

            RentPaymentAllocation::create([
                'rent_payment_id' => $lockedPayment->id,
                'rent_invoice_id' => $invoice->id,
                'amount' => $allocationAmount,
                'allocated_at' => $validated['allocated_at'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->refreshInvoiceBalance($invoice);
        });

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('success', 'Payment allocated and invoice balance updated.');
    }

    /**
     * Derive invoice totals from allocations belonging to confirmed payments.
     */
    private function refreshInvoiceBalance(RentInvoice $invoice): void
    {
        $paid = (float) RentPaymentAllocation::where(
            'rent_invoice_id',
            $invoice->id
        )->whereHas('payment', fn ($q) => $q->where('status', 'confirmed'))
            ->sum('amount');

        $total = (float) $invoice->total_amount;

        $paid = round(min($paid, $total), 2);
        $balance = round(max(0, $total - $paid), 2);

        if ($balance <= 0) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partially_paid';
        } else {
            $status = 'unpaid';
        }

        $invoice->update([
            'amount_paid' => $paid,
            'balance_due' => $balance,
            'status' => $status,
        ]);
    }

    private function generatePaymentNumber(): string
    {
        do {
            $number = 'PAY-' . strtoupper(Str::random(10));
        } while (RentPayment::where('payment_number', $number)->exists());

        return $number;
    }
}
