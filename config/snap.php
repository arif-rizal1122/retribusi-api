<?php

return [
    'mode' => env('SNAP_MODE', 'sandbox'),

    'partners' => [
        'BRI' => [
            'partner_id' => env('BRI_SNAP_PARTNER_ID', 'test-partner'),
            'client_key' => env('BRI_SNAP_CLIENT_KEY', 'test-client'),
            'client_secret' => env('BRI_SNAP_CLIENT_SECRET', ''),
            'signature_algorithm' => env('BRI_SNAP_SIGNATURE_ALGORITHM', 'hmac_sha512'),
            'signature_secret' => env('BRI_SNAP_SIGNATURE_SECRET', ''),
            'public_key' => env('BRI_SNAP_PUBLIC_KEY', ''),
            'public_key_path' => env('BRI_SNAP_PUBLIC_KEY_PATH', ''),
        ],
        'BTN' => [
            'partner_id' => env('BTN_SNAP_PARTNER_ID', 'test-partner-btn'),
            'client_key' => env('BTN_SNAP_CLIENT_KEY', 'test-client-btn'),
            'client_secret' => env('BTN_SNAP_CLIENT_SECRET', ''),
            'signature_algorithm' => env('BTN_SNAP_SIGNATURE_ALGORITHM', 'rsa_sha256'),
            'signature_secret' => env('BTN_SNAP_SIGNATURE_SECRET', ''),
            'public_key' => env('BTN_SNAP_PUBLIC_KEY', ''),
            'public_key_path' => env('BTN_SNAP_PUBLIC_KEY_PATH', ''),
        ],
    ],

    'callback_base_url' => env('SNAP_CALLBACK_BASE_URL', env('APP_URL')),
    'allowed_ips' => array_filter(array_map('trim', explode(',', env('SNAP_ALLOWED_IPS', '')))),

    'timestamp_tolerance_seconds' => (int) env('SNAP_TIMESTAMP_TOLERANCE_SECONDS', 300),
    'token_ttl_seconds' => (int) env('SNAP_TOKEN_TTL_SECONDS', 900),
    'idempotency_ttl_minutes' => (int) env('SNAP_IDEMPOTENCY_TTL_MINUTES', 1440),

    'briva' => [
        'enabled' => env('BRI_SNAP_ENABLED', false),
        'va_prefix' => env('BRI_SNAP_VA_PREFIX', ''),
        'va_length' => (int) env('BRI_SNAP_VA_LENGTH', 18),
        'payment_request_expiry_minutes' => (int) env('BRI_SNAP_PAYMENT_REQUEST_EXPIRY_MINUTES', 1440),
    ],

    'security' => [
        'mpad_private_key_path' => env('SNAP_MPAD_PRIVATE_KEY_PATH', ''),
        'mpad_public_key_path' => env('SNAP_MPAD_PUBLIC_KEY_PATH', ''),
        'require_bearer_token' => env('SNAP_REQUIRE_BEARER_TOKEN', true),
    ],
];
