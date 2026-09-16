<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\DlhMarketTicket;
use App\Models\DlhRemittance;
use App\Models\Opd;
use App\Models\Payment;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use App\Models\UserRetributionAssignment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data operasional DLH — dual-mode penagihan mobile retribusi persampahan:
 *
 *  [1] MODE KELURAHAN (e-SKRD Bulanan)
 *      - Klasifikasi PERSAMPAHAN-KELURAHAN + form_schema kategori bangunan.
 *      - Wajib pajak + tax object per kelurahan Baubau dengan koordinat.
 *      - Tagihan bulanan variasi status: lunas, menunggu bayar, jatuh tempo.
 *
 *  [2] MODE PASAR (Karcis Harian + Kliring Batch Akhir Bulan)
 *      - Klasifikasi KARCIS-PASAR-DLH.
 *      - Karcis 3.000/6.000 (TUNAI / QRIS_INSTANT) oleh juru pungut pasar.
 *      - Sebagian karcis langsung disetorkan via remittance (kliring batch
 *        akhir bulan), sebagian lagi belum (holding balance di aplikasi).
 *
 *  [3] RBAC — hanya petugas OPD DLH yang melihat data DLH:
 *      - Akun petugas DLH dibuat dengan opd_id = OPD DLH.
 *      - UserRetributionAssignment disematkan untuk tipe Retribusi Persampahan
 *        (petugas tanpa assignment TIDAK melihat bill/statistik apa pun).
 *      - Frontend retribusi-petugas membatasi route /dlh-collector hanya untuk
 *        isDlhOfficer() (lihat officerRoleUtils.ts).
 *
 * Idempotent: aman dijalankan ulang.
 */
class DlhDualModeSeeder extends Seeder
{
    private const CODE_KELURAHAN = 'PERSAMPAHAN-KELURAHAN';
    private const CODE_PASAR = 'KARCIS-PASAR-DLH';

    /** Nama pasar yang dipakai kontrak DlhCollectorPage (frontend). */
    private const MARKETS = [
        'Pasar Karya Nugraha',
        'Pasar Wameo',
        'Pasar Laelangi',
        'Pasar Baruga',
        'Kawasan Sentra Kuliner Kotamara',
    ];

    /** Tarif bulanan per kategori bangunan (Mode Kelurahan). */
    private const MONTHLY_RATES = [
        'SOSIAL_IBADAH' => 15000,
        'RUMAH_SEDERHANA' => 20000,
        'RUMAH_MENENGAH' => 30000,
        'RUKO_NIAGA' => 50000,
        'RESTORAN' => 75000,
        'PASAR' => 60000,
        'INDUSTRI' => 100000,
    ];

    public function run(): void
    {
        $dlh = Opd::where('code', 'DLH')->first();
        if (!$dlh) {
            $this->command?->error('Seeder ini membutuhkan OPD DLH (jalankan DatabaseSeeder terlebih dahulu).');
            return;
        }

        $type = RetributionType::withoutGlobalScopes()
            ->where('opd_id', $dlh->id)
            ->where('name', 'Retribusi Persampahan')
            ->first();
        if (!$type) {
            $this->command?->error('Tipe "Retribusi Persampahan" DLH tidak ditemukan.');
            return;
        }

        // ============================================================
        // 0. RBAC — akun DLH (hanya OPD DLH) + user_retribution_assignments.
        // ============================================================
        $admin = $this->ensureUser('dlh@retribusi.id', 'Admin DLH', 'opd', $dlh->id);
        $petugasPasar = $this->ensureUser('dlh.pasar@baubaukota.go.id', 'Juru Pungut Pasar DLH', 'petugas', $dlh->id);
        $petugasKel = $this->ensureUser('dlh.kelurahan@baubaukota.go.id', 'Petugas Sambung Sampah DLH', 'petugas', $dlh->id);

        $kelurahanClass = $this->ensureClassification($dlh, $type, self::CODE_KELURAHAN, 'Retribusi Sampah Domestik (e-SKRD Bulanan)');
        $pasarClass = $this->ensureClassification($dlh, $type, self::CODE_PASAR, 'Karcis Sampah Pasar (Harian)');

        $this->assign($admin, $type, null);
        $this->assign($petugasPasar, $type, null);
        $this->assign($petugasPasar, $type, $pasarClass);
        $this->assign($petugasKel, $type, null);
        $this->assign($petugasKel, $type, $kelurahanClass);

        // ============================================================
        // 1. MODE KELURAHAN — e-SKRD bulanan.
        // ============================================================
        $this->seedKelurahanBilling($dlh, $type, $kelurahanClass, $petugasKel);

        // ============================================================
        // 2. MODE PASAR — karcis harian + kliring batch akhir bulan.
        // ============================================================
        $this->seedMarketTickets($petugasPasar);

        $this->command?->info('DlhDualModeSeeder selesai: RBAC DLH + e-SKRD kelurahan + karcis pasar (kliring batch).');
        $this->command?->info('Login DLH:');
        $this->command?->info('  - dlh.pasar@baubaukota.go.id / password123 (Juru Pungut Pasar)');
        $this->command?->info('  - dlh.kelurahan@baubaukota.go.id / password123 (Petugas Kelurahan)');
        $this->command?->info('  - dlh@retribusi.id / password123 (Admin OPD DLH)');
    }

    // ------------------------------------------------------------------
    // RBAC helpers
    // ------------------------------------------------------------------

    private function ensureUser(string $email, string $name, string $role, int $opdId): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password123'),
                'role' => $role,
                'opd_id' => $opdId,
                'status' => 'active',
            ]
        );
    }

    private function assign(User $user, RetributionType $type, ?RetributionClassification $class): void
    {
        UserRetributionAssignment::firstOrCreate([
            'user_id' => $user->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $class?->id,
        ]);
    }

    private function ensureClassification(Opd $dlh, RetributionType $type, string $code, string $name): RetributionClassification
    {
        $isKelurahan = $code === self::CODE_KELURAHAN;

        return RetributionClassification::updateOrCreate(
            ['opd_id' => $dlh->id, 'code' => $code],
            [
                'retribution_type_id' => $type->id,
                'name' => $name,
                'icon' => $isKelurahan ? 'home' : 'store',
                'is_self_assessment' => $isKelurahan ? false : true,
                'description' => $isKelurahan
                    ? 'Penagihan sampah domestik per kelurahan dengan e-SKRD bulanan (jatuh tempo tgl 15).'
                    : 'Penagihan karcis sampah pasar harian (3.000/6.000) dengan kliring batch akhir bulan.',
                'form_schema' => $isKelurahan ? $this->kelurahanFormSchema() : $this->pasarFormSchema(),
                'requirements' => $isKelurahan
                    ? [
                        'foto_titik_persil_rumah',
                        'buktidukung_sktm_sosial',
                        'nomor_rekening',
                    ]
                    : [
                        'foto_karcis',
                        'nomor_lapak',
                        'foto_lingkungan_stan',
                    ],
                'calculation_formula' => $isKelurahan
                    ? 'tarif_per_kategori_bangunan_per_bulan (status_hunian KOSONG = 50%)'
                    : 'harian: karcis 3.000 (pedagang kecil) / 6.000 (kios/lapak tetap); disetor batch akhir bulan',
                'bank_accounts' => [
                    [
                        'bank' => 'Bank Sultra',
                        'account_number' => '8168.00.000011.3',
                        'account_name' => 'Kas Daerah Pemkot Baubau - DLH',
                    ],
                ],
            ]
        );
    }

    private function kelurahanFormSchema(): array
    {
        return [
            [
                'key' => 'kategori_bangunan',
                'label' => 'Kategori Bangunan',
                'type' => 'select',
                'options' => array_keys(self::MONTHLY_RATES),
                'required' => true,
            ],
            [
                'key' => 'status_hunian',
                'label' => 'Status Hunian',
                'type' => 'select',
                'options' => ['BERPENGHUNI', 'KOSONG'],
                'required' => true,
            ],
            ['key' => 'luas_bangunan', 'label' => 'Luas Bangunan (m²)', 'type' => 'number', 'required' => false],
            ['key' => 'jumlah_penghuni', 'label' => 'Jumlah Penghuni', 'type' => 'number', 'required' => false],
            ['key' => 'kategori_sosial', 'label' => 'SKTM Sosial (jika kategori rumah sederhana)', 'type' => 'file', 'required' => false],
        ];
    }

    private function pasarFormSchema(): array
    {
        return [
            [
                'key' => 'nama_pasar',
                'label' => 'Pasar',
                'type' => 'select',
                'options' => self::MARKETS,
                'required' => true,
            ],
            ['key' => 'nomor_lapak', 'label' => 'Nomor Lapak / Kios', 'type' => 'text', 'required' => false],
            ['key' => 'nama_pedagang', 'label' => 'Nama Pedagang', 'type' => 'text', 'required' => true],
            [
                'key' => 'jenis_karcis',
                'label' => 'Jenis Karcis',
                'type' => 'select',
                'options' => ['Karcis 3.000 (Pedagang Kecil)', 'Karcis 6.000 (Kios/Lapak Tetap)'],
                'required' => true,
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Mode 1: Kelurahan — e-SKRD bulanan
    // ------------------------------------------------------------------

    private function seedKelurahanBilling(
        Opd $dlh,
        RetributionType $type,
        RetributionClassification $class,
        User $petugas
    ): void {
        $kelurahanSeed = [
            [
                'nik' => '7471014108800011',
                'name' => 'Wa Ode Fatimah',
                'address' => 'Jl. Jend. Sudirman No. 21, Kel. Baadia, Kec. Murhum, Baubau',
                'district' => 'Murhum',
                'sub_district' => 'Baadia',
                'category' => 'RUMAH_SEDERHANA',
                'occupancy' => 'BERPENGHUNI',
                'meta' => ['luas_bangunan' => 36, 'jumlah_penghuni' => 4],
                'lat' => -5.4628,
                'lng' => 122.6041,
                'current_bill_status' => 'paid',
            ],
            [
                'nik' => '7471011505760012',
                'name' => 'La Ode Ahmad',
                'address' => 'Jl. Sriwijaya No. 8, Kel. Bataraguru, Kec. Wolio, Baubau',
                'district' => 'Wolio',
                'sub_district' => 'Bataraguru',
                'category' => 'RUMAH_MENENGAH',
                'occupancy' => 'BERPENGHUNI',
                'meta' => ['luas_bangunan' => 70, 'jumlah_penghuni' => 5],
                'lat' => -5.4660,
                'lng' => 122.6015,
                'current_bill_status' => 'pending',
            ],
            [
                'nik' => '7471015701930013',
                'name' => 'Toko Sembako Sari',
                'address' => 'Jl. Kamboja No. 12, Kel. Waboraga, Kec. Kokalukuna, Baubau',
                'district' => 'Kokalukuna',
                'sub_district' => 'Waboraga',
                'category' => 'RUKO_NIAGA',
                'occupancy' => 'BERPENGHUNI',
                'meta' => ['luas_bangunan' => 45, 'jumlah_penghuni' => 3],
                'lat' => -5.4522,
                'lng' => 122.5980,
                'current_bill_status' => 'overdue',
            ],
            [
                'nik' => '7471010204710014',
                'name' => 'Restoran Ikhlas',
                'address' => 'Jl. Ade Irma Nasution No. 2, Kel. Tomba, Kec. Kokalukuna, Baubau',
                'district' => 'Kokalukuna',
                'sub_district' => 'Tomba',
                'category' => 'RESTORAN',
                'occupancy' => 'BERPENGHUNI',
                'meta' => ['luas_bangunan' => 120, 'jumlah_penghuni' => 6],
                'lat' => -5.4580,
                'lng' => 122.6060,
                'current_bill_status' => 'paid',
            ],
            [
                'nik' => '7471016405770015',
                'name' => 'Wa Ode Sri Wahyuni',
                'address' => 'Jl. Bone-Bone No. 9, Kel. Bone-Bone, Kec. Batupoaro, Baubau',
                'district' => 'Batupoaro',
                'sub_district' => 'Bone-Bone',
                'category' => 'RUMAH_SEDERHANA',
                'occupancy' => 'BERPENGHUNI',
                'meta' => ['luas_bangunan' => 28, 'jumlah_penghuni' => 3],
                'lat' => -5.4700,
                'lng' => 122.6110,
                'current_bill_status' => 'pending',
            ],
            [
                'nik' => '7471011911640016',
                'name' => 'Andi Taslim',
                'address' => 'Jl. Wolter Monginsidi No. 33, Kel. Wangkanapi, Kec. Wolio, Baubau',
                'district' => 'Wolio',
                'sub_district' => 'Wangkanapi',
                'category' => 'RUMAH_MENENGAH',
                'occupancy' => 'KOSONG',
                'meta' => ['luas_bangunan' => 84, 'jumlah_penghuni' => 0],
                'lat' => -5.4685,
                'lng' => 122.5990,
                'current_bill_status' => 'overdue',
            ],
        ];

        $now = Carbon::now();
        $prev = $now->copy()->subMonth();

        foreach ($kelurahanSeed as $i => $data) {
            $rate = $this->monthlyRate($data['category'], $data['occupancy']);

            $taxpayer = Taxpayer::withoutGlobalScopes()->firstOrNew(['nik' => $data['nik']]);
            $taxpayer->fill([
                'opd_id' => $dlh->id,
                'name' => $data['name'],
                'address' => $data['address'],
                'district' => $data['district'],
                'sub_district' => $data['sub_district'],
                'phone' => '08' . str_pad((string) (1000000000 + $i), 11, '0'),
                'is_active' => true,
                'created_by' => $petugas->id,
                'created_at' => $prev->copy()->subDays(count($kelurahanSeed) - $i),
                'updated_at' => $prev->copy()->subDays(count($kelurahanSeed) - $i),
            ]);
            if (!$taxpayer->exists) {
                $taxpayer->npwpd = Taxpayer::resolveNpwpd($data['nik']);
            }
            $taxpayer->save();
            $taxpayer->retributionTypes()->syncWithoutDetaching([$type->id]);

            $object = TaxObject::updateOrCreate(
                ['nop' => 'DLH-KEL-' . $data['nik']],
                [
                    'taxpayer_id' => $taxpayer->id,
                    'retribution_type_id' => $type->id,
                    'retribution_classification_id' => $class->id,
                    'opd_id' => $dlh->id,
                    'name' => $data['name'] . ' - Penagihan Sampah',
                    'address' => $data['address'],
                    'district' => $data['district'],
                    'sub_district' => $data['sub_district'],
                    'latitude' => $data['lat'],
                    'longitude' => $data['lng'],
                    'transaction_type' => 'perekaman_data',
                    'status' => 'active',
                    'is_active' => true,
                    'metadata' => [
                        'source' => 'dlh_kelurahan',
                        'kategori_bangunan' => $data['category'],
                        'status_hunian' => $data['occupancy'],
                        'luas_bangunan' => $data['meta']['luas_bangunan'],
                        'jumlah_penghuni' => $data['meta']['jumlah_penghuni'],
                        'tarif_bulanan' => $rate,
                    ],
                ]
            );

            $this->seedKelurahanBills($taxpayer, $object, $petugas, $dlh, $type, $class, $rate, $data['current_bill_status']);
        }
    }

    private function monthlyRate(string $category, string $occupancy): int
    {
        $rate = self::MONTHLY_RATES[$category] ?? self::MONTHLY_RATES['RUMAH_SEDERHANA'];
        if ($occupancy === 'KOSONG') {
            $rate = (int) round($rate * 0.5 / 1000) * 1000;
        }
        return max(10000, $rate);
    }

    /** Tagihan e-SKRD bulan lalu (lunas) + bulan berjalan (variasi status). */
    private function seedKelurahanBills(
        Taxpayer $taxpayer,
        TaxObject $object,
        User $petugas,
        Opd $dlh,
        RetributionType $type,
        RetributionClassification $class,
        float $rate,
        string $currentStatus
    ): void {
        $now = Carbon::now();
        $prev = $now->copy()->subMonth();
        $thisMonth = $now->copy()->startOfMonth();
        $prevMonth = $prev->copy()->startOfMonth();

        // Bulan lalu — lunas
        $this->createKelurahanBill([
            'user_id' => $petugas->id,
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $object->id,
            'opd_id' => $dlh->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $class->id,
            'bill_number' => 'E-SKRD-' . $prev->format('Y-m') . '-' . $object->nop,
            'amount' => $rate,
            'period' => $prev->format('Y-m'),
            'period_start' => $prevMonth,
            'period_end' => $prev->copy()->endOfMonth(),
            'due_date' => $prev->copy()->day(15),
            'status' => 'paid',
        ], $petugas, $prev->copy()->day(16));

        // Bulan berjalan — variasi: lunas / menunggu bayar / jatuh tempo
        if ($currentStatus === 'paid') {
            $this->createKelurahanBill([
                'user_id' => $petugas->id,
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $object->id,
                'opd_id' => $dlh->id,
                'retribution_type_id' => $type->id,
                'retribution_classification_id' => $class->id,
                'bill_number' => 'E-SKRD-' . $now->format('Y-m') . '-' . $object->nop,
                'amount' => $rate,
                'period' => $now->format('Y-m'),
                'period_start' => $thisMonth,
                'period_end' => $now->copy()->endOfMonth(),
                'due_date' => $now->copy()->day(15),
                'status' => 'paid',
            ], $petugas, $now->copy()->subDays(3));
        } elseif ($currentStatus === 'overdue') {
            $this->createKelurahanBill([
                'user_id' => $petugas->id,
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $object->id,
                'opd_id' => $dlh->id,
                'retribution_type_id' => $type->id,
                'retribution_classification_id' => $class->id,
                'bill_number' => 'E-SKRD-' . $now->format('Y-m') . '-' . $object->nop,
                'amount' => $rate,
                'period' => $now->format('Y-m'),
                'period_start' => $thisMonth,
                'period_end' => $now->copy()->endOfMonth(),
                'due_date' => $now->copy()->day(15)->subDays(3),
                'status' => 'pending', // due_date lampau => terbaca "overdue"
            ], $petugas, null);
        } else {
            $this->createKelurahanBill([
                'user_id' => $petugas->id,
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $object->id,
                'opd_id' => $dlh->id,
                'retribution_type_id' => $type->id,
                'retribution_classification_id' => $class->id,
                'bill_number' => 'E-SKRD-' . $now->format('Y-m') . '-' . $object->nop,
                'amount' => $rate,
                'period' => $now->format('Y-m'),
                'period_start' => $thisMonth,
                'period_end' => $now->copy()->endOfMonth(),
                'due_date' => $now->copy()->day(15)->addDays(10),
                'status' => 'pending',
            ], $petugas, null);
        }
    }

    private function createKelurahanBill(array $data, User $petugas, ?Carbon $paidAt): Bill
    {
        $bill = Bill::withoutGlobalScopes()->where('bill_number', $data['bill_number'])->first();
        $data['metadata'] = [
            'source' => 'dlh_kelurahan_eskrd',
            'kategori_bangunan' => $this->categoryFromObject($data['tax_object_id']),
            'status_hunian' => $this->occupancyFromObject($data['tax_object_id']),
            'jatuh_tempo' => 15,
            'penyetoran' => 'online_petugas',
        ];

        if ($bill) {
            $bill->update($data);
            $this->ensureKelurahanPayment($bill, $petugas, $paidAt);
            return $bill;
        }

        $bill = Bill::create($data);
        $this->ensureKelurahanPayment($bill, $petugas, $paidAt);
        return $bill;
    }

    private function ensureKelurahanPayment(Bill $bill, User $petugas, ?Carbon $paidAt): void
    {
        if ($bill->getRawOriginal('status') === 'paid' && !$bill->payments()->exists()) {
            Payment::create([
                'bill_id' => $bill->id,
                'taxpayer_id' => $bill->taxpayer_id,
                'tax_object_id' => $bill->tax_object_id,
                'amount' => $bill->amount,
                'payment_method' => 'tunai',
                'status' => 'success',
                'billing_period' => $bill->period,
                'paid_at' => $paidAt,
                'approved_by' => $petugas->id,
            ]);
        }
    }

    private function categoryFromObject(int $taxObjectId): ?string
    {
        return TaxObject::withoutGlobalScopes()->find($taxObjectId)?->metadata['kategori_bangunan'] ?? null;
    }

    private function occupancyFromObject(int $taxObjectId): ?string
    {
        return TaxObject::withoutGlobalScopes()->find($taxObjectId)?->metadata['status_hunian'] ?? null;
    }

    // ------------------------------------------------------------------
    // Mode 2: Pasar — karcis harian + kliring batch akhir bulan
    // ------------------------------------------------------------------

    private function seedMarketTickets(User $collector): void
    {
        $now = Carbon::now();
        $prev = $now->copy()->subMonth();

        // --- Batch kliring bulan lalu (karcis sudah disetor akhir bulan) ---
        $prevBatch = $this->ensureRemittance($collector, $prev);

        $prevTickets = [
            ['Pasar Karya Nugraha', 'L1', 'H. La Daud', 3000, 'TUNAI', 3],
            ['Pasar Karya Nugraha', 'L2', 'Ibu Marni', 6000, 'QRIS_INSTANT', 5],
            ['Pasar Karya Nugraha', 'L3', 'Siti Aminah', 6000, 'TUNAI', 7],
            ['Pasar Wameo', 'K-05', 'Jafar Laduni', 3000, 'TUNAI', 9],
            ['Pasar Wameo', 'K-09', 'Wa Rina', 6000, 'TUNAI', 11],
            ['Pasar Laelangi', 'K-12', 'Mukmin', 6000, 'TUNAI', 13],
            ['Pasar Baruga', 'L-01', 'Nurdin', 3000, 'QRIS_INSTANT', 15],
            ['Kawasan Sentra Kuliner Kotamara', 'KUL-03', 'Wa Ode Nur', 6000, 'QRIS_INSTANT', 17],
        ];

        $prevTotal = 0;
        foreach ($prevTickets as $i => $t) {
            $ticket = $this->createTicket(
                $collector,
                $prev->copy()->day($t[5])->setTime(7, 30 + ($i % 4)),
                $t[0],
                $t[1],
                $t[2],
                $t[3],
                $t[4],
                $i + 1
            );
            $ticket->update(['remittance_id' => $prevBatch->id]);
            $prevTotal += $t[3];
        }

        $prevBatch->update([
            'remitted_amount' => $prevTotal,
            'bank_reference_number' => $this->kliringReference($prev),
            'notes' => 'Kliring batch karcis sampah pasar — periode ' . $prev->format('F Y'),
            'remitted_at' => $prev->copy()->endOfMonth()->setTime(23, 59),
        ]);

        // --- Karcis bulan berjalan (BELUM disetor => holding balance terbentuk) ---
        $currentTickets = [
            ['Pasar Karya Nugraha', 'L1', 'H. La Daud', 3000, 'TUNAI', 0],
            ['Pasar Karya Nugraha', 'L2', 'Ibu Marni', 6000, 'QRIS_INSTANT', -1],
            ['Pasar Karya Nugraha', 'L3', 'Siti Aminah', 6000, 'TUNAI', -2],
            ['Pasar Wameo', 'K-05', 'Jafar Laduni', 3000, 'TUNAI', -1],
            ['Pasar Wameo', 'K-09', 'Wa Rina', 6000, 'TUNAI', -3],
            ['Pasar Laelangi', null, 'Tukmol Darwis', 3000, 'QRIS_INSTANT', -1],
            ['Pasar Laelangi', 'K-12', 'Mukmin', 6000, 'TUNAI', -2],
            ['Pasar Baruga', 'L-01', 'Nurdin', 3000, 'TUNAI', -1],
            ['Kawasan Sentra Kuliner Kotamara', 'KUL-03', 'Wa Ode Nur', 6000, 'QRIS_INSTANT', -1],
            ['Pasar Baruga', 'L-04', 'Arif Saimin', 3000, 'TUNAI', 0],
        ];

        foreach ($currentTickets as $i => $t) {
            $issuedAt = $now->copy()->addDays($t[5])->setTime(8, 0 + ($i % 3));
            if ($issuedAt->gt($now)) {
                $issuedAt = $now;
            }
            $this->createTicket($collector, $issuedAt, $t[0], $t[1], $t[2], $t[3], $t[4], $i + 1, $now);
        }
    }

    private function ensureRemittance(User $collector, Carbon $period): DlhRemittance
    {
        return DlhRemittance::firstOrCreate([
            'collector_user_id' => $collector->id,
            'period_month' => (int) $period->format('n'),
            'period_year' => (int) $period->format('Y'),
            'status' => 'cleared',
        ]);
    }

    private function createTicket(
        User $collector,
        Carbon $issuedAt,
        string $market,
        ?string $stall,
        string $merchant,
        int $amount,
        string $method,
        int $seq,
        ?Carbon $yearMonth = null
    ): DlhMarketTicket {
        $period = ($yearMonth ?? $issuedAt);
        $code = 'DLH-K' . $period->format('Ym') . '-' . $this->shortCode($market) . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return DlhMarketTicket::updateOrCreate(
            ['ticket_code' => $code],
            [
                'collector_user_id' => $collector->id,
                'market_name' => $market,
                'stall_name_or_number' => $stall,
                'merchant_name' => $merchant,
                'amount' => $amount,
                'payment_method' => $method,
                'qr_token' => $method === 'QRIS_INSTANT' ? ('QR-' . substr(md5($code . $merchant), 0, 16)) : null,
                'issued_at' => $issuedAt,
            ]
        );
    }

    private function kliringReference(Carbon $period): string
    {
        return 'KLIRING-DLH-' . $period->format('Y-m') . '-B1';
    }

    private function shortCode(string $market): string
    {
        return Str::of($market)
            ->replace('Pasar ', '')
            ->replace('Kawasan Sentra Kuliner ', '')
            ->limit(4, '')
            ->upper();
    }
}