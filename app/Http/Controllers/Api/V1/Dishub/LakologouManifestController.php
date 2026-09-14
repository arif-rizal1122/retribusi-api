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

class LakologouManifestController extends Controller
{
    // Tarif Retribusi Bongkar Muat Komoditas Pelabuhan Lakologou (Perda 1/2024)
    const TARIFF_PER_TON = 20000; // Rp 20.000 / ton

    /**
     * Rekam manifes komoditas tambang / aspal Pelabuhan Lakologou
     */
    public function storeManifest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ship_name' => 'required|string|max:150',
            'consignee_company' => 'required|string|max:200',
            'commodity_type' => 'required|string|in:ASPAL_BUTON,BATU_PECAH,PASIR_SILIKA,KLINKER_SEMEN,KOMODITAS_LAIN',
            'tonnage' => 'required|numeric|min:0.5',
            'origin_or_destination' => 'nullable|string|max:150',
            'payment_method' => 'required|string|in:TRANSFER_KASDA,VA_BANK_SULTRA,TUNAI',
        ]);

        $tonnage = (float) $validated['tonnage'];
        $totalAmount = $tonnage * self::TARIFF_PER_TON;
        $manifestNumber = 'MNF-LKG-' . date('ymd') . '-' . strtoupper(Str::random(5));
        $userId = $request->user()?->id ?? 1;

        return DB::transaction(function () use ($validated, $tonnage, $totalAmount, $manifestNumber, $userId) {
            $taxpayer = Taxpayer::withoutGlobalScopes()->firstOrCreate(
                [
                    'opd_id' => 2,
                    'name' => $validated['consignee_company'],
                ],
                [
                    'phone' => '08110000000',
                    'address' => 'Pelabuhan Lakologou, Kota Baubau',
                    'is_active' => true,
                ]
            );

            $bill = Bill::create([
                'taxpayer_id' => $taxpayer->id,
                'opd_id' => 2, // Dishub
                'retribution_type_id' => 18, // Jasa Kepelabuhanan
                'bill_number' => $manifestNumber,
                'amount' => $totalAmount,
                'admin_fee' => 0.00,
                'penalty_amount' => 0.00,
                'status' => 'PAID',
                'period' => date('Y-m'),
                'period_start' => Carbon::now()->startOfMonth()->toDateString(),
                'period_end' => Carbon::now()->endOfMonth()->toDateString(),
                'due_date' => Carbon::now()->toDateString(),
                'metadata' => json_encode([
                    'type' => 'LAKOLOGOU_MINING_MANIFEST',
                    'ship_name' => $validated['ship_name'],
                    'commodity' => $validated['commodity_type'],
                    'tonnage' => $tonnage,
                    'rate_per_ton' => self::TARIFF_PER_TON,
                    'route' => $validated['origin_or_destination'] ?? 'Domestik',
                    'recorded_by' => $userId,
                ]),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Manifes komoditas tambang Pelabuhan Lakologou berhasil diterbitkan.',
                'data' => [
                    'manifest_number' => $manifestNumber,
                    'ship_name' => $validated['ship_name'],
                    'consignee_company' => $validated['consignee_company'],
                    'commodity_type' => $validated['commodity_type'],
                    'tonnage' => $tonnage,
                    'rate_per_ton' => self::TARIFF_PER_TON,
                    'total_amount' => $totalAmount,
                    'bill_id' => $bill->id,
                    'issued_at' => now()->toISOString(),
                ],
            ], 201);
        });
    }

    /**
     * Dapatkan rekapitulasi manifes bulanan Pelabuhan Lakologou
     */
    public function getSummary(Request $request): JsonResponse
    {
        $period = $request->query('period', date('Y-m'));

        $bills = Bill::withoutGlobalScopes()
            ->where('opd_id', 2)
            ->where('bill_number', 'LIKE', 'MNF-LKG-%')
            ->where('period', $period)
            ->get();

        $totalTonnage = 0;
        $manifests = [];

        foreach ($bills as $b) {
            $meta = json_decode($b->metadata, true) ?? [];
            $ton = (float) ($meta['tonnage'] ?? 0);
            $totalTonnage += $ton;

            $manifests[] = [
                'manifest_number' => $b->bill_number,
                'ship_name' => $meta['ship_name'] ?? '-',
                'commodity' => $meta['commodity'] ?? '-',
                'tonnage' => $ton,
                'amount' => (float) $b->amount,
                'created_at' => $b->created_at?->toISOString(),
            ];
        }

        return response()->json([
            'status' => 'success',
            'period' => $period,
            'summary' => [
                'total_manifests' => count($manifests),
                'total_tonnage' => $totalTonnage,
                'total_revenue' => (float) $bills->sum('amount'),
                'tariff_rate' => self::TARIFF_PER_TON,
            ],
            'data' => $manifests,
        ]);
    }
}
