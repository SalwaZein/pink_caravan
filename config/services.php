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

    /*
    | Emirates ID card reader. In production, point this at the local reader
    | bridge/middleware running on the clinic device (e.g. the official Emirates
    | ID Toolkit exposing a localhost endpoint that returns the normalised card
    | JSON). When empty, the app falls back to its built-in dev mock endpoint so
    | the "Read Emirates ID" flow is fully testable without hardware.
    */
    'emirates_id' => [
        'reader_url' => env('EMIRATES_ID_READER_URL'),
    ],

    /*
    | Patient messaging gateways for report-ready notifications. When a channel's
    | `url` is set, PatientNotifier POSTs {to, from, body} to it (with a bearer
    | token if provided); otherwise it logs (stub) until a provider is connected.
    | Email uses Laravel's mail system (config/mail.php) directly.
    */
    'sms' => [
        'url'   => env('SMS_GATEWAY_URL'),
        'token' => env('SMS_GATEWAY_TOKEN'),
        'from'  => env('SMS_FROM', 'PinkCaravan'),
    ],
    'whatsapp' => [
        'url'   => env('WHATSAPP_GATEWAY_URL'),
        'token' => env('WHATSAPP_GATEWAY_TOKEN'),
        'from'  => env('WHATSAPP_FROM'),
    ],

];
