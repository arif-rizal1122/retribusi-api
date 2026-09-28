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
use App\Models\User;
use App\Support\SpatialAuditLogger;

class MarketController extends Controller
{
    /**
     * Verifikasi otorisasi petugas/admin operasional Disperindagkop (RBAC Disperindag).
     */
    private function authorizeDisperindagOfficer(?User $user): bool
    {
        if (!$user) return false;
        if (in_array($user->role, ['super_admin', 'admin'], true)) return true;

        $opd = $user->opd;
        $opdCode = strtoupper($opd?->code ?? '');
        $metadata = is_array($user->metadata) ? $user->metadata : [];
        $roleCode = strtolower($metadata['role_code'] ?? ($user->role_code ?? ''));

        return $user->opd_id === 3
            || in_array($opdCode, ['DISPERINDAG', 'PERINDAG', 'DISPERINDAGKOP', 'PERDAGINKOP'], true)
            || in_array($roleCode, ['perindag', 'disperindag', 'admin_disperindag', 'juru_pungut_pasar'], true)
            || MarketOfficerAssignment::where('user_id', $user->id)->exists();
    }

    /**
     * Verifikasi wewenang admin manajemen Disperindagkop (manajemen tarif/gedung).
     */
    private function authorizeDisperindagAdmin(?User $user): bool
    {
        if (!$user) return false;
        if ($user->role === 'super_admin') return true;
        if (in_array($user->role, ['admin', 'opd'], true)) {
            return $this->authorizeDisperindagOfficer($user);
        }
        return false;
    }

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

        // Status Klaim Warga & Kuasa Bayar
        $totalClaimed = (clone $stallQuery)->where('metadata->is_claimed', true)->count();
        $totalPendingClaims = (clone $stallQuery)->where('metadata->claim_status', 'pending_verification')->count();
        $totalActiveClaims = (clone $stallQuery)->where('metadata->claim_status', 'active')->count();

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
            'stalls as claimed_stalls' => fn($q) => $q->where('metadata->is_claimed', true),
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
                DB::raw("SUM(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.is_claimed')) = 'true' THEN 1 ELSE 0 END) as claimed_count"),
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

        // Realisasi Karcis Harian PKL & Pelataran (MarketDailyTicket)
        $today = now()->toDateString();
        $ticketQuery = MarketDailyTicket::query();
        if ($marketId) {
            $selectedMarket = Market::find($marketId);
            if ($selectedMarket) {
                $ticketQuery->where('market_name', $selectedMarket->name);
            }
        }

        $totalDailyTicketsCount = (clone $ticketQuery)->sum('quantity') ?: 0;
        $totalDailyTicketsAmount = (clone $ticketQuery)->sum('total_amount') ?: 0;
        $todayDailyTicketsCount = (clone $ticketQuery)->whereDate('issued_at', $today)->sum('quantity') ?: 0;
        $todayDailyTicketsAmount = (clone $ticketQuery)->whereDate('issued_at', $today)->sum('total_amount') ?: 0;
        $dailyTicketsCash = (clone $ticketQuery)->where('payment_method', 'TUNAI')->sum('total_amount') ?: 0;
        $dailyTicketsQris = (clone $ticketQuery)->where('payment_method', 'QRIS')->sum('total_amount') ?: 0;
        $recentTickets = (clone $ticketQuery)->orderBy('issued_at', 'desc')->take(10)->get();

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
                    'claimed_stalls' => $totalClaimed,
                    'pending_claims' => $totalPendingClaims,
                    'active_claims' => $totalActiveClaims,
                    'occupancy_rate' => $totalStalls > 0 ? round(($totalActive / $totalStalls) * 100, 1) : 0,
                    'monthly_los_potential' => (float) $monthlyLosPotential,
                    'yearly_kios_potential' => (float) $yearlyKiosPotential,
                    'total_billed' => (float) $totalBilled,
                    'total_collected' => (float) $totalCollected,
                    'total_arrears' => (float) $totalArrears,
                    'collection_rate' => $collectionRate,
                    'daily_tickets_count' => (int) $totalDailyTicketsCount,
                    'daily_tickets_amount' => (float) $totalDailyTicketsAmount,
                    'today_daily_tickets_count' => (int) $todayDailyTicketsCount,
                    'today_daily_tickets_amount' => (float) $todayDailyTicketsAmount,
                    'daily_tickets_cash' => (float) $dailyTicketsCash,
                    'daily_tickets_qris' => (float) $dailyTicketsQris,
                    'officer_teams_count' => $totalOfficers,
                ],
                'markets' => $marketsSummary,
                'buildings' => $buildingsSummary,
                'recent_tickets' => $recentTickets,
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
        $query = MarketStall::with(['market', 'zone', 'taxpayer', 'taxObject']);

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

        if ($request->filled('claim_status')) {
            $claimStatus = $request->query('claim_status');
            if ($claimStatus === 'unclaimed') {
                $query->where(function ($q) {
                    $q->whereNull('metadata->is_claimed')
                      ->orWhere('metadata->is_claimed', false);
                });
            } elseif ($claimStatus === 'all_claimed' || $claimStatus === 'claimed') {
                $query->where('metadata->is_claimed', true);
            } else {
                $query->where('metadata->is_claimed', true)
                      ->where('metadata->claim_status', $claimStatus);
            }
        } elseif ($request->boolean('claimed_only')) {
            $query->where('metadata->is_claimed', true);
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
        $user = $request->user();
        if (!$this->authorizeDisperindagOfficer($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk mengubah data lapak pasar.',
            ], 403);
        }

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
        $user = $request->user();
        if (!$this->authorizeDisperindagAdmin($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Wewenang admin Disperindagkop diperlukan untuk mengubah tarif gedung pasar.',
            ], 403);
        }

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
     * Update nominal retribusi dan siklus penagihan massal berdasarkan kategori tarif untuk banyak gedung sekaligus.
     */
    public function batchAssignTariff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tariff_amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:daily,weekly,monthly,yearly',
            'building_names' => 'required_without:stall_ids|array',
            'building_names.*' => 'string',
            'stall_ids' => 'nullable|array',
            'stall_ids.*' => 'integer',
            'market_name' => 'nullable|string',
            'tier_label' => 'nullable|string',
        ]);

        $query = MarketStall::query();

        if (!empty($validated['market_name'])) {
            $query->where('market_name', $validated['market_name']);
        }

        if (!empty($validated['building_names'])) {
            $query->whereIn('building_name', $validated['building_names']);
        } elseif (!empty($validated['stall_ids'])) {
            $query->whereIn('id', $validated['stall_ids']);
        }

        $totalStalls = $query->count();
        if ($totalStalls === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada lapak atau gedung yang cocok dengan kriteria yang dipilih.',
            ], 404);
        }

        $query->update([
            'tariff_amount' => $validated['tariff_amount'],
            'billing_cycle' => $validated['billing_cycle'],
        ]);

        $buildingsCount = !empty($validated['building_names']) ? count($validated['building_names']) : 1;
        $tierName = $validated['tier_label'] ?? 'tarif Perda';

        return response()->json([
            'success' => true,
            'message' => "Berhasil menerapkan {$tierName} ke {$totalStalls} unit lapak ({$buildingsCount} gedung) secara serentak.",
            'data' => [
                'updated_stalls' => $totalStalls,
                'buildings_count' => $buildingsCount,
                'tariff_amount' => $validated['tariff_amount'],
                'billing_cycle' => $validated['billing_cycle'],
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
            'area_sqm' => 'nullable|numeric|min:0.5',
            'dimensions' => 'nullable|string|max:100',
            'unit_amount' => 'nullable|numeric|min:500',
            'payment_method' => 'nullable|string|in:TUNAI,QRIS',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric',
        ]);

        $qty = $validated['quantity'] ?? 1;
        $unitAmount = $validated['unit_amount'] ?? 1000;
        $totalAmount = $qty * $unitAmount;
        $areaSqm = isset($validated['area_sqm']) ? (float) $validated['area_sqm'] : ($qty * 3);
        $dimensions = $validated['dimensions'] ?? ($qty == 1 ? '1,5 m × 2,0 m (3 m²)' : ($qty * 1.5) . ' m × 2,0 m (' . ($qty * 3) . ' m²)');
        $user = $request->user();

        if (!$this->authorizeDisperindagOfficer($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Hanya petugas juru pungut resmi Disperindagkop yang berwenang menerbitkan karcis pasar.',
            ], 403);
        }

        $lat = isset($validated['latitude']) ? (float) $validated['latitude'] : null;
        $lng = isset($validated['longitude']) ? (float) $validated['longitude'] : null;

        if ($user && $lat && $lng) {
            $user->update(['latitude' => $lat, 'longitude' => $lng]);
        }

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
            'area_sqm' => $areaSqm,
            'dimensions' => $dimensions,
            'unit_amount' => $unitAmount,
            'total_amount' => $totalAmount,
            'payment_method' => $validated['payment_method'] ?? 'TUNAI',
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy' => isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            'qr_token' => $qrToken,
            'issued_at' => now(),
        ]);

        SpatialAuditLogger::log(
            action: 'DISPERINDAG_DAILY_TICKET_ISSUED',
            nop: null,
            latitude: $lat,
            longitude: $lng,
            deviceLat: $lat,
            deviceLng: $lng,
            details: [
                'ticket_code' => $ticketCode,
                'market_name' => $validated['market_name'],
                'building_name' => $validated['building_name'] ?? 'Pelataran PKL',
                'amount' => $totalAmount,
                'payment_method' => $validated['payment_method'] ?? 'TUNAI',
                'accuracy' => $validated['accuracy'] ?? null,
            ]
        );

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
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric',
        ]);

        $user = $request->user();

        if (!$this->authorizeDisperindagOfficer($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Hanya petugas penagihan resmi Disperindagkop yang berwenang mencatat setoran retribusi pasar.',
            ], 403);
        }

        $lat = isset($validated['latitude']) ? (float) $validated['latitude'] : null;
        $lng = isset($validated['longitude']) ? (float) $validated['longitude'] : null;

        if ($user && $lat && $lng) {
            $user->update(['latitude' => $lat, 'longitude' => $lng]);
        }

        $ntpd = 'NTPD-PSR-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        // Update status stall menjadi aktif
        $stall->update([
            'status' => 'aktif',
            'notes' => $validated['notes'] ?? $stall->notes,
        ]);

        SpatialAuditLogger::log(
            action: 'DISPERINDAG_STALL_PAID',
            nop: null,
            latitude: $lat,
            longitude: $lng,
            deviceLat: $lat,
            deviceLng: $lng,
            details: [
                'stall_id' => $stall->id,
                'full_code' => $stall->full_code,
                'amount' => $validated['amount'],
                'ntpd' => $ntpd,
                'payment_method' => $validated['payment_method'] ?? 'TUNAI',
                'accuracy' => $validated['accuracy'] ?? null,
            ]
        );

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
     * Update data penugasan petugas penagihan retribusi pasar.
     */
    public function updateOfficer(Request $request, int $id): JsonResponse
    {
        $officer = MarketOfficerAssignment::findOrFail($id);

        $validated = $request->validate([
            'officer_name' => 'sometimes|string|max:255',
            'market_name' => 'sometimes|string|max:255',
            'market_id' => 'nullable|integer|exists:markets,id',
            'assigned_areas' => 'sometimes|array',
            'assigned_areas.*' => 'string|max:255',
            'collects_daily_pkl' => 'sometimes|boolean',
            'signed_by' => 'nullable|string|max:255',
            'decree_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Jika market_name berubah, cari market_id yang sesuai
        if (isset($validated['market_name']) && !isset($validated['market_id'])) {
            $market = Market::where('name', $validated['market_name'])->first();
            if ($market) {
                $validated['market_id'] = $market->id;
            }
        }

        $officer->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Data petugas {$officer->officer_name} berhasil diperbarui.",
            'data' => $officer->fresh(),
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

    /**
     * Cari daftar lapak yang tersedia untuk diklaim / ditandai oleh warga.
     */
    public function availableStalls(Request $request): JsonResponse
    {
        $query = MarketStall::with(['market', 'zone']);

        if ($request->filled('market_name')) {
            $query->where('market_name', 'like', '%' . trim($request->query('market_name')) . '%');
        }

        if ($request->filled('building_name')) {
            $query->where('building_name', 'like', '%' . trim($request->query('building_name')) . '%');
        }

        if ($request->filled('stall_type')) {
            $query->where('stall_type', $request->query('stall_type'));
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

        $stalls = $query->orderBy('building_name')
            ->orderByRaw('CAST(stall_number AS UNSIGNED) ASC')
            ->limit(200)
            ->get();

        $data = $stalls->map(function ($st) {
            $meta = is_array($st->metadata) ? $st->metadata : [];
            $claimStatus = $meta['claim_status'] ?? null;
            $isClaimed = !empty($meta['is_claimed']) && in_array($claimStatus, ['active', 'pending_verification']);

            // Ambil foto petugas jika sudah ada task survei lapangan
            $surveyPhoto = null;
            if ($st->tax_object_id) {
                $task = \App\Models\PetugasTask::where('tax_object_id', $st->tax_object_id)
                    ->whereNotNull('completion_photo_path')
                    ->latest()
                    ->first();
                if ($task) {
                    $surveyPhoto = $task->completion_photo_url;
                }
            }

            return [
                'id' => $st->id,
                'market_id' => $st->market_id,
                'market_name' => $st->market_name,
                'building_name' => $st->building_name,
                'stall_type' => $st->stall_type,
                'stall_number' => $st->stall_number,
                'full_code' => $st->full_code,
                'merchant_name' => $st->merchant_name,
                'tariff_amount' => (float) $st->tariff_amount,
                'billing_cycle' => $st->billing_cycle,
                'status' => $st->status,
                'is_claimed' => $isClaimed,
                'claim_status' => $claimStatus,
                'payer_taxpayer_id' => $isClaimed ? ($meta['payer_taxpayer_id'] ?? null) : null,
                'payer_name' => $isClaimed ? ($meta['payer_name'] ?? null) : null,
                'claim_for' => $isClaimed ? ($meta['claim_for'] ?? 'self') : null,
                'target_merchant_name' => $isClaimed ? ($meta['target_merchant_name'] ?? null) : null,
                'relationship' => $isClaimed ? ($meta['relationship'] ?? null) : null,
                'survey_photo_url' => $surveyPhoto,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Tandai / Klaim Lapak Terdata (Status awal Menunggu Verifikasi Admin Disperindag & Foto Petugas Pasar).
     */
    public function claimStall(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Autentikasi diperlukan.'], 401);
        }

        $request->validate([
            'claim_for' => 'required|in:self,representative',
            'target_name' => 'nullable|string|max:255',
            'target_nik' => 'nullable|string|max:20',
            'target_phone' => 'nullable|string|max:20',
            'relationship' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $stall = MarketStall::findOrFail($id);

        // Cari taxpayer record dari user yang login
        $taxpayer = null;
        if ($user instanceof Taxpayer) {
            $taxpayer = $user;
        } else {
            $taxpayer = Taxpayer::where('nik', $user->nik ?? '')->orWhere('email', $user->email ?? '')->first();
        }

        if (!$taxpayer) {
            return response()->json([
                'success' => false,
                'message' => 'Akun pembayar wajib pajak tidak ditemukan. Lengkapi profil terlebih dahulu.',
            ], 422);
        }

        $meta = is_array($stall->metadata) ? $stall->metadata : [];
        if (!empty($meta['is_claimed']) && in_array($meta['claim_status'] ?? '', ['active', 'pending_verification']) && ($meta['payer_taxpayer_id'] ?? null) != $taxpayer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Lapak ini sudah ditautkan atau sedang dalam proses verifikasi akun lain. Hubungi pos Disperindag jika terjadi kekeliruan.',
            ], 422);
        }

        $claimFor = $request->input('claim_for', 'self');
        $targetName = trim($request->input('target_name', ''));
        $targetNik = trim($request->input('target_nik', ''));
        $targetPhone = trim($request->input('target_phone', ''));
        $relationship = trim($request->input('relationship', ''));
        $notes = trim($request->input('notes', ''));

        if ($claimFor === 'representative' && empty($targetName)) {
            $targetName = $stall->merchant_name ?: 'Pedagang Binaan';
        }

        // Tentukan nama penanggung jawab dan catatan
        $displayResponsible = $claimFor === 'representative'
            ? "{$targetName} (Kuasa Bayar: {$taxpayer->name}" . ($relationship ? " [{$relationship}]" : "") . ")"
            : $taxpayer->name;

        DB::beginTransaction();
        try {
            // Update metadata MarketStall (Status Awal: pending_verification)
            $meta['is_claimed'] = true;
            $meta['claim_status'] = 'pending_verification';
            $meta['claim_for'] = $claimFor;
            $meta['payer_taxpayer_id'] = $taxpayer->id;
            $meta['payer_name'] = $taxpayer->name;
            $meta['payer_nik'] = $taxpayer->nik;
            $meta['payer_phone'] = $taxpayer->phone;
            $meta['target_merchant_name'] = $targetName ?: $stall->merchant_name;
            $meta['target_merchant_nik'] = $targetNik;
            $meta['target_merchant_phone'] = $targetPhone;
            $meta['relationship'] = $relationship;
            $meta['claimed_at'] = now()->toDateTimeString();
            $meta['notes'] = $notes;

            $stall->taxpayer_id = $taxpayer->id;
            if ($claimFor === 'self' && !empty($taxpayer->name)) {
                $stall->merchant_name = $taxpayer->name;
            } elseif ($claimFor === 'representative' && !empty($targetName)) {
                $stall->merchant_name = $targetName;
            }
            $stall->metadata = $meta;
            $stall->save();

            // Hubungkan TaxObject dengan status pending
            $taxObject = $stall->tax_object_id ? TaxObject::find($stall->tax_object_id) : null;
            if ($taxObject) {
                $objMeta = is_array($taxObject->metadata) ? $taxObject->metadata : [];
                $objMeta['is_representative_payment'] = ($claimFor === 'representative');
                $objMeta['payer_taxpayer_id'] = $taxpayer->id;
                $objMeta['payer_name'] = $taxpayer->name;
                $objMeta['target_merchant_name'] = $targetName;
                $objMeta['target_merchant_nik'] = $targetNik;
                $objMeta['relationship'] = $relationship;
                $objMeta['claimed_at'] = now()->toDateTimeString();

                $taxObject->taxpayer_id = $taxpayer->id;
                $taxObject->penanggung_jawab_nama = $displayResponsible;
                if (!empty($targetPhone)) {
                    $taxObject->penanggung_jawab_kontak = $targetPhone;
                }
                $taxObject->tipe_penghuni = ($claimFor === 'representative') ? 'KUASA_BAYAR' : 'PENYEWA';
                $taxObject->status = 'pending';
                $taxObject->metadata = $objMeta;
                $taxObject->save();

                // 1. Buat Permintaan Verifikasi Resmi ke Admin Disperindag
                $verification = \App\Models\Verification::create([
                    'opd_id' => 3, // DISPERINDAG
                    'tax_object_id' => $taxObject->id,
                    'user_id' => $user->id,
                    'document_number' => 'VRC-INDAG-' . strtoupper(Str::random(8)),
                    'taxpayer_name' => $taxpayer->name,
                    'type' => 'pendaftaran_objek',
                    'amount' => $stall->tariff_amount ?? 0,
                    'status' => 'pending',
                    'submitted_at' => now(),
                    'notes' => "Klaim Lapak: {$stall->full_code} ({$stall->building_name} Unit {$stall->stall_number}). " . ($claimFor === 'representative' ? "Mewakili: {$targetName} ({$relationship})" : "Lapak Sendiri"),
                ]);

                // 2. Buat Tugas Survei & Foto Fisik ke Petugas Lapangan Pasar
                $assignedOfficer = MarketOfficerAssignment::where('assigned_areas', 'like', "%{$stall->building_name}%")->first();
                $officerUserId = $assignedOfficer?->user_id ?: 202; // default ke petugas Disperindag

                \App\Models\PetugasTask::create([
                    'user_id' => $officerUserId,
                    'taxpayer_id' => $taxpayer->id,
                    'tax_object_id' => $taxObject->id,
                    'verification_id' => $verification->id,
                    'task_type' => 'survey_verifikasi',
                    'due_date' => now()->addDays(3),
                    'notes' => "Survei & foto fisik kios/los {$stall->building_name} No. {$stall->stall_number}. Verifikasi kepemilikan pedagang asli ({$targetName}) atas kuasa pembayar ({$taxpayer->name}).",
                    'status' => 'pending',
                    'created_by' => $user->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $claimFor === 'representative'
                    ? "Pengajuan tautan lapak untuk {$targetName} berhasil dikirim! Menunggu verifikasi admin Disperindag dan foto kios dari petugas pasar."
                    : "Pengajuan tautan lapak berhasil dikirim! Menunggu verifikasi admin Disperindag dan foto kios dari petugas pasar.",
                'data' => [
                    'stall_id' => $stall->id,
                    'tax_object_id' => $stall->tax_object_id,
                    'stall_number' => $stall->stall_number,
                    'building_name' => $stall->building_name,
                    'merchant_name' => $stall->merchant_name,
                    'claim_for' => $claimFor,
                    'claim_status' => 'pending_verification',
                    'payer_name' => $taxpayer->name,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menandai lapak: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verifikasi Klaim Lapak oleh Admin Disperindag (Approve / Reject).
     */
    public function verifyClaim(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['super_admin', 'admin', 'opd'])) {
            return response()->json(['success' => false, 'message' => 'Hanya admin Disperindag yang berwenang memverifikasi klaim.'], 403);
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        $action = $request->input('action');
        $notes = $request->input('notes');

        $stall = MarketStall::findOrFail($id);
        $meta = is_array($stall->metadata) ? $stall->metadata : [];
        $taxObject = $stall->tax_object_id ? TaxObject::find($stall->tax_object_id) : null;
        $verification = $taxObject ? \App\Models\Verification::where('tax_object_id', $taxObject->id)->latest()->first() : null;

        DB::beginTransaction();
        try {
            if ($action === 'approve') {
                $meta['claim_status'] = 'active';
                $meta['verified_at'] = now()->toDateTimeString();
                $meta['verified_by'] = $user->id;
                $stall->metadata = $meta;
                $stall->save();

                if ($taxObject) {
                    $taxObject->status = 'active';
                    $taxObject->approved_at = now();
                    $taxObject->approved_by = $user->id;
                    $taxObject->save();

                    // Reassign bill ke taxpayer pendaftar agar bisa dibayarkan lewat dia
                    if (!empty($meta['payer_taxpayer_id'])) {
                        Bill::where('tax_object_id', $taxObject->id)
                            ->where('status', '!=', 'lunas')
                            ->update(['taxpayer_id' => $meta['payer_taxpayer_id']]);
                    }
                }

                if ($verification) {
                    $verification->update([
                        'status' => 'approved',
                        'notes' => $notes ?: 'Klaim lapak telah diverifikasi dan disetujui oleh admin Disperindag.',
                        'verifier_id' => $user->id,
                        'verified_at' => now(),
                    ]);
                }

                // Tandai selesai tugas petugas
                if ($taxObject) {
                    \App\Models\PetugasTask::where('tax_object_id', $taxObject->id)
                        ->where('status', '!=', 'completed')
                        ->update(['status' => 'completed', 'completed_at' => now()]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Klaim lapak berhasil disetujui dan kini aktif resmi.',
                ]);
            } else {
                // Reject / Batalkan Klaim
                $meta['is_claimed'] = false;
                $meta['claim_status'] = 'rejected';
                $meta['rejected_at'] = now()->toDateTimeString();
                $meta['rejected_by'] = $user->id;
                $meta['rejection_notes'] = $notes;
                $stall->taxpayer_id = null;
                $stall->metadata = $meta;
                $stall->save();

                if ($taxObject) {
                    $taxObject->status = 'rejected';
                    $taxObject->save();
                }

                if ($verification) {
                    $verification->update([
                        'status' => 'rejected',
                        'notes' => $notes ?: 'Klaim lapak ditolak oleh admin Disperindag.',
                        'verifier_id' => $user->id,
                        'verified_at' => now(),
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Klaim lapak telah ditolak / dicabut.',
                ]);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses verifikasi klaim: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tarik klaim / Lepas tautan lapak (Unclaim).
     */
    public function unclaimStall(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Autentikasi diperlukan.'], 401);
        }

        $stall = MarketStall::findOrFail($id);
        $taxpayerId = ($user instanceof Taxpayer) ? $user->id : ($user->taxpayer_id ?? null);
        $isAdmin = !($user instanceof Taxpayer) && in_array($user->role ?? '', ['admin', 'super_admin', 'admin_disperindag', 'disperindag']);

        $meta = is_array($stall->metadata) ? $stall->metadata : [];
        $currentPayerId = $meta['payer_taxpayer_id'] ?? $stall->taxpayer_id;

        if (!$isAdmin && $currentPayerId != $taxpayerId) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak untuk melepas tautan lapak ini.',
            ], 403);
        }

        $reason = trim($request->input('reason', 'Ditarik oleh wajib pajak / pemegang akun'));

        DB::beginTransaction();
        try {
            $meta['is_claimed'] = false;
            $meta['claim_status'] = 'unclaimed';
            $meta['unclaimed_at'] = now()->toDateTimeString();
            $meta['unclaimed_by'] = $user->name ?? 'User';
            $meta['unclaimed_reason'] = $reason;

            $stall->taxpayer_id = null;
            $stall->metadata = $meta;
            $stall->save();

            $taxObject = $stall->tax_object_id ? TaxObject::find($stall->tax_object_id) : null;
            if ($taxObject) {
                $objMeta = is_array($taxObject->metadata) ? $taxObject->metadata : [];
                $objMeta['is_representative_payment'] = false;
                $objMeta['unclaimed_at'] = now()->toDateTimeString();
                $objMeta['unclaimed_reason'] = $reason;

                $taxObject->taxpayer_id = null;
                $taxObject->penanggung_jawab_nama = null;
                $taxObject->penanggung_jawab_kontak = null;
                $taxObject->metadata = $objMeta;
                $taxObject->save();

                // Lepas keterkaitan tagihan yang belum lunas
                Bill::where('tax_object_id', $taxObject->id)
                    ->where('status', '!=', 'lunas')
                    ->update(['taxpayer_id' => null]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tautan lapak berhasil dilepas. Lapak kembali tersedia dan tagihan tidak lagi dibebankan ke akun Anda.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal melepas tautan lapak: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tarik klaim berdasarkan tax_object_id.
     */
    public function unclaimByTaxObject(Request $request, int $taxObjectId): JsonResponse
    {
        $stall = MarketStall::where('tax_object_id', $taxObjectId)->first();
        if ($stall) {
            return $this->unclaimStall($request, $stall->id);
        }

        $taxObject = TaxObject::findOrFail($taxObjectId);
        $user = $request->user();
        $taxpayerId = ($user instanceof Taxpayer) ? $user->id : ($user->taxpayer_id ?? null);
        $isAdmin = !($user instanceof Taxpayer) && in_array($user->role ?? '', ['admin', 'super_admin', 'admin_disperindag', 'disperindag']);

        if (!$isAdmin && $taxObject->taxpayer_id != $taxpayerId) {
            return response()->json(['success' => false, 'message' => 'Anda tidak berhak melepas tautan objek ini.'], 403);
        }

        $taxObject->taxpayer_id = null;
        $taxObject->penanggung_jawab_nama = null;
        $taxObject->penanggung_jawab_kontak = null;
        $taxObject->save();

        Bill::where('tax_object_id', $taxObject->id)
            ->where('status', '!=', 'lunas')
            ->update(['taxpayer_id' => null]);

        return response()->json(['success' => true, 'message' => 'Tautan objek berhasil dilepas.']);
    }

    /**
     * Daftar log transaksi karcis harian pelataran / PKL untuk admin & audit pengawas.
     */
    public function dailyTickets(Request $request): JsonResponse
    {
        $query = MarketDailyTicket::with('collector');

        if ($request->filled('market_name')) {
            $query->where('market_name', $request->query('market_name'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', strtoupper($request->query('payment_method')));
        }

        if ($request->filled('date')) {
            $query->whereDate('issued_at', $request->query('date'));
        }

        if ($request->filled('collector_name')) {
            $query->where('collector_name', 'like', '%' . $request->query('collector_name') . '%');
        }

        if ($request->filled('q')) {
            $search = trim($request->query('q'));
            $query->where(function ($q) use ($search) {
                $q->where('ticket_code', 'like', "%{$search}%")
                  ->orWhere('merchant_name', 'like', "%{$search}%")
                  ->orWhere('building_name', 'like', "%{$search}%")
                  ->orWhere('collector_name', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('issued_at', 'desc')
            ->paginate($request->query('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }
}
