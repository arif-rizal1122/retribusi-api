<?php

return [
    'mode' => env('SNAP_MODE', 'sandbox'),

    'partner_id' => env('BRI_SNAP_PARTNER_ID', 'test-partner'),
    'client_key' => env('BRI_SNAP_CLIENT_KEY', 'test-client'),
    'client_secret' => env('BRI_SNAP_CLIENT_SECRET', ''),

    'callback_base_url' => env('BRI_SNAP_CALLBACK_BASE_URL', env('APP_URL')),
    'allowed_ips' => array_filter(array_map('trim', explode(',', env('BRI_SNAP_ALLOWED_IPS', '')))),

    'timestamp_tolerance_seconds' => (int) env('BRI_SNAP_TIMESTAMP_TOLERANCE_SECONDS', 300),
    'token_ttl_seconds' => (int) env('BRI_SNAP_TOKEN_TTL_SECONDS', 900),
    'idempotency_ttl_minutes' => (int) env('BRI_SNAP_IDEMPOTENCY_TTL_MINUTES', 1440),

    'security' => [
        'bank_public_key' => env('BRI_SNAP_PUBLIC_KEY', ''),
        'bank_public_key_path' => env('BRI_SNAP_PUBLIC_KEY_PATH', ''),
        'mpad_private_key_path' => env('BRI_SNAP_MPAD_PRIVATE_KEY_PATH', ''),
        'mpad_public_key_path' => env('BRI_SNAP_MPAD_PUBLIC_KEY_PATH', ''),
        'require_bearer_token' => env('BRI_SNAP_REQUIRE_BEARER_TOKEN', true),
    ],
];
