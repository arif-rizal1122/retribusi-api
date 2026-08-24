<?php

return [
    'mode' => env('SNAP_MODE', 'sandbox'),

    'partners' => [
        'BRI' => [
            'partner_id' => env('BRI_SNAP_PARTNER_ID', 'test-partner'),
            'client_key' => env('BRI_SNAP_CLIENT_KEY', 'test-client'),
            'client_secret' => env('BRI_SNAP_CLIENT_SECRET', ''),
            'signature_algorithm' => env('BRI_SNAP_SIGNATURE_ALGORITHM', 'rsa_sha256'),
            'signature_secret' => env('BRI_SNAP_SIGNATURE_SECRET', ''),
            'public_key' => env('BRI_SNAP_PUBLIC_KEY', ''),
            'public_key_path' => env('BRI_SNAP_PUBLIC_KEY_PATH', ''),
            'base_url' => env('BRI_SNAP_BASE_URL', ''),
            'private_key_path' => env('BRI_SNAP_PRIVATE_KEY_PATH', storage_path('app/keys/mpad_bri_private.pem')),
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
        'BANK_SULTRA' => [
            'partner_id' => env('BANK_SULTRA_SNAP_PARTNER_ID', ''),
            'client_key' => env('BANK_SULTRA_SNAP_CLIENT_KEY', ''),
            'client_secret' => env('BANK_SULTRA_SNAP_CLIENT_SECRET', ''),
            'channel_id' => env('BANK_SULTRA_SNAP_CHANNEL_ID', ''),
            'signature_algorithm' => env('BANK_SULTRA_SNAP_SIGNATURE_ALGORITHM', 'rsa_sha256'),
            'signature_secret' => env('BANK_SULTRA_SNAP_SIGNATURE_SECRET', ''),
            'private_key_path' => env('BANK_SULTRA_SNAP_PRIVATE_KEY_PATH', ''),
            'public_key_path' => env('BANK_SULTRA_SNAP_PUBLIC_KEY_PATH', ''),
        ],
    ],

    'callback_base_url' => env('SNAP_CALLBACK_BASE_URL', env('APP_URL')),
    'allowed_ips' => array_filter(array_map('trim', explode(',', env('SNAP_ALLOWED_IPS', '')))),

    'timestamp_tolerance_seconds' => (int) env('SNAP_TIMESTAMP_TOLERANCE_SECONDS', 300),
    'token_ttl_seconds' => (int) env('SNAP_TOKEN_TTL_SECONDS', 900),
    'idempotency_ttl_minutes' => (int) env('SNAP_IDEMPOTENCY_TTL_MINUTES', 1440),

    'briva' => [
        'enabled' => env('BRI_SNAP_ENABLED', false),
        'inquiry_enabled' => env('BRI_SNAP_INQUIRY_ENABLED', false),
        'partner_service_id' => env('BRI_SNAP_PARTNER_SERVICE_ID', ''),
        'va_prefix' => env('BRI_SNAP_VA_PREFIX', ''),
        'customer_no_length' => (int) env('BRI_SNAP_CUSTOMER_NO_LENGTH', 20),
        'va_length' => (int) env('BRI_SNAP_VA_LENGTH', 28),
        'payment_request_expiry_minutes' => (int) env('BRI_SNAP_PAYMENT_REQUEST_EXPIRY_MINUTES', 1440),
    ],

    'bank_sultra_aft' => [
        'enabled' => env('BANK_SULTRA_AFT_ENABLED', false),
        'selected_product' => env('BANK_SULTRA_AFT_SELECTED_PRODUCT', ''),
        'base_url' => env('BANK_SULTRA_SNAP_BASE_URL', ''),
        'version' => env('BANK_SULTRA_SNAP_VERSION', 'v1.0'),
        'timeout_seconds' => (int) env('BANK_SULTRA_AFT_TIMEOUT_SECONDS', 15),
        'allowed_ips' => array_filter(array_map('trim', explode(',', env('BANK_SULTRA_AFT_ALLOWED_IPS', '')))),
        'source_account_no' => env('BANK_SULTRA_AFT_SOURCE_ACCOUNT_NO', ''),
        'destination_account_no' => env('BANK_SULTRA_AFT_DESTINATION_ACCOUNT_NO', ''),
    ],

    'security' => [
        'mpad_private_key_path' => env('SNAP_MPAD_PRIVATE_KEY_PATH', ''),
        'mpad_public_key_path' => env('SNAP_MPAD_PUBLIC_KEY_PATH', ''),
        'require_bearer_token' => env('SNAP_REQUIRE_BEARER_TOKEN', true),
    ],
];
