<?php

namespace App\Http\Controllers\Api\V1\Disperindag;

use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\MarketStall;
use App\Models\MarketOfficerAssignment;
use App\Models\Zone;
use App\Models\TaxObject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\MarketDailyTicket;

class MarketController extends Controller
{
    /**
     * Ringkasan eksekutif tata kelola pasar Disperindag (Dashboard Metrics & KPI).
     */
    public function summary(Request $request): JsonResponse
    {
        $marketId = $request->query('market_id');

        $stallQuery = MarketStall::query();
        if ($marketId) {
            $stallQuery->where('market_id', $marketId);
        }

        $totalStalls = (clone $stallQuery)->count();
        $totalKios = (clone $stallQuery)->where('stall_type', 'kios')->count();
        $totalLos = (clone $stallQuery)->where('stall_type', 'los')->count();
        $totalActive = (clone $stallQuery)->where('status', 'aktif')->count();
        $totalSealed = (clone $stallQuery)->where('status', 'tersegel')->count();
        $totalVacant = (clone $stallQuery)->where('status', 'kosong')->count();

        // Potensi Retribusi
        $monthlyLosPotential = (clone $stallQuery)
            ->where('stall_type', 'los')
            ->where('status', '!=', 'kosong')
            ->sum('tariff_amount');

        $yearlyKiosPotential = (clone $stallQuery)
            ->where('stall_type', 'kios')
            ->where('status', '!=', 'kosong')
            ->sum('tariff_amount');

        // Breakdown per pasar
        $marketsSummary = Market::withCount([
            'stalls as total_stalls',
            'stalls as total_kios' => fn($q) => $q->where('stall_type', 'kios'),
            'stalls as total_los' => fn($q) => $q->where('stall_type', 'los'),
            'stalls as active_stalls' => fn($q) => $q->where('status', 'aktif'),
            'stalls as sealed_stalls' => fn($q) => $q->where('status', 'tersegel'),
            'stalls as vacant_stalls' => fn($q) => $q->where('status', 'kosong'),
        ])->get();

        // Breakdown per gedung / blok
        $buildingsSummary = MarketStall::select(
                'market_id',
                'market_name',
                'building_name',
                DB::raw('COUNT(*) as total_units'),
                DB::raw("SUM(CASE WHEN stall_type = 'kios' THEN 1 ELSE 0 END) as kios_count"),
                DB::raw("SUM(CASE WHEN stall_type = 'los' THEN 1 ELSE 0 END) as los_count"),
                DB::raw("SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as active_count"),
                DB::raw("SUM(CASE WHEN status = 'tersegel' THEN 1 ELSE 0 END) as sealed_count"),
                DB::raw("SUM(CASE WHEN status = 'kosong' THEN 1 ELSE 0 END) as vacant_count"),
                DB::raw("SUM(tariff_amount) as total_tariff_nominal"),
                DB::raw("MAX(tariff_amount) as sample_tariff"),
                DB::raw("MAX(billing_cycle) as sample_cycle"),
                DB::raw("MAX(stall_type) as sample_type")
            )
            ->when($marketId, fn($q) => $q->where('market_id', $marketId))
            ->groupBy('market_id', 'market_name', 'building_name')
            ->orderBy('building_name')
            ->get();

        // Realisasi Penagihan & Keuangan (SKRD & Pembayaran)
        $billQuery = \App\Models\Bill::where('opd_id', 3);
        if ($marketId) {
            $stallObjectIds = (clone $stallQuery)->pluck('tax_object_id')->filter();
            $billQuery->whereIn('tax_object_id', $stallObjectIds);
        }

        $totalBilled = (clone $billQuery)->sum('amount');
        $totalCollected = (clone $billQuery)->where('status', 'lunas')->sum('amount');
        $totalArrears = (clone $billQuery)->where('status', '!=', 'lunas')->sum('amount');
        $collectionRate = $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 1) : 0;

        $totalOfficers = MarketOfficerAssignment::count();

        return response()->json([
            'success' => true,
            'data' => [
                'kpi' => [
                    'total_stalls' => $totalStalls,
                    'total_kios' => $totalKios,
                    'total_los' => $totalLos,
                    'active_stalls' => $totalActive,
                    'sealed_stalls' => $totalSealed,
                    'vacant_stalls' => $totalVacant,
                    'occupancy_rate' => $totalStalls > 0 ? round(($totalActive / $totalStalls) * 100, 1) : 0,
                    'monthly_los_potential' => (float) $monthlyLosPotential,
                    'yearly_kios_potential' => (float) $yearlyKiosPotential,
                    'total_billed' => (float) $totalBilled,
                    'total_collected' => (float) $totalCollected,
                    'total_arrears' => (float) $totalArrears,
                    'collection_rate' => $collectionRate,
                    'officer_teams_count' => $totalOfficers,
                ],
                'markets' => $marketsSummary,
                'buildings' => $buildingsSummary,
            ]
        ]);
    }

    /**
     * Daftar pasar definitif Disperindag.
     */
    public function markets(): JsonResponse
    {
        $markets = Market::withCount(['stalls'])->get();

        return response()->json([
            'success' => true,
            'data' => $markets,
        ]);
    }

    /**
     * Daftar zona/gedung pasar.
     */
    public function zones(Request $request): JsonResponse
    {
        $marketId = $request->query('market_id');
        $opdId = 3; // Disperindag

        $query = Zone::where('opd_id', $opdId);

        $zones = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }

    /**
     * Daftar lapak kios & los pasar dengan fitur filter, pencarian, dan paginasi.
     */
    public function stalls(Request $request): JsonResponse
    {
        $query = MarketStall::with(['market', 'zone', 'taxpayer']);

        if ($request->filled('market_id')) {
            $query->where('market_id', $request->query('market_id'));
        }

        if ($request->filled('building_name')) {
            $query->where('building_name', $request->query('building_name'));
        }

        if ($request->filled('stall_type')) {
            $query->where('stall_type', $request->query('stall_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('q')) {
            $search = trim($request->query('q'));
            $query->where(function ($q) use ($search) {
                $q->where('merchant_name', 'like', "%{$search}%")
                  ->orWhere('stall_number', 'like', "%{$search}%")
                  ->orWhere('full_code', 'like', "%{$search}%")
                  ->orWhere('building_name', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('all') || $request->query('per_page') == -1) {
            $stalls = $query->orderBy('building_name')
                ->orderByRaw('CAST(stall_number AS UNSIGNED) ASC')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $stalls,
                'meta' => [
                    'total' => $stalls->count(),
                ]
            ]);
        }

        $perPage = min((int) ($request->query('per_page', 50)), 200);
        $stalls = $query->orderBy('building_name')
            ->orderByRaw('CAST(stall_number AS UNSIGNED) ASC')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $stalls->items(),
            'meta' => [
                'current_page' => $stalls->currentPage(),
                'last_page' => $stalls->lastPage(),
                'per_page' => $stalls->perPage(),
                'total' => $stalls->total(),
            ]
        ]);
    }

    /**
     * Daftar penugasan tim petugas penagihan retribusi per gedung.
     */
    public function officers(): JsonResponse
    {
        $officers = MarketOfficerAssignment::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data' => $officers,
        ]);
    }

    /**
     * Update status dan data lengkap lapak (termasuk tarif, siklus, pedagang, gedung).
     */
    public function updateStall(Request $request, int $id): JsonResponse
    {
        $stall = MarketStall::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:aktif,tersegel,kosong',
            'merchant_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'tariff_amount' => 'nullable|numeric|min:0',
            'billing_cycle' => 'nullable|in:daily,weekly,monthly,yearly',
            'building_name' => 'nullable|string|max:255',
            'stall_type' => 'nullable|in:kios,los,pelataran',
            'stall_number' => 'nullable|string|max:50',
        ]);

        $stall->update($validated);

        // Sinkronisasi ke TaxObject jika terkait
        if ($stall->tax_object_id) {
            $taxObject = TaxObject::find($stall->tax_object_id);
            if ($taxObject) {
                $updates = [];
                if (isset($validated['status'])) {
                    $updates['status'] = match($validated['status']) {
                        'tersegel' => 'suspended',
                        'kosong' => 'inactive',
                        default => 'active',
                    };
                    $updates['status_hunian'] = ($validated['status'] === 'kosong') ? 'KOSONG' : 'BERPENGHUNI';
                }
                if (array_key_exists('merchant_name', $validated)) {
                    $updates['penanggung_jawab_nama'] = $validated['merchant_name'];
                }
                if (!empty($updates)) {
                    $taxObject->update($updates);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Data lapak berhasil diperbarui',
            'data' => $stall->fresh(['market', 'zone', 'taxpayer']),
        ]);
    }

    /**
     * Alias untuk backward-compatibility dengan endpoint status lama.
     */
    public function updateStallStatus(Request $request, int $id): JsonResponse
    {
        return $this->updateStall($request, $id);
    }

    /**
     * Update nominal retribusi dan siklus penagihan untuk seluruh lapak di satu gedung.
     */
    public function updateBuildingTariff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'market_name' => 'required|string',
            'building_name' => 'required|string',
            'tariff_amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:daily,weekly,monthly,yearly',
            'new_building_name' => 'nullable|string|max:255',
        ]);

        $query = MarketStall::where('market_name', $validated['market_name'])
            ->where('building_name', $validated['building_name']);

        $count = $query->count();
        if ($count === 0) {
            return response()->json([
                'success' => false,
                'message' => "Gedung {$validated['building_name']} pada {$validated['market_name']} tidak ditemukan.",
            ], 404);
        }

        $updateData = [
            'tariff_amount' => $validated['tariff_amount'],
            'billing_cycle' => $validated['billing_cycle'],
        ];

        if (!empty($validated['new_building_name']) && $validated['new_building_name'] !== $validated['building_name']) {
            $updateData['building_name'] = $validated['new_building_name'];
        }

        $query->update($updateData);

        return response()->json([
            'success' => true,
            'message' => "Berhasil memperbarui tarif {$count} unit lapak di gedung {$validated['building_name']}.",
            'data' => [
                'market_name' => $validated['market_name'],
                'building_name' => $updateData['building_name'] ?? $validated['building_name'],
                'tariff_amount' => $validated['tariff_amount'],
                'billing_cycle' => $validated['billing_cycle'],
                'updated_count' => $count,
            ],
        ]);
    }

    /**
     * Tambah lapak / unit pasar baru.
     */
    public function createStall(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'market_name' => 'required|string',
            'building_name' => 'required|string|max:255',
            'stall_type' => 'required|in:kios,los,pelataran',
            'stall_number' => 'required|string|max:50',
            'tariff_amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:daily,weekly,monthly,yearly',
            'merchant_name' => 'nullable|string|max:255',
            'status' => 'nullable|in:aktif,tersegel,kosong',
            'notes' => 'nullable|string',
        ]);

        $market = Market::where('name', $validated['market_name'])->first();
        $marketId = $market ? $market->id : 1;

        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['market_name']), 0, 3));
        $typePrefix = strtoupper(substr($validated['stall_type'], 0, 3));
        $buildingClean = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['building_name']), 0, 6));
        $numPad = str_pad($validated['stall_number'], 3, '0', STR_PAD_LEFT);
        $fullCode = "{$prefix}-{$typePrefix}-{$buildingClean}-{$numPad}";

        // Cegah duplikasi kode unik
        $existing = MarketStall::where('full_code', $fullCode)->first();
        if ($existing) {
            $fullCode .= '-' . rand(10, 99);
        }

        $stall = MarketStall::create([
            'market_id' => $marketId,
            'market_name' => $validated['market_name'],
            'building_name' => $validated['building_name'],
            'stall_type' => $validated['stall_type'],
            'stall_number' => $validated['stall_number'],
            'full_code' => $fullCode,
            'merchant_name' => $validated['merchant_name'] ?? null,
            'tariff_amount' => $validated['tariff_amount'],
            'billing_cycle' => $validated['billing_cycle'],
            'status' => $validated['status'] ?? 'aktif',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lapak baru berhasil ditambahkan',
            'data' => $stall,
        ], 201);
    }

    /**
     * Daftar seluruh gedung/blok pasar lengkap dengan ringkasan unit & tarif.
     * Dapat diakses oleh admin Disperindag maupun warga pemohon.
     */
    public function buildings(Request $request): JsonResponse
    {
        $query = MarketStall::select(
                'market_id',
                'market_name',
                'building_name',
                DB::raw('COUNT(*) as total_units'),
                DB::raw("SUM(CASE WHEN stall_type = 'kios' THEN 1 ELSE 0 END) as kios_count"),
                DB::raw("SUM(CASE WHEN stall_type = 'los' THEN 1 ELSE 0 END) as los_count"),
                DB::raw("SUM(CASE WHEN stall_type = 'pelataran' THEN 1 ELSE 0 END) as pelataran_count"),
                DB::raw("SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as active_count"),
                DB::raw("SUM(CASE WHEN status = 'tersegel' THEN 1 ELSE 0 END) as sealed_count"),
                DB::raw("SUM(CASE WHEN status = 'kosong' THEN 1 ELSE 0 END) as vacant_count"),
                DB::raw("MAX(tariff_amount) as tariff_amount"),
                DB::raw("MAX(billing_cycle) as billing_cycle"),
                DB::raw("MAX(stall_type) as stall_type")
            )
            ->groupBy('market_id', 'market_name', 'building_name')
            ->orderBy('market_name')
            ->orderBy('building_name');

        if ($request->filled('market_id')) {
            $query->where('market_id', $request->query('market_id'));
        }

        if ($request->filled('market_name')) {
            $query->where('market_name', 'like', '%' . $request->query('market_name') . '%');
        }

        $buildings = $query->get();

        return response()->json([
            'success' => true,
            'data' => $buildings,
        ]);
    }

    /**
     * Admin input daftar gedung / blok pasar baru.
     */
    public function createBuilding(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'market_name' => 'required|string',
            'building_name' => 'required|string|max:255',
            'stall_type' => 'required|in:kios,los,pelataran',
            'tariff_amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:daily,weekly,monthly,yearly',
            'initial_units' => 'nullable|integer|min:1|max:200',
        ]);

        $market = Market::where('name', $validated['market_name'])->first();
        $marketId = $market ? $market->id : 1;

        $existing = MarketStall::where('market_name', $validated['market_name'])
            ->where('building_name', $validated['building_name'])
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "Gedung '{$validated['building_name']}' pada {$validated['market_name']} sudah terdaftar.",
            ], 422);
        }

        $unitsCount = (int) ($validated['initial_units'] ?? 1);

        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['market_name']), 0, 3));
        $typePrefix = strtoupper(substr($validated['stall_type'], 0, 3));
        $buildingClean = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['building_name']), 0, 6));

        for ($i = 1; $i <= $unitsCount; $i++) {
            $numPad = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $fullCode = "{$prefix}-{$typePrefix}-{$buildingClean}-{$numPad}";

            MarketStall::create([
                'market_id' => $marketId,
                'market_name' => $validated['market_name'],
                'building_name' => $validated['building_name'],
                'stall_type' => $validated['stall_type'],
                'stall_number' => (string) $i,
                'full_code' => $fullCode,
                'merchant_name' => null,
                'tariff_amount' => $validated['tariff_amount'],
                'billing_cycle' => $validated['billing_cycle'],
                'status' => 'kosong',
                'notes' => 'Unit baru siap ditempati',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil mendaftarkan gedung {$validated['building_name']} dengan {$unitsCount} unit lapak.",
            'data' => [
                'market_name' => $validated['market_name'],
                'building_name' => $validated['building_name'],
                'stall_type' => $validated['stall_type'],
                'tariff_amount' => $validated['tariff_amount'],
                'billing_cycle' => $validated['billing_cycle'],
                'total_units' => $unitsCount,
            ],
        ], 201);
    }

    /**
     * Hapus gedung pasar jika kosong/diarsipkan.
     */
    public function deleteBuilding(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'market_name' => 'required|string',
            'building_name' => 'required|string',
        ]);

        $stalls = MarketStall::where('market_name', $validated['market_name'])
            ->where('building_name', $validated['building_name']);

        $count = $stalls->count();
        if ($count === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Gedung tidak ditemukan.',
            ], 404);
        }

        // Cek apakah ada yang aktif terisi
        $activeCount = (clone $stalls)->where('status', 'aktif')->count();
        if ($activeCount > 0 && !$request->boolean('force')) {
            return response()->json([
                'success' => false,
                'message' => "Gedung memiliki {$activeCount} lapak aktif/terisi. Harap kosongkan terlebih dahulu atau gunakan konfirmasi paksa.",
            ], 422);
        }

        $stalls->delete();

        return response()->json([
            'success' => true,
            'message' => "Gedung {$validated['building_name']} beserta {$count} unit lapak berhasil dihapus.",
        ]);
    }

    /**
     * Penerbitan karcis harian PKL/pelataran pasar secara instan oleh petugas lapangan.
     */
    public function issueDailyTicket(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'market_name' => 'required|string',
            'building_name' => 'nullable|string',
            'merchant_name' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer|min:1|max:50',
            'unit_amount' => 'nullable|numeric|min:500',
            'payment_method' => 'nullable|string|in:TUNAI,QRIS',
        ]);

        $qty = $validated['quantity'] ?? 1;
        $unitAmount = $validated['unit_amount'] ?? 1000;
        $totalAmount = $qty * $unitAmount;
        $user = $request->user();

        $ticketCode = 'TKT-PSR-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        $qrToken = 'QR-TKT-' . Str::uuid();

        $ticket = MarketDailyTicket::create([
            'ticket_code' => $ticketCode,
            'collector_user_id' => $user?->id,
            'collector_name' => $user?->name ?? 'Petugas Pasar Disperindag',
            'market_name' => $validated['market_name'],
            'building_name' => $validated['building_name'] ?? 'Pelataran PKL',
            'merchant_name' => $validated['merchant_name'] ?? 'Pedagang Harian',
            'quantity' => $qty,
            'unit_amount' => $unitAmount,
            'total_amount' => $totalAmount,
            'payment_method' => $validated['payment_method'] ?? 'TUNAI',
            'qr_token' => $qrToken,
            'issued_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Karcis berhasil diterbitkan: {$ticketCode}",
            'data' => $ticket,
        ]);
    }

    /**
     * Terima setoran / pembayaran langsung di tempat untuk lapak kios/los oleh petugas lapangan.
     */
    public function payStall(Request $request, int $id): JsonResponse
    {
        $stall = MarketStall::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'payment_method' => 'nullable|string|in:TUNAI,QRIS,TRANSFER',
            'period' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $ntpd = 'NTPD-PSR-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        // Update status stall menjadi aktif
        $stall->update([
            'status' => 'aktif',
            'notes' => $validated['notes'] ?? $stall->notes,
        ]);

        $receiptData = [
            'stall_id' => $stall->id,
            'full_code' => $stall->full_code,
            'stall_number' => $stall->stall_number,
            'stall_type' => $stall->stall_type,
            'market_name' => $stall->market_name,
            'building_name' => $stall->building_name,
            'merchant_name' => $stall->merchant_name,
            'amount' => (float) $validated['amount'],
            'payment_method' => $validated['payment_method'] ?? 'TUNAI',
            'period' => $validated['period'] ?? ($stall->billing_cycle === 'yearly' ? date('Y') : date('F Y')),
            'ntpd' => $ntpd,
            'officer_name' => $user?->name ?? 'Petugas Penagih Pasar',
            'paid_at' => now()->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'message' => "Pembayaran lapak {$stall->full_code} berhasil dicatat.",
            'data' => $receiptData,
        ]);
    }

    /**
     * Ringkasan harian penarikan retribusi petugas pasar yang login.
     */
    public function officerStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();

        $ticketsQuery = MarketDailyTicket::whereDate('created_at', $today);
        if ($user && $user->role !== 'super_admin') {
            $ticketsQuery->where('collector_user_id', $user->id);
        }

        $todayTicketsCount = (clone $ticketsQuery)->sum('quantity') ?: 0;
        $todayTicketsAmount = (clone $ticketsQuery)->sum('total_amount') ?: 0;

        // Cari penugasan wilayah petugas
        $assignment = null;
        if ($user) {
            $assignment = MarketOfficerAssignment::where('user_id', $user->id)
                ->orWhere('officer_name', 'like', '%' . $user->name . '%')
                ->first();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'today_tickets_count' => (int) $todayTicketsCount,
                'today_tickets_amount' => (float) $todayTicketsAmount,
                'assignment' => $assignment,
            ],
        ]);
    }
}
