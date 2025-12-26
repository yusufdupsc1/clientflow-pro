<?php

return [
<<<<<<< HEAD
    'mode' => env('STRIPE_MODE', env('APP_ENV') === 'production' ? 'live' : 'test'),

    'secret_keys' => [
        'live' => env('STRIPE_LIVE_SECRET'),
        'test' => env('STRIPE_TEST_SECRET'),
    ],

    'publishable_keys' => [
        'live' => env('STRIPE_LIVE_PUBLISHABLE_KEY'),
        'test' => env('STRIPE_TEST_PUBLISHABLE_KEY'),
    ],

    'webhook' => [
        'secrets' => [
            'live' => env('STRIPE_LIVE_WEBHOOK_SECRET'),
            'test' => env('STRIPE_TEST_WEBHOOK_SECRET'),
        ],
        'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],

    'default_currency' => env('STRIPE_DEFAULT_CURRENCY', 'usd'),

    // Prevent accidentally using live keys in non-prod environments.
    'guard_live_on_non_prod' => env('STRIPE_GUARD_LIVE_ON_NON_PROD', true),
=======
    /*
    |--------------------------------------------------------------------------
    | Stripe API Keys
    |--------------------------------------------------------------------------
    |
    | These keys are used for Stripe payment processing. Set STRIPE_TEST_MODE
    | to true to use test keys, or false for live keys. In production, ensure
    | APP_ENV=production and STRIPE_TEST_MODE=false.
    |
    */

    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Test Mode
    |--------------------------------------------------------------------------
    |
    | When true, uses test keys and allows test-mode operations.
    | CRITICAL: Never set to false in non-production environments!
    |
    */

    'test_mode' => env('STRIPE_TEST_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Default currency for Stripe payments. Uses lowercase ISO code.
    |
    */

    'currency' => env('STRIPE_CURRENCY', 'usd'),

    /*
    |--------------------------------------------------------------------------
    | Success/Cancel URLs
    |--------------------------------------------------------------------------
    |
    | URLs for Stripe Checkout redirect after payment completion or cancellation.
    |
    */

    'success_url' => env('STRIPE_SUCCESS_URL', '/pay/{invoice}/success'),
    'cancel_url' => env('STRIPE_CANCEL_URL', '/pay/{invoice}/cancel'),
>>>>>>> 6337e80 (feat: Implement comprehensive billing and payment functionality with Stripe integration, invoice management, refunds, and organization-specific settings.)
];
