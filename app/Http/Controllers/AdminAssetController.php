<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\AssetItem;
use App\Models\AssetRental;
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
        ]);

        $rental = AssetRental::findOrFail($id);
        $this->authorizeAssetScope($rental);

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
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan hasil survey kelayakan lapangan berhasil disimpan.',
            'data' => $rental->load(['surveyor:id,name', 'assetItem:id,name,code']),
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

        $rental = AssetRental::findOrFail($id);
        $this->authorizeAssetScope($rental);

        $rental->update([
            'nomor_kontrak' => $validated['nomor_kontrak'],
            'tahap_1_amount' => $validated['tahap_1_amount'],
            'tahap_2_amount' => $validated['tahap_2_amount'] ?? 0,
            'tahap_1_status' => 'Pending',
            'tahap_2_status' => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kontrak sewa dan termin pembayaran berhasil diterbitkan.',
            'data' => $rental,
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
        $rental = AssetRental::findOrFail($id);
        $this->authorizeAssetScope($rental);
        if (!in_array($rental->status, ['Diajukan', 'Diverifikasi'])) {
            return response()->json(['message' => 'Hanya pengajuan Diajukan/Diverifikasi yang bisa disetujui.'], 422);
        }

        $rental->update([
            'status' => 'Disetujui',
            'tanggal_approval' => now(),
            'catatan_approval' => $request->input('catatan'),
            'approved_by_user_id' => auth()->id(),
        ]);

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

        $rental = AssetRental::findOrFail($id);
        $this->authorizeAssetScope($rental);
        if (!in_array($rental->status, ['Disetujui', 'Diverifikasi'])) {
            return response()->json(['message' => 'Pengajuan belum disetujui, tidak bisa dicatat pengembalian.'], 422);
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
