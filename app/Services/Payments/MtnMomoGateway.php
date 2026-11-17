<?php

namespace App\Services\Payments;

use App\Models\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * MTN Mobile Money — Collection API ("Request to Pay").
 *
 *   POST /collection/token/                        → access token (Basic api_user:api_key)
 *   POST /collection/v1_0/requesttopay             → 202, prompt sent to payer's phone
 *   GET  /collection/v1_0/requesttopay/{refId}     → PENDING | SUCCESSFUL | FAILED
 *
 * Callbacks are never trusted on their own: a callback only tells us which
 * payment to re-check, and the status always comes from the GET above.
 *
 * Written against MTN's public API documentation; it needs a MoMo developer
 * account (subscription key, API user and key) to run.
 */
class MtnMomoGateway implements PaymentGateway
{
    private array $config;

    public function __construct()
    {
        $this->config = config('payments.providers.mtn_momo', []);
    }

    public function name(): string
    {
        return 'mtn_momo';
    }

    public function label(): string
    {
        return $this->config['label'] ?? 'MTN Mobile Money';
    }

    public function initiate(PaymentIntent $intent): void
    {
        $referenceId = (string) Str::uuid();
        $currency = $this->config['currency_override'] ?: $intent->currency;

        $response = Http::withToken($this->token())
            ->withHeaders($this->headers() + [
                'X-Reference-Id' => $referenceId,
                'X-Callback-Url' => $this->config['callback_url'] ?: route('payments.callback', 'mtn_momo'),
            ])
            ->timeout(20)
            ->post($this->url('/collection/v1_0/requesttopay'), [
                'amount' => (string) round((float) $intent->amount),
                'currency' => $currency,
                'externalId' => $intent->reference,
                'payer' => ['partyIdType' => 'MSISDN', 'partyId' => self::msisdn((string) $intent->phone)],
                'payerMessage' => 'Rent payment ' . $intent->reference,
                'payeeNote' => 'Terra rent ' . $intent->reference,
            ]);

        $intent->provider_reference = $referenceId;
        $intent->provider_payload = ['request_status' => $response->status()];

        if ($response->status() !== 202) {
            $intent->status = 'failed';
            $intent->failure_reason = 'MTN MoMo rejected the request (' . $response->status() . ').';
            $intent->provider_payload = ['request_status' => $response->status(), 'body' => Str::limit($response->body(), 500)];
        }

        $intent->save();
    }

    public function fetchStatus(PaymentIntent $intent): array
    {
        $response = Http::withToken($this->token())
            ->withHeaders($this->headers())
            ->timeout(15)
            ->get($this->url('/collection/v1_0/requesttopay/' . $intent->provider_reference));

        if (! $response->successful()) {
            return ['status' => 'pending']; // try again on the next poll
        }

        $body = $response->json();

        return [
            'status' => match (strtoupper((string) ($body['status'] ?? ''))) {
                'SUCCESSFUL' => 'successful',
                'FAILED', 'REJECTED', 'TIMEOUT' => 'failed',
                default => 'pending',
            },
            'reason' => is_array($body['reason'] ?? null) ? ($body['reason']['message'] ?? null) : ($body['reason'] ?? null),
            'transaction_id' => $body['financialTransactionId'] ?? null,
        ];
    }

    public function referenceFromCallback(Request $request): ?string
    {
        return $request->input('externalId');
    }

    /** Rwandan numbers in international format: 0788123456 → 250788123456. */
    public static function msisdn(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return match (true) {
            str_starts_with($digits, '250') => $digits,
            str_starts_with($digits, '0') => '250' . substr($digits, 1),
            strlen($digits) === 9 => '250' . $digits,
            default => $digits,
        };
    }

    private function token(): string
    {
        foreach (['subscription_key', 'api_user', 'api_key'] as $key) {
            if (empty($this->config[$key])) {
                throw new RuntimeException('MTN MoMo is not configured (missing ' . $key . ').');
            }
        }

        return Cache::remember('mtn-momo-collection-token', now()->addMinutes(50), function () {
            $response = Http::withBasicAuth($this->config['api_user'], $this->config['api_key'])
                ->withHeaders(['Ocp-Apim-Subscription-Key' => $this->config['subscription_key']])
                ->timeout(15)
                ->post($this->url('/collection/token/'));

            if (! $response->successful() || ! $response->json('access_token')) {
                throw new RuntimeException('Could not obtain an MTN MoMo access token (' . $response->status() . ').');
            }

            return $response->json('access_token');
        });
    }

    private function headers(): array
    {
        return [
            'Ocp-Apim-Subscription-Key' => $this->config['subscription_key'],
            'X-Target-Environment' => $this->config['target_environment'] ?? 'sandbox',
        ];
    }

    private function url(string $path): string
    {
        return rtrim($this->config['base_url'] ?? '', '/') . $path;
    }
}
