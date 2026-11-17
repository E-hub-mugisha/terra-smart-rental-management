<?php

/*
 | Online rent payments (brief §9).
 |
 | Terra does not hold customer funds: each provider collects straight into
 | the landlord / property manager's own merchant account, and Terra only
 | records the confirmed result in its ledger.
 */

return [

    // sandbox | mtn_momo
    'default' => env('PAYMENT_PROVIDER', 'sandbox'),

    // Minimum amount a tenant can pay online, in the invoice currency.
    'minimum_amount' => (float) env('PAYMENT_MINIMUM_AMOUNT', 100),

    // Pending intents older than this are marked expired.
    'expire_after_minutes' => (int) env('PAYMENT_EXPIRE_MINUTES', 30),

    'providers' => [

        // Simulated Mobile Money for development and demos. Never moves money.
        // Phone numbers ending in 0000 are declined, everything else waits for
        // the "approve on phone" button on the payment page.
        'sandbox' => [
            'label' => 'Mobile Money (test mode)',
            'method' => 'mobile_money',
        ],

        // MTN Mobile Money — Collection API "Request to Pay".
        // https://momodeveloper.mtn.com/api-documentation
        'mtn_momo' => [
            'label' => 'MTN Mobile Money',
            'method' => 'mobile_money',
            'base_url' => env('MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
            'target_environment' => env('MOMO_TARGET_ENVIRONMENT', 'sandbox'), // e.g. mtnrwanda in production
            'subscription_key' => env('MOMO_COLLECTION_SUBSCRIPTION_KEY'),
            'api_user' => env('MOMO_API_USER'),
            'api_key' => env('MOMO_API_KEY'),
            'callback_url' => env('MOMO_CALLBACK_URL'), // defaults to route('payments.callback', 'mtn_momo')
            // The MTN sandbox only accepts EUR; production uses the invoice currency.
            'currency_override' => env('MOMO_CURRENCY_OVERRIDE'),
        ],
    ],
];
