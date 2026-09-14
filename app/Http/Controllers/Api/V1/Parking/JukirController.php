<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Parking\Concerns\ParkingAccess;
use App\Models\ParkingDeposit;
use App\Services\ParkingShiftService;
use App\Services\ParkingTariffService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JukirController extends Controller
{
    use ParkingAccess;

    public function __construct(private ParkingShiftService $shiftService)
    {
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $this->ensureParkingRole($user, self::PARKING_JUKIR_ROLES);

        $suratExpired = $user->surat_tugas_expired_at;
        $isExpired = $suratExpired ? $suratExpired->isPast() : false;
        $daysRemaining = $suratExpired
            ? (int) max(0, ceil(abs((float) $suratExpired->diffInSeconds(now())) / 86400))
            : null;

        if ($user->status === 'suspended') {
            $status = 'suspended';
        } elseif ($isExpired) {
            $status = 'expired';
        } else {
            $status = 'active';
        }

        $shift = $this->shiftService->getActiveShift($user);
        $assignedLocation = $shift?->location
            ?? $this->parkingLocationQuery($user)->active()->orderBy('name')->first();

        return response()->json([
            'success' => true,
            'message' => 'Profil jukir parkir Dishub.',
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'nik' => (string) ($user->nik ?? ''),
                'surat_tugas_no' => $user->surat_tugas_no,
                'surat_tugas_expired_at' => $suratExpired?->toISOString(),
                'days_remaining' => $daysRemaining,
                'is_expired' => $isExpired,
                'status' => $status,
                'deposit_balance' => (float) ParkingDeposit::balanceFor($user->id),
                'split_rkud_percent' => ParkingTariffService::SPLIT_RKUD_PERCENT,
                'split_jukir_percent' => ParkingTariffService::SPLIT_JUKIR_PERCENT,
                'assigned_location' => $assignedLocation,
                'shift_status' => $shift?->shift_status ?? 'closed',
            ],
        ]);
    }

    public function topup(Request $request)
    {
        $user = $request->user();
        $this->ensureParkingRole($user, self::PARKING_JUKIR_ROLES);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10000|max:1000000',
            'payment_method' => 'required|string|in:qris,bank_sultra,cash_dishub',
        ]);

        $amount = (float) $validated['amount'];
        $balance = ParkingDeposit::balanceFor($user->id);

        $newBalance = DB::transaction(function () use ($user, $amount, $balance, $validated) {
            return ParkingDeposit::create([
                'user_id' => $user->id,
                'type' => 'topup',
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference' => null,
                'balance_after' => (float) $balance + $amount,
                'remark' => 'Top-up deposit parkir (' . $validated['payment_method'] . ')',
            ])->balance_after;
        });

        return response()->json([
            'success' => true,
            'message' => 'Top-up deposit berhasil.',
            'data' => [
                'topup_amount' => $amount,
                'balance_after' => (float) $newBalance,
            ],
        ]);
    }
}