<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\JukirWallet;
use App\Models\ParkingLocation;
use App\Models\ParkingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ParkingController extends Controller
{
    /**
     * GET /api/parking/locations
     * Daftar titik parkir aktif (filter by opd_id jika user terikat OPD).
     */
    public function locations(Request $request): JsonResponse
    {
        $query = ParkingLocation::active()->with('opd:id,name', 'classification:id,name,code');

        // Scope ke OPD user jika bukan super_admin
        $user = $request->user();
        if ($user->role !== 'super_admin' && $user->opd_id) {
            $query->forOpd($user->opd_id);
        }

        $locations = $query->orderBy('name')->get();

        return response()->json(['data' => $locations]);
    }

    /**
     * POST /api/parking/sessions
     * Catat 1 sesi parkir (R2/R4 + metode bayar).
     * Auto-generate bills record agar SIPD/BPK konsisten.
     */
    public function recordSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parking_location_id' => 'required|exists:parking_locations,id',
            'vehicle_type' => 'required|in:r2,r4',
            'payment_method' => 'required|in:cash,qris',
            'plate_hint' => 'nullable|string|max:10',
            'qris_reference' => 'nullable|string|max:20',
        ]);

        $user = $request->user();
        $location = ParkingLocation::findOrFail($validated['parking_location_id']);
        $amount = $location->rateFor($validated['vehicle_type']);
        $today = now()->toDateString();

        // Pastikan shift sudah terbuka
        $wallet = JukirWallet::forUser($user->id)->whereDate('shift_date', $today)->first();
        if (!$wallet || $wallet->shift_status !== 'open') {
            return response()->json([
                'message' => 'Anda belum membuka shift hari ini. Silakan buka shift terlebih dahulu.',
            ], 422);
        }

        $taxpayer = \App\Models\Taxpayer::withoutGlobalScopes()->firstOrCreate(
            [
                'opd_id' => $location->opd_id,
                'nik' => '7472000000000001',
            ],
            [
                'name' => 'Wajib Retribusi Parkir Umum',
                'address' => 'Kota Baubau',
                'is_active' => true,
            ]
        );

        $retributionTypeId = $location->classification?->retribution_type_id
            ?? $location->classification()->value('retribution_type_id')
            ?? \App\Models\RetributionType::where('opd_id', $location->opd_id)->value('id');

        $session = DB::transaction(function () use ($validated, $user, $location, $amount, $today, $wallet, $taxpayer, $retributionTypeId) {
            // 1. Auto-generate Bill record (agar laporan keuangan terpusat konsisten)
            $bill = Bill::create([
                'user_id' => $user->id,
                'taxpayer_id' => $taxpayer->id,
                'opd_id' => $location->opd_id,
                'retribution_type_id' => $retributionTypeId,
                'retribution_classification_id' => $location->classification_id,
                'bill_number' => 'PKR-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'amount' => $amount,
                'status' => 'paid', // Langsung lunas (retribusi langsung)
                'period' => now()->format('Y-m'),
                'period_start' => $today,
                'period_end' => $today,
                'due_date' => now(),
                'metadata' => [
                    'source' => 'parking_quick_cashier',
                    'vehicle_type' => $validated['vehicle_type'],
                    'payment_method' => $validated['payment_method'],
                    'plate_hint' => $validated['plate_hint'] ?? null,
                    'parking_location' => $location->name,
                ],
            ]);

            // 2. Buat parking session record
            $session = ParkingSession::create([
                'parking_location_id' => $location->id,
                'jukir_user_id' => $user->id,
                'shift_date' => $today,
                'vehicle_type' => $validated['vehicle_type'],
                'plate_hint' => $validated['plate_hint'] ?? null,
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'qris_reference' => $validated['qris_reference'] ?? null,
                'bill_id' => $bill->id,
                'status' => 'completed',
            ]);

            // 3. Update wallet totals
            $wallet->addSession($amount, $validated['payment_method']);

            return $session;
        });

        return response()->json([
            'message' => 'Transaksi parkir berhasil dicatat.',
            'data' => $session->load('parkingLocation:id,name,code'),
            'shift_summary' => [
                'total_cash' => $wallet->fresh()->total_cash,
                'total_qris' => $wallet->fresh()->total_qris,
                'total_sessions' => $wallet->fresh()->total_sessions,
            ],
        ], 201);
    }

    /**
     * GET /api/parking/shift-summary
     * Rekap shift jukir hari ini.
     */
    public function shiftSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();

        $wallet = JukirWallet::forUser($user->id)->whereDate('shift_date', $today)->first();

        if (!$wallet) {
            return response()->json([
                'data' => null,
                'message' => 'Belum ada shift hari ini.',
            ]);
        }

        $sessions = ParkingSession::forJukir($user->id)
            ->whereDate('shift_date', $today)
            ->completed()
            ->with('parkingLocation:id,name,code')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => [
                'wallet' => $wallet,
                'sessions' => $sessions,
                'summary' => [
                    'total_cash' => $wallet->total_cash,
                    'total_qris' => $wallet->total_qris,
                    'total_revenue' => $wallet->total_revenue,
                    'total_sessions' => $wallet->total_sessions,
                    'shift_status' => $wallet->shift_status,
                    'shift_opened_at' => $wallet->shift_opened_at,
                ],
            ],
        ]);
    }

    /**
     * POST /api/parking/shift/open
     * Buka shift baru (1 shift per hari per jukir).
     */
    public function openShift(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parking_location_id' => 'required|exists:parking_locations,id',
        ]);

        $user = $request->user();
        $today = now()->toDateString();

        // Cek apakah sudah ada shift hari ini
        $existing = JukirWallet::forUser($user->id)->whereDate('shift_date', $today)->first();
        if ($existing) {
            if ($existing->shift_status === 'open') {
                return response()->json([
                    'message' => 'Shift sudah terbuka.',
                    'data' => $existing,
                ]);
            }
            return response()->json([
                'message' => 'Shift hari ini sudah ditutup. Tidak bisa membuka shift baru di hari yang sama.',
            ], 422);
        }

        $wallet = JukirWallet::create([
            'user_id' => $user->id,
            'parking_location_id' => $validated['parking_location_id'],
            'shift_date' => $today,
            'shift_status' => 'open',
            'shift_opened_at' => now(),
            'total_cash' => 0,
            'total_qris' => 0,
            'total_sessions' => 0,
        ]);

        return response()->json([
            'message' => 'Shift berhasil dibuka.',
            'data' => $wallet->load('parkingLocation:id,name,code'),
        ], 201);
    }

    /**
     * POST /api/parking/shift/close
     * Tutup shift (kunci saldo).
     */
    public function closeShift(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();

        $wallet = JukirWallet::forUser($user->id)->whereDate('shift_date', $today)->open()->first();
        if (!$wallet) {
            return response()->json([
                'message' => 'Tidak ada shift terbuka untuk ditutup.',
            ], 422);
        }

        $wallet->closeShift();

        return response()->json([
            'message' => 'Shift berhasil ditutup.',
            'data' => [
                'total_cash' => $wallet->total_cash,
                'total_qris' => $wallet->total_qris,
                'total_revenue' => $wallet->total_revenue,
                'total_sessions' => $wallet->total_sessions,
                'shift_opened_at' => $wallet->shift_opened_at,
                'shift_closed_at' => $wallet->shift_closed_at,
            ],
        ]);
    }

    /**
     * GET /api/parking/dashboard
     * Agregat semua jukir aktif + titik (untuk admin Dishub / super_admin).
     */
    public function dashboard(Request $request): JsonResponse
    {
        $today = now()->toDateString();

        // Semua shift hari ini
        $wallets = JukirWallet::whereDate('shift_date', $today)
            ->with(['user:id,name,email', 'parkingLocation:id,name,code'])
            ->get();

        // Agregat
        $totalCash = $wallets->sum('total_cash');
        $totalQris = $wallets->sum('total_qris');
        $totalSessions = $wallets->sum('total_sessions');
        $activeShifts = $wallets->where('shift_status', 'open')->count();

        // Titik parkir aktif
        $locations = ParkingLocation::active()
            ->withCount(['sessions' => fn($q) => $q->whereDate('shift_date', $today)->completed()])
            ->get();

        return response()->json([
            'data' => [
                'date' => $today,
                'active_shifts' => $activeShifts,
                'total_jukir' => $wallets->count(),
                'total_cash' => $totalCash,
                'total_qris' => $totalQris,
                'total_revenue' => $totalCash + $totalQris,
                'total_sessions' => $totalSessions,
                'wallets' => $wallets,
                'locations' => $locations,
            ],
        ]);
    }
}
