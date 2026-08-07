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
            Log::warning('PBB inquiry FALLBACK ke SPPT lokal — data tidak ditemukan', [
                'nop' => $nop, 'tahun' => $tahun, 'reason' => $reason,
            ]);
            H2hConnectorLog::record('simpbb', 'fallback', 'inquiry', 'Fallback SPPT lokal gagal: data tidak ada', [
                'nop' => $nop, 'tahun' => $tahun,
            ]);

            return [
                'status'        => 503,
                'msg'           => 'Layanan Bapenda tidak tersedia dan tidak ada data SPPT lokal.',
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

        $data = [
            'status'              => 200,
            'nop'                 => $nop,
            'tahun'               => $tahun,
            'nama_wp'             => $record->taxpayer_name,
            'alamat_wp'           => $alamat,
            'kelurahan'           => $record->kelurahan,
            'kota'                => '-',
            'pbb_pokok'           => $pokok,
            'denda'               => 0,
            'total_harus_dibayar' => $pokok,
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
            'data'                => [
                'nop'                 => $nop,
                'tahun'               => $tahun,
                'nama_wp'             => $record->taxpayer_name,
                'alamat_wp'           => $alamat,
                'kelurahan'           => $record->kelurahan,
                'kota'                => '-',
                'pbb_pokok'           => $pokok,
                'denda'               => 0,
                'total_harus_dibayar' => $pokok,
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
