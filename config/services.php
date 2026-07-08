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

    'squadco' => [
        'api_key' => env('SQUADCO_API_KEY'),
        'public_key' => env('SQUADCO_PUBLIC_KEY'),
        'base_url' => env('SQUADCO_BASE_URL'),
        // When true, withdrawal requests attempt immediate payout via Squadco
        'auto_approve_withdrawals' => env('SQUADCO_AUTO_APPROVE', false),
        'webhook_secret' => env('SQUADCO_WEBHOOK_SECRET', null),
        'request_retries' => env('SQUADCO_REQUEST_RETRIES', 3),
        'request_retry_delay_ms' => env('SQUADCO_REQUEST_RETRY_DELAY_MS', 200),
    ],

];
