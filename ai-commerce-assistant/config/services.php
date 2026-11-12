<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ebay' => [
        // In production, mock mode is forbidden; default safely to 'production' when APP_ENV=production.
        'mode' => env('EBAY_MODE', env('APP_ENV', 'production') === 'production' ? 'production' : 'mock'), // 'mock' (non-prod only), 'sandbox', 'production'
        'client_id' => env('EBAY_CLIENT_ID', ''),
        'client_secret' => env('EBAY_CLIENT_SECRET', ''),
        'marketplace_id' => env('EBAY_MARKETPLACE_ID', 'EBAY_US'),
        'timeout' => (int) env('EBAY_TIMEOUT', 15),
        'retry_attempts' => (int) env('EBAY_RETRY_ATTEMPTS', 2),
        'retry_backoff_ms' => (int) env('EBAY_RETRY_BACKOFF_MS', 500),
        'cache_ttl_search' => (int) env('EBAY_CACHE_TTL_SEARCH', 900), // 15 minutes
        'cache_ttl_item' => (int) env('EBAY_CACHE_TTL_ITEM', 3600), // 1 hour
    ],

];
