<?php

return [
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
];
