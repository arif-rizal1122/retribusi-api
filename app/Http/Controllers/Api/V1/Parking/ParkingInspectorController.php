<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Parking\Concerns\ParkingAccess;
use App\Models\ParkingSanction;
use App\Models\ParkingSession;
use App\Models\ParkingShift;
use App\Models\User;
use Illuminate\Http\Request;

class ParkingInspectorController extends Controller
{
    use ParkingAccess;

    /**
     * Sidak lapangan: bandingkan jumlah kendaraan fisik vs sesi digital hari ini.
     */
    public function spotCheck(Request $request)
    {
        $user = $request->user();
        $this->ensureParkingRole($user, self::PARKING_INSPECTOR_ROLES);

        $validated = $request->validate([
            'parking_location_id' => 'required|integer',
            'physical_r2' => 'nullable|integer|min:0',
            'physical_r4' => 'nullable|integer|min:0',
        ]);

        $location = $this->resolveParkingLocation($user, (int) $validated['parking_location_id']);
        $today = now()->toDateString();

        $shift = ParkingShift::where('parking_location_id', $location->id)
            ->where('shift_date', $today)
            ->where('shift_status', 'open')
            ->with('user:id,name')
            ->first();

        $sessionsToday = ParkingSession::where('parking_location_id', $location->id)
            ->where('shift_date', $today)
            ->where('status', 'completed')
            ->get();

        $digitalR2 = (int) $sessionsToday->where('vehicle_type', 'r2')->count();
        $digitalR4 = (int) $sessionsToday->where('vehicle_type', 'r4')->count();
        $digitalTotal = $digitalR2 + $digitalR4;

        $physicalR2 = (int) ($validated['physical_r2'] ?? 0);
        $physicalR4 = (int) ($validated['physical_r4'] ?? 0);
        $physicalTotal = $physicalR2 + $physicalR4;

        $diff = max(0, $physicalTotal - $digitalTotal);
        $percent = $physicalTotal > 0 ? (int) round(($diff / $physicalTotal) * 100) : 0;

        $riskLevel = $diff === 0 ? 'normal' : ($percent >= 20 ? 'critical' : 'warning');

        $recommendation = match ($riskLevel) {
            'normal' => 'Aman: selisih fisik vs digital tidak signifikan.',
            'warning' => 'Waspada: ada selisih catatan. Sarankan teguran lisan dan pengamatan ulang.',
            'critical' => 'Kritis: indikasi kebocoran tunai. Berikan SP1 dan laporkan ke Kasubbid Pengawasan Dishub.',
        };

        return response()->json([
            'success' => true,
            'message' => 'Hasil sidak lapangan parkir Dishub.',
            'data' => [
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'code' => $location->code,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                ],
                'active_jukir' => $shift && $shift->user ? [
                    'id' => $shift->user->id,
                    'name' => $shift->user->name,
                    'shift_opened_at' => $shift->shift_opened_at?->toISOString(),
                ] : null,
                'digital_active_count' => [
                    'r2' => $digitalR2,
                    'r4' => $digitalR4,
                    'total' => $digitalTotal,
                ],
                'physical_observed_count' => [
                    'r2' => $physicalR2,
                    'r4' => $physicalR4,
                    'total' => $physicalTotal,
                ],
                'audit_result' => [
                    'discrepancy_units' => $diff,
                    'discrepancy_percent' => $percent,
                    'risk_level' => $riskLevel,
                    'recommendation' => $recommendation,
                ],
            ],
        ]);
    }

    /**
     * Catat sanksi SP1/SP2/SP3. SP3 membekukan Surat Tugas (status user = suspended).
     */
    public function sanction(Request $request)
    {
        $user = $request->user();
        $this->ensureParkingRole($user, self::PARKING_INSPECTOR_ROLES);

        $validated = $request->validate([
            'jukir_user_id' => 'required|integer|exists:users,id',
            'sanction_type' => 'required|string|in:sp1_warning,sp2_freeze_7days,sp3_revoke_st',
            'reason' => 'nullable|string|max:500',
        ]);

        $jukir = User::findOrFail((int) $validated['jukir_user_id']);

        if ($jukir->role !== 'petugas' || !$this->isDishubScoped($jukir)) {
            return response()->json([
                'success' => false,
                'message' => 'Sasaran sanksi harus petugas parkir Dishub.',
            ], 422);
        }

        $sanction = ParkingSanction::create([
            'jukir_user_id' => $jukir->id,
            'inspector_user_id' => $user->id,
            'sanction_type' => $validated['sanction_type'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'applied',
        ]);

        if ($validated['sanction_type'] === 'sp3_revoke_st') {
            $jukir->update(['status' => 'suspended']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sanksi berhasil dicatat' . ($validated['sanction_type'] === 'sp3_revoke_st' ? ' dan Surat Tugas dibekukan.' : '.'),
            'data' => [
                'sanction' => $sanction->load(['jukir:id,name', 'inspector:id,name']),
                'jukir_status' => $jukir->fresh()->status,
            ],
        ], 201);
    }
}