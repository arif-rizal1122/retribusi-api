<?php

return [
    'default' => env('PAYMENT_DEFAULT_DRIVER', 'sultra'),

    'drivers' => [
        'sultra' => [
            'secret' => env('BANK_SULTRA_SECRET', 'secret_sultra_2026'),
            'allowed_ips' => explode(',', env('BANK_SULTRA_ALLOWED_IPS', '127.0.0.1')),
        ],
    ],
];
