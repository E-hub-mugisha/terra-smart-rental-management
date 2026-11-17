<?php

namespace App\Services\Payments;

use App\Models\PaymentIntent;
use Illuminate\Http\Request;

/** A payment provider Terra can collect rent through (Mobile Money, bank, gateway). */
interface PaymentGateway
{
    public function name(): string;

    public function label(): string;

    /** Ask the provider to collect the payment. Sets provider_reference; may mark the intent failed. */
    public function initiate(PaymentIntent $intent): void;

    /** Ask the provider for the current status: pending | successful | failed. */
    public function fetchStatus(PaymentIntent $intent): array;

    /** Our intent reference from a provider callback payload, or null if unrecognised. */
    public function referenceFromCallback(Request $request): ?string;
}
