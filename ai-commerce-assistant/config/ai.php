<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | Supported providers: "mock", "openai"
    |
    | "mock" runs deterministically locally and in CI without incurring API costs
    | (blocked in APP_ENV=production).
    | "openai" connects to OpenAI Chat Completions API with JSON mode.
    |
    */
    'default' => env('AI_PROVIDER', env('APP_ENV', 'production') === 'production' ? 'openai' : 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Active Prompt Version
    |--------------------------------------------------------------------------
    |
    | Controls which versioned prompt contract is active (e.g. 'v1', 'v2').
    | Allows backwards-compatible prompt auditing across historical analyses.
    |
    */
    'prompt_version' => env('AI_PROMPT_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Network Timeouts and Retries
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('AI_TIMEOUT', 30),
    'max_retries' => (int) env('AI_MAX_RETRIES', 2),

    /*
    |--------------------------------------------------------------------------
    | Provider Specific Settings
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'mock' => [
            'model' => 'mock-commerce-intelligence-v1',
        ],

        'openai' => [
            'api_key' => env('AI_API_KEY'),
            'model' => env('AI_MODEL', 'gpt-4o-mini'),
            'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
            'temperature' => (float) env('AI_TEMPERATURE', 0.1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Prevents abuse and excessive upstream API consumption per user / IP.
    |
    */
    'rate_limit' => [
        'max_attempts' => (int) env('AI_RATE_LIMIT_PER_MINUTE', 15),
    ],

];

