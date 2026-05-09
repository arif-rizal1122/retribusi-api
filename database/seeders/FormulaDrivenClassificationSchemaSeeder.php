<?php

namespace Database\Seeders;

use App\Models\RetributionClassification;
use Illuminate\Database\Seeder;

class FormulaDrivenClassificationSchemaSeeder extends Seeder
{
    private const REQUIREMENTS = [
        [
            'key' => 'foto_lokasi_open_kamera',
            'label' => 'Dokumentasi Foto Objek/Lokasi',
            'name' => 'Dokumentasi Foto Objek/Lokasi',
            'type' => 'image',
            'required' => true,
        ],
        [
            'key' => 'formulir_data_dukung',
            'label' => 'Formulir/Data Dukung',
            'name' => 'Formulir/Data Dukung',
            'type' => 'document',
            'required' => true,
        ],
    ];

    public function run(): void
    {
        $schemas = [
            'W1-OTH' => [
                'name' => 'Umum Lainnya',
                'fields' => [
                    $this->money('tarif_flat', 'Tarif Tetap/Nominal (Rp)'),
                ],
            ],
            'PBB-P2' => [
                'name' => 'PBB-P2',
                'fields' => [
                    $this->money('njop', 'NJOP Total Objek Pajak (Rp)'),
                ],
            ],
            'BPHTB' => [
                'name' => 'BPHTB',
                'fields' => [
                    $this->money('npop', 'Nilai Perolehan Objek Pajak/NPOP (Rp)'),
                ],
            ],
            'REKLAME' => [
                'name' => 'Pajak Reklame',
                'fields' => [
                    $this->money('njopr', 'Nilai Jual Objek Pajak Reklame/NJOPR (Rp)'),
                    $this->money('nspr', 'Nilai Strategis Pemasangan Reklame/NSPR (Rp)'),
                    $this->number('luas', 'Luas Reklame (m2)'),
                    $this->number('sisi', 'Jumlah Sisi', 1),
                ],
            ],
            'MBLB' => [
                'name' => 'Pajak MBLB',
                'fields' => [
                    $this->number('volume', 'Volume Pengambilan/Penjualan'),
                    $this->money('harga', 'Harga Patokan/Satuan (Rp)'),
                ],
            ],
            'WALET' => [
                'name' => 'Pajak Walet',
                'fields' => [
                    $this->number('volume', 'Volume/Hasil Produksi'),
                    $this->money('harga', 'Harga Jual/Satuan (Rp)'),
                ],
            ],
            'OPSEN' => [
                'name' => 'Opsen Pajak',
                'fields' => [
                    $this->money('pokok_provinsi', 'Pokok Pajak Provinsi (Rp)'),
                ],
            ],
            'PBJT-MNM' => [
                'name' => 'PBJT - Makan dan Minum',
                'fields' => [
                    $this->money('omzet', 'Total Omzet/Nilai (Rp)'),
                ],
            ],
            'PBJT-HTL' => [
                'name' => 'PBJT - Jasa Perhotelan',
                'fields' => [
                    $this->money('omzet', 'Total Omzet/Nilai (Rp)'),
                ],
            ],
            'PBJT-HBR' => [
                'name' => 'PBJT - Jasa Kesenian dan Hiburan',
                'fields' => [
                    $this->money('omzet', 'Total Omzet/Nilai (Rp)'),
                ],
            ],
            'PBJT-HBR-SP' => [
                'name' => 'Hiburan Malam',
                'fields' => [
                    $this->money('omzet', 'Total Omzet/Nilai (Rp)'),
                ],
            ],
            'PBJT-LIS' => [
                'name' => 'PBJT - Tenaga Listrik',
                'fields' => [
                    $this->money('tagihan', 'Nilai Tagihan Listrik (Rp)'),
                ],
            ],
            'AIR-TANAH' => [
                'name' => 'Pajak Air Tanah',
                'fields' => [
                    $this->number('volume', 'Volume Pemakaian Air (m3)'),
                    $this->money('hda', 'Harga Dasar Air/HDA (Rp)'),
                ],
            ],
            'RET-SMP' => [
                'name' => 'Persampahan',
                'fields' => [
                    $this->money('tarif_flat', 'Tarif Tetap Persampahan (Rp)'),
                ],
            ],
            'RET-PRK' => [
                'name' => 'Parkir Tepi Jalan',
                'fields' => [
                    $this->money('tarif_flat', 'Tarif Tetap Parkir (Rp)'),
                ],
            ],
            'RET-PKD' => [
                'name' => 'Sewa PKD (Lapak)',
                'fields' => [
                    $this->money('tarif_dasar', 'Tarif Dasar Sewa (Rp)'),
                    $this->number('koefisien', 'Koefisien'),
                ],
            ],
            'PBJT-PRK' => [
                'name' => 'PBJT - Jasa Parkir',
                'fields' => [
                    $this->money('omzet', 'Total Omzet/Nilai (Rp)'),
                ],
            ],
        ];

        foreach ($schemas as $code => $schema) {
            $classification = RetributionClassification::withoutGlobalScopes()
                ->where('code', $code)
                ->where('name', $schema['name'])
                ->first();

            if (!$classification) {
                $this->command?->warn("Classification not found: {$code} {$schema['name']}");
                continue;
            }

            $classification->update([
                'form_schema' => $schema['fields'],
                'requirements' => self::REQUIREMENTS,
            ]);

            $this->command?->info("Updated formula-driven schema: {$classification->code} {$classification->name}");
        }
    }

    private function money(string $key, string $label, mixed $defaultValue = null): array
    {
        return $this->number($key, $label, $defaultValue);
    }

    private function number(string $key, string $label, mixed $defaultValue = null): array
    {
        $field = [
            'key' => $key,
            'label' => $label,
            'type' => 'number',
            'required' => true,
        ];

        if ($defaultValue !== null) {
            $field['default_value'] = $defaultValue;
        }

        return $field;
    }
}
