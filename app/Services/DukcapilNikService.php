<?php

namespace App\Services;

use App\Models\H2hConnectorLog;
use Illuminate\Support\Facades\Http;

class DukcapilNikService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;

    public function __construct()
    {
        $this->baseUrl   = (string) config('services.dukcapil.base_url', '');
        $this->username  = (string) config('services.dukcapil.username', '');
        $this->password  = (string) config('services.dukcapil.password', '');
    }

    /**
     * Kerangka H2H Dukcapil siap dipakai begitu PKS selesai:
     * isi DUKCAPIL_BASE_URL / DUKCAPIL_USERNAME / DUKCAPIL_PASSWORD di .env.
     */
    public function available(): bool
    {
        return $this->baseUrl !== '';
    }

    /**
     * Validasi format NIK 16 digit tanpa library eksternal:
     * - 16 digit numerik
     * - 6 digit pertama: kode provinsi (11–96), kabupaten/kota (01–99), kecamatan (01–99)
     * - digit 7–12: tanggal lahir DDMMYY (perempuan: tanggal + 40)
     * - 4 digit terakhir: nomor urut & pembeda (01–99)
     */
    public function validateFormat(string $nik): bool
    {
        $nik = trim($nik);

        if (! preg_match('/^\d{16}$/', $nik)) {
            return false;
        }

        $province = (int) substr($nik, 0, 2);
        $regency  = (int) substr($nik, 2, 2);
        $district = (int) substr($nik, 4, 2);

        if ($province < 11 || $province > 96) {
            return false;
        }
        if ($regency < 1 || $regency > 99) {
            return false;
        }
        if ($district < 1 || $district > 99) {
            return false;
        }

        $day   = (int) substr($nik, 6, 2);
        $month = (int) substr($nik, 8, 2);
        $year  = (int) substr($nik, 10, 2);

        // Perempuan: tanggal lahir + 40
        if ($day > 40) {
            $day -= 40;
        }

        if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
            return false;
        }

        // Validasi tanggal riil (tahun 2 digit, coba abad 1900 & 2000)
        if (! checkdate($month, $day, 1900 + $year) && ! checkdate($month, $day, 2000 + $year)) {
            return false;
        }

        return true;
    }

    /**
     * Validasi NIK ke server Dukcapil (H2H).
     * Default: not_configured → tidak memblokir alur, hanya dicatat.
     */
    public function validate(string $nik, ?string $name = null): array
    {
        if (! $this->available()) {
            H2hConnectorLog::record(
                'dukcapil',
                'not_configured',
                '/validate',
                'DUKCAPIL belum dikonfigurasi (menunggu PKS)'
            );

            return ['available' => false, 'valid' => null, 'reason' => 'not_configured'];
        }

        try {
            $response = Http::asForm()->timeout(15)
                ->post(rtrim($this->baseUrl, '/') . '/validate', [
                    'username' => $this->username,
                    'password' => $this->password,
                    'nik'      => $nik,
                    'name'     => $name,
                ]);

            $data = $response->json() ?? [];

            if ($response->failed()) {
                H2hConnectorLog::record('dukcapil', 'failed', '/validate', 'HTTP ' . $response->status(), ['nik' => $nik]);

                return ['available' => true, 'valid' => false, 'reason' => 'error:http_' . $response->status()];
            }

            $valid = (($data['status'] ?? 0) === 200) && (($data['valid'] ?? false) === true);

            return [
                'available' => true,
                'valid'     => $valid,
                'reason'    => $valid ? 'ok' : ($data['msg'] ?? 'NIK tidak ditemukan di Dukcapil'),
                'data'      => $data,
            ];

        } catch (\Exception $e) {
            H2hConnectorLog::record('dukcapil', 'failed', '/validate', $e->getMessage(), ['nik' => $nik]);

            return ['available' => true, 'valid' => false, 'reason' => 'error:' . $e->getMessage()];
        }
    }
}
