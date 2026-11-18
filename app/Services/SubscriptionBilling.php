<?php

namespace App\Services;

use App\Models\SubscriptionContract;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Subscription contract lifecycle and billing:
 * pending → active → expired (or terminated), invoices per billing cycle,
 * payments allocated to the oldest open invoice.
 */
class SubscriptionBilling
{
    /** How many past cycles a single run may catch up on. */
    private const MAX_CATCH_UP = 12;

    public function __construct(private Notifier $notifier)
    {
    }

    public function activate(SubscriptionContract $contract): void
    {
        if (! in_array($contract->status, ['pending', 'expired'], true)) {
            throw new InvalidArgumentException('Only pending or expired contracts can be activated.');
        }

        $contract->forceFill([
            'status' => 'active',
            'activated_at' => now(),
            'signed_at' => $contract->signed_at ?? now(),
        ])->save();

        $this->notifier->subscriptionContractEvent($contract, 'activated');
        $this->billDue($contract);
    }

    public function terminate(SubscriptionContract $contract, string $reason): void
    {
        if (in_array($contract->status, ['terminated'], true)) {
            throw new InvalidArgumentException('This contract is already terminated.');
        }

        DB::transaction(function () use ($contract, $reason) {
            $contract->forceFill([
                'status' => 'terminated',
                'terminated_at' => now(),
                'termination_reason' => $reason,
            ])->save();

            // Bills for periods that have not started yet are cancelled.
            $contract->invoices()->where('status', 'unpaid')->whereDate('period_start', '>', today())
                ->update(['status' => 'void', 'notes' => 'Voided: contract terminated']);
        });

        $this->notifier->subscriptionContractEvent($contract, 'terminated');
    }

    /** Extends the contract by whole billing cycles (default: one term of the same length). */
    public function renew(SubscriptionContract $contract, ?int $months = null): void
    {
        if ($contract->status === 'terminated') {
            throw new InvalidArgumentException('A terminated contract cannot be renewed; create a new contract.');
        }

        $months ??= $contract->end_date
            ? max($contract->cycle_months, (int) round($contract->start_date->diffInMonths($contract->end_date->copy()->addDay())))
            : 12;

        $base = $contract->end_date && $contract->end_date->gte(today()) ? $contract->end_date : today()->subDay();

        $contract->forceFill([
            'end_date' => $base->copy()->addMonthsNoOverflow($months),
            'status' => 'active',
            'activated_at' => $contract->activated_at ?? now(),
        ])->save();

        $this->notifier->subscriptionContractEvent($contract, 'renewed');
    }

    /** Issues every invoice that has fallen due for an active contract. Returns how many were created. */
    public function billDue(SubscriptionContract $contract, ?Carbon $today = null): int
    {
        $today ??= today();
        if ($contract->status !== 'active' || (float) $contract->amount <= 0) {
            return 0;
        }

        $created = 0;
        while ($created < self::MAX_CATCH_UP) {
            $start = $this->nextPeriodStart($contract);
            if ($start->gt($today) || ($contract->end_date && $start->gt($contract->end_date))) {
                break;
            }
            $this->issueInvoice($contract, $start);
            $created++;
        }

        return $created;
    }

    public function issueInvoice(SubscriptionContract $contract, ?Carbon $periodStart = null, ?User $by = null): SubscriptionInvoice
    {
        $start = ($periodStart ?? $this->nextPeriodStart($contract))->copy()->startOfDay();
        $end = $start->copy()->addMonthsNoOverflow($contract->cycle_months)->subDay();
        if ($contract->end_date && $end->gt($contract->end_date)) {
            $end = $contract->end_date->copy();
        }

        // Bills are dated when issued; a bill issued in advance falls due after its period starts.
        $issue = today();

        $invoice = SubscriptionInvoice::create([
            'invoice_number' => SubscriptionInvoice::nextNumber(),
            'subscription_contract_id' => $contract->id,
            'user_id' => $contract->user_id,
            'period_start' => $start,
            'period_end' => $end,
            'issue_date' => $issue,
            'due_date' => $start->copy()->max($issue)->addDays((int) $contract->payment_due_days),
            'amount' => $contract->amount,
            'currency' => $contract->currency,
            'status' => 'unpaid',
            'created_by' => $by?->id,
        ]);

        $this->notifier->subscriptionInvoiceIssued($invoice);

        return $invoice;
    }

    /**
     * Records a payment. With no invoice given, the money settles the oldest
     * open invoices first; anything left over is kept as an unallocated payment.
     *
     * @return SubscriptionPayment[]
     */
    public function recordPayment(SubscriptionContract $contract, array $data, User $by): array
    {
        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('The payment amount must be positive.');
        }

        $payments = DB::transaction(function () use ($contract, $data, $by, $amount) {
            $base = [
                'subscription_contract_id' => $contract->id,
                'user_id' => $contract->user_id,
                'currency' => $contract->currency,
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $by->id,
            ];

            $invoices = ! empty($data['subscription_invoice_id'])
                ? $contract->invoices()->whereKey($data['subscription_invoice_id'])->open()->get()
                : $contract->invoices()->open()->orderBy('due_date')->orderBy('id')->get();

            $left = $amount;
            $made = [];
            foreach ($invoices as $invoice) {
                if ($left <= 0) {
                    break;
                }
                $portion = min($left, $invoice->balance);
                if ($portion <= 0) {
                    continue;
                }
                $made[] = SubscriptionPayment::create($base + ['amount' => $portion, 'subscription_invoice_id' => $invoice->id]);
                $invoice->refreshTotals();
                $left = round($left - $portion, 2);
            }

            if ($left > 0) {
                $made[] = SubscriptionPayment::create($base + ['amount' => $left, 'subscription_invoice_id' => null]);
            }

            return $made;
        });

        if ($payments) {
            $total = clone $payments[0];
            $total->amount = $amount;
            $this->notifier->subscriptionPaymentReceived($total);
        }

        return $payments;
    }

    public function voidInvoice(SubscriptionInvoice $invoice, string $reason): void
    {
        if ($invoice->payments()->exists()) {
            throw new InvalidArgumentException('An invoice with payments cannot be voided.');
        }

        $invoice->forceFill(['status' => 'void', 'notes' => mb_substr('Voided: ' . $reason, 0, 255)])->save();
    }

    /**
     * Daily housekeeping: expire or auto-renew ended contracts, issue due
     * invoices, and send expiry / overdue reminders once each.
     *
     * @return array{expired:int, renewed:int, invoices:int, reminders:int}
     */
    public function runDaily(): array
    {
        $stats = ['expired' => 0, 'renewed' => 0, 'invoices' => 0, 'reminders' => 0];

        SubscriptionContract::where('status', 'active')->whereNotNull('end_date')->whereDate('end_date', '<', today())
            ->each(function (SubscriptionContract $contract) use (&$stats) {
                // Bill the final periods before the contract closes.
                $stats['invoices'] += $this->billDue($contract, $contract->end_date->copy());
                if ($contract->auto_renew) {
                    $this->renew($contract);
                    $stats['renewed']++;
                } else {
                    $contract->forceFill(['status' => 'expired'])->save();
                    $this->notifier->subscriptionContractEvent($contract, 'expired');
                    $stats['expired']++;
                }
            });

        SubscriptionContract::where('status', 'active')->each(function (SubscriptionContract $contract) use (&$stats) {
            $stats['invoices'] += $this->billDue($contract);

            if ($contract->isExpiringWithin(30) && ! $contract->auto_renew) {
                $sent = $this->notifier->once('sub-expiring:' . $contract->id . ':' . $contract->end_date->toDateString(), 40,
                    fn () => $this->notifier->subscriptionContractEvent($contract, 'expiring'));
                $stats['reminders'] += (int) $sent;
            }
        });

        SubscriptionInvoice::overdue()->with('subscriber')->each(function (SubscriptionInvoice $invoice) use (&$stats) {
            $sent = $this->notifier->once('sub-overdue:' . $invoice->id, 7, fn () => $this->notifier->subscriptionInvoiceOverdue($invoice));
            $stats['reminders'] += (int) $sent;
        });

        return $stats;
    }

    private function nextPeriodStart(SubscriptionContract $contract): Carbon
    {
        $last = $contract->invoices()->where('status', '!=', 'void')->max('period_end');

        return $last ? Carbon::parse($last)->addDay()->startOfDay() : $contract->start_date->copy()->startOfDay();
    }
}
