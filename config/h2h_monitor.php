<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi SLO & Alert Monitoring H2H
|--------------------------------------------------------------------------
| Dibaca oleh app/Console/Commands/MonitorH2HCommand.php dan
| app/Services/H2HMonitorService.php.
|
| - thresholds : ambang klasifikasi status (ok/slow/down)
| - slo        : target Service Level Objective
| - alert      : aturan notifikasi (consecutive down + cooldown anti-spam)
| - connectors : daftar 12 skema H2H yang dimonitor
|     * config_key / fallback_key : kunci config() yang dibaca saat runtime
|     * literal_url               : URL cadangan hardcoded (tidak diganti tebakan)
|     * endpoint                  : hasil resolve saat ini ('' bila belum terkonfigurasi)
*/

return [

    'thresholds' => [
        'latency_ok_ms'   => 1000, // latency < ini = ok
        'latency_slow_ms' => 3000, // latency di antara ok..slow = slow
        'timeout_ms'      => 3,    // timeout HTTP (detik)
    ],

    'slo' => [
        'uptime_target_pct' => 99,
        'latency_target_ms' => 1000,
    ],

    'alert' => [
        'down_after_consecutive' => 2,  // jumlah pengecekan down beruntun sebelum notif
        'cooldown_minutes'       => 10, // jeda minimal antar notif per connector
    ],

    'connectors' => [
        'bri_snap' => [
            'label'        => 'BRI SNAP (BRIVA)',
            'config_key'   => 'snap.briva.base_url',
            'fallback_key' => null,
            'literal_url'  => 'https://apidev.bri.co.id',
            'endpoint'     => 'https://apidev.bri.co.id',
        ],
        'bank_sultra' => [
            'label'        => 'Bank Sultra (AFT/SNAP)',
            'config_key'   => 'snap.bank_sultra_aft.base_url',
            'fallback_key' => 'snap.bank_sultra.base_url',
            'literal_url'  => null,
            'endpoint'     => '',
            'verify_ssl'   => false, // cert self-signed (port 15009) — tanpa verifikasi SSL
        ],
        'bni' => [
            'label'        => 'BNI',
            'config_key'   => 'services.bni.base_url',
            'fallback_key' => null,
            'literal_url'  => null,
            'endpoint'     => '',
        ],
        'btn' => [
            'label'        => 'Bank BTN',
            'config_key'   => 'services.btn.base_url',
            'fallback_key' => null,
            'literal_url'  => 'https://devapi.btn.co.id',
            'endpoint'     => 'https://devapi.btn.co.id',
        ],
        'mandiri' => [
            'label'        => 'Bank Mandiri',
            'config_key'   => 'payment.drivers.mandiri.api_url',
            'fallback_key' => null,
            'literal_url'  => 'https://api-dev.bankmandiri.co.id',
            'endpoint'     => 'https://api-dev.bankmandiri.co.id',
        ],
        'qris' => [
            'label'        => 'QRIS',
            'config_key'   => 'services.qris.base_url',
            'fallback_key' => null,
            'literal_url'  => null,
            'endpoint'     => '',
        ],
        'bhumi' => [
            'label'        => 'BPN / BHUMI',
            'config_key'   => 'services.bhumi.base_url',
            'fallback_key' => null,
            'literal_url'  => null,
            'endpoint'     => '',
        ],
        'dukcapil' => [
            'label'        => 'Dukcapil (validasi NIK)',
            'config_key'   => 'services.dukcapil.base_url',
            'fallback_key' => null,
            'literal_url'  => null,
            'endpoint'     => '',
        ],
        'pbb_bapenda' => [
            'label'        => 'PBB Bapenda',
            'config_key'   => 'services.pbb_bapenda.base_url',
            'fallback_key' => null,
            'literal_url'  => 'http://103.182.72.241:8000/pospbb/Api_service',
            'endpoint'     => 'http://103.182.72.241:8000/pospbb/Api_service',
        ],
        'simpad' => [
            'label'        => 'SIMPAD',
            'config_key'   => null,
            'fallback_key' => null,
            'literal_url'  => 'http://103.182.72.241',
            'endpoint'     => 'http://103.182.72.241',
        ],
        'wa_gateway' => [
            'label'        => 'WhatsApp Gateway',
            'config_key'   => 'services.wa_gateway.url',
            'fallback_key' => null,
            'literal_url'  => 'http://localhost:5001',
            'endpoint'     => 'http://localhost:3001',
        ],
        'telegram_notif' => [
            'label'        => 'Telegram Notifikasi',
            'config_key'   => null,
            'fallback_key' => null,
            'literal_url'  => 'https://api.telegram.org',
            'endpoint'     => 'https://api.telegram.org',
        ],
    ],

];
