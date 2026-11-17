<?php

namespace App\Services\Payments;

use App\Models\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Simulated Mobile Money for development and demos — never moves money.
 * Numbers ending in 0000 are declined straight away; any other request waits
 * until someone presses "approve" or "decline" on the payment page, which
 * stands in for the tenant confirming the prompt on their phone.
 */
class SandboxGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'sandbox';
    }

    public function label(): string
    {
        return config('payments.providers.sandbox.label', 'Mobile Money (test mode)');
    }

    public function initiate(PaymentIntent $intent): void
    {
        $intent->provider_reference = 'SBX-' . strtoupper(Str::random(12));

        if (str_ends_with(preg_replace('/\D/', '', (string) $intent->phone), '0000')) {
            $intent->status = 'failed';
            $intent->failure_reason = 'Declined by the payer (test number ending in 0000).';
        }

        $intent->provider_payload = ['sandbox' => true, 'initiated_at' => now()->toIso8601String()];
        $intent->save();
    }

    public function fetchStatus(PaymentIntent $intent): array
    {
        $decision = $intent->provider_payload['decision'] ?? null;

        return match ($decision) {
            'approve' => ['status' => 'successful'],
            'decline' => ['status' => 'failed', 'reason' => 'Declined on the phone (test mode).'],
            default => ['status' => $intent->status === 'failed' ? 'failed' : 'pending', 'reason' => $intent->failure_reason],
        };
    }

    public function referenceFromCallback(Request $request): ?string
    {
        return $request->input('reference');
    }

    /** Stand-in for the payer's action on their phone. */
    public function decide(PaymentIntent $intent, string $decision): void
    {
        $payload = $intent->provider_payload ?? [];
        $payload['decision'] = $decision;
        $intent->update(['provider_payload' => $payload]);
    }
}
