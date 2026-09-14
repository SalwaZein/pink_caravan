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
    | SMS gateway for patient notifications. When `url` is set, Messenger POSTs
    | {to, from, body} to it (with a bearer token if provided); otherwise it logs
    | (stub) until a provider is connected. Email uses config/mail.php directly.
    */
    'sms' => [
        'url'   => env('SMS_GATEWAY_URL'),
        'token' => env('SMS_GATEWAY_TOKEN'),
        'from'  => env('SMS_FROM', 'PinkCaravan'),
    ],

    /*
    | WhatsApp Business API — the FOCP number through Outreachable (Meta Cloud
    | API v19.0). `url` is the …/{phone_number_id}/messages endpoint, `token` the
    | bearer token (a secret: .env / host environment only, never committed).
    |
    | Business-initiated WhatsApp messages must use APPROVED templates. Each
    | message below maps to the template name approved in Outreachable; its
    | variables are listed in App\Support\WhatsApp::TEMPLATES. A message whose
    | template is not set yet is not sent (status "no_template").
    | With no url/token at all, messages are logged (stub) as before.
    */
    'whatsapp' => [
        'url'       => env('WHATSAPP_API_URL'),
        'token'     => env('WHATSAPP_API_TOKEN'),
        'language'  => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en'),
        'templates' => [
            'queue_issued'      => env('WHATSAPP_TPL_QUEUE_ISSUED'),
            'queue_approaching' => env('WHATSAPP_TPL_QUEUE_APPROACHING'),
            'queue_called'      => env('WHATSAPP_TPL_QUEUE_CALLED'),
            'report_ready'      => env('WHATSAPP_TPL_REPORT_READY'),
            'radiology_report'  => env('WHATSAPP_TPL_RADIOLOGY_REPORT'),
            'patient_otp'       => env('WHATSAPP_TPL_PATIENT_OTP'),
        ],
    ],

];
