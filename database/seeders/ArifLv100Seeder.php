<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Opd;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\RetributionType;
use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ArifLv100Seeder extends Seeder
{
    /**
     * Seeder data wajib pajak yang ditangani petugas ARIF LV100
     * (TEST@sipanda.online) pada Dinas Perhubungan (DISHUB).
     *
     * Membuat 2 tagihan dengan status:
     *   - overdue (jauh melewati jatuh tempo)
     *   - pending (masih berjalan / belum jatuh tempo)
     */
    public function run(): void
    {
        $petugas = User::where('email', 'TEST@sipanda.online')->first();
        if (!$petugas) {
            $this->command->error('Petugas ARIF LV100 (TEST@sipanda.online) tidak ditemukan.');
            return;
        }

        $dishub = Opd::where('code', 'DISHUB')->first();
        if (!$dishub) {
            $this->command->error('OPD DISHUB tidak ditemukan. Jalankan DatabaseSeeder terlebih dahulu.');
            return;
        }

        $parkirType = RetributionType::where('opd_id', $dishub->id)
            ->where('category', 'Parkir')
            ->first();
        if (!$parkirType) {
            $this->command->error('RetributionType parkir DISHUB tidak ditemukan.');
            return;
        }

        $nik = '7472011511900001';
        $now = Carbon::now();

        // Hapus tagihan lama (kolom due_date ber-ON UPDATE CURRENT_TIMESTAMP,
        // sehingga harus di-recreate dari awal agar due_date tersimpan benar).
        $existingIds = \App\Models\Bill::where('taxpayer_id', function ($q) use ($nik) {
            $q->select('id')->from('taxpayers')->where('nik', $nik)->limit(1);
        })->pluck('id');
        if ($existingIds->count()) {
            \App\Models\Payment::whereIn('bill_id', $existingIds)->delete();
            \App\Models\Bill::whereIn('id', $existingIds)->delete();
        }

        $tp = Taxpayer::updateOrCreate(
            ['nik' => $nik],
            [
                'opd_id' => $dishub->id,
                'name' => 'Hendra Wijaya',
                'address' => 'Jl. Lasolangi No. 10, Kel. Wameo, Kec. Batupoaro, Kota Baubau',
                'district' => 'Batupoaro',
                'sub_district' => 'Wameo',
                'phone' => '082199900011',
                'npwpd' => 'NPWPD-PRK-ARIF01',
                'object_name' => 'Lahan Parkir Jl. Lasolangi',
                'object_address' => 'Jl. Lasolangi No. 10',
                'latitude' => -5.4573,
                'longitude' => 122.6035,
                'is_active' => true,
                'password' => Hash::make('password'),
                'metadata' => [
                    'email' => 'hendra.wijaya@example.com',
                    'mother_name' => 'Siti Rahma',
                    'tarif_pajak' => '10',
                    'omset_penjualan' => 15000000,
                    'keterangan_usaha' => 'Aktif',
                    'nama_jenis_usaha' => 'Lahan Parkir',
                    'petugas' => $petugas->name,
                    'petugas_email' => $petugas->email,
                ],
            ]
        );

        $tp->retributionTypes()->syncWithoutDetaching([$parkirType->id]);

        $object = TaxObject::updateOrCreate(
            ['nop' => 'PRK-' . $nik],
            [
                'taxpayer_id' => $tp->id,
                'retribution_type_id' => $parkirType->id,
                'opd_id' => $dishub->id,
                'name' => 'Lahan Parkir Jl. Lasolangi - Hendra Wijaya',
                'address' => 'Jl. Lasolangi No. 10',
                'latitude' => -5.4573,
                'longitude' => 122.6035,
                'transaction_type' => 'perekaman_data',
                'status' => 'active',
                'is_active' => true,
                'metadata' => [
                    'omzet' => 15000000,
                    'keterangan_usaha' => 'Aktif',
                ],
            ]
        );

        // ===== 1. Tagihan OVERDUE =====
        $bulanOverdue = $now->copy()->subMonths(1);
        $billOverdue = Bill::updateOrCreate(
            ['bill_number' => 'BL-PRK-' . $nik . '-' . $bulanOverdue->format('Ym') . '-OVERDUE'],
            [
                'user_id' => $petugas->id,
                'taxpayer_id' => $tp->id,
                'tax_object_id' => $object->id,
                'opd_id' => $dishub->id,
                'retribution_type_id' => $parkirType->id,
                'amount' => 1500000,
                'status' => 'pending',
                'period' => $bulanOverdue->format('F Y'),
                'period_start' => $bulanOverdue->copy()->startOfMonth(),
                'period_end' => $bulanOverdue->copy()->endOfMonth(),
                // Jatuh tempo sudah lewat -> berubah menjadi 'overdue' lewat accessor
                'due_date' => $bulanOverdue->copy()->day(10),
            ]
        );

        // ===== 2. Tagihan PENDING =====
        $bulanPending = $now->copy();
        $billPending = Bill::updateOrCreate(
            ['bill_number' => 'BL-PRK-' . $nik . '-' . $bulanPending->format('Ym') . '-PENDING'],
            [
                'user_id' => $petugas->id,
                'taxpayer_id' => $tp->id,
                'tax_object_id' => $object->id,
                'opd_id' => $dishub->id,
                'retribution_type_id' => $parkirType->id,
                'amount' => 1500000,
                'status' => 'pending',
                'period' => $bulanPending->format('F Y'),
                'period_start' => $bulanPending->copy()->startOfMonth(),
                'period_end' => $bulanPending->copy()->endOfMonth(),
                // Jatuh tempo masih di masa depan -> tetap 'pending'
                'due_date' => $bulanPending->copy()->endOfMonth(),
            ]
        );

        $this->command->info('✅ Seeder wajib pajak petugas ARIF LV100 (DISHUB) dibuat:');
        $this->command->info('   NIK        : ' . $nik);
        $this->command->info('   Nama       : ' . $tp->name);
        $this->command->info('   Petugas    : ' . $petugas->name . ' (' . $petugas->email . ')');
        $this->command->info('   OPD        : ' . $dishub->name);
        $this->command->info('');
        $this->command->info('   Tagihan OVERDUE  : ' . $billOverdue->bill_number . '  (Rp ' . number_format($billOverdue->amount) . ')');
        $this->command->info('   Tagihan PENDING  : ' . $billPending->bill_number . '  (Rp ' . number_format($billPending->amount) . ')');
    }
}
