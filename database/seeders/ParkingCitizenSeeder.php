<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\RetributionType;
use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ParkingCitizenSeeder extends Seeder
{
    /**
     * Seeder data warga (wajib pajak) yang memilih layanan retribusi parkir
     * dari Dinas Perhubungan (DISHUB), lengkap dengan object pajak dan tagihan.
     *
     * Setiap warga hanya membuat 2 tagihan dengan status:
     *   - overdue (jatuh tempo sudah lewat dan belum dibayar)
     *   - pending (masih berjalan / belum jatuh tempo)
     * Tidak ada tagihan berstatus lunas. Nama wajib pajak dibuat unik per seeder
     * agar tidak ada duplikat nama.
     */
    public function run(): void
    {
        $dishub = Opd::where('code', 'DISHUB')->first();
        if (!$dishub) {
            $this->command->error('OPD DISHUB tidak ditemukan. Jalankan DatabaseSeeder / RetributionTypeSeeder terlebih dahulu.');
            return;
        }

        $parkirType = RetributionType::where('opd_id', $dishub->id)
            ->where(function ($q) {
                $q->where('name', 'Retribusi Parkir')
                    ->orWhere('name', 'Retribusi Parkir Mobil')
                    ->orWhere('name', 'LIKE', '%Parkir%');
            })
            ->where('category', 'Parkir')
            ->first();

        if (!$parkirType) {
            $this->command->error('RetributionType layanan parkir DISHUB tidak ditemukan.');
            return;
        }

        $this->command->info("Membuat data warga untuk layanan: {$parkirType->name} (DISHUB)");

        $dataset = [
            ['name' => 'Agus Prasetyo',    'nik' => '7472011011880001', 'phone' => '081234560001', 'address' => 'Jl. La Ode Hadi, Wameo',              'district' => 'Batupoaro', 'sub_district' => 'Wameo',      'object_name' => 'Lahan Parkir Pasar Wameo',    'lat' => -5.4573, 'lng' => 122.6035, 'omzet' => 12000000],
            ['name' => 'Rina Marlina',     'nik' => '7472012022880002', 'phone' => '081234560002', 'address' => 'Jl. Dayanu Ikhsanuddin, Baadia',     'district' => 'Murhum',    'sub_district' => 'Baadia',     'object_name' => 'Gedung Parkir Baadia',        'lat' => -5.4510, 'lng' => 122.5975, 'omzet' => 25000000],
            ['name' => 'Sulaiman Darwis',  'nik' => '7472013033880003', 'phone' => '081234560003', 'address' => 'Jl. Wangkanapi, Wangkanapi',          'district' => 'Wolio',     'sub_district' => 'Wangkanapi', 'object_name' => 'Area Parkir Pelabuhan',       'lat' => -5.4695, 'lng' => 122.6060, 'omzet' => 18000000],
            ['name' => 'Nurhayati Amin',   'nik' => '7472014044880004', 'phone' => '081234560004', 'address' => 'Jl. Lipu Permai, Lipu',              'district' => 'Betoambari','sub_district' => 'Lipu',       'object_name' => 'Parkir Pusat Kota Lipu',     'lat' => -5.4445, 'lng' => 122.5885, 'omzet' => 35000000],
            ['name' => 'La Ode Muh Akbar', 'nik' => '7472015055880005', 'phone' => '081234560005', 'address' => 'Jl. Sukanaeyo, Sukanaeyo',            'district' => 'Kokalukuna','sub_district' => 'Sukanaeyo',  'object_name' => 'Parkir Objek Wisata',        'lat' => -5.4790, 'lng' => 122.6170, 'omzet' => 8000000],
            ['name' => 'Wa Ode Fitri',     'nik' => '7472016066880006', 'phone' => '081234560006', 'address' => 'Jl. Nusantara, Bataraguru',           'district' => 'Wolio',     'sub_district' => 'Bataraguru', 'object_name' => 'Parkir Terminal Bataraguru',  'lat' => -5.4640, 'lng' => 122.6080, 'omzet' => 15000000],
            ['name' => 'Abdul Karim',      'nik' => '7472017077880007', 'phone' => '081234560007', 'address' => 'Jl. Bataraguru, Kadolomoko',           'district' => 'Wolio',     'sub_district' => 'Kadolomoko', 'object_name' => 'Parkir Rumah Sakit',         'lat' => -5.4662, 'lng' => 122.6018, 'omzet' => 10000000],
            ['name' => 'Hasnah Basri',     'nik' => '7472018088880008', 'phone' => '081234560008', 'address' => 'Jl. Toromo, Waborobo',                'district' => 'Betoambari','sub_district' => 'Waborobo',   'object_name' => 'Parkir Alun-Alun Waborobo',  'lat' => -5.4480, 'lng' => 122.5920, 'omzet' => 20000000],
            ['name' => 'La Ode Syahrul',   'nik' => '7472019099880009', 'phone' => '081234560009', 'address' => 'Jl. Rusa, Bukit Wolio Indah',          'district' => 'Wolio',     'sub_district' => 'Tomba',      'object_name' => 'Parkir Masjid Raya',         'lat' => -5.4700, 'lng' => 122.6025, 'omzet' => 9000000],
            ['name' => 'Wa Ode Hasna',     'nik' => '7472010102880010', 'phone' => '081234560010', 'address' => 'Jl. ST. Hamid, WaJii',                  'district' => 'Murhum',    'sub_district' => 'Wajo',       'object_name' => 'Parkir Pantai',              'lat' => -5.4530, 'lng' => 122.5950, 'omzet' => 13000000],
        ];

        Carbon::setLocale('id');
        $now = Carbon::now();

        foreach ($dataset as $idx => $data) {
            $tp = Taxpayer::updateOrCreate(
                ['nik' => $data['nik']],
                [
                    'opd_id' => $dishub->id,
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'district' => $data['district'],
                    'sub_district' => $data['sub_district'],
                    'phone' => $data['phone'],
                    'npwpd' => 'NPWPD-PRK-' . str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT),
                    'object_name' => $data['object_name'],
                    'object_address' => $data['address'],
                    'latitude' => $data['lat'],
                    'longitude' => $data['lng'],
                    'is_active' => true,
                    'password' => Hash::make('password'),
                    'metadata' => [
                        'tarif_pajak' => '10',
                        'omset_penjualan' => $data['omzet'],
                        'keterangan_usaha' => 'Aktif',
                        'nama_jenis_usaha' => 'Lahan Parkir',
                    ],
                ]
            );

            // Warga memilih layanan retribusi parkir (tanpa klasifikasi khusus untuk DISHUB)
            $tp->retributionTypes()->syncWithoutDetaching([$parkirType->id]);

            $object = TaxObject::updateOrCreate(
                ['nop' => 'PRK-' . $data['nik']],
                [
                    'taxpayer_id' => $tp->id,
                    'retribution_type_id' => $parkirType->id,
                    'opd_id' => $dishub->id,
                    'name' => $data['object_name'] . ' - ' . $data['name'],
                    'address' => $data['address'],
                    'latitude' => $data['lat'],
                    'longitude' => $data['lng'],
                    'transaction_type' => 'perekaman_data',
                    'status' => 'active',
                    'is_active' => true,
                    'metadata' => [
                        'omzet' => $data['omzet'],
                        'keterangan_usaha' => 'Aktif',
                    ],
                ]
            );

            // Hapus tagihan/pembayaran lama agar re-run tidak menyebabkan duplikat
            foreach (\App\Models\Bill::where('taxpayer_id', $tp->id)->pluck('id') as $oldBillId) {
                \App\Models\Payment::where('bill_id', $oldBillId)->delete();
                \App\Models\Bill::destroy($oldBillId);
            }

            // Tagihan untuk 2 status: overdue (bulan lalu) dan pending (bulan berjalan)
            $periodOverdue = $now->copy()->subMonths(1);
            \App\Models\Bill::create([
                'bill_number' => 'BL-PRK-' . $data['nik'] . '-' . $periodOverdue->format('Ym') . '-OVERDUE',
                'taxpayer_id' => $tp->id,
                'tax_object_id' => $object->id,
                'opd_id' => $dishub->id,
                'retribution_type_id' => $parkirType->id,
                'amount' => round($data['omzet'] * 0.1),
                'period' => $periodOverdue->format('F Y'),
                'period_start' => $periodOverdue->copy()->startOfMonth(),
                'period_end' => $periodOverdue->copy()->endOfMonth(),
                'status' => 'pending',
                // Jatuh tempo sudah lewat -> berubah menjadi 'overdue' lewat accessor
                'due_date' => $periodOverdue->copy()->day(10),
            ]);

            $periodPending = $now->copy();
            \App\Models\Bill::create([
                'bill_number' => 'BL-PRK-' . $data['nik'] . '-' . $periodPending->format('Ym') . '-PENDING',
                'taxpayer_id' => $tp->id,
                'tax_object_id' => $object->id,
                'opd_id' => $dishub->id,
                'retribution_type_id' => $parkirType->id,
                'amount' => round($data['omzet'] * 0.1),
                'period' => $periodPending->format('F Y'),
                'period_start' => $periodPending->copy()->startOfMonth(),
                'period_end' => $periodPending->copy()->endOfMonth(),
                'status' => 'pending',
                // Jatuh tempo masih di masa depan -> tetap 'pending'
                'due_date' => $periodPending->copy()->endOfMonth(),
            ]);
        }

        $this->command->info('Sebanyak ' . count($dataset) . ' warga pemilih layanan parkir DISHUB berhasil dibuat.');
    }
}
