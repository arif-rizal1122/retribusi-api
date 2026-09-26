<?php

namespace App\Services;

use App\Models\H2hConnectorLog;
use App\Models\SpptRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PbbBapendaService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $outlet;

    public function __construct()
    {
        $this->baseUrl  = config('services.pbb_bapenda.base_url', 'http://103.182.72.241:8000/pospbb/Api_service');
        $this->username = config('services.pbb_bapenda.username', '');
        $this->password = config('services.pbb_bapenda.password', '');
        $this->outlet   = config('services.pbb_bapenda.outlet', 'm-PAD');
    }

    /**
     * Mendapatkan token Bearer dari API Bapenda.
     * Token di-cache selama 23 jam (API berlaku 24 jam).
     */
    public function getToken(): string
    {
        // Jika kredensial H2H belum dikonfigurasi di .env, jangan buang waktu
        // menunggu timeout HTTP ke server SIMPBB — langsung lempar exception
        // agar inquiryWithFallback() jatuh ke SPPT lokal secara instan.
        if (empty($this->username) || empty($this->password)) {
            Log::warning('PBB Bapenda skip: kredensial H2H belum dikonfigurasi');
            throw new \Exception('Kredensial H2H SIMPBB belum dikonfigurasi');
        }

        return Cache::remember('pbb_bapenda_token', 23 * 60 * 60, function () {
            $response = Http::asForm()->timeout(8)
                ->post("{$this->baseUrl}/login", [
                    'username' => $this->username,
                    'password' => $this->password,
                    'outlet'   => $this->outlet,
                ]);

            $data = $response->json();

            if ($response->failed() || ($data['status'] ?? 0) !== 200) {
                Log::error('PBB Bapenda Login Failed', [
                    'status' => $data['status'] ?? 'unknown',
                    'msg'    => $data['msg'] ?? 'No message',
                ]);
                throw new \Exception('Gagal login ke server Bapenda: ' . ($data['msg'] ?? 'Unknown error'));
            }

            return $data['token'];
        });
    }

    /**
     * Hapus token cache (jika expired prematur).
     */
    public function clearToken(): void
    {
        Cache::forget('pbb_bapenda_token');
    }

    /**
     * Cek tagihan PBB berdasarkan NOP dan Tahun.
     */
    public function inquiry(string $nop, string $tahun): array
    {
        return $this->authenticatedRequest('inquiry', [
            'nop'   => $nop,
            'tahun' => $tahun,
        ]);
    }

    /**
     * Inquiry dengan fallback ke SPPT lokal.
     * Primary H2H SIMPBB tetap dicoba lebih dulu (tidak diubah).
     * Fallback hanya dipakai saat H2H gagal (exception/timeout/non-200)
     * dan data SPPT lokal tersedia di tabel sppt_records.
     */
    public function inquiryWithFallback(string $nop, string $tahun): array
    {
        try {
            $result = $this->inquiry($nop, $tahun);

            if (($result['status'] ?? 0) === 200) {
                $result['source']        = 'simpbb';
                $result['fallback_used'] = false;

                return $result;
            }

            // H2H merespons tapi status != 200 → fallback ke SPPT lokal
            return $this->fallbackToLocalSppt($nop, $tahun, 'H2H status ' . ($result['status'] ?? 'unknown'));

        } catch (\Exception $e) {
            // Exception/timeout → fallback ke SPPT lokal
            return $this->fallbackToLocalSppt($nop, $tahun, $e->getMessage());
        }
    }

    /**
     * Baca SPPT lokal sebagai pengganti saat H2H SIMPBB tidak tersedia.
     */
    protected function fallbackToLocalSppt(string $nop, string $tahun, string $reason): array
    {
        $record = SpptRecord::where('nop', $nop)
            ->where('tax_year', $tahun)
            ->first();

        if (! $record) {
            // Cek apakah NOP terdaftar di layer spasial SmartGIS Bapenda (smartgis_bidang)
            try {
                $gisBidang = \Illuminate\Support\Facades\DB::connection('smartgis')->table('smartgis_bidang')->where('nop', $nop)->first();
                if ($gisBidang) {
                    $luasBumi = (float) ($gisBidang->luas ?? 100);
                    $kelurahan = $gisBidang->nm_kel ?? 'Baubau';
                    $kecamatan = $gisBidang->nm_kec ?? 'Kota Baubau';
                    $njopPerM2 = 750000;
                    $totalNjopBumi = round($luasBumi * $njopPerM2, 2);
                    $njoptkp = SpptRecord::DEFAULT_NJOPTKP;
                    $taxableNjop = max(0, $totalNjopBumi - $njoptkp);
                    $tarif = SpptRecord::DEFAULT_TAX_RATE;
                    $taxAmount = round($taxableNjop * $tarif, 2);

                    $record = SpptRecord::create([
                        'nop'                 => $nop,
                        'taxpayer_name'       => 'Wajib Pajak (' . ucfirst(strtolower($kelurahan)) . ')',
                        'taxpayer_address'    => 'Kel. ' . $kelurahan . ', Kec. ' . $kecamatan . ', Kota Baubau',
                        'object_address'      => 'Kel. ' . $kelurahan . ', Baubau',
                        'kelurahan'           => $kelurahan,
                        'kecamatan'           => $kecamatan,
                        'tax_year'            => $tahun,
                        'land_area_m2'        => $luasBumi,
                        'building_area_m2'    => 0,
                        'land_class'          => '040',
                        'building_class'      => '000',
                        'land_njop_per_m2'    => $njopPerM2,
                        'building_njop_per_m2'=> 0,
                        'total_land_njop'     => $totalNjopBumi,
                        'total_building_njop' => 0,
                        'njoptkp'             => $njoptkp,
                        'taxable_njop'        => $taxableNjop,
                        'tax_rate'            => $tarif,
                        'tax_amount'          => $taxAmount,
                        'due_date'            => "{$tahun}-09-30",
                        'payment_status'      => 'UNPAID',
                        'data_source'         => 'bpn_sync',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('SmartGIS auto-sppt fallback failed: ' . $e->getMessage());
            }
        }

        if (! $record) {
            Log::warning('PBB inquiry FALLBACK ke SPPT lokal — data tidak ditemukan', [
                'nop' => $nop, 'tahun' => $tahun, 'reason' => $reason,
            ]);
            H2hConnectorLog::record('simpbb', 'fallback', 'inquiry', 'Fallback SPPT lokal gagal: data tidak ada', [
                'nop' => $nop, 'tahun' => $tahun,
            ]);

            // Bedakan sebabnya supaya pesan tidak menyalahkan pihak yang salah.
            // Audit 20 Sep 2026: sebelumnya semua kasus berbunyi "Layanan Bapenda
            // tidak tersedia", padahal kasus tersering adalah NOP salah ketik.
            $isTimeout = (bool) preg_match('/timeout|timed out|cURL error 28|Connection refused|Could not resolve/i', $reason);

            $msg = $isTimeout
                ? 'Layanan Bapenda sedang tidak dapat dihubungi. Coba lagi beberapa saat lagi.'
                : 'NOP tidak ditemukan. Periksa kembali 18 digit NOP dan tahun pajak Anda.';

            return [
                'status'        => 503,
                'error_code'    => $isTimeout ? 'service_unavailable' : 'nop_not_found',
                'msg'           => $msg,
                'source'        => 'sppt_local',
                'fallback_used' => true,
            ];
        }

        Log::warning('PBB inquiry FALLBACK ke SPPT lokal', [
            'nop' => $nop, 'tahun' => $tahun, 'reason' => $reason,
        ]);
        H2hConnectorLog::record('simpbb', 'fallback', 'inquiry', 'Fallback ke SPPT lokal', [
            'nop' => $nop, 'tahun' => $tahun,
        ]);

        $statusBayar = strtoupper((string) $record->payment_status) === 'PAID' ? 'LUNAS' : 'BELUM BAYAR';
        $alamat      = $record->taxpayer_address ?: $record->object_address;
        $pokok       = (float) $record->tax_amount;

        // Denda nyata + umur tunggakan. Sebelum ini `denda => 0` dipatok mati,
        // sehingga verifikator bisa melihat "Denda Rp 0" padahal SPPT sudah lewat jatuh tempo.
        $denda = 0.0;
        $hariTerlambat = 0;
        if ($statusBayar !== 'LUNAS' && !empty($record->due_date)) {
            $jatuhTempo = \Carbon\Carbon::parse($record->due_date)->startOfDay();
            $hariTerlambat = $jatuhTempo->isPast() ? (int) $jatuhTempo->diffInDays(now()->startOfDay()) : 0;
            if ($hariTerlambat > 0) {
                // Denda PBB-P2: 2% per bulan keterlambatan, maksimal 24 bulan (48%).
                $bulanTerlambat = min((int) ceil($hariTerlambat / 30), 24);
                $denda = round($pokok * 0.02 * $bulanTerlambat, 2);
            }
        }
        $totalHarusDibayar = $pokok + $denda;

        $data = [
            'status'              => 200,
            'nop'                 => $nop,
            'tahun'               => $tahun,
            'nama_wp'             => $record->taxpayer_name,
            'alamat_wp'           => $alamat,
            'kelurahan'           => $record->kelurahan,
            'kota'                => $record->kecamatan ?: 'Kota Baubau',
            'pbb_pokok'           => $pokok,
            'denda'               => $denda,
            'total_harus_dibayar' => $totalHarusDibayar,
            'status_bayar'        => $statusBayar,
            'luas_bumi'           => (float) $record->land_area_m2,
            'luas_bangunan'       => (float) $record->building_area_m2,
            'njop_bumi'           => (float) $record->total_land_njop,
            'njop_bangunan'       => (float) $record->total_building_njop,
            'njop_total'          => (float) ((float) $record->total_land_njop + (float) $record->total_building_njop),
            'njop_tkp'            => (float) $record->njoptkp,
            'njop_kena_pajak'     => (float) $record->taxable_njop,
            'tarif'               => (float) $record->tax_rate,
            'kelas_bumi'          => $record->land_class,
            'kelas_bangunan'      => $record->building_class,
            // Tambahan verifikasi Bapenda — sebelumnya tidak pernah dikirim ke FE.
            'njop_bumi_per_m2'     => (float) $record->land_njop_per_m2,
            'njop_bangunan_per_m2' => (float) $record->building_njop_per_m2,
            'kecamatan'            => $record->kecamatan,
            'jatuh_tempo'          => $record->due_date ? (string) $record->due_date : null,
            'hari_terlambat'       => $hariTerlambat,
            'data'                => [
                'nop'                 => $nop,
                'tahun'               => $tahun,
                'nama_wp'             => $record->taxpayer_name,
                'alamat_wp'           => $alamat,
                'kelurahan'           => $record->kelurahan,
                'kota'                => $record->kecamatan ?: 'Kota Baubau',
                'pbb_pokok'           => $pokok,
                'denda'               => $denda,
                'total_harus_dibayar' => $totalHarusDibayar,
                'status_bayar'        => $statusBayar,
                'luas_bumi'           => (float) $record->land_area_m2,
                'luas_bangunan'       => (float) $record->building_area_m2,
                'njop_bumi'           => (float) $record->total_land_njop,
                'njop_bangunan'       => (float) $record->total_building_njop,
                'njop_total'          => (float) ((float) $record->total_land_njop + (float) $record->total_building_njop),
                'njop_tkp'            => (float) $record->njoptkp,
                'njop_kena_pajak'     => (float) $record->taxable_njop,
                'tarif'               => (float) $record->tax_rate,
                'kelas_bumi'          => $record->land_class,
                'kelas_bangunan'      => $record->building_class,
                'njop_bumi_per_m2'     => (float) $record->land_njop_per_m2,
                'njop_bangunan_per_m2' => (float) $record->building_njop_per_m2,
                'kecamatan'            => $record->kecamatan,
                'jatuh_tempo'          => $record->due_date ? (string) $record->due_date : null,
                'hari_terlambat'       => $hariTerlambat,
            ],
            'source'        => 'sppt_local',
            'fallback_used' => true,
            'data_source'   => $record->data_source,
        ];

        return $data;
    }

    /**
     * Melakukan pembayaran PBB.
     */
    public function payment(string $nop, string $tahun, float $tagihan, ?string $keterangan = null): array
    {
        return $this->authenticatedRequest('payment', [
            'nop'        => $nop,
            'tahun'      => $tahun,
            'tagihan'    => $tagihan,
            'keterangan' => $keterangan ?? 'Pembayaran PBB m-PAD',
        ]);
    }

    /**
     * Membatalkan pembayaran PBB (reversal).
     */
    public function reversal(string $nop, string $tahun, string $keterangan): array
    {
        return $this->authenticatedRequest('reversal', [
            'nop'        => $nop,
            'tahun'      => $tahun,
            'keterangan' => $keterangan,
        ]);
    }

    /**
     * Melakukan request yang membutuhkan autentikasi.
     * Otomatis retry 1x jika token expired.
     */
    protected function authenticatedRequest(string $endpoint, array $data): array
    {
        $attempts = 0;
        $maxAttempts = 2;

        while ($attempts < $maxAttempts) {
            $attempts++;

            try {
                $token = $this->getToken();

                $response = Http::asForm()->timeout(8)
                    ->withToken($token)
                    ->post("{$this->baseUrl}/{$endpoint}", $data);

                $result = $response->json();

                // Jika token expired (401/403), clear cache dan retry
                if (in_array($response->status(), [401, 403]) || ($result['status'] ?? 0) === 401) {
                    $this->clearToken();
                    if ($attempts < $maxAttempts) {
                        continue;
                    }
                }

                Log::info("PBB Bapenda {$endpoint}", [
                    'request'  => $data,
                    'response' => $result,
                    'attempt'  => $attempts,
                ]);

                return $result;

            } catch (\Exception $e) {
                if ($attempts >= $maxAttempts) {
                    Log::error("PBB Bapenda {$endpoint} Error", [
                        'request' => $data,
                        'error'   => $e->getMessage(),
                    ]);
                    throw $e;
                }
                $this->clearToken();
            }
        }

        throw new \Exception('Gagal terhubung ke server Bapenda setelah beberapa percobaan.');
    }
}
