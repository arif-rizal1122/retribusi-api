<?php

namespace App\Http\Controllers\Api\V1\Asset;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Asset\Concerns\AssetAccess;
use App\Models\AssetRental;
use App\Models\AssetRentalInspection;
use App\Services\PuprOvertimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssetRentalController extends Controller
{
    use AssetAccess;

    public function __construct(
        private PuprOvertimeService $overtimeService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $this->assertAssetRole($user);

        $query = $this->assetRentalQuery($user);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('rental_code', 'like', "%{$search}%")
                    ->orWhere('nomor_kontrak', 'like', "%{$search}%")
                    ->orWhereHas('taxpayer', fn ($t) => $t->where('name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%"));
            });
        }

        $rentals = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($rentals);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !in_array($user->role, ['opd', 'admin'])) {
            return response()->json(['message' => 'Akses Ditolak.'], 403);
        }

        $validated = $request->validate([
            'asset_item_id' => 'required|exists:asset_items,id',
            'taxpayer_id' => 'required|exists:taxpayers,id',
            'opd_id' => 'required|exists:opds,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'lama_sewa' => 'required|string|max:20',
            'satuan_sewa' => 'required|in:per jam,per hari,per rit',
            'tarif_per_satuan' => 'required|numeric|min:0',
            'include_tronton' => 'nullable|boolean',
            'jarak_tronton_km' => 'nullable|numeric|min:0',
            'biaya_tronton' => 'nullable|numeric|min:0',
            'dp' => 'nullable|numeric|min:0',
            'metode_pembayaran' => 'nullable|string|max:50',
            'lokasi_penggunaan' => 'nullable|string|max:255',
            'jenis_pekerjaan' => 'nullable|string|max:100',
            'nama_proyek' => 'nullable|string|max:150',
            'koordinat' => 'nullable|string|max:100',
            'nomor_kontrak' => 'nullable|string|max:100',
            'retribution_type_id' => 'nullable|exists:retribution_types,id',
            'retribution_classification_id' => 'nullable|exists:retribution_classifications,id',
            'penyelia' => 'nullable|string|max:100',
            'hp_penyelia' => 'nullable|string|max:20',
            'operator_nama' => 'nullable|string|max:100',
            'operator_hp' => 'nullable|string|max:20',
            'operator_sim' => 'nullable|string|max:50',
            'nomor_spk' => 'nullable|string|max:100',
            'nama_konsumen' => 'nullable|string|max:150',
            'jenis_bangunan' => 'nullable|string|max:100',
            'jumlah_rit' => 'nullable|integer|min:1',
            'kelurahan' => 'nullable|string|max:100',
            'pelaksana_armada' => 'nullable|string|max:100',
            'keterangan_tambahan' => 'nullable|string',
        ]);

        if (!$user->isSuperAdmin() && $validated['opd_id'] !== $user->opd_id) {
            return response()->json(['message' => 'Akses Ditolak: OPD tidak sesuai.'], 403);
        }

        $tarif = (float) $validated['tarif_per_satuan'];
        $lama = (float) str_replace(',', '.', $validated['lama_sewa']);
        $biayaTronton = (float) ($validated['biaya_tronton'] ?? 0);
        $dp = (float) ($validated['dp'] ?? 0);
        $totalBiaya = ($tarif * $lama) + $biayaTronton;
        $validated['total_biaya'] = $totalBiaya;
        $validated['sisa_pembayaran'] = max(0, $totalBiaya - $dp);

        if (empty($validated['retribution_type_id'])) {
            $validated['retribution_type_id'] = \App\Models\RetributionType::withoutGlobalScopes()
                ->where('opd_id', $validated['opd_id'])
                ->where('name', 'like', '%Sewa Alat Berat%')
                ->value('id');
        }

        $validated['rental_code'] = 'SEWA-' . strtoupper(Str::random(8));
        $validated['status'] = 'pending_verification';

        $rental = AssetRental::create($validated);

        return response()->json([
            'message' => 'Kontrak sewa aset berhasil dibuat.',
            'data' => $rental->load(['taxpayer:id,name,nik', 'assetItem:id,name,code', 'opd:id,name']),
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $rental = $this->resolveAssetRental($request->user(), (int) $id);

        return response()->json([
            'data' => $rental->load(['taxpayer', 'assetItem', 'opd', 'inspections.inspector']),
        ]);
    }

    public function updateStatus(Request $request, string $id)
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !in_array($user->role, ['opd', 'admin'])) {
            return response()->json(['message' => 'Akses Ditolak.'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:verified,approved,active,completed,rejected,voided,cancelled',
        ]);

        $rental = $this->resolveAssetRental($user, (int) $id);

        $transitions = [
            'pending_verification' => ['verified', 'approved', 'rejected', 'voided'],
            'verified' => ['approved', 'rejected', 'voided'],
            'approved' => ['active', 'completed', 'rejected', 'voided'],
            'active' => ['completed', 'voided'],
        ];

        $allowed = $transitions[$rental->status] ?? [];

        if (!in_array($validated['status'], $allowed, true)) {
            return response()->json([
                'message' => "Transisi dari status '{$rental->status}' ke '{$validated['status']}' tidak diizinkan.",
            ], 422);
        }

        $rental->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Status kontrak sewa berhasil diperbarui.',
            'data' => $rental->fresh(['assetItem:id,name,code', 'taxpayer:id,name']),
        ]);
    }

    public function survey(Request $request, string $id)
    {
        $user = $request->user();
        $this->assertAssetRole($user);

        $rental = $this->resolveAssetRental($user, (int) $id);

        $validated = $request->validate([
            'survey_akses_jalan' => 'required|boolean',
            'survey_dekat_jalan_raya' => 'required|boolean',
            'survey_keamanan' => 'required|boolean',
            'survey_lahan_luas' => 'required|boolean',
            'survey_rekomendasi_tronton' => 'nullable|string|max:255',
            'survey_penjebolan_akses' => 'nullable|boolean',
            'survey_penjebolan_catatan' => 'nullable|string',
            'survey_rekomendasi_alat' => 'nullable|string|max:255',
            'survey_kesimpulan' => 'nullable|string',
            'survey_foto_path' => 'nullable|string|max:500',
        ]);

        $isAllEligible = $validated['survey_akses_jalan']
            && $validated['survey_dekat_jalan_raya']
            && $validated['survey_keamanan']
            && $validated['survey_lahan_luas'];

        $defaults = [];
        if (!isset($validated['survey_kesimpulan']) || $validated['survey_kesimpulan'] === null) {
            $defaults['survey_kesimpulan'] = $isAllEligible
                ? 'Lokasi layak & memenuhi standar operasional olah gerak alat berat.'
                : 'Perlu penyesuaian lokasi/akses jalan sebelum unit dimobilisasi.';
        }

        $rental->update($validated + $defaults + [
            'survey_submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Laporan lengkap survey kelayakan & tronton berhasil disimpan!',
            'data' => $rental->fresh(),
        ]);
    }

    public function inspection(Request $request, string $id)
    {
        $user = $request->user();
        $this->assertAssetRole($user);

        $rental = $this->resolveAssetRental($user, (int) $id);

        $validated = $request->validate([
            'inspection_type' => 'required|in:pre_operation,post_operation',
            'hour_meter_value' => 'required|numeric|min:0',
            'checklist' => 'required|array',
            'checklist.*' => 'nullable|string',
            'damage_notes' => 'nullable|string',
            'inspector_gps_lat' => 'nullable|numeric|between:-90,90',
            'inspector_gps_lng' => 'nullable|numeric|between:-180,180',
            'photo_path' => 'nullable|string|max:500',
        ]);

        $checklist = $validated['checklist'];
        $normalizedChecklist = [];
        foreach ($checklist as $key => $value) {
            $normalizedChecklist[$key] = in_array(strtoupper((string) $value), ['GOOD', 'TRUE', '1', 'BAIK', 'OK']) ? 'GOOD' : 'ATTENTION';
        }

        $inspection = AssetRentalInspection::create([
            'asset_rental_id' => $rental->id,
            'inspected_by' => $user->id,
            'inspection_type' => $validated['inspection_type'],
            'hour_meter' => $validated['hour_meter_value'],
            'checklist' => $normalizedChecklist,
            'notes' => $validated['damage_notes'] ?? null,
            'latitude' => $validated['inspector_gps_lat'] ?? null,
            'longitude' => $validated['inspector_gps_lng'] ?? null,
            'photo_path' => $validated['photo_path'] ?? null,
            'inspected_at' => now(),
        ]);

        $overtimeResult = null;

        if ($validated['inspection_type'] === 'post_operation') {
            // Penagihan overtime otomatis (SKRD Denda) — idempotent per sewa.
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
            'message' => 'Laporan inspeksi fisik alat berat berhasil disimpan.',
            'data' => $inspection,
            'overtime' => $overtimeResult,
        ], 201);
    }

    public function verify(Request $request, string $id)
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !in_array($user->role, ['opd', 'admin'])) {
            return response()->json(['message' => 'Akses Ditolak.'], 403);
        }

        $validated = $request->validate([
            'catatan' => 'nullable|string',
        ]);

        $rental = $this->resolveAssetRental($user, (int) $id);

        if ($rental->status !== 'pending_verification') {
            return response()->json([
                'message' => "Verifikasi hanya bisa dilakukan dari status 'Diajukan'.",
            ], 422);
        }

        $rental->update([
            'status' => 'verified',
            'verified_by' => $user->id,
            'catatan_verifikator' => $validated['catatan'] ?? null,
        ]);

        return response()->json([
            'message' => 'Pengajuan berhasil diverifikasi (Tahap 1).',
            'data' => $rental->fresh(['assetItem:id,name,code', 'taxpayer:id,name', 'verifiedBy:id,name']),
        ]);
    }

    public function approve(Request $request, string $id)
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !in_array($user->role, ['opd', 'admin'])) {
            return response()->json(['message' => 'Akses Ditolak.'], 403);
        }

        $validated = $request->validate([
            'catatan' => 'nullable|string',
        ]);

        $rental = $this->resolveAssetRental($user, (int) $id);

        if ($rental->status !== 'verified') {
            return response()->json([
                'message' => "Persetujuan hanya bisa dilakukan dari status 'Diverifikasi'.",
            ], 422);
        }

        $rental->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'catatan_approval' => $validated['catatan'] ?? null,
        ]);

        return response()->json([
            'message' => 'Pengajuan disetujui oleh Kepala UPTD (Tahap 2).',
            'data' => $rental->fresh(['assetItem:id,name,code', 'taxpayer:id,name', 'approvedBy:id,name']),
        ]);
    }

    public function contract(Request $request, string $id)
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !in_array($user->role, ['opd', 'admin'])) {
            return response()->json(['message' => 'Akses Ditolak.'], 403);
        }

        $validated = $request->validate([
            'nomor_kontrak' => 'required|string|max:150',
            'tahap_1_amount' => 'nullable|numeric|min:0',
            'tahap_2_amount' => 'nullable|numeric|min:0',
        ]);

        $rental = $this->resolveAssetRental($user, (int) $id);

        $rental->update([
            'nomor_kontrak' => $validated['nomor_kontrak'],
            'tahap_1_amount' => $validated['tahap_1_amount'] ?? null,
            'tahap_2_amount' => $validated['tahap_2_amount'] ?? null,
        ]);

        return response()->json([
            'message' => 'Kontrak dan termin pembayaran berhasil diterbitkan.',
            'data' => $rental->fresh(['assetItem:id,name,code', 'taxpayer:id,name']),
        ]);
    }

    public function complete(Request $request, string $id)
    {
        $user = $request->user();

        if (!$user->isSuperAdmin() && !in_array($user->role, ['opd', 'admin'])) {
            return response()->json(['message' => 'Akses Ditolak.'], 403);
        }

        $validated = $request->validate([
            'tanggal_pengembalian' => 'nullable|date',
            'kondisi_pengembalian' => 'nullable|string|max:100',
            'actual_hours' => 'nullable|numeric|min:0',
            'denda' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        $rental = $this->resolveAssetRental($user, (int) $id);

        if (!in_array($rental->status, ['approved', 'active'], true)) {
            return response()->json([
                'message' => "Pencatatan pengembalian hanya bisa dari status 'Disetujui' / berjalan.",
            ], 422);
        }

        $rental->update([
            'status' => 'completed',
            'tanggal_selesai' => $validated['tanggal_pengembalian'] ?? $rental->tanggal_selesai ?? now()->toDateString(),
            'tanggal_pengembalian' => $validated['tanggal_pengembalian'] ?? now()->toDateString(),
            'kondisi_pengembalian' => $validated['kondisi_pengembalian'] ?? null,
            'actual_hours' => $validated['actual_hours'] ?? $rental->actual_hours,
            'denda' => $validated['denda'] ?? null,
            'catatan_approval' => $validated['catatan'] ?? $rental->catatan_approval,
        ]);

        return response()->json([
            'message' => 'Pengembalian aset dicatat dan sewa diselesaikan.',
            'data' => $rental->fresh(['assetItem:id,name,code', 'taxpayer:id,name', 'verifiedBy:id,name', 'approvedBy:id,name']),
        ]);
    }
}