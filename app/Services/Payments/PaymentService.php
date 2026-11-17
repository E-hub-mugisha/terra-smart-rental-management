<?php

namespace App\Services\Payments;

use App\Models\PaymentIntent;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\RentPaymentAllocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Online payment flow (brief §9):
 *   Payment initiated → provider → confirmation → Terra ledger → receipt → notification
 *
 * A successful intent becomes a confirmed RentPayment allocated to the
 * invoice; the existing NotificationObserver then sends "Payment successful"
 * to the tenant and "Rent received" to the property team.
 */
class PaymentService
{
    public function gateway(?string $name = null): PaymentGateway
    {
        return match ($name ?? config('payments.default')) {
            'sandbox' => app(SandboxGateway::class),
            'mtn_momo' => app(MtnMomoGateway::class),
            default => throw new InvalidArgumentException('Unknown payment provider.'),
        };
    }

    public function startForInvoice(User $user, RentInvoice $invoice, float $amount, string $phone): PaymentIntent
    {
        $gateway = $this->gateway();

        $intent = PaymentIntent::create([
            'reference' => 'TRP-' . now()->format('ymd') . '-' . strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'tenant_id' => $invoice->tenant_id,
            'lease_id' => $invoice->lease_id,
            'unit_id' => $invoice->unit_id,
            'rent_invoice_id' => $invoice->id,
            'provider' => $gateway->name(),
            'phone' => $phone,
            'amount' => round($amount, 2),
            'currency' => $invoice->currency ?: 'RWF',
            'status' => 'pending',
        ]);

        try {
            $gateway->initiate($intent);
        } catch (\Throwable $e) {
            Log::error('Payment initiation failed: ' . $e->getMessage(), ['intent' => $intent->reference]);
            $intent->update(['status' => 'failed', 'failure_reason' => 'The payment provider could not be reached. Please try again.']);
        }

        return $intent->fresh();
    }

    /** Ask the provider for news and act on it. Safe to call repeatedly. */
    public function sync(PaymentIntent $intent): PaymentIntent
    {
        if ($intent->isFinal()) {
            return $intent;
        }

        try {
            $result = $this->gateway($intent->provider)->fetchStatus($intent);
        } catch (\Throwable $e) {
            Log::warning('Payment status check failed: ' . $e->getMessage(), ['intent' => $intent->reference]);
            $result = ['status' => 'pending'];
        }

        if ($result['status'] === 'successful') {
            $this->complete($intent, $result['transaction_id'] ?? null);
        } elseif ($result['status'] === 'failed') {
            $intent->update(['status' => 'failed', 'failure_reason' => $result['reason'] ?? 'The payment was not completed.']);
        } elseif ($intent->created_at->lt(now()->subMinutes(config('payments.expire_after_minutes', 30)))) {
            $intent->update(['status' => 'expired', 'failure_reason' => 'No confirmation was received in time.']);
        }

        return $intent->fresh();
    }

    /** Record the money: confirmed payment, allocation to the invoice, updated balance. Idempotent. */
    public function complete(PaymentIntent $intent, ?string $transactionId = null): void
    {
        DB::transaction(function () use ($intent, $transactionId) {
            $locked = PaymentIntent::whereKey($intent->id)->lockForUpdate()->first();
            if (! $locked || $locked->rent_payment_id) {
                return; // already recorded
            }

            $gateway = $this->gateway($locked->provider);

            $payment = RentPayment::create([
                'payment_number' => $this->paymentNumber(),
                'tenant_id' => $locked->tenant_id,
                'lease_id' => $locked->lease_id,
                'unit_id' => $locked->unit_id,
                'recorded_by' => $locked->user_id,
                'payment_method' => config("payments.providers.{$locked->provider}.method", 'payment_gateway'),
                'provider' => $gateway->label(),
                'transaction_reference' => $transactionId ?: $locked->provider_reference ?: $locked->reference,
                'amount' => $locked->amount,
                'currency' => $locked->currency,
                'payment_date' => now()->toDateString(),
                'status' => 'confirmed',
                'notes' => 'Paid online by the tenant (' . $locked->reference . ').',
            ]);

            if ($locked->rent_invoice_id && ($invoice = RentInvoice::whereKey($locked->rent_invoice_id)->lockForUpdate()->first())) {
                $allocate = round(min((float) $locked->amount, (float) $invoice->balance_due), 2);
                if ($allocate > 0) {
                    RentPaymentAllocation::create([
                        'rent_payment_id' => $payment->id,
                        'rent_invoice_id' => $invoice->id,
                        'amount' => $allocate,
                        'allocated_at' => now()->toDateString(),
                        'notes' => 'Automatic allocation of online payment.',
                    ]);
                }
                InvoiceBalance::refresh($invoice);
            }

            $locked->update([
                'status' => 'successful',
                'rent_payment_id' => $payment->id,
                'completed_at' => now(),
                'failure_reason' => null,
            ]);
        });
    }

    private function paymentNumber(): string
    {
        do {
            $number = 'PAY-' . strtoupper(Str::random(10));
        } while (RentPayment::where('payment_number', $number)->exists());

        return $number;
    }
}
