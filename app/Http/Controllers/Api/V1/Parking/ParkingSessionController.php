<?php

namespace App\Http\Controllers\Api\V1\Parking;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Parking\Concerns\ParkingAccess;
use App\Models\ParkingDeposit;
use App\Models\ParkingSession;
use App\Services\ParkingReceiptBuilder;
use App\Services\ParkingShiftService;
use App\Services\ParkingTariffService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParkingSessionController extends Controller
{
    use ParkingAccess;

    private const VEHICLE_TYPES = [
        'r2', 'r4', 'truk_bus', 'inap_truk',
        'proxy_gt_1', 'proxy_gt_2', 'proxy_gt_3', 'proxy_gt_4',
    ];

    public function __construct(
        private ParkingShiftService $shiftService,
        private ParkingTariffService $tariffService,
        private ParkingReceiptBuilder $receiptBuilder,
    ) {
    }

    /**
     * Catat sesi QRIS setelah pembayaran QRIS berhasil diverifikasi.
     */
    public function record(Request $request)
    {
        $user = $request->user();
        $this->ensureParkingRole($user, self::PARKING_JUKIR_ROLES);

        $validated = $request->validate([
            'parking_location_id' => 'required|integer',
            'vehicle_type' => 'required|string|in:' . implode(',', self::VEHICLE_TYPES),
            'payment_method' => 'required|string|in:qris',
            'plate_hint' => 'nullable|string|max:30',
            'duration_days' => 'nullable|integer|min:1|max:30',
            'qris_reference' => 'nullable|string|max:40',
        ]);

        $location = $this->resolveParkingLocation($user, (int) $validated['parking_location_id']);
        $shift = $this->requireActiveShiftAtLocation($user, $location);

        $amount = $this->tariffService->amountFor(
            $location,
            $validated['vehicle_type'],
            (int) ($validated['duration_days'] ?? 1)
        );

        $session = ParkingSession::create([
            'parking_location_id' => $location->id,
            'jukir_user_id' => $user->id,
            'shift_id' => $shift->id,
            'shift_date' => $shift->shift_date->toDateString(),
            'vehicle_type' => $validated['vehicle_type'],
            'plate_hint' => $validated['plate_hint'] ?? null,
            'duration_days' => (int) ($validated['duration_days'] ?? 1),
            'amount' => $amount,
            'payment_method' => 'qris',
            'qris_reference' => $validated['qris_reference'] ?? null,
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi QRIS berhasil dicatat.',
            'data' => [
                'session' => $session->load('location:id,name,code'),
            ],
        ], 201);
    }

    /**
     * Transaksi tunai pre-paid (Quadruple-Lock):
     * 1. shift harus terbuka    2. tarif server-side
     * 3. potong 70% RKUD dari deposit    4. sisakan 30% jukir (uang fisik tunai).
     */
    public function prepaidCash(Request $request)
    {
        $user = $request->user();
        $this->ensureParkingRole($user, self::PARKING_JUKIR_ROLES);

        $validated = $request->validate([
            'parking_location_id' => 'required|integer',
            'vehicle_type' => 'required|string|in:' . implode(',', self::VEHICLE_TYPES),
            'duration_days' => 'nullable|integer|min:1|max:30',
            'plate_hint' => 'nullable|string|max:30',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $location = $this->resolveParkingLocation($user, (int) $validated['parking_location_id']);
        $shift = $this->requireActiveShiftAtLocation($user, $location);

        $amount = $this->tariffService->amountFor(
            $location,
            $validated['vehicle_type'],
            (int) ($validated['duration_days'] ?? 1)
        );

        $split = $this->tariffService->split($amount);

        $balance = ParkingDeposit::balanceFor($user->id);

        if ($balance < $split['rkud']) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'code' => 'INSUFFICIENT_DEPOSIT',
                'message' => 'Saldo deposit RKUD tidak cukup. Silakan top-up kuota parkir terlebih dahulu.',
                'deposit_balance' => $balance,
                'required_rkud' => $split['rkud'],
            ], 422);
        }

        [$session, $newBalance] = DB::transaction(function () use (
            $user, $location, $shift, $validated, $amount, $split, $balance
        ) {
            $session = ParkingSession::create([
                'parking_location_id' => $location->id,
                'jukir_user_id' => $user->id,
                'shift_id' => $shift->id,
                'shift_date' => $shift->shift_date->toDateString(),
                'vehicle_type' => $validated['vehicle_type'],
                'plate_hint' => $validated['plate_hint'] ?? null,
                'duration_days' => (int) ($validated['duration_days'] ?? 1),
                'amount' => $amount,
                'payment_method' => 'cash',
                'status' => 'completed',
            ]);

            $newBalance = (float) $balance - $split['rkud'];

            ParkingDeposit::create([
                'user_id' => $user->id,
                'type' => 'deduction',
                'amount' => -$split['rkud'],
                'payment_method' => 'cash_dishub',
                'reference' => 'SESSION:' . $session->id,
                'balance_after' => $newBalance,
                'remark' => 'Potongan 70% RKUD transaksi tunai parkir #' . $session->id,
            ]);

            return [$session, $newBalance];
        });

        $state = $this->shiftService->buildState($shift->fresh());

        return response()->json([
            'status' => 'success',
            'message' => 'Transaksi tunai tercatat. Deposit RKUD dipotong otomatis.',
            'data' => [
                'session' => $session->load('location:id,name,code'),
                'receipt_token' => $this->receiptBuilder->receiptNo($session),
                'tariff_total' => $amount,
                'deposit_deducted_rkud' => $split['rkud'],
                'cash_kept_by_jukir' => $split['jukir'],
                'jukir_net_earnings' => $split['jukir'],
                'remaining_deposit' => $newBalance,
                'thermal_print_payload' => $this->receiptBuilder->build($session),
            ],
            'shift_summary' => [
                'total_cash' => $state['summary']['total_cash'],
                'total_qris' => $state['summary']['total_qris'],
                'total_sessions' => $state['summary']['total_sessions'],
            ],
        ]);
    }
}