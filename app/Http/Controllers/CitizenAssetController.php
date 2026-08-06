<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\AssetItem;
use App\Models\AssetRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CitizenAssetController extends Controller
{
    /**
     * Daftar kategori aset (1 layanan JU-ASET, field pertama = dropdown kategori).
     */
    public function categories()
    {
        $categories = AssetCategory::withCount('items')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'icon' => $c->icon,
                'item_count' => $c->items_count,
            ]);

        return response()->json(['data' => $categories]);
    }

    /**
     * Daftar unit aset per kategori — real-time dari DB.
     */
    public function items(Request $request)
    {
        $query = AssetItem::with('category:id,name,slug')
            ->where('is_active', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('status')) {
            $query->where('status_operasional', $request->input('status'));
        }

        $items = $query->orderBy('name')
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->name,
                'code' => $i->code,
                'merk_type' => $i->merk_type,
                'spesifikasi' => $i->spesifikasi,
                'kondisi' => $i->kondisi,
                'status_operasional' => $i->status_operasional,
                'lokasi' => $i->lokasi,
                'tarif' => (float) $i->tarif_default,
                'satuan_tarif' => $i->satuan_tarif,
                'is_demo' => $i->is_demo,
                'category' => $i->category?->name,
            ]);

        return response()->json(['data' => $items]);
    }

    /**
     * Template form dinamis per kategori (JSON schema).
     */
    public function formTemplate(Request $request, $categoryId)
    {
        $category = AssetCategory::findOrFail($categoryId);

        // Template form default — disimpan real-time, bisa diedit admin tanpa deploy.
        // Struktur per kategori mengikuti Matriks Form Isian Retribusi (190 field).
        $templates = $this->defaultTemplates();
        $template = $templates[$category->slug] ?? $templates['umum'];

        return response()->json([
            'category' => $category->name,
            'template' => $template,
        ]);
    }

    /**
     * Ajukan sewa / pemanfaatan aset.
     */
    public function storeRental(Request $request)
    {
        $validated = $request->validate([
            'asset_item_id' => 'required|exists:asset_items,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'satuan_sewa' => 'required|string',
            'tarif_per_satuan' => 'required|numeric|min:0',
            'dp' => 'nullable|numeric|min:0',
            'metode_pembayaran' => 'required|string',
            'lokasi_penggunaan' => 'nullable|string',
            'koordinat' => 'nullable|string',
            'jenis_pekerjaan' => 'nullable|string',
            'luas_volume' => 'nullable|string',
            'nama_proyek' => 'nullable|string',
            'nomor_spk' => 'nullable|string',
            'penyelia' => 'nullable|string',
            'hp_penyelia' => 'nullable|string',
            'operator_nama' => 'nullable|string',
            'operator_hp' => 'nullable|string',
            'operator_sim' => 'nullable|string',
            'dokumen' => 'nullable|array',
        ]);

        $item = AssetItem::findOrFail($validated['asset_item_id']);
        if (!$item->is_active) {
            return response()->json(['message' => 'Aset tidak aktif.'], 422);
        }
        if ($item->status_operasional !== 'Operasional') {
            return response()->json(['message' => 'Aset berstatus ' . $item->status_operasional . '. Hubungi admin untuk ketersediaan.'], 422);
        }

        // Hitung lama sewa & total otomatis (real-time)
        $start = \Carbon\Carbon::parse($validated['tanggal_mulai']);
        $end = $validated['tanggal_selesai']
            ? \Carbon\Carbon::parse($validated['tanggal_selesai'])
            : $start;
        $satuan = $validated['satuan_sewa'];
        $lamaSewa = match ($satuan) {
            'per jam' => max(1, (int) ceil($start->diffInHours($end))),
            'per hari' => max(1, (int) ceil($start->diffInDays($end)) + 1),
            'per bulan' => max(1, (int) ceil($start->diffInMonths($end)) + 1),
            default => 1,
        };
        $tarif = (float) $validated['tarif_per_satuan'];
        $total = $lamaSewa * $tarif;
        $dp = (float) ($validated['dp'] ?? 0);
        $sisa = max(0, $total - $dp);

        // Guard 'citizens' → provider taxpayers: $request->user() adalah Taxpayer langsung
        $taxpayer = $request->user();

        $rental = AssetRental::create([
            'rental_code' => 'AST-' . strtoupper(Str::random(6)) . '-' . now()->format('Ymd'),
            'taxpayer_id' => $taxpayer->id,
            'asset_item_id' => $item->id,
            'retribution_type_id' => $item->category_id ? $this->jasaUsahaTypeId() : null,
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
            'lama_sewa' => $lamaSewa,
            'satuan_sewa' => $satuan,
            'tarif_per_satuan' => $tarif,
            'total_biaya' => $total,
            'dp' => $dp,
            'sisa_pembayaran' => $sisa,
            'metode_pembayaran' => $validated['metode_pembayaran'],
            'status' => 'Diajukan',
            'lokasi_penggunaan' => $validated['lokasi_penggunaan'] ?? null,
            'koordinat' => $validated['koordinat'] ?? null,
            'jenis_pekerjaan' => $validated['jenis_pekerjaan'] ?? null,
            'luas_volume' => $validated['luas_volume'] ?? null,
            'nama_proyek' => $validated['nama_proyek'] ?? null,
            'nomor_spk' => $validated['nomor_spk'] ?? null,
            'penyelia' => $validated['penyelia'] ?? null,
            'hp_penyelia' => $validated['hp_penyelia'] ?? null,
            'operator_nama' => $validated['operator_nama'] ?? null,
            'operator_hp' => $validated['operator_hp'] ?? null,
            'operator_sim' => $validated['operator_sim'] ?? null,
            'dokumen' => $validated['dokumen'] ?? null,
            'is_demo' => $item->is_demo,
            'metadata' => ['source' => 'citizen_submission', 'item_code' => $item->code],
        ]);

        return response()->json([
            'message' => 'Pengajuan sewa aset berhasil dibuat.',
            'data' => $rental->load('assetItem:id,name,code'),
        ], 201);
    }

    /**
     * Riwayat pengajuan sewa user (real-time).
     */
    public function myRentals(Request $request)
    {
        $taxpayer = $request->user();
        $rentals = AssetRental::with('assetItem:id,name,code')
            ->where('taxpayer_id', $taxpayer->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $rentals]);
    }

    /**
     * Detail pengajuan sewa.
     */
    public function showRental(Request $request, $id)
    {
        $taxpayer = $request->user();
        $rental = AssetRental::with('assetItem:id,name,code,category_id')
            ->where('id', $id)
            ->where('taxpayer_id', $taxpayer->id)
            ->firstOrFail();

        return response()->json(['data' => $rental]);
    }

    /**
     * Cari retribution type Jasa Usaha (JU-ASET). Fallback null jika belum dibuat.
     */
    private function jasaUsahaTypeId()
    {
        return DB::table('retribution_types')
            ->where('name', 'like', '%Aset%')
            ->orWhere('name', 'like', '%Jasa Usaha%')
            ->value('id');
    }

    private function defaultTemplates(): array
    {
        // Struktur default — akan di-upgrade ke JSON template lengkap (ASET-R4)
        return [
            'umum' => [
                'informasi_peminjam' => [
                    ['key' => 'nama_peminjam', 'label' => 'Nama Peminjam', 'type' => 'text', 'required' => true],
                    ['key' => 'jenis_peminjam', 'label' => 'Jenis Peminjam', 'type' => 'select', 'options' => ['Perorangan', 'Instansi Pemerintah', 'Instansi Swasta', 'LSM'], 'required' => true],
                    ['key' => 'nik_npwp', 'label' => 'NIK/NPWP', 'type' => 'text', 'required' => true],
                    ['key' => 'no_hp', 'label' => 'No. HP', 'type' => 'tel', 'required' => true],
                    ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                    ['key' => 'alamat', 'label' => 'Alamat Lengkap', 'type' => 'textarea', 'required' => true],
                ],
                'dokumen_pendukung' => [
                    ['key' => 'surat_permohonan', 'label' => 'Surat Permohonan', 'type' => 'file', 'required' => true],
                    ['key' => 'ktp', 'label' => 'KTP', 'type' => 'file', 'required' => true],
                    ['key' => 'npwp', 'label' => 'NPWP', 'type' => 'file', 'required' => false],
                ],
            ],
            'alat-berat' => [
                'informasi_alat' => [
                    ['key' => 'jenis_alat', 'label' => 'Jenis Alat', 'type' => 'select', 'options' => ['Excavator', 'Wheel Loader', 'Bulldozer', 'Tandem Roller', 'Backhoe Loader'], 'required' => true],
                    ['key' => 'nopol', 'label' => 'Nomor Polisi', 'type' => 'text', 'required' => true],
                    ['key' => 'merk_type', 'label' => 'Merk/Type', 'type' => 'text', 'required' => true],
                    ['key' => 'kapasitas', 'label' => 'Kapasitas/HP', 'type' => 'text', 'required' => false],
                    ['key' => 'kondisi', 'label' => 'Kondisi Alat', 'type' => 'select', 'options' => ['Baik', 'Terbatas', 'Tidak Operasional'], 'required' => true],
                ],
                'informasi_penggunaan' => [
                    ['key' => 'lokasi_proyek', 'label' => 'Lokasi Proyek', 'type' => 'textarea', 'required' => true],
                    ['key' => 'jenis_pekerjaan', 'label' => 'Jenis Pekerjaan', 'type' => 'select', 'options' => ['Pengerasan Jalan', 'Drainase', 'Bangunan', 'Lainnya'], 'required' => true],
                    ['key' => 'luas_volume', 'label' => 'Luas/Volume', 'type' => 'text', 'required' => false],
                    ['key' => 'nama_proyek', 'label' => 'Nama Proyek', 'type' => 'text', 'required' => false],
                    ['key' => 'nomor_spk', 'label' => 'No. SPK/Kontrak', 'type' => 'text', 'required' => false],
                ],
            ],
            'kendaraan' => [
                'informasi_kendaraan' => [
                    ['key' => 'jenis_kendaraan', 'label' => 'Jenis Kendaraan', 'type' => 'select', 'options' => ['Dump Truck', 'Flat Deck', 'Tronton', 'Sedot Tinja'], 'required' => true],
                    ['key' => 'nopol', 'label' => 'Nomor Polisi', 'type' => 'text', 'required' => true],
                    ['key' => 'merk_type', 'label' => 'Merk/Type', 'type' => 'text', 'required' => true],
                ],
                'informasi_operator' => [
                    ['key' => 'nama_operator', 'label' => 'Nama Operator', 'type' => 'text', 'required' => false],
                    ['key' => 'hp_operator', 'label' => 'No. HP Operator', 'type' => 'tel', 'required' => false],
                    ['key' => 'sim_operator', 'label' => 'SIM Operator', 'type' => 'text', 'required' => false],
                ],
            ],
            'tanah' => [
                'informasi_tanah' => [
                    ['key' => 'luas', 'label' => 'Luas (m2)', 'type' => 'number', 'required' => true],
                    ['key' => 'lokasi', 'label' => 'Lokasi', 'type' => 'textarea', 'required' => true],
                    ['key' => 'sertifikat', 'label' => 'No. Sertifikat/HGB', 'type' => 'text', 'required' => false],
                ],
            ],
            'bangunan' => [
                'informasi_bangunan' => [
                    ['key' => 'luas_bangunan', 'label' => 'Luas Bangunan (m2)', 'type' => 'number', 'required' => true],
                    ['key' => 'peruntukan', 'label' => 'Peruntukan', 'type' => 'text', 'required' => false],
                    ['key' => 'jumlah_ruang', 'label' => 'Jumlah Ruang', 'type' => 'number', 'required' => false],
                ],
            ],
        ];
    }
}
