<?php

return [
    'default' => env('PAYMENT_DEFAULT_DRIVER', 'sultra'),

    'drivers' => [
        'midtrans' => [
            'server_key' => env('MIDTRANS_SERVER_KEY', ''),
            'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
            'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
            'snap_url' => env('MIDTRANS_SNAP_URL', 'https://app.sandbox.midtrans.com/snap/snap.js'),
        ],
        'sultra' => [
            'secret' => env('BANK_SULTRA_SECRET', 'secret_sultra_2026'),
            'allowed_ips' => explode(',', env('BANK_SULTRA_ALLOWED_IPS', '127.0.0.1')),
        ],
        'mandiri' => [
            'api_url' => env('MANDIRI_API_URL', 'https://api-dev.bankmandiri.co.id'),
            'client_id' => env('MANDIRI_CLIENT_ID', ''),
            'client_secret' => env('MANDIRI_CLIENT_SECRET', ''),
        ],
    ],
];
