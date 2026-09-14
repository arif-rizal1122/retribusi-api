<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Parking\Concerns\ParkingAccess;
use App\Models\ParkingShift;
use App\Services\ParkingShiftService;
use Illuminate\Http\Request;

class ParkingShiftController extends Controller
{
    use ParkingAccess;

    public function __construct(private ParkingShiftService $shiftService)
    {
    }

    public function open(Request $request)
    {
        $user = $request->user();
        $this->ensureParkingRole($user, self::PARKING_JUKIR_ROLES);

        $request->validate(['parking_location_id' => 'required|integer']);

        $location = $this->resolveParkingLocation($user, (int) $request->parking_location_id);

        $shift = $this->shiftService->openShift($user, $location->id);

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil dibuka. Selamat bertugas.',
            'data' => $this->shiftService->buildState($shift->fresh()),
        ], 201);
    }

    public function close(Request $request)
    {
        $user = $request->user();
        $this->ensureParkingRole($user, self::PARKING_JUKIR_ROLES);

        $state = $this->shiftService->closeShift($user);

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil ditutup. Seluruh transaksi tersimpan.',
            'data' => $state,
        ]);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        $this->ensureParkingRole($user, self::PARKING_JUKIR_ROLES);

        $today = now()->toDateString();

        $shift = $this->shiftService->getActiveShift($user, $today)
            ?? ParkingShift::where('user_id', $user->id)
                ->where('shift_date', $today)
                ->latest('id')
                ->first();

        $state = $this->shiftService->buildState($shift);

        return response()->json(array_merge([
            'success' => true,
            'message' => 'Rekap shift jukir.',
        ], $state));
    }
}