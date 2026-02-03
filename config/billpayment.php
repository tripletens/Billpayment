<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Bill Payment Provider
    |--------------------------------------------------------------------------
    |
    | This value determines which bill payment provider will be used by default
    | for electricity, airtime, and other bill payments.
    |
    | Supported: "buypower", "vtpass", "interswitch"
    |
    */
    'provider' => env('BILL_PAYMENT_PROVIDER', 'buypower'),

    /*
    |--------------------------------------------------------------------------
    | BuyPower Configuration
    |--------------------------------------------------------------------------
    */
    'buypower' => [
        'base_url' => env('BUYPOWER_BASE_URL', 'https://api.buypower.ng'),
        'token' => env('BUYPOWER_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | VTPass Configuration
    |--------------------------------------------------------------------------
    */
    'vtpass' => [
        'base_url' => env('VTPASS_BASE_URL', 'https://api.vtpass.com/api'),
        'api_key' => env('VTPASS_API_KEY'),
        'secret' => env('VTPASS_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Interswitch Configuration
    |--------------------------------------------------------------------------
    */
    'interswitch' => [
        'base_url' => env('INTERSWITCH_BASE_URL', 'https://sandbox.interswitchng.com'),
        'client_id' => env('INTERSWITCH_CLIENT_ID'),
        'secret' => env('INTERSWITCH_SECRET'),
        'terminal_id' => env('INTERSWITCH_TERMINAL_ID'),
    ],
];
