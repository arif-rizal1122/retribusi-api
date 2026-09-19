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
        'base_url' => env('PBB_BAPENDA_BASE_URL', 'http://103.182.72.241:8000/pospbb/Api_pos'),
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
        // Endpoint push webhook status lunas ke BPN (SPLP) — kosong sampai PKS selesai
        'splp_endpoint' => env('BHUMI_SPLP_ENDPOINT', ''),
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

    'bpn' => [
        // H2H BPN — key untuk endpoint check-pbb (BPN -> M-PAD).
        // Kosong = dev mode (endpoint bisa diuji tanpa key). Isi saat PKS.
        'h2h_key' => env('BPN_H2H_KEY', ''),
    ],

    'bapenda' => [
        'penagihan_wa' => env('BAPENDA_PENAGIHAN_WA', '6281234567890'),

        /*
        | Tanda tangan dokumen resmi (blok TTE).
        | Nilai di bawah adalah PLACEHOLDER yang jelas — WAJIB diisi lewat .env
        | dengan pejabat yang benar sebelum dokumen dipakai sebagai dasar hukum.
        | JANGAN mengisi NIP karangan.
        */
        'kepala_nama'    => env('BAPENDA_KEPALA_NAMA', '[Nama Kepala Bapenda — set BAPENDA_KEPALA_NAMA di .env]'),
        'kepala_nip'     => env('BAPENDA_KEPALA_NIP', '[NIP belum diisi — set BAPENDA_KEPALA_NIP di .env]'),
        'kepala_jabatan' => env('BAPENDA_KEPALA_JABATAN', 'Kepala Badan Pendapatan Daerah Kota Baubau'),
        'signature_city' => env('BAPENDA_SIGNATURE_CITY', 'Baubau'),

        /*
        | Status TTE sebenarnya. BSrE/BSSN BELUM aktif (tte_logs = 0).
        | Set BAPENDA_BSRE_AKTIF=true HANYA setelah integrasi BSrE benar-benar live,
        | supaya dokumen berhenti mencetak keterangan "belum tersertifikasi BSrE".
        */
        'bsre_aktif' => filter_var(env('BAPENDA_BSRE_AKTIF', false), FILTER_VALIDATE_BOOLEAN),
        'dpmptsp' => [
            'base_url' => env('DPMPTSP_API_BASE_URL', ''),
            'api_key' => env('DPMPTSP_API_KEY', ''),
            'enabled' => env('DPMPTSP_VALIDATION_ENABLED', false),
        ],
    ],

    // TTE BSrE (Badan Siber dan Sandi Negara) — Sertifikat Elektronik
    // Mode: kalau url kosong/placeholder → fallback segel lokal (jangan klaim sah BSrE)
    'bsre' => [
        'url' => env('BSRE_API_URL', ''),
        'username' => env('BSRE_USERNAME', ''),
        'password' => env('BSRE_PASSWORD', ''),
        'timeout' => env('BSRE_TIMEOUT', 3000),
        // URL verifikasi publik yang dicetak di QR Code dokumen
        'verify_base_url' => env('BSRE_VERIFY_URL', 'https://mpad.baubaukota.go.id/verify'),
    ],

];
