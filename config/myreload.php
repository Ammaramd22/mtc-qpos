<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MyReload API (https://myreload.lk/)
    |--------------------------------------------------------------------------
    | Each business / customer stores its OWN API User Name + API Key in
    | Business Settings → Reload Module. Never share one key across shops.
    |
    | Demo values below are only for local testing / fill-demo helper.
    | Contact: info@myreload.lk / 0767 223 223
    */
    'enabled' => env('MYRELOAD_ENABLED', true),
    'api_url' => env('MYRELOAD_API_URL', 'https://api.myreload.lk/v1/reload'),
    // Optional platform defaults — left empty so each business must save its own key.
    'api_username' => env('MYRELOAD_API_USERNAME', ''),
    'api_key' => env('MYRELOAD_API_KEY', ''),
    'merchant_id' => env('MYRELOAD_MERCHANT_ID', ''),
    'callback_url' => env('MYRELOAD_CALLBACK_URL', ''),
    'timeout' => (int) env('MYRELOAD_TIMEOUT', 30),

    // Reference only — shown in settings as "Fill Demo"; not used as a shared live key.
    'demo_api_username' => env('MYRELOAD_DEMO_API_USERNAME', 'Demo'),
    'demo_api_key' => env('MYRELOAD_DEMO_API_KEY', '316bacdc0b01ab3eed57620914ab0eb7'),
];
