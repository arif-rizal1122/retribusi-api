<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\RusunRoom;
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
    public function categories(Request $request)
    {
        $this->citizenTaxpayer($request);

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
        $this->citizenTaxpayer($request);

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
        $this->citizenTaxpayer($request);

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
        $this->citizenTaxpayer($request);

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
            'lokasi_penggunaan' => 'nullable|string',
            'koordinat' => ['bail', 'nullable', 'string', 'max:100', new Wgs84Point],
            'room_id' => 'nullable|integer',
            'is_guarantee_paid' => 'nullable|boolean',
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
            // Simple Sedot Kakus Google Form fields per Perda 1/2024 & Smart Screening
            'nama_konsumen' => 'nullable|string',
            'jenis_bangunan' => 'nullable|string',
            'jumlah_rit' => 'nullable|integer|min:1',
            'kelurahan' => 'nullable|string',
            'pelaksana_armada' => 'nullable|string',
            'foto_lokasi' => 'nullable|string',
            'bukti_pembayaran_manual' => 'nullable|string',
            'screening_akses_jalan' => 'nullable|string|in:lebar_truk,sempit_motor',
            'screening_jarak_selang' => 'nullable|string|in:kurang_20m,20_sampai_40m,lebih_50m',
            'screening_kondisi_lubang' => 'nullable|string|in:sudah_terbuka,bisa_dibuka,tertimbun_semen',
        ]);

        $mulai = Carbon::parse($validated['tanggal_mulai'])->startOfDay();
        $selesai = ! empty($validated['tanggal_selesai'])
            ? Carbon::parse($validated['tanggal_selesai'])->startOfDay()
            : $mulai->copy();

        return DB::transaction(function () use ($validated, $taxpayer, $mulai, $selesai) {
            $asset = AssetItem::where('id', $validated['asset_item_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $asset->is_active || $asset->kondisi === 'Tidak Operasional' || $asset->status_operasional !== 'Operasional') {
                return response()->json([
                    'success' => false,
                    'message' => 'Alat berat sedang dalam perbaikan / tidak dapat disewa saat ini.',
                ], 422);
            }

            $isRusunawa = str_starts_with((string) ($asset->code ?? ''), 'RUSUN-');
            $roomId = null;
            $isGuaranteePaid = (bool) ($validated['is_guarantee_paid'] ?? false);

            if ($isRusunawa) {
                if (empty($validated['room_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Silakan pilih nomor unit kamar rusunawa.',
                    ], 422);
                }

                $room = RusunRoom::whereKey($validated['room_id'])
                    ->where('asset_item_id', $asset->id)
                    ->lockForUpdate()
                    ->first();

                if (! $room || $room->status !== 'available') {
                    return response()->json([
                        'success' => false,
                        'error_code' => 'ROOM_UNAVAILABLE',
                        'message' => 'Unit kamar tidak tersedia atau sedang ditempati.',
                    ], 422);
                }

                $roomId = $room->id;
                $hasConflict = AssetRental::where('room_id', $roomId)
                    ->whereIn('status', ['Diajukan', 'Diverifikasi', 'Disetujui'])
                    ->lockForUpdate()
                    ->exists();

                if ($hasConflict) {
                    return response()->json([
                        'success' => false,
                        'error_code' => 'ROOM_ALREADY_RESERVED',
                        'message' => 'Kamar ini sedang dalam proses pengajuan atau sewa aktif.',
                    ], 422);
                }

                $bufferHari = 0;
                $days = 1;
                $satuanSewa = 'per bulan';
                $tarifPerSatuan = (float) $asset->tarif_default;
                $includeTronton = false;
                $biayaTronton = 0.0;
                $jarakKm = null;
                $isWajibTronton = false;
                $lokasiPenggunaan = $validated['lokasi_penggunaan'] ?? ($asset->lokasi ?: 'Rusunawa Kota Baubau');
            } else {
                if (blank($validated['lokasi_penggunaan'] ?? null)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lokasi penggunaan wajib diisi.',
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
                // Tarif and unit are owned by the published asset master. Values sent
                // by the mobile client are display hints only and must never set the
                // amount persisted for a rental.
                $tarifPerSatuan = (float) $asset->tarif_default;
                $satuanSewa = (string) ($asset->satuan_tarif ?: 'per hari');
                $isWajibTronton = (bool) ($asset->wajib_tronton ?? false);
                $includeTronton = $isWajibTronton || (bool) ($validated['include_tronton'] ?? false);
                $jarakKm = isset($validated['jarak_tronton_km']) ? (float) $validated['jarak_tronton_km'] : 10.0;
                $biayaTronton = $includeTronton ? $this->calculateTrontonFee($jarakKm) : 0.0;
                $lokasiPenggunaan = $validated['lokasi_penggunaan'];
            }

            // Tarif and unit are owned by the published asset master. Values sent
            // by the mobile client are display hints only and must never set the
            // amount persisted for a rental.
            if ($tarifPerSatuan <= 0) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'ASSET_RATE_UNAVAILABLE',
                    'message' => 'Tarif resmi aset belum tersedia. Pengajuan belum dapat diteruskan.',
                ], 422);
            }

            $isSedotKakus = str_starts_with($asset->code ?? '', 'SK-')
                || str_contains(strtolower($asset->name ?? ''), 'tinja')
                || str_contains(strtolower($asset->name ?? ''), 'kakus')
                || str_contains(strtolower($satuanSewa), 'rit');

            if ($isSedotKakus && ! empty($validated['jumlah_rit'])) {
                $rit = (int) $validated['jumlah_rit'];
                $totalBiaya = $rit * $tarifPerSatuan;
                $days = 1;
                $satuanSewa = 'per rit';
            } else {
                $totalBiaya = ($days * $tarifPerSatuan) + $biayaTronton;
            }

            // A submitted DP is not a settlement proof. Keep it as an audit
            // hint only; confirmed payment must update the rental through the
            // payment flow owned by the API.
            $requestedDp = (float) ($validated['dp'] ?? 0);
            $dp = 0.0;
            $sisaPembayaran = $totalBiaya;

            // Keep the exact server quote beside the rental so later review can
            // explain which master row and inputs produced the amount. This is
            // an audit snapshot, not an approval or a replacement for the
            // official tariff versioning decision.
            $quoteSnapshot = [
                'version' => 'asset-master-v1',
                'source' => 'asset_items',
                'asset_item_id' => (int) $asset->id,
                'asset_updated_at' => $asset->updated_at?->toIso8601String(),
                'tarif_per_satuan' => $tarifPerSatuan,
                'satuan_sewa' => $satuanSewa,
                'service_quantity' => $isSedotKakus && ! empty($validated['jumlah_rit'])
                    ? (int) $validated['jumlah_rit']
                    : $days,
                'rental_days' => $days,
                'include_tronton' => $includeTronton,
                'jarak_tronton_km' => $includeTronton ? $jarakKm : null,
                'biaya_tronton' => $biayaTronton,
                'total_biaya' => $totalBiaya,
                'requested_dp' => $requestedDp,
                'confirmed_dp' => $dp,
                'sisa_pembayaran' => $sisaPembayaran,
            ];

            $asetType = $this->jasaUsahaType();
            $asetClassification = $this->jasaUsahaClassification();

            $prefix = $isSedotKakus ? 'KKS-' : 'AST-';
            $rentalCode = $prefix.now()->format('Ym').'-'.strtoupper(Str::random(5));

            $metadata = [
                'source' => 'citizen_submission',
                'item_code' => $asset->code,
                'is_wajib_tronton' => $isWajibTronton,
                'quote_source' => 'asset_items.tarif_default',
                'requested_dp' => $requestedDp,
                'quote' => $quoteSnapshot,
            ];

            if ($isRusunawa) {
                $metadata['is_rusunawa'] = true;
                $metadata['room_id'] = $roomId;
                $metadata['periode_bulan'] = $mulai->format('Y-m');
            }

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

                $isEkstrem = ($validated['screening_jarak_selang'] ?? '') === 'lebih_50m' || (int) ($validated['jumlah_rit'] ?? 1) > 5;
                $metadata['smart_screening'] = [
                    'akses_jalan' => $validated['screening_akses_jalan'] ?? 'lebar_truk',
                    'jarak_selang' => $validated['screening_jarak_selang'] ?? 'kurang_20m',
                    'kondisi_lubang' => $validated['screening_kondisi_lubang'] ?? 'bisa_dibuka',
                    'is_akses_ekstrem' => $isEkstrem,
                ];
                $metadata['requires_survey'] = $isEkstrem;
                $metadata['survey_reason'] = $isEkstrem ? 'Jarak selang > 50 meter / Kapasitas ritase industri besar' : null;
            }

            $rental = AssetRental::create([
                'rental_code' => $rentalCode,
                'taxpayer_id' => $taxpayer->id,
                'asset_item_id' => $asset->id,
                'room_id' => $roomId,
                'retribution_type_id' => $asetType?->id ?? 106,
                'retribution_classification_id' => $asetClassification?->id ?? 211,
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => ! empty($validated['tanggal_selesai']) ? $selesai->toDateString() : null,
                'lama_sewa' => $days,
                'satuan_sewa' => $satuanSewa,
                'periode_bulan' => $isRusunawa ? $mulai->format('Y-m') : null,
                'buffer_hari' => $bufferHari,
                'tarif_per_satuan' => $tarifPerSatuan,
                'include_tronton' => $includeTronton,
                'jarak_tronton_km' => $includeTronton ? $jarakKm : null,
                'biaya_tronton' => $includeTronton ? $biayaTronton : null,
                'total_biaya' => $totalBiaya,
                'dp' => $dp,
                'is_guarantee_paid' => $isGuaranteePaid,
                'guarantee_paid_at' => $isGuaranteePaid ? now() : null,
                'sisa_pembayaran' => $sisaPembayaran,
                'metode_pembayaran' => $validated['metode_pembayaran'] ?? 'Transfer Bank',
                'status' => 'Diajukan',
                'lokasi_penggunaan' => $lokasiPenggunaan,
                'jenis_pekerjaan' => $validated['jenis_pekerjaan'] ?? ($isSedotKakus ? 'Penyedotan Kakus / Tinja' : ($isRusunawa ? 'Hunian Rusunawa' : null)),
                'luas_volume' => $validated['luas_volume'] ?? ($isSedotKakus ? ($validated['jumlah_rit'] ?? 1).' Rit' : ($isRusunawa ? '1 Unit Kamar' : null)),
                'nama_proyek' => $validated['nama_proyek'] ?? ($isSedotKakus ? 'Layanan Sedot Kakus '.($validated['jenis_bangunan'] ?? '') : ($isRusunawa ? 'Sewa '.$asset->name : null)),
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

            $message = $isRusunawa
                ? 'Permohonan sewa rusunawa berhasil diajukan. Petugas Disperkim akan memverifikasi permohonan Anda.'
                : ($isSedotKakus
                    ? 'Permohonan layanan sedot kakus berhasil diajukan.'
                    : 'Permohonan sewa berhasil diajukan. Petugas PUPR akan memverifikasi permohonan Anda.');

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $this->citizenRentalPayload($rental->load(['assetItem:id,name,code', 'room:id,room_number,floor,complex_code'])),
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

    private function calculateTrontonFee(float $distanceKm): float
    {
        // Existing server tariff bands; official route/zone confirmation remains
        // an operational dependency before this becomes a final bill amount.
        return match (true) {
            $distanceKm <= 20 => 450000.0,
            $distanceKm <= 40 => 900000.0,
            $distanceKm <= 60 => 1350000.0,
            $distanceKm <= 80 => 1800000.0,
            $distanceKm <= 100 => 2700000.0,
            default => 2250000.0,
        };
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
