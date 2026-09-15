<?php

namespace Database\Seeders;

use App\Models\Bill;
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
use Illuminate\Support\Str;

/**
 * Data operasional petugas lapangan PUPR (retribusi-petugas):
 * 1) Pendaftaran wajib pajak — variasi status registrasi.
 * 2) Status billing Sewa Alat Berat + Denda Overtime dengan variasi status
 *    (lunas, menunggu bayar, jatuh tempo) agar dashboard petugas terisi.
 *
 * Catatan penting: petugas tanpa UserRetributionAssignment TIDAK melihat bill
 * dan statistik apa pun. Seeder ini membuat assignments untuk akun
 * pupr.petugas@baubaukota.go.id.
 *
 * Idempotent: aman dijalankan ulang.
 */
class PuprPetugasSeeder extends Seeder
{
    public function run(): void
    {
        $pupr = Opd::where('code', 'PUPR')->first();
        if (!$pupr) {
            $this->command?->error('Seeder ini membutuhkan OPD PUPR (jalankan PuprAssetSeeder terlebih dahulu).');
            return;
        }

        $petugas = User::where('email', 'pupr.petugas@baubaukota.go.id')->first();
        if (!$petugas) {
            $this->command?->error('Akun pupr.petugas@baubaukota.go.id tidak ditemukan (jalankan PuprAssetSeeder).');
            return;
        }

        $type = RetributionType::where('opd_id', $pupr->id)->where('name', 'Sewa Alat Berat')->first();
        $overTimeClass = RetributionClassification::where('code', 'DENDA-OVERTIME-SEWA-ALAT')->first();
        if (!$type) {
            $this->command?->error('Retribution Type "Sewa Alat Berat" PUPR tidak ditemukan.');
            return;
        }

        // ============================================================
        // 1. Assignments — syarat agar /api/bills, statistik dashboard &
        //    map potensi memperlihatkan tipe Sewa Alat Berat & Denda Overtime.
        // ============================================================
        UserRetributionAssignment::firstOrCreate([
            'user_id' => $petugas->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => null,
        ]);
        if ($overTimeClass) {
            UserRetributionAssignment::firstOrCreate([
                'user_id' => $petugas->id,
                'retribution_type_id' => $type->id,
                'retribution_classification_id' => $overTimeClass->id,
            ]);
        }

        // ============================================================
        // 2. Pendaftaran wajib pajak.
        // ============================================================
        $registrations = [
            [
                'nik' => '7471012304880003',
                'name' => 'PT. Bumi Wanti Karya',
                'address' => 'Jl. Sultan Hasanuddin No. 17, Kel. Baadia, Kec. Murhum, Baubau',
                'district' => 'Murhum',
                'sub_district' => 'Baadia',
                'phone' => '085299999110',
                'object_status' => 'pending',   // baru didaftarkan, menunggu verifikasi admin
            ],
            [
                'nik' => '7471011101840004',
                'name' => 'CV. Mandiri Konstruksi',
                'address' => 'Jl. Sorawolio No. 9, Kel. Bataraguru, Kec. Wolio, Baubau',
                'district' => 'Wolio',
                'sub_district' => 'Bataraguru',
                'phone' => '082193847562',
                'object_status' => 'active',
            ],
            [
                'nik' => '7471010903770005',
                'name' => 'UPTD Mitra Alat Berat',
                'address' => 'Komp. Pergudangan Kel. Wangkanapi, Kec. Wolio, Baubau',
                'district' => 'Wolio',
                'sub_district' => 'Wangkanapi',
                'phone' => '081340011223',
                'object_status' => 'active',
            ],
            [
                'nik' => '7471015205890006',
                'name' => 'Siti Rahma (Penyedia Sedot Kakus)',
                'address' => 'Jl. Ade Irma Nasution No. 3, Kel. Tomba, Kec. Kokalukuna, Baubau',
                'district' => 'Kokalukuna',
                'sub_district' => 'Tomba',
                'phone' => '082156789012',
                'object_status' => 'pending',
            ],
            [
                'nik' => '7471011203780007',
                'name' => 'CV. Konstruksi Bhaladika',
                'address' => 'Jl. Nusantara No. 21, Kel. Bone-Bone, Kec. Batupoaro, Baubau',
                'district' => 'Batupoaro',
                'sub_district' => 'Bone-Bone',
                'phone' => '081339988776',
                'object_status' => 'active',
            ],
            [
                'nik' => '7471011808810008',
                'name' => 'Usman Kerangkeng (Non Aktif)',
                'address' => 'Jl. Kartini No. 14, Kel. Wajo, Kec. Batupoaro, Baubau',
                'district' => 'Batupoaro',
                'sub_district' => 'Wajo',
                'phone' => '082199001122',
                'object_status' => 'active',
                'is_active' => false,
            ],
        ];

        foreach ($registrations as $i => $data) {
            $taxpayer = Taxpayer::withoutGlobalScopes()->firstOrNew(['nik' => $data['nik']]);
            $taxpayer->fill([
                'opd_id' => $pupr->id,
                'name' => $data['name'],
                'address' => $data['address'],
                'district' => $data['district'],
                'sub_district' => $data['sub_district'],
                'phone' => $data['phone'],
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $petugas->id,
                'created_at' => Carbon::now()->subDays(count($registrations) - $i),
                'updated_at' => Carbon::now()->subDays(count($registrations) - $i),
            ]);
            if (!$taxpayer->exists) {
                $taxpayer->npwpd = Taxpayer::resolveNpwpd($data['nik']);
            }
            $taxpayer->save();
            $taxpayer->retributionTypes()->syncWithoutDetaching([$type->id]);

            $lat = -5.4600 - ($i * 0.0061);
            $lng = 122.5850 + (($i % 3) * 0.0052);

            $objectNames = [
                'PT. Bumi Wanti Karya' => 'Sewa Excavator PC200 - Proyek Jalan BNS',
                'CV. Mandiri Konstruksi' => 'Sewa Vibro Roller - Pemadatan Jalan',
                'UPTD Mitra Alat Berat' => 'Sewa Wheel Loader - Stockpile PUPR',
                'Siti Rahma (Penyedia Sedot Kakus)' => 'Jasa Sedot Kakus/Mobil Tinja',
                'CV. Konstruksi Bhaladika' => 'Sewa Dump Truck - Pengangkutan Material',
                'Usman Kerangkeng (Non Aktif)' => 'Sewa Mesin Molen - Pekerjaan Siring',
            ];

            TaxObject::updateOrCreate(
                ['nop' => 'AST-' . $data['nik']],
                [
                    'taxpayer_id' => $taxpayer->id,
                    'retribution_type_id' => $type->id,
                    'retribution_classification_id' => $data['object_status'] === 'pending' ? null : ($overTimeClass->id ?? null),
                    'opd_id' => $pupr->id,
                    'name' => $objectNames[$data['name']] ?? ('Sewa Alat Berat - ' . $data['name']),
                    'address' => $data['address'],
                    'district' => $data['district'],
                    'sub_district' => $data['sub_district'],
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'transaction_type' => $data['object_status'] === 'pending' ? 'pendaftaran' : 'perekaman_data',
                    'status' => $data['object_status'],  // pending / active
                    'is_active' => $data['is_active'] ?? true,
                    'created_at' => Carbon::now()->subDays(count($registrations) - $i),
                    'updated_at' => Carbon::now()->subDays(count($registrations) - $i),
                ]
            );

            $this->seedBills($taxpayer, $petugas, $pupr, $type, $overTimeClass);
        }

        $this->command?->info('PuprPetugasSeeder selesai: ' . count($registrations) . ' WP + assignmen petugas.');
        $this->command?->info('Login demo: pupr.petugas@baubaukota.go.id / password123');
    }

    /**
     * Buat tagihan dengan variasi status: lunas, menunggu bayar, jatuh tempo,
     * dan denda overtime. Idempotent per nomor tagihan.
     */
    private function seedBills(Taxpayer $taxpayer, User $petugas, Opd $pupr, RetributionType $type, ?RetributionClassification $overTimeClass): void
    {
        $now = Carbon::now();
        $month = $now->format('Y-m');
        $prevMonth = $now->copy()->subMonth();
        $prevMonthStr = $prevMonth->format('Y-m');
        $prevStart = $prevMonth->copy()->startOfMonth();
        $prevEnd = $prevMonth->copy()->endOfMonth();
        $prevDue = $prevMonth->copy()->addDays(15);

        $taxObjectId = TaxObject::where('taxpayer_id', $taxpayer->id)->value('id');

        // ---- LUNAS (status paid + payment) ----
        $this->createBillWithPayment([
            'user_id' => $petugas->id,
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObjectId,
            'opd_id' => $pupr->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => null,
            'bill_number' => 'SKRD-' . $month . '-LUNAS-' . strtoupper($this->shortCode($taxpayer)),
            'amount' => 14000000,
            'period' => $month,
            'period_start' => $now->copy()->startOfMonth(),
            'period_end' => $now->copy()->endOfMonth(),
            'due_date' => $now->copy()->subDays(5),
            'status' => 'paid',
            'payment' => null,
        ], $petugas, $now->copy()->subDays(2));

        if (!$taxpayer->is_active) {
            return; // WP non aktif: cukup satu riwayat lunas.
        }

        // ---- MENUNGGU BAYAR (pending, belum jatuh tempo) ----
        $this->createBillWithPayment([
            'user_id' => $petugas->id,
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObjectId,
            'opd_id' => $pupr->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => null,
            'bill_number' => 'SKRD-' . $month . '-BYR-' . strtoupper($this->shortCode($taxpayer)),
            'amount' => 14000000,
            'period' => $month,
            'period_start' => $now->copy()->startOfMonth(),
            'period_end' => $now->copy()->endOfMonth(),
            'due_date' => $now->copy()->addDays(10),
            'status' => 'pending',
            'payment' => null,
        ], $petugas, null);

        // ---- JATUH TEMPO (done_date lampau, status pending -> aksesor 'overdue') ----
        $this->createBillWithPayment([
            'user_id' => $petugas->id,
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObjectId,
            'opd_id' => $pupr->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => null,
            'bill_number' => 'SKRD-' . $prevMonthStr . '-OVR-' . strtoupper($this->shortCode($taxpayer)),
            'amount' => 14000000,
            'period' => $prevMonthStr,
            'period_start' => $prevStart,
            'period_end' => $prevEnd,
            'due_date' => $prevDue,
            'status' => 'pending',
            'payment' => null,
        ], $petugas, null);

        // ---- DENDA OVERTIME (klasifikasi DENDA-OVERTIME-SEWA-ALAT) ----
        if ($overTimeClass) {
            $this->createBillWithPayment([
                'user_id' => $petugas->id,
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $taxObjectId,
                'opd_id' => $pupr->id,
                'retribution_type_id' => $type->id,
                'retribution_classification_id' => $overTimeClass->id,
                'bill_number' => 'DND-' . $prevMonthStr . '-OVT-' . strtoupper($this->shortCode($taxpayer)),
                'amount' => 1750000,
                'period' => $prevMonthStr,
                'period_start' => $prevMonth->copy()->startOfMonth(),
                'period_end' => $prevMonth->copy()->endOfMonth(),
                'due_date' => $now->copy()->addDays(7),
                'status' => 'pending',
                'payment' => null,
                'metadata' => [
                    'source' => 'pupr_overtime',
                    'overtime_hours' => 5,
                    'note' => 'Melebihi jam operasional 8 jam/hari (5 jam overtime).',
                ],
            ], $petugas, null);
        }
    }

    /**
     * Buat bill + payment (bila status paid). Idempotent per bill_number.
     */
    private function createBillWithPayment(array $billData, User $petugas, ?Carbon $paidAt): Bill
    {
        $bill = Bill::withoutGlobalScopes()
            ->where('bill_number', $billData['bill_number'])
            ->first();

        unset($billData['payment']);

        if ($bill) {
            // Rujuk tanggal/status bila sudah ada (self-healing pada re-run).
            $bill->update($billData);
            // Pastikan payment untuk bill lunas tetap ada (sinkron pada re-run).
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
            return $bill;
        }

        $bill = Bill::create($billData);

        if ($bill->getRawOriginal('status') === 'paid') {
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

        return $bill;
    }

    private function shortCode(Taxpayer $taxpayer): string
    {
        return Str::substr(preg_replace('/[^A-Za-z]+/', '', $taxpayer->name), 0, 6);
    }
}