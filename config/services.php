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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'wa_gateway' => [
        'url' => env('WA_GATEWAY_URL', 'http://localhost:3001'),
    ],

    'pbb_bapenda' => [
        'base_url' => env('PBB_BAPENDA_BASE_URL', 'http://103.182.72.241:8000/pospbb/Api_service'),
        'username' => env('PBB_BAPENDA_USERNAME', ''),
        'password' => env('PBB_BAPENDA_PASSWORD', ''),
        'outlet'   => env('PBB_BAPENDA_OUTLET', 'm-PAD'),
    ],

    // H2H Dukcapil (validasi NIK) — kerangka siap pakai, default kosong (not_configured) sampai PKS selesai
    'dukcapil' => [
        'base_url' => env('DUKCAPIL_BASE_URL', ''),
        'username' => env('DUKCAPIL_USERNAME', ''),
        'password' => env('DUKCAPIL_PASSWORD', ''),
    ],

    // H2H BPN/BHUMI (matching NIB-NOP) — kerangka siap pakai, default kosong (not_configured) sampai PKS selesai
    'bhumi' => [
        'base_url' => env('BHUMI_BASE_URL', ''),
        'token'    => env('BHUMI_TOKEN', ''),
    ],

    // Notifikasi status H2H ke admin via Telegram — default off
    'h2h_notify' => [
        'telegram_enabled' => env('H2H_NOTIFY_TELEGRAM', false),
        'bot_token'        => env('MPAD_BOT_TOKEN', ''),
        'chat_id'          => env('H2H_NOTIFY_CHAT_ID', '846880203'),
    ],

    'btn' => [
        'base_url' => env('BTN_BASE_URL', 'https://devapi.btn.co.id'),
        'client_id' => env('BTN_CLIENT_ID', ''), // OAuth ID
        'client_secret' => env('BTN_CLIENT_SECRET', ''), // Apikey Secret
        'api_key' => env('BTN_API_KEY', ''), // Apikey ID
        'partner_id' => env('BTN_PARTNER_ID', '99017'),
        'channel_id' => env('BTN_CHANNEL_ID', '00001'),
        'private_key_path' => env('BTN_PRIVATE_KEY_PATH', storage_path('app/keys/btn_private.pem')),
    ],

];
