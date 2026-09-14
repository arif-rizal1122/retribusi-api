<?php

namespace App\Http\Controllers\Api\V1\Dishub;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Taxpayer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HarborProxyGtController extends Controller
{
    const RATES = [
        'proxy_gt_1' => [
            'name' => 'Golongan I (Perahu Motor Kecil / Katinting)',
            'gt_range' => '< 5 GT',
            'daily_rate' => 1000,
            'description' => 'Perahu nelayan tradisional dayung / katinting motor tempel kecil.',
        ],
        'proxy_gt_2' => [
            'name' => 'Golongan II (Speedboat Penumpang Tradisional)',
            'gt_range' => '5 - 10 GT',
            'daily_rate' => 3000,
            'description' => 'Speedboat komersial penumpang antarpulau / rute pendek.',
        ],
        'proxy_gt_3' => [
            'name' => 'Golongan III (Kapal Kayu / KLM Sedang)',
            'gt_range' => '11 - 20 GT',
            'daily_rate' => 5000,
            'description' => 'Kapal Layar Motor / kapal kayu muatan barang dan sembako.',
        ],
        'proxy_gt_4' => [
            'name' => 'Golongan IV (Kapal Layar Motor / KM Besar)',
            'gt_range' => '> 20 GT',
            'daily_rate' => 10000,
            'description' => 'Kapal motor ukuran besar pengangkut komoditas antarprovinsi.',
        ],
    ];

    /**
     * Dapatkan daftar tarif Proxy GT Tambat Labuh Kapal Rakyat (Perda 1/2024 Pasal 91)
     */
    public function indexRates(): JsonResponse
    {
        $data = [];
        foreach (self::RATES as $code => $info) {
            $data[] = [
                'code' => $code,
                'name' => $info['name'],
                'gt_range' => $info['gt_range'],
                'rate' => $info['daily_rate'],
                'unit' => 'per hari',
                'description' => $info['description'],
            ];
        }

        return response()->json([
            'status' => 'success',
            'legal_basis' => 'Perda Kota Baubau No. 1 Tahun 2024 Pasal 91 & SOP 05 Layanan Pendapatan Dishub M-PAD V5',
            'data' => $data,
        ]);
    }

    /**
     * Petugas lapangan menerbitkan karcis/retribusi tambat labuh dermaga
     */
    public function storeSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ship_name' => 'required|string|max:150',
            'captain_or_owner_name' => 'nullable|string|max:150',
            'gt_category' => 'required|string|in:proxy_gt_1,proxy_gt_2,proxy_gt_3,proxy_gt_4',
            'duration_days' => 'required|integer|min:1|max:30',
            'berth_location' => 'required|string|max:100', // Dermaga Jembatan Batu, Wameo, Lakologou, dll
            'payment_method' => 'required|string|in:TUNAI,QRIS_INSTANT',
        ]);

        $catInfo = self::RATES[$validated['gt_category']];
        $dailyRate = $catInfo['daily_rate'];
        $duration = (int) $validated['duration_days'];
        $totalAmount = $dailyRate * $duration;

        $officerId = $request->user()?->id ?? 1;
        $ticketCode = 'TMB-DSH-' . date('ymd') . '-' . strtoupper(Str::random(5));
        $qrToken = 'MPAD-DISHUB-GT-' . md5($ticketCode . now()->timestamp);

        return DB::transaction(function () use (
            $validated,
            $catInfo,
            $totalAmount,
            $duration,
            $officerId,
            $ticketCode,
            $qrToken
        ) {
            $dishubOpdId = DB::table('opds')->where('code', 'DISHUB')->value('id') ?? 2;

            // Catat transaksi tambat
            $sessionId = DB::table('parking_sessions')->insertGetId([
                'parking_location_id' => 1, // Lokasi Dermaga
                'vehicle_type' => 'KAPAL_' . strtoupper($validated['gt_category']),
                'license_plate' => strtoupper($validated['ship_name']),
                'check_in_time' => now(),
                'check_out_time' => now()->addDays($duration),
                'amount' => $totalAmount,
                'status' => 'PAID',
                'payment_method' => $validated['payment_method'],
                'user_id' => $officerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Retribusi tambat labuh kapal berhasil diterbitkan.',
                'data' => [
                    'id' => $sessionId,
                    'ticket_code' => $ticketCode,
                    'ship_name' => $validated['ship_name'],
                    'captain_or_owner_name' => $validated['captain_or_owner_name'] ?? '-',
                    'berth_location' => $validated['berth_location'],
                    'gt_category' => $validated['gt_category'],
                    'category_name' => $catInfo['name'],
                    'duration_days' => $duration,
                    'daily_rate' => $catInfo['daily_rate'],
                    'total_amount' => $totalAmount,
                    'payment_method' => $validated['payment_method'],
                    'qr_token' => $qrToken,
                    'issued_at' => now()->toISOString(),
                    'valid_until' => now()->addDays($duration)->toISOString(),
                ],
            ], 201);
        });
    }

    /**
     * Dapatkan manifes harian kapal tambat per dermaga
     */
    public function getManifest(Request $request, $date = null): JsonResponse
    {
        $targetDate = $date ?: now()->toDateString();

        $sessions = DB::table('parking_sessions')
            ->whereDate('check_in_time', $targetDate)
            ->where('vehicle_type', 'LIKE', 'KAPAL_%')
            ->get();

        $totalRevenue = $sessions->sum('amount');
        $totalShips = $sessions->count();

        return response()->json([
            'status' => 'success',
            'date' => $targetDate,
            'summary' => [
                'total_ships' => $totalShips,
                'total_revenue' => (float) $totalRevenue,
            ],
            'manifest' => $sessions->map(function ($s) {
                return [
                    'id' => $s->id,
                    'ship_name' => $s->license_plate,
                    'category' => str_replace('KAPAL_', '', $s->vehicle_type),
                    'check_in' => $s->check_in_time,
                    'check_out' => $s->check_out_time,
                    'amount' => (float) $s->amount,
                    'payment_method' => $s->payment_method,
                ];
            }),
        ]);
    }
}
