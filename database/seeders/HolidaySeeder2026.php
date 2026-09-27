<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Hari libur nasional & cuti bersama 2026 (untuk kalkulasi SLA hari kerja).
 *
 * Sumber: SKB Menteri (Agama, Ketenagakerjaan, PANRB) tentang Hari Libur Nasional
 * dan Cuti Bersama Tahun 2026. Silakan verifikasi ulang bila ada perubahan resmi.
 *
 * Seeder ini boleh dijalankan berulang (idempotent via updateOrInsert).
 */
class HolidaySeeder2026 extends Seeder
{
    public function run(): void
    {
        $rows = [
            // ---- Libur nasional 2026 ----
            ['2026-01-01', 'Tahun Baru 2026 Masehi', false],
            ['2026-01-17', 'Isra Mikraj Nabi Muhammad SAW', false],
            ['2026-02-17', 'Tahun Baru Imlek 2577 Kongzili', false],
            ['2026-03-19', 'Hari Suci Nyepi (Tahun Baru Saka 1948)', false],
            ['2026-03-20', 'Awal Ramadan 1447 H', false],
            ['2026-03-21', 'Hari Raya Idul Fitri 1447 H', false],
            ['2026-03-22', 'Hari Raya Idul Fitri 1447 H (Hari Kedua)', false],
            ['2026-04-03', 'Wafat Isa Al Masih', false],
            ['2026-04-05', 'Kebangkitan Isa Al Masih', false],
            ['2026-05-01', 'Hari Buruh Internasional', false],
            ['2026-05-14', 'Kenaikan Isa Al Masih', false],
            ['2026-05-27', 'Hari Raya Idul Adha 1447 H', false],
            ['2026-05-31', 'Hari Raya Waisak 2570 BE', false],
            ['2026-06-01', 'Hari Lahir Pancasila', false],
            ['2026-06-17', 'Tahun Baru Islam 1448 H', false],
            ['2026-08-17', 'Hari Kemerdekaan Republik Indonesia', false],
            ['2026-08-25', 'Maulid Nabi Muhammad SAW', false],
            ['2026-12-25', 'Hari Raya Natal', false],

            // ---- Cuti bersama 2026 ----
            ['2026-03-23', 'Cuti Bersama Idul Fitri 1447 H', true],
            ['2026-03-24', 'Cuti Bersama Idul Fitri 1447 H', true],
            ['2026-03-25', 'Cuti Bersama Idul Fitri 1447 H', true],
            ['2026-03-26', 'Cuti Bersama Idul Fitri 1447 H', true],
            ['2026-05-15', 'Cuti Bersama Kenaikan Isa Al Masih', true],
            ['2026-12-24', 'Cuti Bersama Hari Raya Natal', true],
        ];

        foreach ($rows as [$date, $name, $joint]) {
            DB::table('holidays')->updateOrInsert(
                ['holiday_date' => $date],
                [
                    'name'           => $name,
                    'is_joint_leave' => $joint,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]
            );
        }

        $this->command?->info('HolidaySeeder2026: ' . count($rows) . ' hari libur dimasukkan/diperbarui.');
    }
}
