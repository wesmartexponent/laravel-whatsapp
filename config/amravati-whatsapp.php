<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AmravatiSMS WhatsApp API Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control how your Laravel application connects to the
    | AmravatiSMS WhatsApp Business API.
    |
    */

    'base_url' => env('AMRAVATISMS_BASE_URL', 'https://automate.amravatisms.com'),

    'api_key' => env('AMRAVATISMS_API_KEY'),

    'phone_number_id' => env('AMRAVATISMS_PHONE_NUMBER_ID'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    */

    'http' => [
        'timeout' => env('AMRAVATISMS_TIMEOUT', 30),
        'retry_times' => env('AMRAVATISMS_RETRY_TIMES', 3),
        'retry_sleep' => env('AMRAVATISMS_RETRY_SLEEP', 100), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */

    'webhook' => [
        'enabled' => env('AMRAVATISMS_WEBHOOK_ENABLED', true),
        'route' => env('AMRAVATISMS_WEBHOOK_ROUTE', 'webhook/whatsapp'),
        'verify_signature' => env('AMRAVATISMS_VERIFY_SIGNATURE', true),
        'secret' => env('AMRAVATISMS_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Sync Configuration
    |--------------------------------------------------------------------------
    */

    'templates' => [
        'sync_enabled' => env('AMRAVATISMS_TEMPLATE_SYNC', true),
        'sync_interval' => env('AMRAVATISMS_SYNC_INTERVAL', 'hourly'),
        'cache_duration' => env('AMRAVATISMS_TEMPLATE_CACHE', 3600),
        'table' => 'whatsapp_templates',
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Logging
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled' => env('AMRAVATISMS_LOGGING', true),
        'table' => 'whatsapp_message_logs',
        'channel' => env('AMRAVATISMS_LOG_CHANNEL', 'amravati-whatsapp'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'enabled' => env('AMRAVATISMS_QUEUE_ENABLED', false),
        'connection' => env('AMRAVATISMS_QUEUE_CONNECTION'),
        'queue' => env('AMRAVATISMS_QUEUE_NAME', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Language for Templates
    |--------------------------------------------------------------------------
    */

    'default_language' => env('AMRAVATISMS_DEFAULT_LANGUAGE', 'en_US'),
];
