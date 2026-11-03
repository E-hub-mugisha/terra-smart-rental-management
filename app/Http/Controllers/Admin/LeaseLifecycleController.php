<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\RentInvoice;
use App\Models\RentPaymentAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LeaseLifecycleController extends Controller
{
    /**
     * Admin lease dashboard.
     */
    public function index(Request $request)
    {
        $query = Lease::query()
            ->with(['tenant', 'unit.building'])
            ->withCount('invoices');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('lease_number', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function ($tenantQuery) use ($search) {
                        $tenantQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('tenant_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('unit', function ($unitQuery) use ($search) {
                        $unitQuery->where('unit_number', 'like', "%{$search}%");
                    });
            });
        }

        $leases = $query->latest()->paginate(15)->withQueryString();

        $counts = [
            'all' => Lease::count(),
            'draft' => Lease::where('status', 'draft')->count(),
            'awaiting_acceptance' => Lease::where(
                'status',
                'pending_tenant_acceptance'
            )->count(),
            'pending_activation' => Lease::where(
                'status',
                'pending_activation'
            )->count(),
            'active' => Lease::where('status', 'active')->count(),
        ];

        return view('admin.leases.index', compact('leases', 'counts'));
    }

    /**
     * Display lease, invoices, and payment allocation history.
     */
    public function show(Lease $lease)
    {
        $lease->load([
            'tenant',
            'unit.building',
            'application',
            'invoices.allocations.payment',
        ]);

        $invoices = $lease->invoices;

        $invoiceSummary = [
            'count' => $invoices->count(),
            'total' => (float) $invoices->sum('total_amount'),
            'paid' => (float) $invoices->sum(function ($invoice) {
                return $this->confirmedAllocatedAmount($invoice);
            }),
            'balance' => (float) $invoices->sum(function ($invoice) {
                return max(
                    0,
                    (float) $invoice->total_amount
                        - $this->confirmedAllocatedAmount($invoice)
                );
            }),
        ];

        return view(
            'admin.leases.show',
            compact('lease', 'invoices', 'invoiceSummary')
        );
    }

    /**
     * Generate the deposit and initial rent invoices.
     */
    public function generateRequiredInvoices(Lease $lease)
    {
        if (
            $lease->status !== 'pending_activation'
            || ! $lease->tenant_accepted_at
        ) {
            throw ValidationException::withMessages([
                'lease' => 'The tenant must accept the lease before invoices can be generated.',
            ]);
        }

        DB::transaction(function () use ($lease) {
            $lockedLease = Lease::whereKey($lease->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->createInvoiceIfMissing(
                $lockedLease,
                'deposit',
                (float) $lockedLease->deposit_amount,
                'Security deposit - '.$lockedLease->lease_number
            );

            $start = $lockedLease->start_date->copy();
            $end = $start->copy()->addMonthNoOverflow()->subDay();

            if ($end->gt($lockedLease->end_date)) {
                $end = $lockedLease->end_date->copy();
            }

            $this->createInvoiceIfMissing(
                $lockedLease,
                'rent',
                (float) $lockedLease->monthly_rent,
                'Initial rent - '.$lockedLease->lease_number,
                $start,
                $end
            );
        });

        return back()->with(
            'success',
            'Required invoices have been generated or already exist.'
        );
    }

    /**
     * Record move-in confirmation.
     */
    public function confirmMoveIn(Lease $lease)
    {
        DB::transaction(function () use ($lease) {
            $lockedLease = Lease::whereKey($lease->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedLease->status !== 'pending_activation'
                || ! $lockedLease->tenant_accepted_at
            ) {
                throw ValidationException::withMessages([
                    'lease' => 'Only a tenant-accepted lease awaiting activation can be confirmed for move-in.',
                ]);
            }

            $lockedLease->update([
                'move_in_confirmed_at' => now(),
            ]);
        });

        return back()->with(
            'success',
            'Move-in has been confirmed.'
        );
    }

    /**
     * Activate after verifying required payments, move-in, and availability.
     */
    public function activate(Lease $lease)
    {
        DB::transaction(function () use ($lease) {
            $lockedLease = Lease::whereKey($lease->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLease->status !== 'pending_activation') {
                throw ValidationException::withMessages([
                    'lease' => 'Only a lease awaiting activation can be activated.',
                ]);
            }

            if (! $lockedLease->tenant_accepted_at) {
                throw ValidationException::withMessages([
                    'lease' => 'The tenant must accept the lease first.',
                ]);
            }

            if (! $lockedLease->move_in_confirmed_at) {
                throw ValidationException::withMessages([
                    'lease' => 'Confirm move-in before activating the lease.',
                ]);
            }

            if ($lockedLease->start_date->isFuture()) {
                throw ValidationException::withMessages([
                    'lease' => 'The lease start date has not yet arrived.',
                ]);
            }

            $invoices = RentInvoice::where('lease_id', $lockedLease->id)
                ->whereIn('invoice_type', ['deposit', 'rent'])
                ->lockForUpdate()
                ->get();

            $depositRequired = (float) $lockedLease->deposit_amount > 0;

            $hasDeposit = $invoices->contains(
                fn ($invoice) => $invoice->invoice_type === 'deposit'
            );

            $hasRent = $invoices->contains(
                fn ($invoice) => $invoice->invoice_type === 'rent'
            );

            if (($depositRequired && ! $hasDeposit) || ! $hasRent) {
                throw ValidationException::withMessages([
                    'lease' => 'Generate the required deposit and initial rent invoices first.',
                ]);
            }

            foreach ($invoices as $invoice) {
                $paid = $this->confirmedAllocatedAmount($invoice);
                $total = (float) $invoice->total_amount;
                $balance = max(0, round($total - $paid, 2));

                $invoice->update([
                    'amount_paid' => min($total, $paid),
                    'balance_due' => $balance,
                    'status' => $balance <= 0
                        ? 'paid'
                        : ($paid > 0 ? 'partially_paid' : 'unpaid'),
                ]);

                if ($balance > 0) {
                    throw ValidationException::withMessages([
                        'lease' => 'Invoice '.$invoice->invoice_number
                            .' has an outstanding balance of '
                            .$invoice->currency.' '
                            .number_format($balance, 2).'.',
                    ]);
                }
            }

            $conflict = Lease::where('unit_id', $lockedLease->unit_id)
                ->where('id', '<>', $lockedLease->id)
                ->where('status', 'active')
                ->whereDate('start_date', '<=', $lockedLease->end_date)
                ->whereDate('end_date', '>=', $lockedLease->start_date)
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'lease' => 'Another active lease overlaps these dates for this unit.',
                ]);
            }

            $unit = $lockedLease->unit()
                ->lockForUpdate()
                ->firstOrFail();

            $lockedLease->update([
                'status' => 'active',
                'activated_at' => now(),
            ]);

            $unit->update([
                'status' => 'occupied',
            ]);
        });

        return back()->with(
            'success',
            'Lease activated successfully. The unit is now marked as occupied.'
        );
    }

    /**
     * Sum allocations belonging to confirmed payments only.
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

    /**
     * Create each required invoice only once for its billing period.
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

        $exists = RentInvoice::where('lease_id', $lease->id)
            ->where('invoice_type', $type)
            ->when(
                $periodStart,
                fn ($query) => $query->whereDate(
                    'billing_period_start',
                    $periodStart
                )
            )
            ->when(
                $periodEnd,
                fn ($query) => $query->whereDate(
                    'billing_period_end',
                    $periodEnd
                )
            )
            ->exists();

        if ($exists) {
            return;
        }

        RentInvoice::create([
            'invoice_number' => 'INV-'.strtoupper(Str::random(8)),
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
}