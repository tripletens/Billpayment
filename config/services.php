<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'lytepay' => [
        'api_key' => env('LYTEPAY_API_KEY'),
        'secret' => env('LYTEPAY_SECRET'),
    ],

    'internal' => [
        'server_token' => env('INTERNAL_SERVER_TOKEN'),
    ],

    /* Mail provider configuration for multi-provider email service */
    'mail_provider' => env('MAIL_PROVIDER', 'mailtrap'),

    'mailtrap' => [
        'api_key' => env('MAILTRAP_API_KEY'),
        'base_url' => env('MAILTRAP_BASE_URL', 'https://mailtrap.example/api/send'),
    ],

    'sendgrid' => [
        'api_key' => env('SENDGRID_API_KEY'),
    ],

    'aws_ses' => [
        'key' => env('AWS_SES_KEY'),
        'secret' => env('AWS_SES_SECRET'),
        'region' => env('AWS_SES_REGION', 'us-east-1'),
    ],

    'paystack' => [
        'secret_key' => env('APP_ENV') === 'production' ? env('PAYSTACK_LIVE_SECRET_KEY') : env('PAYSTACK_TEST_SECRET_KEY'),
        'public_key' => env('APP_ENV') === 'production' ? env('PAYSTACK_LIVE_PUBLIC_KEY') : env('PAYSTACK_TEST_PUBLIC_KEY'),
        'base_url' => 'https://api.paystack.co',
    ],
];

