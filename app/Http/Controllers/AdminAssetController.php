<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\Bill;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAssetController extends Controller
{
    /** Role yang boleh melihat/memverifikasi aset seluruh OPD. */
    private const GLOBAL_ASSET_ROLES = [
        'super_admin',
        'admin',
        'kepala_bapenda',
        'walikota',
        'pengawas',
        'kabid_pengawas',
        'kasubid_pengawas',
        'viewer',
    ];

    /**
     * M39-2 — Batasi akses aset sesuai OPD pengampu.
     * Role global bebas; role OPD/petugas/verifikator hanya boleh menyentuh
     * aset yang opd_id-nya sama dengan opd_id akun.
     */
    private function authorizeAssetScope(AssetRental $rental)
    {
        $user = auth()->user();
        if (! $user || ! ($user instanceof User)) {
            abort(403, 'Forbidden: Internal staff access required.');
        }

        if (in_array($user->role, self::GLOBAL_ASSET_ROLES, true)) {
            return;
        }

        $itemOpdId = $rental->assetItem?->opd_id;
        if (! $user->opd_id || ! $itemOpdId || (int) $user->opd_id !== (int) $itemOpdId) {
            abort(403, 'Forbidden: Anda hanya dapat memverifikasi aset pada OPD Anda sendiri.');
        }
    }

    /**
     * Batasi hak akses modifikasi unit aset (item) sesuai OPD akun.
     */
    private function authorizeAssetItemScope(AssetItem $item): void
    {
        $user = auth()->user();
        if (! $user || ! ($user instanceof User)) {
            abort(403, 'Forbidden: Internal staff access required.');
        }

        if (in_array($user->role, self::GLOBAL_ASSET_ROLES, true)) {
            return;
        }

        if (! $user->opd_id || (int) $user->opd_id !== (int) $item->opd_id) {
            abort(403, 'Forbidden: Anda hanya dapat mengelola aset milik OPD Anda sendiri.');
        }
    }

    /** Scope query rental sesuai OPD user (role non-global). */
    private function scopeQueryToUserOpd($query)
    {
        $user = auth()->user();
        if (! $user || ! ($user instanceof User)) {
            abort(403, 'Forbidden: Internal staff access required.');
        }

        if (in_array($user->role, self::GLOBAL_ASSET_ROLES, true)) {
            return $query;
        }
        if (! $user->opd_id) {
            abort(403, 'Forbidden: Akun Anda belum terhubung ke OPD.');
        }

        return $query->whereHas('assetItem', fn ($q) => $q->where('opd_id', $user->opd_id));
    }

    /**
     * Daftar pengajuan sewa aset (admin) — filter status opsional.
     */
    public function rentals(Request $request)
    {
        $query = AssetRental::with([
            'taxpayer:id,name,nik,npwpd,phone',
            'assetItem:id,name,code,category_id',
            'assetItem.category:id,name',
            'surveyor:id,name',
            'verifiedBy:id,name',
            'approvedBy:id,name',
        ]);

        $query = $this->scopeQueryToUserOpd($query);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('category_id')) {
            $query->whereHas('assetItem', fn ($q) => $q->where('category_id', $request->input('category_id')));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('rental_code', 'like', "%{$s}%")
                    ->orWhere('nomor_kontrak', 'like', "%{$s}%")
                    ->orWhereHas('taxpayer', fn ($t) => $t->where('name', 'like', "%{$s}%")->orWhere('nik', 'like', "%{$s}%"))
                    ->orWhereHas('assetItem', fn ($i) => $i->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
            });
        }

        $rentals = $query->orderByDesc('created_at')->paginate($request->input('per_page', 20));

        return response()->json($rentals);
    }

    /**
     * Detail pengajuan sewa aset (admin).
     */
    public function showRental($id)
    {
        $rental = AssetRental::with([
            'taxpayer:id,name,nik,npwpd,phone',
            'assetItem:id,name,code,category_id,opd_id',
            'assetItem.category:id,name',
            'surveyor:id,name,email',
            'verifiedBy:id,name',
            'approvedBy:id,name',
            'inspections.officer:id,name',
            'inspections' => fn ($q) => $q->orderBy('created_at'),
        ])->findOrFail($id);

        $this->authorizeAssetScope($rental);

        return response()->json([
            'success' => true,
            'data' => $rental,
        ]);
    }

    /**
     * Submit hasil survey lapangan (4 butir checklist kelayakan).
     */
    public function submitSurvey(Request $request, $id)
    {
        $validated = $request->validate([
            'survey_akses_jalan' => 'required|boolean',
            'survey_dekat_jalan_raya' => 'required|boolean',
            'survey_keamanan' => 'required|boolean',
            'survey_lahan_luas' => 'required|boolean',
            'survey_kesimpulan' => 'nullable|string',
            'survey_foto_path' => 'nullable|string',
            'survey_rekomendasi_tronton' => 'nullable|string',
            'survey_penjebolan_akses' => 'nullable|boolean',
            'survey_penjebolan_catatan' => 'nullable|string',
            'survey_rekomendasi_alat' => 'nullable|string',
            'survey_gps_lat' => 'nullable|numeric',
            'survey_gps_lng' => 'nullable|numeric',
            'survey_gps_status' => 'nullable|string',
            'survey_gps_notes' => 'nullable|string',
        ]);

        $rental = AssetRental::findOrFail($id);
        $this->authorizeAssetScope($rental);

        $metadata = $rental->metadata ?? [];
        $metadata['survey_gps'] = [
            'lat' => $validated['survey_gps_lat'] ?? null,
            'lng' => $validated['survey_gps_lng'] ?? null,
            'status' => $validated['survey_gps_status'] ?? 'active',
            'notes' => $validated['survey_gps_notes'] ?? null,
            'recorded_at' => now()->toIso8601String(),
        ];

        $rental->update([
            'survey_akses_jalan' => $validated['survey_akses_jalan'],
            'survey_dekat_jalan_raya' => $validated['survey_dekat_jalan_raya'],
            'survey_keamanan' => $validated['survey_keamanan'],
            'survey_lahan_luas' => $validated['survey_lahan_luas'],
            'survey_kesimpulan' => $validated['survey_kesimpulan'] ?? null,
            'survey_foto_path' => $validated['survey_foto_path'] ?? null,
            'survey_rekomendasi_tronton' => $validated['survey_rekomendasi_tronton'] ?? null,
            'survey_penjebolan_akses' => (bool) ($validated['survey_penjebolan_akses'] ?? false),
            'survey_penjebolan_catatan' => $validated['survey_penjebolan_catatan'] ?? null,
            'survey_rekomendasi_alat' => $validated['survey_rekomendasi_alat'] ?? null,
            'surveyor_user_id' => auth()->id(),
            'survey_submitted_at' => now(),
            'metadata' => $metadata,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan hasil survey kelayakan lapangan berhasil disimpan.',
            'data' => $rental->load(['surveyor:id,name', 'assetItem:id,name,code']),
        ]);
    }

    /**
     * Submit asesmen & eksekusi on-the-spot mobil tinja / sedot kakus oleh Sopir & Operator.
     * Tidak memerlukan survei lapangan formal terpisah.
     */
    public function submitSedotKakusExecution(Request $request, $id)
    {
        $validated = $request->validate([
            'foto_sebelum' => 'required|string',
            'foto_sesudah' => 'required|string',
            'signature_pemohon' => 'nullable|string',
            'catatan_lapangan' => 'nullable|string',
            'volume_rit_aktual' => 'nullable|numeric',
            'status_tutup_lubang' => 'nullable|string',
            'pembuangan_iplt' => 'nullable|boolean',
            'gps_lat' => 'nullable|numeric',
            'gps_lng' => 'nullable|numeric',
        ]);

        $rental = AssetRental::findOrFail($id);
        $this->authorizeAssetScope($rental);

        $metadata = $rental->metadata ?? [];
        $metadata['sedot_kakus_execution'] = [
            'foto_sebelum' => $validated['foto_sebelum'],
            'foto_sesudah' => $validated['foto_sesudah'],
            'signature_pemohon' => $validated['signature_pemohon'] ?? null,
            'catatan_lapangan' => $validated['catatan_lapangan'] ?? null,
            'volume_rit_aktual' => $validated['volume_rit_aktual'] ?? $rental->jumlah_unit ?? 1,
            'status_tutup_lubang' => $validated['status_tutup_lubang'] ?? 'Rapi dan tertutup kembali',
            'pembuangan_iplt' => (bool) ($validated['pembuangan_iplt'] ?? true),
            'gps' => [
                'lat' => $validated['gps_lat'] ?? null,
                'lng' => $validated['gps_lng'] ?? null,
            ],
            'executed_by_id' => auth()->id(),
            'executed_by_name' => auth()->user()?->name ?? 'Operator UPTD',
            'executed_at' => now()->toIso8601String(),
        ];

        // Tandai sewa selesai dan catat kondisi
        $rental->update([
            'status' => 'Selesai',
            'kondisi_pengembalian' => 'Selesai disedot dan dialirkan ke tangki armada',
            'metadata' => $metadata,
        ]);

        // Selesaikan tugas petugas jika ada
        try {
            \App\Models\PetugasTask::where('target_id', $rental->id)
                ->whereIn('task_type', ['eksekusi_sedot_kakus', 'survey_aset'])
                ->update(['status' => 'completed']);
        } catch (\Throwable $e) {
            // Task model might have different columns or table
        }

        return response()->json([
            'success' => true,
            'message' => 'Layanan sedot kakus berhasil diselesaikan di lokasi dan siap dibuang ke IPLT.',
            'data' => $rental->fresh(['taxpayer', 'assetItem']),
        ]);
    }

    /**
     * Terbitkan kontrak sewa dan tentukan termin pembayaran (Tahap 1 & Tahap 2).
     */
    public function generateContract(Request $request, $id)
    {
        $validated = $request->validate([
            'nomor_kontrak' => 'required|string|max:100',
            'tahap_1_amount' => 'required|numeric|min:0',
            'tahap_2_amount' => 'nullable|numeric|min:0',
        ]);

        $rental = AssetRental::with(['assetItem.category'])->findOrFail($id);
        $this->authorizeAssetScope($rental);

        $metadata = $rental->metadata ?? [];

        // Buat atau sinkronkan tagihan SKRD di tabel bills untuk Tahap 1 (Uang Muka / Sewa Awal)
        $billNumber = 'SKRD-AST-' . $rental->rental_code . '-T1';
        $bill = Bill::updateOrCreate(
            ['bill_number' => $billNumber],
            [
                'taxpayer_id' => $rental->taxpayer_id,
                'opd_id' => $rental->assetItem?->category?->opd_id ?? 9,
                'retribution_type_id' => $rental->retribution_type_id ?? 106,
                'retribution_classification_id' => $rental->retribution_classification_id ?? 211,
                'amount' => $validated['tahap_1_amount'],
                'admin_fee' => 0,
                'penalty_amount' => 0,
                'status' => 'pending',
                'due_date' => now()->addDays(7),
                'period' => now()->format('Y-m'),
                'metadata' => [
                    'rental_id' => $rental->id,
                    'rental_code' => $rental->rental_code,
                    'tahap' => 1,
                    'asset_name' => $rental->assetItem?->name ?? 'Aset Daerah',
                ],
            ]
        );

        $metadata['tahap_1_bill_id'] = $bill->id;
        $metadata['tahap_1_bill_number'] = $bill->bill_number;

        $rental->update([
            'nomor_kontrak' => $validated['nomor_kontrak'],
            'tahap_1_amount' => $validated['tahap_1_amount'],
            'tahap_2_amount' => $validated['tahap_2_amount'] ?? 0,
            'tahap_1_status' => 'Pending',
            'tahap_2_status' => 'Pending',
            'metadata' => $metadata,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kontrak sewa dan SKRD termin pembayaran berhasil diterbitkan.',
            'data' => $rental,
            'bill' => $bill,
        ]);
    }


    /**
     * Daftar petugas lapangan yang dapat ditugaskan untuk survey dan operasional aset.
     */
    public function officers(Request $request)
    {
        $user = auth()->user();
        $query = User::where('role', 'petugas')->where('status', 'active');
        if ($user && $user->opd_id && ! $user->isSuperAdmin()) {
            $query->where('opd_id', $user->opd_id);
        }

        $officers = $query->orderBy('name')->get(['id', 'name', 'email', 'phone', 'metadata']);
        return response()->json([
            'success' => true,
            'data' => $officers,
        ]);
    }

    /**
     * Disposisi / Penugasan Petugas Surveyor dan Operator Alat Berat.
     */
    public function assignOfficer(Request $request, $id)
    {
        $validated = $request->validate([
            'surveyor_user_id' => 'nullable|exists:users,id',
            'operator_nama' => 'nullable|string|max:100',
            'operator_hp' => 'nullable|string|max:30',
            'operator_sim' => 'nullable|string|max:50',
            'catatan' => 'nullable|string',
        ]);

        $rental = AssetRental::with(['taxpayer', 'assetItem'])->findOrFail($id);
        $this->authorizeAssetScope($rental);

        $rental->update([
            'surveyor_user_id' => $validated['surveyor_user_id'] ?? $rental->surveyor_user_id,
            'operator_nama' => $validated['operator_nama'] ?? $rental->operator_nama,
            'operator_hp' => $validated['operator_hp'] ?? $rental->operator_hp,
            'operator_sim' => $validated['operator_sim'] ?? $rental->operator_sim,
            'catatan_verifikator' => $validated['catatan'] ?? $rental->catatan_verifikator,
        ]);

        $isSedotKakus = (bool) ($rental->metadata['is_sedot_kakus'] ?? false)
            || str_starts_with($rental->assetItem?->code ?? '', 'SK-')
            || str_contains(strtolower($rental->assetItem?->name ?? ''), 'tinja')
            || str_contains(strtolower($rental->assetItem?->name ?? ''), 'kakus');

        // Buat atau sinkronkan tugas di PetugasTask agar muncul di mobile app retribusi-petugas
        if (! empty($validated['surveyor_user_id'])) {
            \App\Models\PetugasTask::create([
                'user_id' => $validated['surveyor_user_id'],
                'taxpayer_id' => $rental->taxpayer_id,
                'task_type' => $isSedotKakus ? 'eksekusi_sedot_kakus' : 'survey_aset',
                'due_date' => now()->addDays(2),
                'notes' => $isSedotKakus
                    ? "Pelayanan Sedot Kakus: {$rental->rental_code} ({$rental->assetItem?->name}). Lokasi: {$rental->lokasi_penggunaan}. Armada: " . ($rental->metadata['pelaksana_armada'] ?? 'Truk Tangki UPTD PUPR') . ". Sopir/Operator: " . ($validated['operator_nama'] ?? $rental->operator_nama ?? 'Tim UPTD')
                    : "Survei Kelayakan Sewa: {$rental->rental_code} ({$rental->assetItem?->name}). Lokasi: {$rental->lokasi_penggunaan}. Catatan: " . ($validated['catatan'] ?? 'Segera lakukan survey 4 parameter lapangan.'),
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $isSedotKakus ? 'Disposisi armada dan sopir/operator sedot kakus berhasil diterbitkan.' : 'Penugasan petugas lapangan dan operator berhasil disimpan.',
            'data' => $rental->load(['surveyor:id,name,phone', 'assetItem:id,name,code']),
        ]);
    }

    /**
     * Verifikasi pengajuan (status Diajukan → Diverifikasi).
     */
    public function verify(Request $request, $id)
    {
        $rental = AssetRental::findOrFail($id);
        $this->authorizeAssetScope($rental);
        if ($rental->status !== 'Diajukan') {
            return response()->json(['message' => 'Hanya pengajuan berstatus Diajukan yang bisa diverifikasi.'], 422);
        }

        $rental->update([
            'status' => 'Diverifikasi',
            'tanggal_verifikasi' => now(),
            'catatan_verifikator' => $request->input('catatan'),
            'verified_by_user_id' => auth()->id(),
        ]);

        return response()->json(['message' => 'Pengajuan diverifikasi.', 'data' => $rental]);
    }

    /**
     * Approve pengajuan (status Diverifikasi → Disetujui).
     */
    public function approve(Request $request, $id)
    {
        $rental = AssetRental::with(['assetItem', 'room'])->findOrFail($id);
        $this->authorizeAssetScope($rental);
        if (!in_array($rental->status, ['Diajukan', 'Diverifikasi'])) {
            return response()->json(['message' => 'Hanya pengajuan Diajukan/Diverifikasi yang bisa disetujui.'], 422);
        }

        if ($rental->room_id && $rental->room) {
            app(\App\Services\RusunBillingService::class)->checkInRoom($rental, $rental->room, auth()->user());
            $rental->refresh();
        } else {
            $rental->update([
                'status' => 'Disetujui',
                'tanggal_approval' => now(),
                'catatan_approval' => $request->input('catatan'),
                'approved_by_user_id' => auth()->id(),
            ]);
        }

        return response()->json(['message' => 'Pengajuan disetujui.', 'data' => $rental]);
    }

    /**
     * Tolak pengajuan.
     */
    public function reject(Request $request, $id)
    {
        $rental = AssetRental::findOrFail($id);
        $this->authorizeAssetScope($rental);
        if (in_array($rental->status, ['Selesai', 'Ditolak'])) {
            return response()->json(['message' => 'Status pengajuan sudah final.'], 422);
        }

        $rental->update([
            'status' => 'Ditolak',
            'catatan_approval' => $request->input('catatan', 'Ditolak oleh admin'),
        ]);

        return response()->json(['message' => 'Pengajuan ditolak.', 'data' => $rental]);
    }

    /**
     * Catat pengembalian aset (Disetujui → Selesai + kondisi + denda + actual hours).
     */
    public function complete(Request $request, $id)
    {
        $validated = $request->validate([
            'tanggal_pengembalian' => 'required|date',
            'kondisi_pengembalian' => 'required|in:Baik,Rusak Ringan,Rusak Berat,Hilang',
            'catatan' => 'nullable|string',
            'denda' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
        ]);

        $rental = AssetRental::with(['assetItem', 'room'])->findOrFail($id);
        $this->authorizeAssetScope($rental);
        if (!in_array($rental->status, ['Disetujui', 'Diverifikasi'])) {
            return response()->json(['message' => 'Pengajuan belum disetujui, tidak bisa dicatat pengembalian.'], 422);
        }

        $isRusunawa = $rental->room_id !== null || str_starts_with($rental->assetItem?->code ?? '', 'RUSUN-');
        if ($isRusunawa) {
            app(\App\Services\RusunBillingService::class)->checkOutRoom($rental, auth()->user());
            $rental->refresh();
        }

        $rental->update([
            'status' => 'Selesai',
            'tanggal_pengembalian' => $validated['tanggal_pengembalian'],
            'kondisi_pengembalian' => $validated['kondisi_pengembalian'],
            'catatan_pengembalian' => $validated['catatan'] ?? null,
            'denda' => $validated['denda'] ?? 0,
            'actual_hours' => $validated['actual_hours'] ?? null,
        ]);

        return response()->json(['message' => 'Pengembalian aset dicatat.', 'data' => $rental]);
    }

    /**
     * Konfirmasi pembayaran termin / pelunasan sewa aset (Admin Pengampu).
     */
    public function confirmPayment(Request $request, $id)
    {
        $validated = $request->validate([
            'tahap' => 'required|in:1,2,all',
            'status' => 'required|in:Lunas,Pending,Dibatalkan',
            'catatan' => 'nullable|string',
        ]);

        $rental = AssetRental::findOrFail($id);
        $this->authorizeAssetScope($rental);

        $updates = [];
        if (in_array($validated['tahap'], ['1', 'all'])) {
            $updates['tahap_1_status'] = $validated['status'];
        }
        if (in_array($validated['tahap'], ['2', 'all'])) {
            $updates['tahap_2_status'] = $validated['status'];
        }

        if ($validated['status'] === 'Lunas' && in_array($validated['tahap'], ['1', 'all'])) {
            $dpPaid = (float) ($rental->tahap_1_amount ?: $rental->dp);
            $updates['dp'] = $dpPaid;
            $updates['sisa_pembayaran'] = max(0, (float) $rental->total_biaya - $dpPaid);

            // Sinkronkan status bill SKRD menjadi lunas
            if (!empty($rental->metadata['tahap_1_bill_id'])) {
                Bill::where('id', $rental->metadata['tahap_1_bill_id'])->update(['status' => 'lunas']);
            }
        }

        if ($validated['status'] === 'Lunas' && $validated['tahap'] === '2') {
            $updates['sisa_pembayaran'] = 0;
        }

        $rental->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Status pembayaran berhasil dikonfirmasi.',
            'data' => $rental,
        ]);
    }

    /**
     * CRUD kategori aset.
     */
    public function categories()
    {
        $categories = AssetCategory::withCount('items')->orderBy('sort_order')->get();
        return response()->json(['data' => $categories]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:asset_categories,slug',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
        ]);

        $category = AssetCategory::create($validated);
        return response()->json(['message' => 'Kategori dibuat.', 'data' => $category], 201);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = AssetCategory::findOrFail($id);
        $category->update($request->only(['name', 'slug', 'icon', 'description', 'sort_order', 'is_active']));
        return response()->json(['message' => 'Kategori diperbarui.', 'data' => $category]);
    }

    public function deleteCategory($id)
    {
        $category = AssetCategory::findOrFail($id);
        if ($category->items()->count() > 0) {
            return response()->json(['message' => 'Kategori masih memiliki unit aset.'], 422);
        }
        $category->delete();
        return response()->json(['message' => 'Kategori dihapus.']);
    }

    /**
     * CRUD unit aset (item).
     */
    public function items(Request $request)
    {
        $query = AssetItem::with('category:id,name,slug')->with('opd:id,name,code');

        // Scope berdasarkan OPD akun jika non-global
        $user = auth()->user();
        if ($user && ! in_array($user->role, self::GLOBAL_ASSET_ROLES, true)) {
            if ($user->opd_id) {
                $query->where('opd_id', $user->opd_id);
            }
        } elseif ($request->filled('opd_id') && $request->input('opd_id') !== 'all') {
            $query->where('opd_id', $request->input('opd_id'));
        }

        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term)
                  ->orWhere('merk_type', 'like', $term)
                  ->orWhere('spesifikasi', 'like', $term)
                  ->orWhere('lokasi', 'like', $term);
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('category_slug')) {
            $slug = $request->input('category_slug');
            $query->whereHas('category', function ($q) use ($slug) {
                $q->where('slug', $slug);
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status_operasional', $request->input('status'));
        }

        $perPage = $request->input('per_page', 50);
        if ($perPage == -1 || $perPage === 'all') {
            $items = $query->orderBy('category_id')->orderBy('name')->get();
            return response()->json(['data' => $items]);
        }

        $items = $query->orderBy('category_id')->orderBy('name')->paginate((int)$perPage);
        return response()->json($items);
    }

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:asset_categories,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100',
            'merk_type' => 'nullable|string|max:255',
            'spesifikasi' => 'nullable|string|max:255',
            'kondisi' => 'required|in:Baik,Terbatas,Tidak Operasional,Dialihfungsikan',
            'status_operasional' => 'required|in:Operasional,Terbatas,Tidak Operasional,Dialihfungsikan',
            'lokasi' => 'nullable|string|max:255',
            'pic' => 'nullable|string|max:255',
            'tarif_default' => 'required|numeric|min:0',
            'satuan_tarif' => 'required|in:per jam,per hari,per km,per bulan,per tahun,per kegiatan',
            'opd_id' => 'nullable|exists:opds,id',
            'image_url' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        if ($user && ! in_array($user->role, self::GLOBAL_ASSET_ROLES, true)) {
            $validated['opd_id'] = $user->opd_id;
        } elseif (! isset($validated['opd_id']) && $user?->opd_id) {
            $validated['opd_id'] = $user->opd_id;
        }

        $item = AssetItem::create(array_merge($validated, ['is_demo' => false, 'is_active' => true]));
        return response()->json(['message' => 'Unit aset ditambahkan.', 'data' => $item], 201);
    }

    public function updateItem(Request $request, $id)
    {
        $item = AssetItem::findOrFail($id);
        $this->authorizeAssetItemScope($item);

        $user = auth()->user();
        $fields = [
            'category_id', 'name', 'code', 'merk_type', 'spesifikasi', 'kondisi',
            'status_operasional', 'lokasi', 'pic', 'tarif_default', 'satuan_tarif',
            'wajib_tronton', 'is_active', 'is_demo', 'image_url',
        ];
        if ($user && in_array($user->role, self::GLOBAL_ASSET_ROLES, true)) {
            $fields[] = 'opd_id';
        }

        $item->update($request->only($fields));
        return response()->json(['message' => 'Unit aset diperbarui.', 'data' => $item]);
    }

    public function deleteItem($id)
    {
        $item = AssetItem::findOrFail($id);
        $this->authorizeAssetItemScope($item);

        if ($item->rentals()->whereNotIn('status', ['Selesai', 'Ditolak', 'Dibatalkan'])->count() > 0) {
            return response()->json(['message' => 'Unit aset sedang memiliki sewa aktif.'], 422);
        }
        $item->delete();
        return response()->json(['message' => 'Unit aset dihapus.']);
    }

    /**
     * Dashboard ringkasan aset (real-time).
     */
    public function dashboard()
    {
        $user = auth()->user();
        $isGlobal = ! $user || in_array($user->role, self::GLOBAL_ASSET_ROLES, true);
        $opdId = $isGlobal ? null : $user->opd_id;

        $itemsQuery = AssetItem::where('is_active', true);
        $rentalsQuery = AssetRental::query();

        if ($opdId) {
            $itemsQuery->where('opd_id', $opdId);
            $rentalsQuery->whereHas('assetItem', fn ($q) => $q->where('opd_id', $opdId));
        }

        $totalItems = (clone $itemsQuery)->count();
        $totalDemo = (clone $itemsQuery)->where('is_demo', true)->count();
        $operasional = (clone $itemsQuery)->where('status_operasional', 'Operasional')->count();
        $terbatas = (clone $itemsQuery)->where('status_operasional', 'Terbatas')->count();
        $tidakOperasional = (clone $itemsQuery)->where('status_operasional', 'Tidak Operasional')->count();

        $pengajuan = (clone $rentalsQuery)->count();
        $pending = (clone $rentalsQuery)->whereIn('status', ['Diajukan', 'Diverifikasi'])->count();
        $disetujui = (clone $rentalsQuery)->where('status', 'Disetujui')->count();
        $selesai = (clone $rentalsQuery)->where('status', 'Selesai')->count();
        $pendapatan = (float) (clone $rentalsQuery)->where('status', 'Selesai')->sum('total_biaya');
        $pendapatanPending = (float) (clone $rentalsQuery)->whereIn('status', ['Disetujui', 'Diverifikasi'])->sum('total_biaya');

        $perCategoryQuery = AssetItem::select('category_id', DB::raw('count(*) as total'))
            ->where('is_active', true);
        if ($opdId) {
            $perCategoryQuery->where('opd_id', $opdId);
        }
        $perCategory = $perCategoryQuery
            ->groupBy('category_id')
            ->with('category:id,name')
            ->get()
            ->map(fn ($i) => ['category' => $i->category?->name, 'total' => $i->total]);

        $roomMatrix = null;
        if ($isGlobal || (int) $opdId === 11) {
            $roomMatrix = \App\Models\RusunRoom::with([
                'assetItem:id,name,code,tarif_default',
                'currentRental.taxpayer:id,name,phone,nik',
            ])->get()->groupBy(['complex_code', 'floor']);
        }

        return response()->json([
            'items' => [
                'total' => $totalItems,
                'is_demo' => $totalDemo,
                'operasional' => $operasional,
                'terbatas' => $terbatas,
                'tidak_operasional' => $tidakOperasional,
                'per_category' => $perCategory,
            ],
            'rentals' => [
                'total' => $pengajuan,
                'pending' => $pending,
                'disetujui' => $disetujui,
                'selesai' => $selesai,
                'pendapatan_terkumpul' => $pendapatan,
                'pendapatan_pipeline' => $pendapatanPending,
            ],
            'room_matrix' => $roomMatrix,
        ]);
    }

    /**
     * Submit catatan inspeksi fisik alat berat pra & pasca operasi (Hour Meter + Kondisi).
     */
    public function submitInspection(Request $request, $id)
    {
        $validated = $request->validate([
            'inspection_type' => 'required|in:pre_operation,post_operation',
            'hour_meter_value' => 'required|numeric|min:0',
            'checklist' => 'nullable|array',
            'damage_notes' => 'nullable|string',
            'photos' => 'nullable|array',
            'inspector_gps_lat' => 'nullable|numeric',
            'inspector_gps_lng' => 'nullable|numeric',
        ]);

        $rental = AssetRental::findOrFail($id);
        $this->authorizeAssetScope($rental);

        $metadata = $rental->metadata ?? [];
        $inspections = $metadata['inspections'] ?? [];

        $record = [
            'type' => $validated['inspection_type'],
            'hour_meter' => (float) $validated['hour_meter_value'],
            'checklist' => $validated['checklist'] ?? [],
            'damage_notes' => $validated['damage_notes'] ?? null,
            'photos' => $validated['photos'] ?? [],
            'gps' => [
                'lat' => $validated['inspector_gps_lat'] ?? null,
                'lng' => $validated['inspector_gps_lng'] ?? null,
            ],
            'inspector_user_id' => auth()->id(),
            'inspector_name' => auth()->user()?->name ?? 'Petugas Lapangan',
            'submitted_at' => now()->toIso8601String(),
        ];

        $inspections[] = $record;
        $metadata['inspections'] = $inspections;

        // If post_operation, update actual_hours
        if ($validated['inspection_type'] === 'post_operation') {
            $pre = collect($inspections)->firstWhere('type', 'pre_operation');
            if ($pre && isset($pre['hour_meter'])) {
                $rental->actual_hours = max(0, (float) $validated['hour_meter_value'] - (float) $pre['hour_meter']);
            }
        }

        $rental->metadata = $metadata;
        $rental->save();

        return response()->json([
            'success' => true,
            'message' => 'Laporan inspeksi fisik alat berat berhasil disimpan.',
            'data' => [
                'rental_id' => $rental->id,
                'rental_code' => $rental->rental_code,
                'inspection' => $record,
                'actual_hours' => $rental->actual_hours,
            ],
        ]);
    }

    /**
     * Dapatkan dokumen e-SIPAB (Surat Izin Pemakaian Alat Berat) ber-QR dan TTE.
     */
    public function documentSipab($id)
    {
        $rental = AssetRental::with(['taxpayer', 'assetItem', 'approvedBy'])->findOrFail($id);
        $this->authorizeAssetScope($rental);

        $qrVerificationUrl = "https://mpad.baubaukota.go.id/verify/sipab/{$rental->rental_code}";

        return response()->json([
            'status' => 'success',
            'document_type' => 'e-SIPAB',
            'title' => 'Surat Izin Pemakaian Alat Berat',
            'data' => [
                'nomor_dokumen' => 'SIPAB/PUPR/' . date('Y') . '/' . $rental->rental_code,
                'nomor_kontrak' => $rental->nomor_kontrak,
                'rental_code' => $rental->rental_code,
                'pemohon' => [
                    'nama' => $rental->taxpayer?->name,
                    'nik' => $rental->taxpayer?->nik,
                    'telepon' => $rental->taxpayer?->phone,
                ],
                'alat' => [
                    'nama' => $rental->assetItem?->name,
                    'kode' => $rental->assetItem?->code,
                    'kategori' => $rental->assetItem?->category?->name ?? 'Alat Berat',
                ],
                'jadwal' => [
                    'mulai' => $rental->tanggal_mulai?->format('Y-m-d'),
                    'selesai' => $rental->tanggal_selesai?->format('Y-m-d'),
                    'lama_sewa' => $rental->lama_sewa . ' ' . $rental->satuan_sewa,
                ],
                'mobilisasi' => [
                    'include_tronton' => (bool) $rental->include_tronton,
                    'jarak_km' => $rental->jarak_tronton_km,
                    'biaya_tronton' => $rental->biaya_tronton,
                ],
                'lokasi_proyek' => $rental->lokasi_penggunaan,
                'status' => $rental->status,
                'pejabat_tte' => [
                    'nama' => $rental->approvedBy?->name ?? 'Kepala UPTD Workshop Dinas PUPR Kota Baubau',
                    'nip' => '197805122006041008',
                    'jabatan' => 'Kepala UPTD Workshop & Alat Berat',
                    'tte_status' => 'TTE_VERIFIED_BSRE',
                ],
                'qr_verification_url' => $qrVerificationUrl,
            ],
        ]);
    }

    /**
     * Dapatkan dokumen e-BAST (Berita Acara Serah Terima) ber-QR dan TTE.
     */
    public function documentBast($id)
    {
        $rental = AssetRental::with(['taxpayer', 'assetItem', 'approvedBy'])->findOrFail($id);
        $this->authorizeAssetScope($rental);

        $qrVerificationUrl = "https://mpad.baubaukota.go.id/verify/bast/{$rental->rental_code}";

        return response()->json([
            'status' => 'success',
            'document_type' => 'e-BAST',
            'title' => 'Berita Acara Serah Terima Pemakaian Alat Berat',
            'data' => [
                'nomor_dokumen' => 'BAST/PUPR/' . date('Y') . '/' . $rental->rental_code,
                'rental_code' => $rental->rental_code,
                'pihak_pertama' => [
                    'instansi' => 'UPTD Workshop Dinas PUPR Kota Baubau',
                    'pejabat' => $rental->approvedBy?->name ?? 'Pengelola Workshop BMD PUPR',
                ],
                'pihak_kedua' => [
                    'nama' => $rental->taxpayer?->name,
                    'nik' => $rental->taxpayer?->nik,
                ],
                'alat' => [
                    'nama' => $rental->assetItem?->name,
                    'kode' => $rental->assetItem?->code,
                ],
                'kondisi' => $rental->kondisi_pengembalian ?? 'Baik (Laik Operasi)',
                'actual_hours' => $rental->actual_hours,
                'inspeksi' => $rental->metadata['inspections'] ?? [],
                'status' => $rental->status,
                'qr_verification_url' => $qrVerificationUrl,
            ],
        ]);
    }
}
