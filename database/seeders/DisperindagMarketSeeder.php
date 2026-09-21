<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Opd;
use App\Models\Market;
use App\Models\MarketStall;
use App\Models\MarketOfficerAssignment;
use App\Models\Zone;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\RetributionClassification;
use App\Models\RetributionRate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DisperindagMarketSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Memulai seeding Data Digitalisasi Pasar Disperindag...');

        // 1. Dapatkan atau Buat OPD Disperindag
        $disperindag = Opd::where('code', 'DISPERINDAG')
            ->orWhere('name', 'like', '%Perdagangan%')
            ->first();

        if (!$disperindag) {
            $disperindag = Opd::create([
                'code' => 'DISPERINDAG',
                'name' => 'Dinas Perdagangan, Perindustrian, Koperasi, dan UKM',
                'address' => 'Jl. Balai Kota No. 1 / Jl. Pasar No. 2, Kota Baubau',
                'phone' => '0401-654321',
                'email' => 'disperindag@baubaukota.go.id',
                'status' => 'approved',
                'is_active' => true,
            ]);
        }
        $opdId = $disperindag->id;

        // 2. Sinkronkan Klasifikasi Retribusi (ID 202 RET-PSR & ID 203 JU-TKU)
        $classificationPsr = RetributionClassification::find(202);
        if ($classificationPsr) {
            $classificationPsr->update([
                'opd_id' => $opdId,
                'is_active' => true,
                'description' => 'Retribusi Pelayanan Pasar (Jasa Umum) — los, pelataran harian/subuh, dan sarana penunjang Perda 1/2024.'
            ]);
        }

        $classificationTku = RetributionClassification::find(203);
        if ($classificationTku) {
            $classificationTku->update([
                'opd_id' => $opdId,
                'is_active' => true,
                'description' => 'Pemanfaatan Kekayaan Daerah: Sewa Kios Pasar (Jasa Usaha) Perda 1/2024.'
            ]);
        }

        // 3. Masukkan Tarif Resmi Perda No. 1 Tahun 2024 ke retribution_rates
        if ($classificationPsr) {
            $ratesPsr = [
                ['name' => 'Karcis Pelataran / Pedagang Harian (PKL Subuh)', 'amount' => 1000, 'unit' => 'hari'],
                ['name' => 'Los Pasar A (RB 64 Los)', 'amount' => 40000, 'unit' => 'bulan'],
                ['name' => 'Los Pasar B (Gedung Kios 23 & Gedung Biru)', 'amount' => 35000, 'unit' => 'bulan'],
                ['name' => 'Los Pasar C (Los Baja Ringan)', 'amount' => 30000, 'unit' => 'bulan'],
                ['name' => 'Kios Pasar (Pelayanan Pasar Bulanan)', 'amount' => 40000, 'unit' => 'bulan'],
            ];
            foreach ($ratesPsr as $r) {
                RetributionRate::updateOrCreate(
                    [
                        'retribution_classification_id' => $classificationPsr->id,
                        'name' => $r['name'],
                    ],
                    [
                        'opd_id' => $opdId,
                        'retribution_type_id' => 17,
                        'amount' => $r['amount'],
                        'unit' => $r['unit'],
                        'is_active' => true,
                    ]
                );
            }
        }

        if ($classificationTku) {
            $ratesTku = [
                ['name' => 'Sewa Kios Permanen Pasar Wameo (Tahunan)', 'amount' => 3000000, 'unit' => 'tahun'],
                ['name' => 'Sewa Kios Semi Permanen / Los Orens (Tahunan)', 'amount' => 1500000, 'unit' => 'tahun'],
                ['name' => 'Sewa Kios Pasar Karya Baru Tahap I (Tahunan)', 'amount' => 800000, 'unit' => 'tahun'],
                ['name' => 'Sewa Kios Pasar Karya Baru Tahap II (Tahunan)', 'amount' => 600000, 'unit' => 'tahun'],
            ];
            foreach ($ratesTku as $r) {
                RetributionRate::updateOrCreate(
                    [
                        'retribution_classification_id' => $classificationTku->id,
                        'name' => $r['name'],
                    ],
                    [
                        'opd_id' => $opdId,
                        'retribution_type_id' => 17,
                        'amount' => $r['amount'],
                        'unit' => $r['unit'],
                        'is_active' => true,
                    ]
                );
            }
        }

        // 4. Baca JSON Master Data
        $jsonPath = database_path('data/market_disperindag_master.json');
        if (!File::exists($jsonPath)) {
            $this->command->error("File {$jsonPath} tidak ditemukan!");
            return;
        }

        $data = json_decode(File::get($jsonPath), true);

        // 5. Seed Markets (Level 1 Hierarki)
        $marketMap = [];
        foreach ($data['markets'] as $m) {
            $market = Market::updateOrCreate(
                ['code' => $m['code']],
                [
                    'opd_id' => $opdId,
                    'name' => $m['name'],
                    'type' => $m['type'],
                    'address' => $m['address'],
                    'district' => $m['district'],
                    'latitude' => $m['latitude'],
                    'longitude' => $m['longitude'],
                    'description' => $m['description'],
                    'is_active' => true,
                ]
            );
            $marketMap[$m['code']] = $market;
            $marketMap[$m['name']] = $market;
        }

        // 6. Kumpulkan dan Seed Zones / Gedung (Level 2 Hierarki)
        $allBuildings = [];
        foreach ($data['los'] as $item) {
            $allBuildings[$item['market_code']][$item['building_name']] = 'los';
        }
        foreach ($data['kios'] as $item) {
            $allBuildings[$item['market_code']][$item['building_name']] = 'kios';
        }

        $zoneMap = [];
        $zoneSeq = 1;
        foreach ($allBuildings as $mCode => $buildings) {
            $market = $marketMap[$mCode];
            foreach ($buildings as $bName => $prefType) {
                $slug = Str::slug($bName);
                $code = "ZONE-" . ($mCode === 'MKT-WAMEO' ? 'WAM' : 'KB') . '-' . strtoupper(substr($slug, 0, 12)) . '-' . $zoneSeq++;
                
                $latOffset = ($zoneSeq % 5) * 0.0001;
                $lngOffset = ($zoneSeq % 7) * 0.0001;

                $zone = Zone::updateOrCreate(
                    ['name' => $bName, 'opd_id' => $opdId],
                    [
                        'code' => $code,
                        'retribution_classification_id' => $prefType === 'kios' ? ($classificationTku->id ?? 203) : ($classificationPsr->id ?? 202),
                        'description' => "Gedung / Blok {$bName} di {$market->name}",
                        'geometry_type' => 'point',
                        'latitude' => $market->latitude ? ($market->latitude + $latOffset) : null,
                        'longitude' => $market->longitude ? ($market->longitude + $lngOffset) : null,
                    ]
                );
                $zoneMap[$mCode][$bName] = $zone;
            }
        }

        // Tambahkan Zona Khusus Pelataran Ikan & Sayur Subuh (Quick-Tap PKL)
        $zonePelataran = Zone::updateOrCreate(
            ['name' => 'Pelataran Gedung Ikan & Sayur (Subuh)', 'opd_id' => $opdId],
            [
                'code' => 'ZONE-WAM-PELATARAN-SUBUH',
                'retribution_classification_id' => $classificationPsr->id ?? 202,
                'description' => 'Area Pelataran Gedung Ikan & Sayur Pasar Wameo untuk Pedagang Harian (PKL Subuh)',
                'geometry_type' => 'point',
                'latitude' => -5.4628500,
                'longitude' => 122.6046000,
            ]
        );

        // 7. Seed Market Stalls (Level 3 Hierarki) + Taxpayer & TaxObject Universal
        $combinedStalls = array_merge($data['los'], $data['kios']);
        $totalStalls = count($combinedStalls);
        $this->command->info("Memproses {$totalStalls} unit lapak (Kios & Los)...");

        $nopCounter = 1;
        $createdTaxpayers = 0;
        $createdTaxObjects = 0;

        foreach ($combinedStalls as $idx => $st) {
            $mCode = $st['market_code'];
            $market = $marketMap[$mCode];
            $bName = $st['building_name'];
            $zone = $zoneMap[$mCode][$bName] ?? null;

            $prefix = ($mCode === 'MKT-WAMEO' ? 'WAM' : 'KB');
            $typePrefix = ($st['stall_type'] === 'kios' ? 'KIO' : 'LOS');
            $cleanBName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $bName));
            $cleanBNameShort = substr($cleanBName, 0, 6);
            $fullCode = "{$prefix}-{$typePrefix}-{$cleanBNameShort}-" . str_pad($st['stall_number'], 3, '0', STR_PAD_LEFT);

            $merchantName = $st['merchant_name'];
            $status = $st['status'];
            $tariff = $st['tariff_amount'];
            $cycle = $st['billing_cycle'];
            $notes = $st['notes'];

            $taxpayer = null;
            $taxObject = null;

            // Jika ada pedagang, hubungkan / buat Taxpayer & TaxObject
            if ($merchantName) {
                $emailSlug = Str::slug($merchantName);
                $uniqueEmail = "{$emailSlug}.{$fullCode}@pedagang.mpad.online";

                $taxpayer = Taxpayer::updateOrCreate(
                    [
                        'name' => $merchantName,
                        'opd_id' => $opdId,
                        'object_name' => "{$fullCode} ({$bName})",
                    ],
                    [
                        'email' => $uniqueEmail,
                        'address' => "{$bName}, {$market->name}, Kota Baubau",
                        'object_address' => "{$bName}, {$market->name}",
                        'is_active' => true,
                    ]
                );
                $createdTaxpayers++;

                $classificationId = ($st['stall_type'] === 'kios' && $tariff >= 600000) 
                    ? ($classificationTku->id ?? 203) 
                    : ($classificationPsr->id ?? 202);

                $nopFormatted = sprintf("74.72.03.%s.%s.%04d", 
                    ($prefix === 'WAM' ? '01' : '02'),
                    ($st['stall_type'] === 'kios' ? '20' : '10'),
                    $nopCounter++
                );

                $taxObject = TaxObject::updateOrCreate(
                    ['nop' => $nopFormatted],
                    [
                        'taxpayer_id' => $taxpayer->id,
                        'retribution_type_id' => 17,
                        'retribution_classification_id' => $classificationId,
                        'opd_id' => $opdId,
                        'zone_id' => $zone?->id,
                        'name' => "Unit " . strtoupper($st['stall_type']) . " {$st['stall_number']} {$bName} ({$market->name})",
                        'tipe_penghuni' => 'PENYEWA',
                        'status_hunian' => $status === 'kosong' ? 'KOSONG' : 'BERPENGHUNI',
                        'building_category' => $st['stall_type'],
                        'penanggung_jawab_nama' => $merchantName,
                        'address' => "{$bName} No. {$st['stall_number']}, {$market->name}",
                        'district' => $market->district,
                        'latitude' => $zone?->latitude,
                        'longitude' => $zone?->longitude,
                        'status' => $status === 'tersegel' ? 'suspended' : 'active',
                        'is_active' => true,
                        'metadata' => [
                            'stall_code' => $fullCode,
                            'stall_type' => $st['stall_type'],
                            'building_name' => $bName,
                            'stall_number' => $st['stall_number'],
                            'tariff_amount' => $tariff,
                            'billing_cycle' => $cycle,
                            'notes' => $notes,
                        ]
                    ]
                );
                $createdTaxObjects++;
            }

            // Simpan ke tabel relasional cepat market_stalls
            MarketStall::updateOrCreate(
                [
                    'market_id' => $market->id,
                    'building_name' => $bName,
                    'stall_type' => $st['stall_type'],
                    'stall_number' => $st['stall_number'],
                ],
                [
                    'zone_id' => $zone?->id,
                    'tax_object_id' => $taxObject?->id,
                    'taxpayer_id' => $taxpayer?->id,
                    'market_name' => $market->name,
                    'full_code' => $fullCode,
                    'merchant_name' => $merchantName,
                    'tariff_amount' => $tariff,
                    'billing_cycle' => $cycle,
                    'status' => $status,
                    'notes' => $notes,
                    'metadata' => [
                        'row_index' => $st['row_index'] ?? null,
                    ]
                ]
            );
        }

        // 8. Seed Penugasan Petugas Penagih (Officer-Zone Binding)
        foreach ($data['officers'] as $off) {
            $mCode = $off['market_code'];
            $market = $marketMap[$mCode] ?? null;

            MarketOfficerAssignment::updateOrCreate(
                [
                    'market_name' => $off['market_name'],
                    'officer_name' => $off['officer_name'],
                ],
                [
                    'market_id' => $market?->id,
                    'assigned_areas' => $off['assigned_areas'],
                    'collects_daily_pkl' => $off['collects_daily_pkl'],
                    'signed_by' => $off['signed_by'],
                    'decree_date' => $off['decree_date'],
                    'notes' => $off['notes'],
                ]
            );
        }

        $this->command->info("Seeding Pasar Disperindag Berhasil:");
        $this->command->info("- Markets: " . Market::count());
        $this->command->info("- Zones/Gedung: " . Zone::where('opd_id', $opdId)->count());
        $this->command->info("- Total Market Stalls: " . MarketStall::count());
        $this->command->info("- Kios Tersegel: " . MarketStall::sealed()->count());
        $this->command->info("- Lapak Kosong: " . MarketStall::vacant()->count());
        $this->command->info("- Taxpayers Pedagang: " . $createdTaxpayers);
        $this->command->info("- TaxObjects Lapak: " . $createdTaxObjects);
        $this->command->info("- Tim Petugas Penagih: " . MarketOfficerAssignment::count());
    }
}
