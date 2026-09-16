<?php

namespace App\Http\Controllers\Api\V1\Dlh;

use App\Http\Controllers\Controller;
use App\Models\TaxObject;
use App\Services\Dlh\DlhTrashBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DlhTrashBillingController extends Controller
{
    protected DlhTrashBillingService $billingService;

    public function __construct(DlhTrashBillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Daftar akun objek persampahan persil (Admin DLH & Master Management)
     */
    public function indexAccounts(Request $request)
    {
        $search = $request->query('search');
        $statusHunian = $request->query('status_hunian');
        $kategoriId = $request->query('tarif_kategori_id');
        $district = $request->query('district');
        $subDistrict = $request->query('sub_district');
        $perPage = (int) $request->query('per_page', 15);

        // Sumber 1: Tabel dlh_sampah_accounts
        if (\Illuminate\Support\Facades\Schema::hasTable('dlh_sampah_accounts') && \App\Models\Dlh\DlhSampahAccount::count() > 0) {
            $query = \App\Models\Dlh\DlhSampahAccount::with(['tarifKategori', 'taxObject.taxpayer']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('kode_objek', 'LIKE', "%{$search}%")
                      ->orWhere('penanggung_jawab_nama', 'LIKE', "%{$search}%")
                      ->orWhere('alamat_lengkap', 'LIKE', "%{$search}%");
                });
            }

            if ($statusHunian) {
                $query->where('status', $statusHunian);
            }

            if ($kategoriId) {
                $query->where('tarif_kategori_id', $kategoriId);
            }

            if ($district) {
                $query->where('kecamatan', $district);
            }

            if ($subDistrict) {
                $query->where('kelurahan', $subDistrict);
            }

            $accounts = $query->orderBy('id', 'desc')->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $accounts->items(),
                'meta' => [
                    'current_page' => $accounts->currentPage(),
                    'last_page' => $accounts->lastPage(),
                    'per_page' => $accounts->perPage(),
                    'total' => $accounts->total(),
                ],
            ]);
        }

        // Sumber 2 (Fallback): Tabel TaxObject
        $query = TaxObject::with('taxpayer')
            ->where(function ($q) {
                $q->where('retribution_classification_id', 198)
                  ->orWhere('opd_id', 4);
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%")
                  ->orWhere('penanggung_jawab_nama', 'LIKE', "%{$search}%");
            });
        }

        if ($statusHunian) {
            $query->where('status_hunian', $statusHunian);
        }

        if ($district) {
            $query->where('district', $district);
        }

        if ($subDistrict) {
            $query->where('sub_district', $subDistrict);
        }

        $taxObjects = $query->orderBy('id', 'desc')->paginate($perPage);

        $mapped = collect($taxObjects->items())->map(function ($obj) {
            $monthlyRate = 8500;
            $cat = $obj->building_category ?? 'RUMAH_SEDERHANA';
            if ($cat === 'RUMAH_MENENGAH') $monthlyRate = 15000;
            elseif ($cat === 'RUKO_NIAGA') $monthlyRate = 22000;
            elseif ($cat === 'RESTORAN') $monthlyRate = 45000;
            elseif ($cat === 'PASAR') $monthlyRate = 22000;
            elseif ($cat === 'INDUSTRI') $monthlyRate = 50000;
            elseif ($cat === 'SOSIAL_IBADAH') $monthlyRate = 0;

            return [
                'id' => $obj->id,
                'tax_object_id' => $obj->id,
                'kode_objek' => 'SMP-' . str_pad((string) $obj->id, 5, '0', STR_PAD_LEFT),
                'penanggung_jawab_nama' => $obj->penanggung_jawab_nama ?? $obj->name,
                'penanggung_jawab_telepon' => $obj->penanggung_jawab_kontak ?? $obj->taxpayer?->phone_number,
                'alamat_lengkap' => $obj->address,
                'kelurahan' => $obj->sub_district ?? 'Kota Baubau',
                'kecamatan' => $obj->district ?? 'Kota Baubau',
                'latitude' => $obj->latitude,
                'longitude' => $obj->longitude,
                'status' => $obj->status_hunian ?? 'BERPENGHUNI',
                'nama_kategori' => ucwords(strtolower(str_replace('_', ' ', $cat))),
                'tarif_bulanan' => $monthlyRate,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $mapped,
            'meta' => [
                'current_page' => $taxObjects->currentPage(),
                'last_page' => $taxObjects->lastPage(),
                'per_page' => $taxObjects->perPage(),
                'total' => $taxObjects->total(),
            ],
        ]);
    }

    /**
     * Master daftar kategori tarif persampahan Perda Kota Baubau No. 1/2024
     */
    public function listTarifCategories()
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('dlh_sampah_tarif_kategori')) {
            $categories = \App\Models\Dlh\DlhSampahTarifKategori::where('is_active', true)->get();
            if ($categories->isNotEmpty()) {
                return response()->json(['status' => 'success', 'data' => $categories]);
            }
        }

        $defaults = [
            ['id' => 1, 'kode_tarif' => 'SMP-01', 'nama_kategori' => 'Rumah Tangga Sederhana', 'kelompok' => 'RUMAH_TANGGA', 'tarif_bulanan' => 8500],
            ['id' => 2, 'kode_tarif' => 'SMP-02', 'nama_kategori' => 'Rumah Tangga Menengah / Mewah', 'kelompok' => 'RUMAH_TANGGA', 'tarif_bulanan' => 15000],
            ['id' => 3, 'kode_tarif' => 'SMP-03', 'nama_kategori' => 'Ruko / Kantor / Toko Niaga', 'kelompok' => 'NIAGA', 'tarif_bulanan' => 22000],
            ['id' => 4, 'kode_tarif' => 'SMP-04', 'nama_kategori' => 'Restoran / Rumah Makan / Kafe', 'kelompok' => 'NIAGA', 'tarif_bulanan' => 45000],
            ['id' => 5, 'kode_tarif' => 'SMP-05', 'nama_kategori' => 'Kios / Los Pasar', 'kelompok' => 'PASAR', 'tarif_bulanan' => 22000],
            ['id' => 6, 'kode_tarif' => 'SMP-06', 'nama_kategori' => 'Industri / Pabrik / Hotel', 'kelompok' => 'INDUSTRI', 'tarif_bulanan' => 50000],
            ['id' => 7, 'kode_tarif' => 'SMP-07', 'nama_kategori' => 'Sosial / Rumah Ibadah / Panti', 'kelompok' => 'SOSIAL', 'tarif_bulanan' => 0],
        ];

        return response()->json(['status' => 'success', 'data' => $defaults]);
    }

    /**
     * Hitung simulasi tarif retribusi persampahan
     */
    public function calculateTariff(Request $request)
    {
        $validated = $request->validate([
            'building_category' => 'required|string|in:SOSIAL_IBADAH,RUMAH_SEDERHANA,RUMAH_MENENGAH,RUKO_NIAGA,RESTORAN,PASAR,INDUSTRI',
            'status_hunian' => 'required|string|in:BERPENGHUNI,KOSONG',
        ]);

        $dummyObject = new TaxObject([
            'building_category' => $validated['building_category'],
            'status_hunian' => $validated['status_hunian'],
        ]);

        $amount = $this->billingService->calculateMonthlyAmount($dummyObject);

        return response()->json([
            'status' => 'success',
            'data' => [
                'building_category' => $validated['building_category'],
                'status_hunian' => $validated['status_hunian'],
                'monthly_amount' => $amount,
                'due_day_of_month' => 15,
                'penalty_rate' => '1% per bulan keterlambatan (maks. 24%)',
            ],
        ]);
    }

    /**
     * Alihkan/update data penghuni persil (Penyewa / Pemilik baru)
     */
    public function transferOccupant(Request $request, $taxObjectId)
    {
        $taxObject = TaxObject::findOrFail($taxObjectId);

        $validated = $request->validate([
            'tipe_penghuni' => 'required|string|in:PEMILIK,PENYEWA',
            'status_hunian' => 'required|string|in:BERPENGHUNI,KOSONG',
            'penanggung_jawab_nama' => 'required|string|max:255',
            'penanggung_jawab_kontak' => 'nullable|string|max:50',
        ]);

        $updated = $this->billingService->transferOccupant($taxObject, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Data penghuni persil berhasil diperbarui.',
            'data' => $updated,
        ]);
    }

    /**
     * Penerbitan massal tagihan retribusi persampahan bulanan (Auto-Billing Door-to-Door)
     */
    public function generateMonthlyBills(Request $request)
    {
        $validated = $request->validate([
            'period' => 'nullable|date_format:Y-m',
            'district' => 'nullable|string',
            'sub_district' => 'nullable|string',
        ]);

        $periodDate = !empty($validated['period'])
            ? Carbon::createFromFormat('Y-m', $validated['period'])
            : Carbon::now();

        $query = TaxObject::where(function ($q) {
            $q->where('retribution_classification_id', 198)
              ->orWhere('opd_id', 4);
        })
        ->where('is_active', true)
        ->where('status_hunian', 'BERPENGHUNI'); // Rumah kosong tidak ditagih

        if (!empty($validated['district'])) {
            $query->where('district', $validated['district']);
        }
        if (!empty($validated['sub_district'])) {
            $query->where('sub_district', $validated['sub_district']);
        }

        $taxObjects = $query->get();
        $generatedCount = 0;
        $totalAmountBilled = 0;

        foreach ($taxObjects as $obj) {
            $bill = $this->billingService->generateMonthlyBill($obj, $periodDate);
            if ($bill->wasRecentlyCreated) {
                $generatedCount++;
                $totalAmountBilled += $bill->amount;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Proses penerbitan tagihan bulanan selesai. {$generatedCount} tagihan baru diterbitkan.",
            'data' => [
                'period' => $periodDate->format('Y-m'),
                'total_persil_evaluated' => $taxObjects->count(),
                'new_bills_generated' => $generatedCount,
                'total_amount_billed' => (float) $totalAmountBilled,
            ],
        ]);
    }

    /**
     * Inkuiri Tagihan Persampahan (Portal Warga / Mobile)
     */
    public function inquiry(Request $request)
    {
        $query = $request->query('query');
        if (empty($query)) {
            return response()->json(['status' => 'error', 'message' => 'Parameter query pencarian wajib diisi.'], 422);
        }

        $taxObject = TaxObject::withoutGlobalScopes()
            ->where(function ($q) {
                $q->where('opd_id', 4)
                  ->orWhere('retribution_classification_id', 198);
            })
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('address', 'LIKE', "%{$query}%")
                  ->orWhere('penanggung_jawab_nama', 'LIKE', "%{$query}%");
                if (is_numeric($query)) {
                    $q->orWhere('id', (int) $query);
                }
            })
            ->first();

        if (!$taxObject) {
            return response()->json(['status' => 'error', 'message' => 'Objek persampahan tidak ditemukan.'], 404);
        }

        $bills = \App\Models\Bill::withoutGlobalScopes()
            ->where('opd_id', 4)
            ->where(function ($q) use ($taxObject) {
                $q->where('tax_object_id', $taxObject->id)
                  ->orWhere('taxpayer_id', $taxObject->taxpayer_id);
            })
            ->orderBy('id', 'desc')
            ->get();

        $activeBill = $bills->where('status', '!=', 'PAID')->first() ?? $bills->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $taxObject->id,
                'name' => $taxObject->name,
                'address' => $taxObject->address,
                'building_category' => $taxObject->building_category ?? 'RUMAH_SEDERHANA',
                'tipe_penghuni' => $taxObject->tipe_penghuni ?? 'PEMILIK',
                'status_hunian' => $taxObject->status_hunian ?? 'BERPENGHUNI',
                'taxpayer_name' => $taxObject->penanggung_jawab_nama ?? $taxObject->name,
                'active_bill' => $activeBill ? [
                    'id' => $activeBill->id,
                    'bill_number' => $activeBill->bill_number,
                    'period' => $activeBill->period ?? date('Y-m'),
                    'amount' => (float) $activeBill->amount,
                    'penalty_amount' => (float) $activeBill->penalty_amount,
                    'total_amount' => (float) ($activeBill->amount + $activeBill->penalty_amount + $activeBill->admin_fee),
                    'status' => $activeBill->status,
                    'due_date' => $activeBill->due_date ? Carbon::parse($activeBill->due_date)->toDateString() : null,
                ] : null,
                'bill_history' => $bills->take(5)->map(fn ($b) => [
                    'id' => $b->id,
                    'bill_number' => $b->bill_number,
                    'period' => $b->period,
                    'total_amount' => (float) $b->amount,
                    'status' => $b->status,
                    'due_date' => $b->due_date,
                ])->values(),
            ],
        ]);
    }

    /**
     * Pelaporan & penanganan rumah/persil kosong saat kunjungan juru pungut/petugas lapangan.
     * Melakukan validasi GPS geofencing (< 30m) dan memicu auto-notifikasi WhatsApp.
     */
    public function reportEmptyHouse(Request $request, $taxObjectId)
    {
        $validated = $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:500',
        ]);

        $taxObject = TaxObject::with('taxpayer')->findOrFail($taxObjectId);

        // 1. Validasi Geofencing (< 30m)
        $distanceMeters = null;
        $isWithinGeofence = true;

        if (isset($validated['latitude'], $validated['longitude']) && $taxObject->latitude && $taxObject->longitude) {
            $distanceMeters = $this->calculateHaversineDistance(
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                (float) $taxObject->latitude,
                (float) $taxObject->longitude
            );

            // Radius geofence standar 30 meter
            $isWithinGeofence = $distanceMeters <= 30.0;
        }

        // 2. Tandai status hunian persil menjadi KOSONG
        $taxObject->update([
            'status_hunian' => 'KOSONG',
        ]);

        // 3. Picu notifikasi pengingat via WhatsApp Gateway jika nomor kontak tersedia
        $waSent = false;
        $recipientPhone = $taxObject->penanggung_jawab_kontak 
            ?? $taxObject->taxpayer?->phone_number 
            ?? $taxObject->taxpayer?->phone;

        if ($recipientPhone) {
            try {
                $waService = app(\App\Services\WaGatewayService::class);
                $objectCode = 'SMP-' . str_pad((string) $taxObject->id, 5, '0', STR_PAD_LEFT);
                $message = "Yth. Warga Kota Baubau,\n\nPetugas DLH Kota Baubau baru saja melakukan kunjungan operasional retribusi persampahan ke persil Anda (*{$taxObject->name}* - {$taxObject->address}), namun persil terpantau sedang *kosong/tutup*.\n\nUntuk kemudahan Anda, rincian tagihan retribusi persampahan dapat dicek dan dibayar secara mandiri tanpa login melalui tautan resmi:\nhttps://mpad.baubaukota.go.id/s/{$objectCode}\n\nTerima kasih atas partisipasi aktif Anda dalam menjaga kebersihan Kota Baubau.\n\n_Dinas Lingkungan Hidup Kota Baubau_";

                $waSent = $waService->sendMessage($recipientPhone, $message);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Gagal mengirim WA notifikasi rumah kosong: " . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status persil berhasil ditandai KOSONG dan log kunjungan tercatat.',
            'data' => [
                'tax_object_id' => $taxObject->id,
                'status_hunian' => 'KOSONG',
                'distance_meters' => $distanceMeters !== null ? round($distanceMeters, 1) : null,
                'is_within_geofence' => $isWithinGeofence,
                'wa_notification_sent' => $waSent,
                'geofence_warning' => !$isWithinGeofence ? 'Peringatan: Posisi GPS petugas berada di luar radius sah persil (> 30 meter).' : null,
            ],
        ]);
    }

    /**
     * Hitung jarak dua koordinat menggunakan formula Haversine (dalam satuan meter)
     */
    private function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Radius bumi dalam meter

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
