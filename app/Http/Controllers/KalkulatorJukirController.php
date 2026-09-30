<?php

namespace App\Http\Controllers;

use App\Models\KalkulatorSimulation;
use App\Models\ParkingZone;
use App\Models\ParkingSpot;
use App\Models\ParkingSpotApplication;
use App\Models\JukirProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KalkulatorJukirController extends Controller
{
    /**
     * GET /api/v1/kalkulator/jukir/zones
     * Daftar zona kawasan parkir resmi untuk dropdown simulasi.
     */
    public function zones(): JsonResponse
    {
        $zones = ParkingZone::active()
            ->select('id', 'zone_code', 'zone_name', 'zone_type', 'latitude', 'longitude')
            ->orderBy('zone_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }

    /**
     * POST /api/v1/kalkulator/jukir/simulate
     * Hitung simulasi pendapatan jukir mitra vs jukir liar berdasarkan Perda 1/2024.
     */
    public function simulate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zone_id' => 'nullable|string|max:36',
            'est_r2_per_day' => 'required|integer|min:0|max:5000',
            'est_r4_per_day' => 'required|integer|min:0|max:2000',
            'work_days' => 'nullable|integer|min:1|max:31',
        ]);

        $r2Count = (int) $validated['est_r2_per_day'];
        $r4Count = (int) $validated['est_r4_per_day'];
        $workDays = (int) ($validated['work_days'] ?? 28);
        $zoneId = $validated['zone_id'] ?? null;

        // Tarif resmi Perda Kota Baubau No. 1 Tahun 2024
        $rateR2 = 2000.0;
        $rateR4 = 3000.0;
        $bpjsFee = 16800.0; // Iuran JKK + JKM per bulan

        // Tarif Retribusi Bulanan Tetap (Sesuai Perwali Tata Cara Retribusi Pelayanan Parkir Dishub)
        // Penentuan nominal retribusi bulanan kasda berdasarkan tipe kawasan / zona parkir
        $zone = null;
        if (!empty($zoneId)) {
            $zone = ParkingZone::find($zoneId);
        }

        // Klasifikasi tarif retribusi bulanan per titik:
        // - Tempat Khusus Parkir (Kawasan Premium: Pantai Kamali, Kotamara): Rp 450.000 / bulan
        // - Tepi Jalan Umum (Kawasan Strategis: Pasar Wameo, Pelabuhan Yos Sudarso): Rp 350.000 / bulan
        // - Ruas Jalan Protokol / Standar: Rp 250.000 / bulan
        if ($zone) {
            if ($zone->zone_type === 'TEMPAT_KHUSUS_PARKIR') {
                $monthlyKasdaTariff = 450000.0;
                $zoneTierName = 'Kawasan Khusus / Pelataran (Kelas I)';
            } elseif (str_contains(strtoupper($zone->zone_name), 'WAMEO') || str_contains(strtoupper($zone->zone_name), 'YOS SUDARSO')) {
                $monthlyKasdaTariff = 350000.0;
                $zoneTierName = 'Tepi Jalan Umum Strategis (Kelas II)';
            } else {
                $monthlyKasdaTariff = 250000.0;
                $zoneTierName = 'Tepi Jalan Umum Standar (Kelas III)';
            }
        } else {
            $monthlyKasdaTariff = 350000.0;
            $zoneTierName = 'Tarif Standar Tepi Jalan Umum Dishub';
        }

        // Kalkulasi Omzet Bruto Lapangan
        $dailyR2Gross = $r2Count * $rateR2;
        $dailyR4Gross = $r4Count * $rateR4;
        $dailyGross = $dailyR2Gross + $dailyR4Gross;
        $monthlyGross = $dailyGross * $workDays;

        // Distribusi Sesuai Tarif Bulanan Resmi Dishub (Bukan Bagi Hasil Persentase):
        // 1. Kasda menerima Retribusi Bulanan Tetap (SKRD Bulanan Dishub).
        // 2. Jukir membayar iuran perlindungan BPJS Ketenagakerjaan.
        // 3. Seluruh kelebihan omzet sepenuhnya menjadi hak bersih Juru Parkir (Take Home Pay).
        $kasdaShare = min($monthlyGross, $monthlyKasdaTariff);
        $jukirSurplus = max(0, $monthlyGross - $kasdaShare);
        $jukirNetShare = max(0, $jukirSurplus - $bpjsFee);

        $resultPayload = [
            'daily' => [
                'r2_vehicles' => $r2Count,
                'r4_vehicles' => $r4Count,
                'r2_revenue' => $dailyR2Gross,
                'r4_revenue' => $dailyR4Gross,
                'total_gross' => $dailyGross,
            ],
            'monthly' => [
                'work_days' => $workDays,
                'gross_turnover' => $monthlyGross,
                'scheme_type' => 'TARIF_BULANAN_PERWALI',
                'zone_tier_name' => $zoneTierName,
                'kasda_monthly_tariff' => $kasdaShare,
                'bpjs_tk_deduction' => $bpjsFee,
                'jukir_net_take_home' => $jukirNetShare,
            ],
            'tariffs' => [
                'r2_motor' => $rateR2,
                'r4_mobil' => $rateR4,
                'monthly_retribution_fixed' => $kasdaShare,
            ],
            'comparison' => [
                [
                    'parameter' => 'Legalitas & Keamanan',
                    'jukir_liar' => 'Rawan penertiban/razia Satpol PP & Polres',
                    'jukir_mitra' => 'Rompi resmi ber-QR Code legalitas Bapenda/Dishub, bebas razia',
                ],
                [
                    'parameter' => 'Skema Setoran',
                    'jukir_liar' => 'Setoran diperas preman/oknum dengan jumlah tidak menentu',
                    'jukir_mitra' => 'Tarif bulanan tetap sesuai Perwali Dishub, sisa omzet 100% milik jukir',
                ],
                [
                    'parameter' => 'Jaminan Sosial',
                    'jukir_liar' => 'Tidak ada perlindungan saat celaka/wafat di jalan',
                    'jukir_mitra' => 'Terlindungi BPJS Ketenagakerjaan (JKK & JKM santunan puluhan juta)',
                ],
                [
                    'parameter' => 'Status Hukum',
                    'jukir_liar' => 'Rentan dijerat tindak pidana pungli (Pasal 368 KUHP)',
                    'jukir_mitra' => 'Mitra Resmi Pemkot & berhak program pembinaan dan reward Dishub',
                ],
            ],
        ];

        // Catat jejak simulasi ke database
        $simulation = KalkulatorSimulation::create([
            'user_id' => $request->user()?->id,
            'simulation_type' => 'JUKIR_REVENUE',
            'target_zone_id' => $zoneId,
            'input_payload' => $validated,
            'result_payload' => $resultPayload,
            'converted_to_registration' => false,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'simulation_id' => $simulation->id,
            'data' => $resultPayload,
        ]);
    }

    /**
     * POST /api/v1/kalkulator/jukir/apply
     * Pendaftaran calon Juru Parkir Mitra Resmi dari hasil simulasi kalkulator.
     */
    public function apply(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan masuk (login) terlebih dahulu untuk mendaftar sebagai Jukir Mitra Resmi.',
            ], 401);
        }

        $validated = $request->validate([
            'simulation_id' => 'nullable|string|max:36',
            'target_zone_id' => 'nullable|string|max:36',
            'proposed_spot_name' => 'required|string|max:100',
            'nik' => 'required|string|size:16',
            'phone' => 'required|string|max:20',
            'bank_name' => 'required|string|max:50',
            'bank_account_number' => 'required|string|max:50',
            'bank_account_holder_name' => 'required|string|max:100',
            'polygon_geojson' => 'nullable|array',
            'capacity_r2' => 'nullable|integer|min:0',
            'capacity_r4' => 'nullable|integer|min:0',
        ]);

        // Cek overlap spasial jika polygon_geojson disertakan
        $overlapWarning = null;
        if (!empty($validated['polygon_geojson'])) {
            try {
                $geojsonStr = json_encode($validated['polygon_geojson']);
                $overlapSpots = DB::select("
                    SELECT id, spot_name 
                    FROM parking_spots 
                    WHERE polygon_boundary IS NOT NULL 
                    AND ST_Intersects(polygon_boundary, ST_GeomFromGeoJSON(?, 1, 4326))
                    LIMIT 3
                ", [$geojsonStr]);

                if (!empty($overlapSpots)) {
                    $conflictNames = collect($overlapSpots)->pluck('spot_name')->implode(', ');
                    $overlapWarning = "Perhatian: Titik yang diajukan beririsan spasial dengan titik resmi yang sudah ada ({$conflictNames}). Tim survei Dishub akan melakukan penyesuaian batas saat survei lapangan.";
                }
            } catch (\Throwable $e) {
                Log::warning('Spatial overlap check skipped: ' . $e->getMessage());
            }
        }

        DB::beginTransaction();
        try {
            // 1. Buat Pengajuan Pendaftaran
            $application = ParkingSpotApplication::create([
                'user_id' => $user->id,
                'target_zone_id' => $validated['target_zone_id'] ?? null,
                'proposed_spot_name' => $validated['proposed_spot_name'],
                'proposed_polygon_geojson' => $validated['polygon_geojson'] ?? null,
                'capacity_r2' => $validated['capacity_r2'] ?? 50,
                'capacity_r4' => $validated['capacity_r4'] ?? 10,
                'status' => 'SUBMITTED',
            ]);

            // 2. Perbarui atau buat profil jukir
            JukirProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nik' => $validated['nik'],
                    'surat_tugas_no' => 'REG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                    'surat_tugas_expired_at' => now()->addYear(),
                    'revenue_share_pct' => 40.00,
                    'status' => 'active',
                    'verification_status' => 'PENDING_SURVEY',
                    'bank_name' => $validated['bank_name'],
                    'bank_account_number' => $validated['bank_account_number'],
                    'bank_account_holder_name' => $validated['bank_account_holder_name'],
                    'is_bank_account_verified' => false,
                    'bpjs_tk_status' => 'PENDING',
                ]
            );

            // 3. Update status simulasi jika ada
            if (!empty($validated['simulation_id'])) {
                KalkulatorSimulation::where('id', $validated['simulation_id'])->update([
                    'converted_to_registration' => true,
                    'application_id' => $application->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permohonan pendaftaran Juru Parkir Mitra Resmi berhasil dikirim! Petugas survei Dishub akan segera memverifikasi titik lokasi fisik Anda.',
                'application_id' => $application->id,
                'overlap_warning' => $overlapWarning,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Gagal mengajukan jukir mitra: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses pendaftaran. Silakan coba beberapa saat lagi.',
            ], 500);
        }
    }
}
