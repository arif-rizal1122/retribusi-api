<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Parking\Concerns\ParkingAccess;
use App\Models\ParkingSession;
use App\Models\ParkingShift;
use App\Services\ParkingTariffService;
use Illuminate\Http\Request;

class ParkingController extends Controller
{
    use ParkingAccess;

    public function locations(Request $request)
    {
        $user = $request->user();
        $this->ensureParkingRole($user, array_merge(
            self::PARKING_JUKIR_ROLES,
            self::PARKING_INSPECTOR_ROLES
        ));

        $locations = $this->parkingLocationQuery($user)
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar titik parkir Dishub.',
            'data' => $locations,
        ]);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $this->ensureParkingRole($user, self::PARKING_INSPECTOR_ROLES);

        $locationIds = $this->parkingLocationQuery($user)->pluck('id');
        $today = now()->toDateString();

        $sessionsToday = ParkingSession::whereIn('parking_location_id', $locationIds)
            ->where('shift_date', $today)
            ->where('status', 'completed')
            ->get();

        $data = [
            'total_locations' => (int) $locationIds->count(),
            'active_shifts_today' => (int) ParkingShift::whereIn('parking_location_id', $locationIds)
                ->where('shift_date', $today)
                ->where('shift_status', 'open')
                ->count(),
            'sessions_today' => (int) $sessionsToday->count(),
            'revenue_today' => (float) $sessionsToday->sum('amount'),
            'qris_share_today' => (float) $sessionsToday->where('payment_method', 'qris')->sum('amount'),
            'cash_share_today' => (float) $sessionsToday->where('payment_method', 'cash')->sum('amount'),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Statistik wilayah parkir Dishub.',
            'data' => $data,
        ]);
    }

    public function proxyGtRates(Request $request)
    {
        $user = $request->user();
        $this->ensureParkingRole($user, array_merge(
            self::PARKING_JUKIR_ROLES,
            self::PARKING_INSPECTOR_ROLES
        ));

        $data = [];
        foreach (ParkingTariffService::PROXY_GT_RATES as $code => $rate) {
            $data[] = [
                'code' => $code,
                'rate' => $rate,
                'label' => $this->proxyGtLabel($code),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Tarif tambat labuh kapal rakyat per 24 jam (Pasal 91 Perda 1/2024).',
            'data' => $data,
        ]);
    }

    private function proxyGtLabel(string $code): string
    {
        return match ($code) {
            'proxy_gt_1' => 'Gol I (< 5 GT)',
            'proxy_gt_2' => 'Gol II (5-10 GT)',
            'proxy_gt_3' => 'Gol III (11-20 GT)',
            'proxy_gt_4' => 'Gol IV (> 20 GT)',
            default => $code,
        };
    }
}