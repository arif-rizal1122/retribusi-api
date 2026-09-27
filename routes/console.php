<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('monitor:h2h')->everyFiveMinutes();
Schedule::command('billing:notify-due')->dailyAt('08:00');
Schedule::command('bills:calculate-penalties')->dailyAt('01:00');
Schedule::command('auto-deduct:remind-bills --days=7 --overdue')->dailyAt('07:00');
// Rekonsiliasi harian pembayaran PBB M-PAD vs SIMPBB
Schedule::command('pbb:reconcile')->dailyAt('01:00')->withoutOverlapping();
Schedule::job(new \App\Jobs\AnomalyDetectionJob)->dailyAt('02:00');
// Penagihan e-SKRD bulanan Rusunawa Disperkim setiap tanggal 1 pukul 00:01 WITA
Schedule::command('rusun:generate-bills')
    ->monthlyOn(1, '00:01')
    ->timezone('Asia/Makassar')
    ->withoutOverlapping();

// Penagihan otomatis: buat draf Surat Teguran I/II dan Surat Paksa
// untuk tagihan yang menunggak. Dijalankan harian 07:00 WITA.
Schedule::command('enforcements:generate-drafts')->dailyAt('07:00')->timezone('Asia/Makassar');

// Task 2319 — eskalasi SLA penugasan.
// Dicek tiap 30 menit: ambang eskalasi tahapan terpendek (otorisasi Kabid)
// hanya 3 jam, sehingga pemeriksaan harian akan terlambat memberi peringatan.
Schedule::command('sla:escalate')->everyThirtyMinutes()->timezone('Asia/Makassar');

// Modul Permohonan Layanan (Reklame/PBG/Air Tanah/TKA) — SLA hari kerja.
// Pengingat H-4 jam + eskalasi saat deadline lewat. Jalur TERPISAH dari sla:escalate
// (yang menangani alur PBB dengan menit kalender) — jangan digabung.
Schedule::command('service:sla-monitor')
    ->everyThirtyMinutes()
    ->timezone('Asia/Makassar')
    ->withoutOverlapping();
