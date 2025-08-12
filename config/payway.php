<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PayWay Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for ABA PayWay payment gateway integration
    |
    */

    'base_url' => env('PAYWAY_BASE_URL', 'https://checkout-sandbox.payway.com.kh'),
    'merchant_id' => env('PAYWAY_MERCHANT_ID'),
    'secret_key' => env('PAYWAY_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Environment-specific Settings
    |--------------------------------------------------------------------------
    */

    'environments' => [
        'sandbox' => [
            'base_url' => 'https://checkout-sandbox.payway.com.kh',
            'verify_ssl' => false,
            'timeout' => 30,
        ],
        'production' => [
            'base_url' => 'https://checkout.payway.com.kh',
            'verify_ssl' => true,
            'timeout' => 15,
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | PayWay Settings
    |--------------------------------------------------------------------------
    */

    'default_currency' => env('PAYWAY_DEFAULT_CURRENCY', 'USD'),
    'default_lifetime' => env('PAYWAY_DEFAULT_LIFETIME', 30), // minutes
    'sandbox_mode' => env('PAYWAY_SANDBOX_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | URL Settings
    |--------------------------------------------------------------------------
    */

    'urls' => [
        'return_url' => env('PAYWAY_DEFAULT_RETURN_URL', '/payment/aba-pay/return'),
        'cancel_url' => env('PAYWAY_DEFAULT_CANCEL_URL', '/payment/aba-pay/cancel'),
        'webhook_url' => env('PAYWAY_WEBHOOK_URL', '/payment/aba-pay/webhook'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security & Logging
    |--------------------------------------------------------------------------
    */

    'log_channel' => env('PAYWAY_LOG_CHANNEL', 'stack'),
    'log_level' => env('PAYWAY_LOG_LEVEL', 'info'),
    'hash_algorithm' => 'sha512',
    'webhook_verification' => env('PAYWAY_WEBHOOK_VERIFICATION', true),

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    */

    'retry_attempts' => env('PAYWAY_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('PAYWAY_RETRY_DELAY', 1000), // milliseconds
    'webhook_timeout' => env('PAYWAY_WEBHOOK_TIMEOUT', 30), // seconds
];
