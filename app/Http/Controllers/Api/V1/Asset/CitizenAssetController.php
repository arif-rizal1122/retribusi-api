<?php

namespace App\Http\Controllers\Api\V1\Asset;

use App\Http\Controllers\Controller;
use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CitizenAssetController extends Controller
{
    /**
     * Daftar kategori aset untuk daftar dropdown wajib pajak.
     * Source: asset_items.category (alat-berat, kendaraan, sedot-kakus).
     */
    public function categories(Request $request)
    {
        $categories = AssetItem::withoutGlobalScopes()
            ->where('is_active', true)
            ->select('category')
            ->distinct()
            ->whereNotNull('category')
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return response()->json([
            'data' => $categories->map(fn (string $category) => [
                'id' => $category,
                'name' => str_replace('-', ' ', ucfirst($category)),
                'code' => $category,
            ]),
        ]);
    }

    /**
     * Daftar item aset berdasarkan kategori yang dipilih.
     */
    public function items(Request $request)
    {
        $query = AssetItem::withoutGlobalScopes()
            ->active()
            ->with('opd:id,name,code')
            ->where('status_operasional', 'Tersedia');

        if ($request->has('category_id')) {
            $categoryId = $request->category_id;
            $query->where('category', $categoryId);
        }

        return response()->json([
            'data' => $query->orderBy('name')->get(),
        ]);
    }

    /**
     * Template form pemesanan (dropdown retribusi, default tarif, spesialisasi Sedot Kakus).
     */
    public function formTemplate(Request $request)
    {
        $user = $request->user();
        $opdId = $user?->opd_id;

        $types = RetributionType::withoutGlobalScopes()
            ->where('opd_id', $opdId)
            ->get();

        $classifications = RetributionClassification::withoutGlobalScopes()
            ->where('opd_id', $opdId)
            ->get();

        $defaultHours = [
            'alat-berat' => ['lama_sewa' => '1', 'satuan_sewa' => 'per jam'],
            'kendaraan' => ['lama_sewa' => '1', 'satuan_sewa' => 'per jam'],
            'sedot-kakus' => ['lama_sewa' => '1', 'satuan_sewa' => 'per rit'],
        ];

        return response()->json([
            'data' => [
                'retribution_types' => $types,
                'retribution_classifications' => $classifications,
                'default_hours' => $defaultHours,
                'checklist_keys' => [
                    'engine_oil',
                    'hydraulic_system',
                    'track_tires',
                    'brakes_steering',
                    'safety_cabin_k3',
                ],
            ],
        ]);
    }

    /**
     * Cek ketersediaan unit & perhitungan awal biaya sewa.
     */
    public function availability(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:asset_items,id',
            'category' => 'required|string',
            'lama_sewa' => 'required|numeric|min:0.5',
            'satuan_sewa' => 'required|in:per jam,per hari,per rit',
        ]);

        $item = AssetItem::withoutGlobalScopes()->find($validated['item_id']);

        $isAvailable = in_array($item->status_operasional, ['Tersedia']);

        $items = AssetItem::withoutGlobalScopes()
            ->where('category', $validated['category'])
            ->active()
            ->get();

        return response()->json([
            'is_available' => $isAvailable,
            'item' => $item,
            'items' => $items,
        ]);
    }

    /**
     * Wajib pajak mengajukan pemesanan sewa aset.
     */
    public function storeRental(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:asset_items,id',
            'taxpayer_id' => 'required|exists:taxpayers,id',
            'opd_id' => 'required|exists:opds,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'lama_sewa' => 'required|string|max:20',
            'satuan_sewa' => 'required|in:per jam,per hari,per rit',
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
            'lokasi_penggunaan' => 'nullable|string|max:255',
            'jenis_pekerjaan' => 'nullable|string|max:100',
            'nama_proyek' => 'nullable|string|max:150',
            'koordinat' => 'nullable|string|max:100',
            'keterangan_tambahan' => 'nullable|string',
            'include_tronton' => 'nullable|boolean',
            'jarak_tronton_km' => 'nullable|numeric|min:0',
        ]);

        $item = AssetItem::withoutGlobalScopes()->findOrFail($validated['item_id']);
        $tarif = (float) $item->tarif;
        $lama = (float) str_replace(',', '.', $validated['lama_sewa']);
        $satuan = $validated['satuan_sewa'];

        if ($satuan === 'per rit') {
            $totalBiaya = $tarif * $lama;
        } else {
            $totalBiaya = $tarif * $lama;
        }

        $rentalCode = 'SEWA-' . strtoupper(Str::random(8));

        if (empty($validated['retribution_type_id'])) {
            $validated['retribution_type_id'] = RetributionType::withoutGlobalScopes()
                ->where('opd_id', $validated['opd_id'])
                ->where('name', 'like', '%Sewa Alat Berat%')
                ->value('id');
        }

        $rental = AssetRental::create([
            'rental_code' => $rentalCode,
            'nomor_kontrak' => $validated['nomor_spk'] ?? null,
            'taxpayer_id' => $validated['taxpayer_id'],
            'user_id' => $request->user()?->id,
            'asset_item_id' => $validated['item_id'],
            'opd_id' => $validated['opd_id'],
            'retribution_type_id' => $validated['retribution_type_id'] ?? null,
            'retribution_classification_id' => $validated['retribution_classification_id'] ?? null,
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
            'lama_sewa' => $validated['lama_sewa'],
            'satuan_sewa' => $satuan,
            'tarif_per_satuan' => $tarif,
            'include_tronton' => $validated['include_tronton'] ?? false,
            'jarak_tronton_km' => $validated['jarak_tronton_km'] ?? null,
            'total_biaya' => $totalBiaya,
            'dp' => 0,
            'sisa_pembayaran' => $totalBiaya,
            'status' => 'pending_verification',
            'lokasi_penggunaan' => $validated['lokasi_penggunaan'] ?? null,
            'jenis_pekerjaan' => $validated['jenis_pekerjaan'] ?? null,
            'nama_proyek' => $validated['nama_proyek'] ?? null,
            'koordinat' => $validated['koordinat'] ?? null,
            'penyelia' => $validated['penyelia'] ?? null,
            'hp_penyelia' => $validated['hp_penyelia'] ?? null,
            'operator_nama' => $validated['operator_nama'] ?? null,
            'operator_hp' => $validated['operator_hp'] ?? null,
            'operator_sim' => $validated['operator_sim'] ?? null,
            'nomor_spk' => $validated['nomor_spk'] ?? null,
            'nama_konsumen' => $validated['nama_konsumen'] ?? null,
            'jenis_bangunan' => $validated['jenis_bangunan'] ?? null,
            'jumlah_rit' => $validated['jumlah_rit'] ?? null,
            'kelurahan' => $validated['kelurahan'] ?? null,
            'pelaksana_armada' => $validated['pelaksana_armada'] ?? null,
            'keterangan_tambahan' => $validated['keterangan_tambahan'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $rental->load(['assetItem:id,name,code', 'opd:id,name']),
            'message' => 'Pemesanan sewa aset berhasil diajukan.',
        ], 201);
    }
}