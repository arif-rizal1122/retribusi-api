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
                    ->orWhereHas('taxpayer', fn ($t) => $t->where('name', 'like', "%{$s}%")->orWhere('nik', 'like', "%{$s}%"))
                    ->orWhereHas('assetItem', fn ($i) => $i->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
            });
        }

        $rentals = $query->orderByDesc('created_at')->paginate($request->input('per_page', 20));

        return response()->json($rentals);
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
     * Catat pengembalian aset (Disetujui → Selesai + kondisi + denda).
     */
    public function complete(Request $request, $id)
    {
        $validated = $request->validate([
            'tanggal_pengembalian' => 'required|date',
            'kondisi_pengembalian' => 'required|in:Baik,Rusak Ringan,Rusak Berat,Hilang',
            'catatan' => 'nullable|string',
            'denda' => 'nullable|numeric|min:0',
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
        $query = AssetItem::with('category:id,name,slug')->with('opd:id,name');
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('status')) {
            $query->where('status_operasional', $request->input('status'));
        }
        $items = $query->orderBy('name')->paginate($request->input('per_page', 20));
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
        ]);

        $item = AssetItem::create(array_merge($validated, ['is_demo' => false, 'is_active' => true]));
        return response()->json(['message' => 'Unit aset ditambahkan.', 'data' => $item], 201);
    }

    public function updateItem(Request $request, $id)
    {
        $item = AssetItem::findOrFail($id);
        $item->update($request->only([
            'category_id', 'name', 'code', 'merk_type', 'spesifikasi', 'kondisi',
            'status_operasional', 'lokasi', 'pic', 'tarif_default', 'satuan_tarif',
            'opd_id', 'is_active', 'is_demo',
        ]));
        return response()->json(['message' => 'Unit aset diperbarui.', 'data' => $item]);
    }

    public function deleteItem($id)
    {
        $item = AssetItem::findOrFail($id);
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
        $totalItems = AssetItem::where('is_active', true)->count();
        $totalDemo = AssetItem::where('is_active', true)->where('is_demo', true)->count();
        $operasional = AssetItem::where('is_active', true)->where('status_operasional', 'Operasional')->count();
        $terbatas = AssetItem::where('is_active', true)->where('status_operasional', 'Terbatas')->count();
        $tidakOperasional = AssetItem::where('is_active', true)->where('status_operasional', 'Tidak Operasional')->count();

        $pengajuan = AssetRental::count();
        $pending = AssetRental::whereIn('status', ['Diajukan', 'Diverifikasi'])->count();
        $disetujui = AssetRental::where('status', 'Disetujui')->count();
        $selesai = AssetRental::where('status', 'Selesai')->count();
        $pendapatan = (float) AssetRental::where('status', 'Selesai')->sum('total_biaya');
        $pendapatanPending = (float) AssetRental::whereIn('status', ['Disetujui', 'Diverifikasi'])->sum('total_biaya');

        $perCategory = AssetItem::select('category_id', DB::raw('count(*) as total'))
            ->where('is_active', true)
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
}
