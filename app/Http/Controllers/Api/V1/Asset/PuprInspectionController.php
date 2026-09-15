<?php

namespace App\Http\Controllers\Api\V1\Asset;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Asset\Concerns\AssetAccess;
use App\Models\AssetRental;
use App\Models\AssetRentalInspection;
use App\Services\PuprOvertimeService;

class PuprInspectionController extends Controller
{
    use AssetAccess;

    public function __construct(
        private PuprOvertimeService $overtimeService,
    ) {}

    public function pre(\Illuminate\Http\Request $request)
    {
        $user = $request->user();
        $this->assertAssetRole($user);

        $validated = $request->validate([
            'rental_id' => 'required|exists:asset_rentals,id',
            'hour_meter_value' => 'required|numeric|min:0',
            'fuel_level' => 'nullable|integer|min:0|max:100',
            'checklist' => 'required|array|size:5',
            'checklist.engine_oil' => 'nullable|string|in:GOOD,ATTENTION',
            'checklist.hydraulic_system' => 'nullable|string|in:GOOD,ATTENTION',
            'checklist.track_tires' => 'nullable|string|in:GOOD,ATTENTION',
            'checklist.brakes_steering' => 'nullable|string|in:GOOD,ATTENTION',
            'checklist.safety_cabin_k3' => 'nullable|string|in:GOOD,ATTENTION',
            'condition_notes' => 'nullable|string',
            'inspector_gps_lat' => 'nullable|numeric|between:-90,90',
            'inspector_gps_lng' => 'nullable|numeric|between:-180,180',
            'inspection_date' => 'nullable|date',
            'inspector_name' => 'nullable|string|max:100',
            'photo_path' => 'nullable|string|max:500',
        ]);

        $rental = $this->resolveInspectableRental($user, (int) $validated['rental_id']);

        $inspection = AssetRentalInspection::create([
            'asset_rental_id' => $rental->id,
            'inspected_by' => $user->id,
            'inspection_type' => 'pre_operation',
            'hour_meter' => $validated['hour_meter_value'],
            'fuel_level_percent' => $validated['fuel_level'] ?? null,
            'checklist' => $validated['checklist'],
            'notes' => $validated['condition_notes'] ?? null,
            'latitude' => $validated['inspector_gps_lat'] ?? null,
            'longitude' => $validated['inspector_gps_lng'] ?? null,
            'photo_path' => $validated['photo_path'] ?? null,
            'inspected_at' => isset($validated['inspection_date']) && $validated['inspection_date']
                ? \Carbon\Carbon::parse($validated['inspection_date'])
                : now(),
        ]);

        if ($rental->status === 'pending_verification') {
            $rental->update(['status' => 'active']);
        }

        return response()->json([
            'message' => 'Inspeksi pra-operasi berhasil disimpan.',
            'data' => [
                'inspection' => $inspection,
                'rental' => $rental->fresh(),
            ],
        ], 201);
    }

    public function post(\Illuminate\Http\Request $request)
    {
        $user = $request->user();
        $this->assertAssetRole($user);

        $validated = $request->validate([
            'rental_id' => 'required|exists:asset_rentals,id',
            'hour_meter_value' => 'required|numeric|min:0',
            'fuel_level' => 'nullable|integer|min:0|max:100',
            'checklist' => 'required|array|size:5',
            'checklist.engine_oil' => 'nullable|string|in:GOOD,ATTENTION',
            'checklist.hydraulic_system' => 'nullable|string|in:GOOD,ATTENTION',
            'checklist.track_tires' => 'nullable|string|in:GOOD,ATTENTION',
            'checklist.brakes_steering' => 'nullable|string|in:GOOD,ATTENTION',
            'checklist.safety_cabin_k3' => 'nullable|string|in:GOOD,ATTENTION',
            'damage_notes' => 'nullable|string',
            'inspector_gps_lat' => 'nullable|numeric|between:-90,90',
            'inspector_gps_lng' => 'nullable|numeric|between:-180,180',
            'inspection_date' => 'nullable|date',
            'inspector_name' => 'nullable|string|max:100',
            'photo_path' => 'nullable|string|max:500',
        ]);

        $rental = $this->resolveInspectableRental($user, (int) $validated['rental_id']);

        $inspection = AssetRentalInspection::create([
            'asset_rental_id' => $rental->id,
            'inspected_by' => $user->id,
            'inspection_type' => 'post_operation',
            'hour_meter' => $validated['hour_meter_value'],
            'fuel_level_percent' => $validated['fuel_level'] ?? null,
            'checklist' => $validated['checklist'],
            'notes' => $validated['damage_notes'] ?? null,
            'latitude' => $validated['inspector_gps_lat'] ?? null,
            'longitude' => $validated['inspector_gps_lng'] ?? null,
            'photo_path' => $validated['photo_path'] ?? null,
            'inspected_at' => isset($validated['inspection_date']) && $validated['inspection_date']
                ? \Carbon\Carbon::parse($validated['inspection_date'])
                : now(),
        ]);

        $overtimeResult = null;

        $pre = $rental->inspections()
            ->where('inspection_type', 'pre_operation')
            ->orderByDesc('inspected_at')
            ->first();

        if ($pre) {
            $overtimeResult = $this->overtimeService->evaluate($rental, (float) $inspection->hour_meter);

            $inspection->update([
                'is_overtime' => $overtimeResult['is_overtime'],
                'overtime_hours' => $overtimeResult['overtime_hours'],
                'overtime_rate' => $overtimeResult['overtime_rate'],
                'overtime_amount' => $overtimeResult['overtime_amount'],
            ]);

            $rental->update(['actual_hours' => $overtimeResult['actual_hours']]);

            if ($overtimeResult['is_overtime'] && $overtimeResult['overtime_amount'] > 0) {
                $dendaBill = $this->overtimeService->createDendaBill(
                    $rental,
                    $overtimeResult,
                    $inspection,
                    $user->id
                );

                $overtimeResult['denda_bill'] = [
                    'id' => $dendaBill->id,
                    'bill_number' => $dendaBill->bill_number,
                    'amount' => $dendaBill->amount,
                    'status' => $dendaBill->status,
                    'due_date' => $dendaBill->due_date,
                ];
            }
        }

        return response()->json([
            'message' => 'Inspeksi pasca-operasi berhasil disimpan.',
            'data' => [
                'inspection' => $inspection,
                'rental' => $rental->fresh(),
                'overtime' => $overtimeResult,
            ],
        ], 201);
    }
}