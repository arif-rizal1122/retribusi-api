<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\Taxpayer;
use App\Rules\Wgs84Point;
use App\Services\AssetAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CitizenAssetController extends Controller
{
    protected AssetAvailabilityService $availabilityService;

    public function __construct(AssetAvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }
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
                'image_url' => $i->image_url,
                'pic' => $i->pic,
                'wajib_tronton' => (bool) $i->wajib_tronton,
                'metadata' => $i->metadata,
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
     * Cek status ketersediaan kalender unit aset pada bulan tertentu.
     */
    public function availability(Request $request, $id)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $asset = AssetItem::findOrFail($id);
        $data = $this->availabilityService->getMonthAvailability($asset, $request->input('month'));

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Ajukan sewa / pemanfaatan aset (dengan pessimistic locking & validasi buffer).
     */
    public function storeRental(Request $request)
    {
        $taxpayer = $this->citizenTaxpayer($request);
        $validated = $request->validate([
            'asset_item_id' => 'required|exists:asset_items,id',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'satuan_sewa' => 'nullable|string',
            'tarif_per_satuan' => 'nullable|numeric|min:0',
            'dp' => 'nullable|numeric|min:0',
            'metode_pembayaran' => 'nullable|string',
            'lokasi_penggunaan' => 'required|string',
            'koordinat' => ['bail', 'nullable', 'string', 'max:100', new Wgs84Point],
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
            'include_tronton' => 'nullable|boolean',
            'jarak_tronton_km' => 'nullable|numeric|min:0',
            'biaya_tronton' => 'nullable|numeric|min:0',
            // Simple Sedot Kakus Google Form fields per Perda 1/2024
            'nama_konsumen' => 'nullable|string',
            'jenis_bangunan' => 'nullable|string',
            'jumlah_rit' => 'nullable|integer|min:1',
            'kelurahan' => 'nullable|string',
            'pelaksana_armada' => 'nullable|string',
            'foto_lokasi' => 'nullable|string',
            'bukti_pembayaran_manual' => 'nullable|string',
        ]);

        $mulai = Carbon::parse($validated['tanggal_mulai'])->startOfDay();
        $selesai = !empty($validated['tanggal_selesai'])
            ? Carbon::parse($validated['tanggal_selesai'])->startOfDay()
            : $mulai->copy();

        return DB::transaction(function () use ($validated, $taxpayer, $mulai, $selesai) {
            $asset = AssetItem::where('id', $validated['asset_item_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (!$asset->is_active || $asset->kondisi === 'Tidak Operasional' || $asset->status_operasional !== 'Operasional') {
                return response()->json([
                    'success' => false,
                    'message' => 'Alat berat sedang dalam perbaikan / tidak dapat disewa saat ini.',
                ], 422);
            }

            $bufferHari = (int) ($asset->buffer_default ?? 1);
            $blockedStart = $mulai->copy()->subDays($bufferHari)->toDateString();
            $blockedEnd = $selesai->copy()->addDays($bufferHari)->toDateString();

            $hasConflict = AssetRental::where('asset_item_id', $asset->id)
                ->whereIn('status', ['Diajukan', 'Diverifikasi', 'Disetujui'])
                ->where(function ($query) use ($blockedStart, $blockedEnd) {
                    $query->whereDate('tanggal_mulai', '<=', $blockedEnd)
                        ->where(function ($sub) use ($blockedStart) {
                            $sub->whereDate('tanggal_selesai', '>=', $blockedStart)
                                ->orWhere(function ($s2) use ($blockedStart) {
                                    $s2->whereNull('tanggal_selesai')
                                        ->whereDate('tanggal_mulai', '>=', $blockedStart);
                                });
                        });
                })
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                $durationDays = $mulai->diffInDays($selesai) + 1;
                $nextAvailable = $this->availabilityService->findNextAvailableDate($asset->id, $mulai, $durationDays);

                return response()->json([
                    'success' => false,
                    'error_code' => 'SCHEDULE_CONFLICT',
                    'message' => 'Jadwal yang Anda pilih bentrok dengan penyewaan lain atau masa jeda mobilisasi alat (1 hari).',
                    'suggested_dates' => $nextAvailable,
                ], 422);
            }

            $days = $mulai->diffInDays($selesai) + 1;
            $tarifPerSatuan = isset($validated['tarif_per_satuan']) && $validated['tarif_per_satuan'] !== null
                ? (float) $validated['tarif_per_satuan']
                : (float) $asset->tarif_default;
            $satuanSewa = $validated['satuan_sewa'] ?? ($asset->satuan_tarif ?? 'per hari');

            // Logika Tronton: Wajib untuk alat beroda rantai besi (Excavator/Bulldozer/Roller)
            $isWajibTronton = (bool) ($asset->wajib_tronton ?? false);
            $includeTronton = $isWajibTronton || (bool) ($validated['include_tronton'] ?? false);
            $jarakKm = isset($validated['jarak_tronton_km']) ? (float) $validated['jarak_tronton_km'] : 10.0;
            $biayaTronton = 0;

            if ($includeTronton) {
                if (isset($validated['biaya_tronton']) && (float) $validated['biaya_tronton'] > 0) {
                    $biayaTronton = (float) $validated['biaya_tronton'];
                } else {
                    // Zonasi Tarif Perda 1/2024
                    if ($jarakKm <= 20) {
                        $biayaTronton = 450000;
                    } elseif ($jarakKm <= 40) {
                        $biayaTronton = 900000;
                    } elseif ($jarakKm <= 60) {
                        $biayaTronton = 1350000;
                    } elseif ($jarakKm <= 80) {
                        $biayaTronton = 1800000;
                    } elseif ($jarakKm <= 100) {
                        $biayaTronton = 2700000;
                    } else {
                        $biayaTronton = 2250000;
                    }
                }
            }

            $isSedotKakus = str_starts_with($asset->code ?? '', 'SK-')
                || str_contains(strtolower($asset->name ?? ''), 'tinja')
                || str_contains(strtolower($asset->name ?? ''), 'kakus')
                || str_contains(strtolower($satuanSewa), 'rit');

            if ($isSedotKakus && !empty($validated['jumlah_rit'])) {
                $rit = (int) $validated['jumlah_rit'];
                $totalBiaya = $rit * $tarifPerSatuan;
                $days = 1;
                $satuanSewa = 'per rit';
            } else {
                $totalBiaya = ($days * $tarifPerSatuan) + $biayaTronton;
            }

            $dp = (float) ($validated['dp'] ?? 0);
            $sisaPembayaran = max(0, $totalBiaya - $dp);

            $asetType = $this->jasaUsahaType();
            $asetClassification = $this->jasaUsahaClassification();

            $prefix = $isSedotKakus ? 'KKS-' : 'AST-';
            $rentalCode = $prefix . now()->format('Ym') . '-' . strtoupper(Str::random(5));

            $metadata = [
                'source' => 'citizen_submission',
                'item_code' => $asset->code,
                'is_wajib_tronton' => $isWajibTronton,
            ];

            // The old koordinat column was removed; retain the citizen point in JSON.
            // polygon_geom represents a boundary and must not receive a point.
            if (isset($validated['koordinat'])) {
                $metadata['koordinat'] = implode(',', array_map('trim', explode(',', $validated['koordinat'])));
            }

            if ($isSedotKakus) {
                $metadata['is_sedot_kakus'] = true;
                $metadata['nama_konsumen'] = $validated['nama_konsumen'] ?? $taxpayer->name;
                $metadata['jenis_bangunan'] = $validated['jenis_bangunan'] ?? 'Rumah Tinggal';
                $metadata['jumlah_rit'] = (int) ($validated['jumlah_rit'] ?? 1);
                $metadata['kelurahan'] = $validated['kelurahan'] ?? null;
                $metadata['pelaksana_armada'] = $validated['pelaksana_armada'] ?? null;
                $metadata['foto_lokasi'] = $validated['foto_lokasi'] ?? null;
                $metadata['bukti_pembayaran_manual'] = $validated['bukti_pembayaran_manual'] ?? null;
            }

            $rental = AssetRental::create([
                'rental_code' => $rentalCode,
                'taxpayer_id' => $taxpayer->id,
                'asset_item_id' => $asset->id,
                'retribution_type_id' => $asetType?->id ?? 106,
                'retribution_classification_id' => $asetClassification?->id ?? 211,
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => !empty($validated['tanggal_selesai']) ? $selesai->toDateString() : null,
                'lama_sewa' => $days,
                'satuan_sewa' => $satuanSewa,
                'buffer_hari' => $bufferHari,
                'tarif_per_satuan' => $tarifPerSatuan,
                'include_tronton' => $includeTronton,
                'jarak_tronton_km' => $includeTronton ? $jarakKm : null,
                'biaya_tronton' => $includeTronton ? $biayaTronton : null,
                'total_biaya' => $totalBiaya,
                'dp' => $dp,
                'sisa_pembayaran' => $sisaPembayaran,
                'metode_pembayaran' => $validated['metode_pembayaran'] ?? 'Transfer Bank',
                'status' => 'Diajukan',
                'lokasi_penggunaan' => $validated['lokasi_penggunaan'],
                'jenis_pekerjaan' => $validated['jenis_pekerjaan'] ?? ($isSedotKakus ? 'Penyedotan Kakus / Tinja' : null),
                'luas_volume' => $validated['luas_volume'] ?? ($isSedotKakus ? ($validated['jumlah_rit'] ?? 1) . ' Rit' : null),
                'nama_proyek' => $validated['nama_proyek'] ?? ($isSedotKakus ? 'Layanan Sedot Kakus ' . ($validated['jenis_bangunan'] ?? '') : null),
                'nomor_spk' => $validated['nomor_spk'] ?? null,
                'penyelia' => $validated['penyelia'] ?? null,
                'hp_penyelia' => $validated['hp_penyelia'] ?? null,
                'operator_nama' => $validated['operator_nama'] ?? null,
                'operator_hp' => $validated['operator_hp'] ?? null,
                'operator_sim' => $validated['operator_sim'] ?? null,
                'dokumen' => $validated['dokumen'] ?? null,
                'is_demo' => $asset->is_demo ?? false,
                'metadata' => $metadata,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permohonan sewa berhasil diajukan. Petugas PUPR akan memverifikasi permohonan Anda.',
                'data' => $this->citizenRentalPayload($rental->load('assetItem:id,name,code')),
            ], 201);
        });
    }

    /**
     * Alias untuk storeRental.
     */
    public function store(Request $request)
    {
        return $this->storeRental($request);
    }

    /**
     * Riwayat pengajuan sewa user (real-time).
     */
    public function myRentals(Request $request)
    {
        $taxpayer = $this->citizenTaxpayer($request);
        $rentals = AssetRental::with(['assetItem:id,name,code', 'surveyor:id,name'])
            ->where('taxpayer_id', $taxpayer->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $rentals->map(fn (AssetRental $rental) => $this->citizenRentalPayload($rental))]);
    }

    /**
     * Detail pengajuan sewa.
     */
    public function showRental(Request $request, $id)
    {
        $taxpayer = $this->citizenTaxpayer($request);
        $rental = AssetRental::with(['assetItem:id,name,code,category_id', 'surveyor:id,name', 'verifiedBy:id,name', 'approvedBy:id,name'])
            ->where('id', $id)
            ->where('taxpayer_id', $taxpayer->id)
            ->firstOrFail();

        return response()->json(['data' => $this->citizenRentalPayload($rental)]);
    }

    private function citizenTaxpayer(Request $request): Taxpayer
    {
        $taxpayer = $request->user();
        abort_unless($taxpayer instanceof Taxpayer, 403, 'Endpoint ini hanya untuk wajib pajak.');

        return $taxpayer;
    }

    private function citizenRentalPayload(AssetRental $rental): array
    {
        $payload = $rental->toArray();
        $payload['koordinat'] = $rental->metadata['koordinat'] ?? null;
        // Calendar days have no UTC offset. Preserve timestamps for survey/approval events.
        foreach (['tanggal_mulai', 'tanggal_selesai', 'tanggal_pengembalian'] as $field) {
            $payload[$field] = $rental->{$field}?->toDateString();
        }

        return $payload;
    }

    /**
     * Type JU-ASET yang SUDAH ADA di prod: "Retribusi Jasa Usaha" (id 106, OPD BAPENDA).
     * Fallback: cari type lain bernama aset/jasa usaha.
     */
    private function jasaUsahaType()
    {
        $type = DB::table('retribution_types')
            ->where('name', 'Retribusi Jasa Usaha')
            ->first();
        if ($type) {
            return $type;
        }
        return DB::table('retribution_types')
            ->where('name', 'like', '%Aset%')
            ->orWhere('name', 'like', '%Jasa Usaha%')
            ->first();
    }

    /**
     * Classification "Pemanfaatan Aset Daerah" (id 211) di bawah type JU-ASET.
     */
    private function jasaUsahaClassification()
    {
        return DB::table('retribution_classifications')
            ->where('name', 'Pemanfaatan Aset Daerah')
            ->first();
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
                    ['key' => 'ktp', 'label' => 'KTP', 'type' => 'file', 'required' => false],
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
