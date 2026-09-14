<?php

namespace App\Http\Controllers\Api\V1\Pupr;

use App\Http\Controllers\Controller;
use App\Models\AssetInspection;
use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InspectionController extends Controller
{
    /**
     * Catat inspeksi pra-operasi (Sebelum alat dioperasikan di lapangan)
     */
    public function storePreOperation(Request $request)
    {
        $validated = $request->validate([
            'asset_rental_id' => 'required|integer|exists:asset_rentals,id',
            'hour_meter' => 'required|numeric|min:0',
            'fuel_level_percent' => 'nullable|integer|between:0,100',
            'checklist' => 'nullable|array',
            'condition_notes' => 'nullable|string',
            'photos' => 'nullable|array',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $rental = AssetRental::findOrFail($validated['asset_rental_id']);
        $userId = $request->user()?->id ?? 1;

        $inspection = AssetInspection::create([
            'asset_rental_id' => $rental->id,
            'asset_item_id' => $rental->asset_item_id,
            'inspector_user_id' => $userId,
            'inspection_type' => 'PRE_OPERATION',
            'hour_meter' => $validated['hour_meter'],
            'fuel_level_percent' => $validated['fuel_level_percent'] ?? null,
            'checklist' => $validated['checklist'] ?? null,
            'condition_notes' => $validated['condition_notes'] ?? null,
            'photos' => $validated['photos'] ?? [],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'inspected_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Inspeksi pra-operasi berhasil disimpan.',
            'data' => $inspection,
        ], 201);
    }

    /**
     * Catat inspeksi pasca-operasi & deteksi overtime (Jam pemakaian melebihi kontrak)
     */
    public function storePostOperation(Request $request)
    {
        $validated = $request->validate([
            'asset_rental_id' => 'required|integer|exists:asset_rentals,id',
            'hour_meter' => 'required|numeric|min:0',
            'fuel_level_percent' => 'nullable|integer|between:0,100',
            'checklist' => 'nullable|array',
            'condition_notes' => 'nullable|string',
            'photos' => 'nullable|array',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $rental = AssetRental::with('assetItem')->findOrFail($validated['asset_rental_id']);
        $userId = $request->user()?->id ?? 1;

        // Ambil pre-operation untuk menghitung pemakaian riil
        $preInspection = AssetInspection::where('asset_rental_id', $rental->id)
            ->where('inspection_type', 'PRE_OPERATION')
            ->latest('id')
            ->first();

        $startHm = $preInspection ? (float) $preInspection->hour_meter : (float) $validated['hour_meter'];
        $endHm = (float) $validated['hour_meter'];
        $actualHours = max(0, $endHm - $startHm);

        // Kontrak durasi jam
        $contractedHours = (float) ($rental->lama_sewa ?? 8);
        if ($rental->satuan_sewa === 'hari') {
            $contractedHours = (float) ($rental->lama_sewa * 8); // 1 hari = 8 jam kerja standar
        }

        $isOvertime = $actualHours > $contractedHours;
        $overtimeHours = $isOvertime ? ($actualHours - $contractedHours) : 0;
        $dendaBill = null;

        return DB::transaction(function () use (
            $rental,
            $userId,
            $validated,
            $actualHours,
            $isOvertime,
            $overtimeHours,
            &$dendaBill
        ) {
            // Update rental actual hours
            $rental->update([
                'actual_hours' => $actualHours,
            ]);

            // Jika terjadi overtime, otomatis terbitkan SKRD Denda Overtime
            if ($isOvertime && $overtimeHours > 0) {
                $hourlyRate = $rental->tarif_per_satuan ?: 300000;
                $dendaAmount = $overtimeHours * $hourlyRate;

                $dendaBill = Bill::create([
                    'taxpayer_id' => $rental->taxpayer_id,
                    'opd_id' => 3, // Dinas PUPR
                    'retribution_type_id' => $rental->retribution_type_id ?? 16,
                    'bill_number' => 'SKRD-PUPR-OVT-' . date('ymd') . '-' . strtoupper(Str::random(4)),
                    'amount' => $dendaAmount,
                    'admin_fee' => 0.00,
                    'penalty_amount' => 0.00,
                    'status' => 'UNPAID',
                    'period' => date('Y-m'),
                    'period_start' => Carbon::now()->startOfMonth()->toDateString(),
                    'period_end' => Carbon::now()->endOfMonth()->toDateString(),
                    'due_date' => Carbon::now()->addDays(7)->toDateString(),
                    'metadata' => json_encode([
                        'type' => 'PUPR_OVERTIME_PENALTY',
                        'rental_code' => $rental->rental_code,
                        'overtime_hours' => $overtimeHours,
                        'hourly_rate' => $hourlyRate,
                    ]),
                ]);
            }

            $inspection = AssetInspection::create([
                'asset_rental_id' => $rental->id,
                'asset_item_id' => $rental->asset_item_id,
                'inspector_user_id' => $userId,
                'inspection_type' => 'POST_OPERATION',
                'hour_meter' => $validated['hour_meter'],
                'fuel_level_percent' => $validated['fuel_level_percent'] ?? null,
                'checklist' => $validated['checklist'] ?? null,
                'condition_notes' => $validated['condition_notes'] ?? null,
                'photos' => $validated['photos'] ?? [],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'is_overtime' => $isOvertime,
                'overtime_hours' => $overtimeHours,
                'denda_penalty_bill_id' => $dendaBill?->id ?? null,
                'inspected_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Inspeksi pasca-operasi berhasil dicatat.' . ($isOvertime ? " Terdeteksi overtime {$overtimeHours} jam." : ''),
                'data' => [
                    'inspection' => $inspection,
                    'actual_hours' => $actualHours,
                    'is_overtime' => $isOvertime,
                    'overtime_hours' => $overtimeHours,
                    'denda_bill' => $dendaBill,
                ],
            ], 201);
        });
    }

    /**
     * Dapatkan riwayat inspeksi alat berat berdasarkan ID rental
     */
    public function getHistory($rentalId)
    {
        $inspections = AssetInspection::with('inspector:id,name')
            ->where('asset_rental_id', $rentalId)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $inspections,
        ]);
    }
}
