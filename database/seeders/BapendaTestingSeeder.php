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
use App\Models\Verification;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * BapendaTestingSeeder
 *
 * Seeder lengkap untuk mengisi dashboard, peta, billing, dan data pengujian 
 * lokal Bapenda. Semua titik GPS berada di daratan Kota Baubau.
 *
 * Mengisi: Taxpayers + TaxObjects + Bills + Payments + Verifications
 * Per wilayah: minimal 5 titik
 *
 * Jalankan: php artisan db:seed --class=BapendaTestingSeeder
 */
class BapendaTestingSeeder extends Seeder
{
    /**
     * Koordinat daratan Kota Baubau per Kecamatan
     * Semua titik sudah diverifikasi berada di daratan, BUKAN di laut.
     */
    private array $baubauCoords = [
        'Batupoaro' => [
            ['lat' => -5.4573, 'lng' => 122.6035, 'address' => 'Jl. La Ode Hadi, Wameo'],
            ['lat' => -5.4590, 'lng' => 122.6055, 'address' => 'Jl. Sultan Hasanuddin, Bone-bone'],
            ['lat' => -5.4605, 'lng' => 122.6020, 'address' => 'Jl. Pahlawan, Tarafu'],
            ['lat' => -5.4565, 'lng' => 122.6070, 'address' => 'Jl. Kartini, Kaobula'],
            ['lat' => -5.4585, 'lng' => 122.6000, 'address' => 'Jl. Diponegoro, Lanto'],
        ],
        'Murhum' => [
            ['lat' => -5.4485, 'lng' => 122.5950, 'address' => 'Jl. Sulthan M. Aidrus, Melai'],
            ['lat' => -5.4510, 'lng' => 122.5975, 'address' => 'Jl. Dayanu Ikhsanuddin, Baadia'],
            ['lat' => -5.4495, 'lng' => 122.5930, 'address' => 'Jl. La Ode Wajo, Wajo'],
            ['lat' => -5.4470, 'lng' => 122.5965, 'address' => 'Jl. Lamangga Raya, Lamangga'],
            ['lat' => -5.4525, 'lng' => 122.5940, 'address' => 'Jl. Tanganapada, Tanganapada'],
        ],
        'Wolio' => [
            ['lat' => -5.4640, 'lng' => 122.6080, 'address' => 'Jl. Bataraguru, Bataraguru'],
            ['lat' => -5.4670, 'lng' => 122.6100, 'address' => 'Jl. Tomba Raya, Tomba'],
            ['lat' => -5.4695, 'lng' => 122.6060, 'address' => 'Jl. Wangkanapi, Wangkanapi'],
            ['lat' => -5.4620, 'lng' => 122.6120, 'address' => 'Jl. Bukit Wolio Indah, BWI'],
            ['lat' => -5.4655, 'lng' => 122.6045, 'address' => 'Jl. Kadolokatapi, Kadolokatapi'],
            ['lat' => -5.4680, 'lng' => 122.6135, 'address' => 'Jl. Wale Besar, Wale'],
        ],
        'Betoambari' => [
            ['lat' => -5.4390, 'lng' => 122.5900, 'address' => 'Jl. Sulaa Raya, Sulaa'],
            ['lat' => -5.4420, 'lng' => 122.5870, 'address' => 'Jl. Waborobo, Waborobo'],
            ['lat' => -5.4370, 'lng' => 122.5920, 'address' => 'Jl. Labalawa, Labalawa'],
            ['lat' => -5.4445, 'lng' => 122.5885, 'address' => 'Jl. Lipu Permai, Lipu'],
            ['lat' => -5.4405, 'lng' => 122.5945, 'address' => 'Jl. Katobengke, Katobengke'],
        ],
        'Kokalukuna' => [
            ['lat' => -5.4750, 'lng' => 122.6180, 'address' => 'Jl. Kadolomoko, Kadolomoko'],
            ['lat' => -5.4770, 'lng' => 122.6150, 'address' => 'Jl. Waruruma, Waruruma'],
            ['lat' => -5.4800, 'lng' => 122.6200, 'address' => 'Jl. Lakologou, Lakologou'],
            ['lat' => -5.4735, 'lng' => 122.6220, 'address' => 'Jl. Liwuto, Liwuto'],
            ['lat' => -5.4790, 'lng' => 122.6170, 'address' => 'Jl. Sukanaeyo, Sukanaeyo'],
        ],
    ];

    /**
     * Data Wajib Pajak realistis per jenis usaha
     */
    private array $businesses = [
        'PBJT-MNM' => [
            ['name' => 'RM Padang Sederhana', 'obj' => 'Rumah Makan Padang', 'omzet' => 15000000],
            ['name' => 'Warung Nasi Kuning Ibu Siti', 'obj' => 'Warung Nasi Kuning', 'omzet' => 5000000],
            ['name' => 'Cafe Baubau Kopi', 'obj' => 'Coffee Shop', 'omzet' => 20000000],
            ['name' => 'Bakso Pak Joko', 'obj' => 'Warung Bakso', 'omzet' => 8000000],
            ['name' => 'Seafood Pantai Nirwana', 'obj' => 'Rumah Makan Seafood', 'omzet' => 25000000],
        ],
        'PBJT-HTL' => [
            ['name' => 'Hotel Grand Wolio', 'obj' => 'Hotel Bintang 3', 'omzet' => 80000000],
            ['name' => 'Penginapan Pantai Kamali', 'obj' => 'Penginapan', 'omzet' => 15000000],
            ['name' => 'Villa Buton View', 'obj' => 'Villa', 'omzet' => 30000000],
            ['name' => 'Homestay Kota Lama', 'obj' => 'Homestay', 'omzet' => 10000000],
            ['name' => 'Hotel Sahid Baubau', 'obj' => 'Hotel Bintang 2', 'omzet' => 45000000],
        ],
        'PBJT-PRK' => [
            ['name' => 'Parkir Pasar Wameo', 'obj' => 'Lahan Parkir Pasar', 'omzet' => 12000000],
            ['name' => 'Parkir Mall Baubau', 'obj' => 'Gedung Parkir', 'omzet' => 25000000],
            ['name' => 'Parkir Pelabuhan Murhum', 'obj' => 'Area Parkir Pelabuhan', 'omzet' => 18000000],
            ['name' => 'PT Jaya Parkir Mandiri', 'obj' => 'Parkir Pusat Kota', 'omzet' => 35000000],
            ['name' => 'Parkir Wisata Pantai', 'obj' => 'Parkir Objek Wisata', 'omzet' => 8000000],
        ],
        'PBJT-HBR' => [
            ['name' => 'Karaoke Family Box', 'obj' => 'Studio Karaoke', 'omzet' => 20000000],
            ['name' => 'Billiard Center Wolio', 'obj' => 'Pusat Billiard', 'omzet' => 10000000],
            ['name' => 'Taman Bermain Anak Ceria', 'obj' => 'Arena Bermain', 'omzet' => 15000000],
            ['name' => 'GOR Baubau Entertainment', 'obj' => 'Gedung Serbaguna', 'omzet' => 30000000],
            ['name' => 'Bioskop Mini Baubau', 'obj' => 'Bioskop', 'omzet' => 40000000],
        ],
        'PBJT-LIS' => [
            ['name' => 'PT PLN Distribusi Baubau', 'obj' => 'Gardu Listrik', 'omzet' => 500000000],
            ['name' => 'Genset Industri Mandiri', 'obj' => 'Pembangkit Listrik Swasta', 'omzet' => 80000000],
            ['name' => 'Solar Panel Mall', 'obj' => 'Instalasi Solar', 'omzet' => 20000000],
            ['name' => 'CV Energi Mandiri', 'obj' => 'Pembangkit Diesel', 'omzet' => 45000000],
            ['name' => 'Koperasi Listrik Desa', 'obj' => 'Pembangkit Mikro', 'omzet' => 6000000],
        ],
    ];

    /**
     * Nama-nama WP umum
     */
    private array $names = [
        'Ahmad Subarjo', 'Siti Nurhaliza', 'Budi Santoso', 'Dewi Lestari', 'Muhammad Rizky',
        'Ani Rahayu', 'Hasan Al-Bashri', 'Fatimah Zahra', 'La Ode Rahman', 'Wa Ode Sarina',
        'Agus Prasetyo', 'Rina Marlina', 'Sulaiman Darwis', 'Nurhayati Amin', 'La Ode Muh Akbar',
        'Wa Ode Fitri', 'Abdul Karim', 'Salmah Basri', 'La Ode Syahrul', 'Wa Ode Hasna',
        'Irfan Hakim', 'Marwah Said', 'La Ode Safri', 'Wa Ode Nursia', 'Ruslan Abadi',
    ];

    public function run(): void
    {
        $this->command->info('🏗️  Memulai seeding data testing Bapenda...');

        $bapenda = Opd::where('code', 'BAPENDA')->first();
        if (!$bapenda) {
            $this->command->error('OPD BAPENDA tidak ditemukan! Jalankan DatabaseSeeder dulu.');
            return;
        }

        $admin = User::where('email', 'bapenda@baubaukota.go.id')->first();
        $petugas = User::where('email', 'petugas@bapenda.go.id')->first();

        if (!$admin || !$petugas) {
            $this->command->error('User admin/petugas BAPENDA tidak ditemukan!');
            return;
        }

        $nameIdx = 0;
        $billCounter = 1;
        $kecamatanList = array_keys($this->baubauCoords);

        foreach ($this->businesses as $clsCode => $businessList) {
            $classification = RetributionClassification::where('code', $clsCode)->first();
            if (!$classification) {
                $this->command->warn("Classification {$clsCode} not found, skipping");
                continue;
            }

            $type = RetributionType::find($classification->retribution_type_id);

            $this->command->info("  📋 Seeding {$clsCode}: {$classification->name}");

            foreach ($businessList as $bizIdx => $biz) {
                $kecIdx = $bizIdx % count($kecamatanList);
                $kecamatan = $kecamatanList[$kecIdx];
                $coordList = $this->baubauCoords[$kecamatan];
                $coord = $coordList[$bizIdx % count($coordList)];

                $wpName = $this->names[$nameIdx % count($this->names)];
                $nameIdx++;

                $nik = '7404' . str_pad(rand(10000000, 99999999), 8, '0') . str_pad(rand(1000, 9999), 4, '0');

                // 1. Create Taxpayer
                $taxpayer = Taxpayer::create([
                    'opd_id' => $bapenda->id,
                    'nik' => $nik,
                    'name' => $wpName,
                    'address' => $coord['address'],
                    'district' => $kecamatan,
                    'sub_district' => explode(', ', $coord['address'])[1] ?? $kecamatan,
                    'phone' => '0821' . rand(10000000, 99999999),
                    'npwpd' => 'NPWPD-' . strtoupper(substr(md5($nik), 0, 8)),
                    'object_name' => $biz['obj'],
                    'object_address' => $coord['address'],
                    'latitude' => $coord['lat'],
                    'longitude' => $coord['lng'],
                    'is_active' => true,
                    'metadata' => [
                        'nama_jenis_usaha' => $biz['obj'],
                        'omset_penjualan' => $biz['omzet'],
                        'tarif_pajak' => '10',
                        'keterangan_usaha' => 'Aktif',
                    ],
                    'created_by' => $petugas->id,
                ]);

                // 2. Attach retribution type + classification
                $taxpayer->retributionTypes()->attach($type->id, [
                    'retribution_classification_id' => $classification->id,
                ]);

                // 3. Create Tax Object
                $nop = $taxpayer->npwpd . '-' . $type->id . '-' . $classification->id;
                $taxObject = TaxObject::create([
                    'nop' => $nop,
                    'taxpayer_id' => $taxpayer->id,
                    'retribution_type_id' => $type->id,
                    'retribution_classification_id' => $classification->id,
                    'opd_id' => $bapenda->id,
                    'name' => $biz['obj'] . ' - ' . $wpName,
                    'address' => $coord['address'],
                    'latitude' => $coord['lat'],
                    'longitude' => $coord['lng'],
                    'status' => 'active',
                    'metadata' => [
                        'omzet' => $biz['omzet'],
                        'keterangan_usaha' => 'Aktif',
                    ],
                ]);

                // 4. Create Verification
                Verification::create([
                    'opd_id' => $bapenda->id,
                    'user_id' => $petugas->id,
                    'taxpayer_id' => $taxpayer->id,
                    'tax_object_id' => $taxObject->id,
                    'document_number' => 'VRF-' . date('Ym') . '-' . str_pad($billCounter, 4, '0', STR_PAD_LEFT),
                    'taxpayer_name' => $wpName,
                    'type' => 'field_survey',
                    'amount' => $biz['omzet'] * 0.1,
                    'status' => $bizIdx < 3 ? 'approved' : ($bizIdx < 4 ? 'pending' : 'submitted'),
                    'notes' => "Survei lapangan {$biz['obj']} di {$kecamatan}",
                    'verifier_id' => $admin->id,
                    'submitted_at' => Carbon::now()->subDays(rand(5, 30)),
                    'verified_at' => $bizIdx < 3 ? Carbon::now()->subDays(rand(1, 5)) : null,
                ]);

                // 5. Create Bills (3 months for each taxpayer)
                $months = ['Januari 2026', 'Februari 2026', 'Maret 2026'];
                foreach ($months as $mIdx => $month) {
                    $billAmount = $biz['omzet'] * 0.1; // 10% pajak
                    $isPaid = $mIdx < 2 && $bizIdx < 3; // Only first 2 months for first 3 businesses
                    $isPending = !$isPaid && ($mIdx < 2);
                    $isOverdue = $mIdx === 0 && $bizIdx >= 3;

                    $billStatus = $isPaid ? 'paid' : ($isOverdue ? 'overdue' : 'unpaid');
                    $penaltyAmount = $isOverdue ? $billAmount * 0.02 : 0;

                    $bill = Bill::create([
                        'taxpayer_id' => $taxpayer->id,
                        'tax_object_id' => $taxObject->id,
                        'opd_id' => $bapenda->id,
                        'user_id' => $admin->id,
                        'retribution_type_id' => $type->id,
                        'retribution_classification_id' => $classification->id,
                        'bill_number' => 'BIL-' . date('Y') . '-' . str_pad($billCounter++, 5, '0', STR_PAD_LEFT),
                        'amount' => $billAmount,
                        'penalty_amount' => $penaltyAmount,
                        'status' => $billStatus,
                        'period' => $month,
                        'period_start' => Carbon::create(2026, $mIdx + 1, 1),
                        'period_end' => Carbon::create(2026, $mIdx + 1, 1)->endOfMonth(),
                        'due_date' => Carbon::create(2026, $mIdx + 1, 15),
                    ]);

                    // 6. Create Payment for paid bills
                    if ($isPaid) {
                        Payment::create([
                            'bill_id' => $bill->id,
                            'billing_period' => $month,
                            'taxpayer_id' => $taxpayer->id,
                            'tax_object_id' => $taxObject->id,
                            'payment_method' => ['cash', 'transfer', 'qris'][rand(0, 2)],
                            'amount' => $billAmount,
                            'status' => 'approved',
                            'approved_by' => $admin->id,
                            'paid_at' => Carbon::create(2026, $mIdx + 1, rand(10, 14)),
                        ]);
                    }
                }
            }
        }

        // === SUMMARY ===
        $this->command->newLine();
        $this->command->info('✅ Seeding Bapenda Testing SELESAI!');
        $this->command->table(
            ['Data', 'Jumlah'],
            [
                ['Taxpayers', Taxpayer::where('opd_id', $bapenda->id)->count()],
                ['Tax Objects', TaxObject::where('opd_id', $bapenda->id)->count()],
                ['Bills', Bill::where('opd_id', $bapenda->id)->count()],
                ['Payments', Payment::whereHas('bill', fn($q) => $q->where('opd_id', $bapenda->id))->count()],
                ['Verifications', Verification::where('opd_id', $bapenda->id)->count()],
            ]
        );
    }
}
