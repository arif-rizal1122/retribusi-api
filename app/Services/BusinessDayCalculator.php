<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Holiday;

/**
 * Kalkulasi SLA berbasis HARI KERJA (Senin–Jumat, 08.00–16.00 WITA).
 * Melewati Sabtu, Minggu, dan hari libur nasional/cuti bersama dari tabel `holidays`.
 *
 * Dipakai modul Permohonan Layanan (service_applications).
 * ⚠️ SlaTrackerService (yang lama) tetap pakai menit kalender — JANGAN diubah,
 *    alur PBB produksi bergantung padanya.
 */
class BusinessDayCalculator
{
    public const START_HOUR = 8;   // 08.00 WITA
    public const END_HOUR   = 16;  // 16.00 WITA
    public const TZ         = 'Asia/Makassar';

    /** Cache hari libur per tahun agar tidak query berulang dalam satu request. */
    protected static array $holidayCache = [];

    /**
     * Ambil daftar tanggal libur untuk satu tahun (format Y-m-d).
     */
    protected static function holidaysForYear(int $year): array
    {
        if (!isset(self::$holidayCache[$year])) {
            try {
                self::$holidayCache[$year] = Holiday::whereYear('holiday_date', $year)
                    ->pluck('holiday_date')
                    ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
                    ->toArray();
            } catch (\Throwable $e) {
                // Tabel belum ada / DB error → jangan crash, anggap tidak ada libur
                Self::$holidayCache[$year] = [];
            }
        }
        return self::$holidayCache[$year];
    }

    /**
     * Waktu sekarang dalam WITA.
     * SELALU pakai ini, jangan `now()` — app.timezone = UTC.
     */
    public static function nowWita(): Carbon
    {
        return Carbon::now(self::TZ);
    }

    /** Apakah tanggal ini hari kerja (bukan Sabtu/Minggu, bukan libur)? */
    public static function isWorkDay(Carbon $date): bool
    {
        if ($date->isSaturday() || $date->isSunday()) {
            return false;
        }
        return !in_array($date->format('Y-m-d'), self::holidaysForYear((int) $date->year), true);
    }

    /**
     * Geser ke hari kerja berikutnya pada jam mulai (08.00) bila tanggal jatuh di luar jam kerja.
     */
    public static function nextWorkMoment(Carbon $moment): Carbon
    {
        // WAJIB konversi eksplisit ke WITA. app.timezone = UTC, jadi Carbon mentah
        // (mis. now()) membawa UTC dan menggeser hasil 8 jam -> deadline salah hari.
        $m = $moment->copy()->setTimezone(self::TZ);

        // Setelah jam kerja -> lompat ke jam mulai hari kerja berikutnya
        if ($m->hour >= self::END_HOUR) {
            $m->addDay()->setTime(self::START_HOUR, 0, 0);
        } elseif ($m->hour < self::START_HOUR) {
            $m->setTime(self::START_HOUR, 0, 0);
        }

        $guard = 0;
        while (!self::isWorkDay($m) && $guard < 400) {
            $m->addDay()->setTime(self::START_HOUR, 0, 0);
            $guard++;
        }
        return $m;
    }

    /**
     * Tambah N jam KERJA ke sebuah momen, melewati malam, akhir pekan, dan hari libur.
     *
     * @param  Carbon $start   momen mulai (zona apa pun, akan dinormalkan ke WITA)
     * @param  float  $hours   jumlah jam kerja yang ditambahkan
     * @return Carbon          deadline dalam WITA
     */
    public static function addWorkHours(Carbon $start, float $hours): Carbon
    {
        // Normalkan ke WITA dulu (lihat catatan di nextWorkMoment)
        $cursor = self::nextWorkMoment($start->copy()->setTimezone(self::TZ));
        $remaining = $hours;

        $guard = 0;
        while ($remaining > 0 && $guard < 2000) {
            $guard++;
            $hoursLeftToday = self::END_HOUR - $cursor->hour;

            if ($hoursLeftToday <= 0) {
                $cursor->addDay()->setTime(self::START_HOUR, 0, 0);
                $cursor = self::nextWorkMoment($cursor);
                continue;
            }

            if ($remaining <= $hoursLeftToday) {
                $cursor->addMinutes((int) round($remaining * 60));
                $remaining = 0;
                break;
            }

            $remaining -= $hoursLeftToday;
            $cursor->addDay()->setTime(self::START_HOUR, 0, 0);
            $cursor = self::nextWorkMoment($cursor);
        }

        return $cursor;
    }

    /**
     * Tambah N HARI KERJA (× 8 jam kerja per hari).
     * Contoh: 2 hari kerja = 16 jam kerja → deadline 2 hari dinas dari sekarang.
     */
    public static function addWorkDays(Carbon $start, float $workDays): Carbon
    {
        return self::addWorkHours($start, $workDays * (self::END_HOUR - self::START_HOUR));
    }

    /** Hitung selisih jam KERJA antara dua momen (untuk audit keterlambatan). */
    public static function workHoursBetween(Carbon $from, Carbon $to): float
    {
        if ($to->lessThanOrEqualTo($from)) {
            return 0.0;
        }
        $cursor = self::nextWorkMoment($from->copy()->setTimezone(self::TZ));
        $end = $to->copy()->setTimezone(self::TZ);
        $total = 0.0;

        $guard = 0;
        while ($cursor->lessThan($end) && $guard < 2000) {
            $guard++;
            $dayEnd = $cursor->copy()->setTime(self::END_HOUR, 0, 0);
            $sliceEnd = $dayEnd->lessThan($end) ? $dayEnd : $end;

            if ($sliceEnd->greaterThan($cursor)) {
                $total += $cursor->diffInMinutes($sliceEnd) / 60;
            }

            $cursor->addDay()->setTime(self::START_HOUR, 0, 0);
            $cursor = self::nextWorkMoment($cursor);
        }

        return round($total, 2);
    }

    /** Format deadline untuk pesan WA: "Senin, 29 Sep 2026 14.00 WITA" */
    public static function formatId(Carbon $moment): string
    {
        $hari = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
                 'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
        $bulan = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
                  7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
        $m = $moment->copy()->setTimezone(self::TZ);

        return sprintf('%s, %d %s %d %s WITA',
            $hari[$m->format('l')] ?? $m->format('l'),
            (int) $m->format('j'),
            $bulan[(int) $m->format('n')] ?? $m->format('M'),
            (int) $m->format('Y'),
            $m->format('H.i')
        );
    }
}
