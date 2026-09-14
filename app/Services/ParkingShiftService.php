<?php

namespace App\Services;

use App\Models\ParkingSession;
use App\Models\ParkingShift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParkingShiftService
{
    /**
     * Shift terbuka jukir untuk tanggal tertentu (default: hari ini).
     */
    public function getActiveShift(User $user, ?string $date = null): ?ParkingShift
    {
        $date = $date ?? now()->toDateString();

        return ParkingShift::where('user_id', $user->id)
            ->where('shift_date', $date)
            ->where('shift_status', 'open')
            ->first();
    }

    public function openShift(User $user, int $locationId): ParkingShift
    {
        if ($this->getActiveShift($user)) {
            throw ValidationException::withMessages([
                'shift' => 'Shift sudah terbuka untuk hari ini. Tutup shift terlebih dahulu.',
            ]);
        }

        return ParkingShift::create([
            'user_id' => $user->id,
            'parking_location_id' => $locationId,
            'shift_date' => now()->toDateString(),
            'shift_status' => 'open',
            'shift_opened_at' => now(),
        ]);
    }

    public function closeShift(User $user): array
    {
        $shift = $this->getActiveShift($user);

        if (!$shift) {
            throw ValidationException::withMessages([
                'shift' => 'Tidak ada shift aktif untuk ditutup.',
            ]);
        }

        DB::beginTransaction();
        try {
            $shift->update(['shift_status' => 'closed', 'shift_closed_at' => now()]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $this->buildState($shift);
    }

    public function buildState(?ParkingShift $shift): array
    {
        if (!$shift) {
            return [
                'wallet' => null,
                'sessions' => [],
                'summary' => [
                    'total_cash' => 0,
                    'total_qris' => 0,
                    'total_revenue' => 0,
                    'total_sessions' => 0,
                    'shift_status' => 'closed',
                    'shift_opened_at' => null,
                ],
            ];
        }

        $sessions = ParkingSession::with('location:id,name,code')
            ->where('shift_id', $shift->id)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalCash = (float) $sessions->where('payment_method', 'cash')->sum('amount');
        $totalQris = (float) $sessions->where('payment_method', 'qris')->sum('amount');
        $totalSessions = $sessions->count();

        $wallet = [
            'id' => $shift->id,
            'user_id' => $shift->user_id,
            'parking_location_id' => $shift->parking_location_id,
            'shift_date' => $shift->shift_date->toDateString(),
            'shift_status' => $shift->shift_status,
            'shift_opened_at' => $shift->shift_opened_at?->toISOString(),
            'shift_closed_at' => $shift->shift_closed_at?->toISOString(),
            'total_cash' => $totalCash,
            'total_qris' => $totalQris,
            'total_sessions' => $totalSessions,
            'settled' => $shift->shift_status === 'closed',
        ];

        return [
            'wallet' => $wallet,
            'sessions' => $sessions,
            'summary' => [
                'total_cash' => $totalCash,
                'total_qris' => $totalQris,
                'total_revenue' => $totalCash + $totalQris,
                'total_sessions' => $totalSessions,
                'shift_status' => $shift->shift_status,
                'shift_opened_at' => $shift->shift_opened_at?->toISOString(),
            ],
        ];
    }
}