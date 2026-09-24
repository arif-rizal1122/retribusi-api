<?php
// Tambah field roda 2 & roda 4 ke form schema parkir.
// RET-PRK (Retribusi Parkir Tepi Jalan - Dishub) & PBJT-PRK (Pajak Parkir - Bapenda)
// Konsisten dengan kolom rate_r2/rate_r4 di tabel parking_locations dan
// label yang sudah disiapkan FE di src/lib/sectorFieldRules.ts (kapasitas_r2/kapasitas_r4).

namespace Database\Seeders;

use App\Models\RetributionClassification;
use Illuminate\Database\Seeder;

class ParkingRodaFieldSeeder extends Seeder
{
    public function run(): void
    {
        $schemas = [
            'RET-PRK' => [
                'name' => 'Parkir Tepi Jalan',
                'fields' => [
                    $this->number('kapasitas_r2', 'Jumlah Slot Kendaraan Roda 2'),
                    $this->money('tarif_r2', 'Tarif Parkir Roda 2 (Rp)', 2000),
                    $this->number('kapasitas_r4', 'Jumlah Slot Kendaraan Roda 4+'),
                    $this->money('tarif_r4', 'Tarif Parkir Roda 4+ (Rp)', 3000),
                    $this->number('jumlah_juru_parkir', 'Jumlah Juru Parkir (Jukir)'),
                ],
            ],
            'PBJT-PRK' => [
                'name' => 'PBJT - Jasa Parkir',
                'fields' => [
                    $this->number('kapasitas_r2', 'Jumlah Slot Kendaraan Roda 2'),
                    $this->money('tarif_r2', 'Tarif Parkir Roda 2 (Rp)', 2000),
                    $this->number('kapasitas_r4', 'Jumlah Slot Kendaraan Roda 4+'),
                    $this->money('tarif_r4', 'Tarif Parkir Roda 4+ (Rp)', 3000),
                    $this->money('omzet', 'Total Omzet/Nilai (Rp)'),
                ],
            ],
        ];

        foreach ($schemas as $code => $schema) {
            $c = RetributionClassification::withoutGlobalScopes()
                ->where('code', $code)
                ->where('name', $schema['name'])
                ->first();

            if (!$c) {
                $this->command?->warn("NOT FOUND: $code {$schema['name']}");
                continue;
            }

            $lama = is_array($c->form_schema) ? $c->form_schema : json_decode((string) $c->form_schema, true);

            $c->update([
                'form_schema' => $schema['fields'],
                'requirements' => self::REQUIREMENTS,
            ]);

            $baru = $c->fresh()->form_schema;
            if (is_string($baru)) { $baru = json_decode($baru, true); }

            $kunci = array_map(fn($f) => $f['key'], $baru ?? []);
            $ok = in_array('kapasitas_r2', $kunci, true) && in_array('tarif_r4', $kunci, true) ? 'OK' : 'GAGAL';

            $this->command?->info("$code {$schema['name']} -> $ok");
            $this->command?->info('   LAMA: ' . json_encode($lama));
            $this->command?->info('   BARU: ' . json_encode($kunci));
        }
    }

    private const REQUIREMENTS = [
        ['key' => 'foto_lokasi_open_kamera', 'label' => 'Dokumentasi Foto Objek/Lokasi', 'name' => 'Dokumentasi Foto Objek/Lokasi', 'type' => 'image', 'required' => true],
        ['key' => 'formulir_data_dukung', 'label' => 'Formulir/Data Dukung', 'name' => 'Formulir/Data Dukung', 'type' => 'document', 'required' => true],
        ['key' => 'denah_lokasi_parkir', 'label' => 'Denah Lokasi Parkir & Penempatan Slot', 'name' => 'Denah Lokasi Parkir & Penempatan Slot', 'type' => 'document', 'required' => true],
    ];

    private function money(string $key, string $label, mixed $defaultValue = null): array
    {
        return $this->number($key, $label, $defaultValue);
    }

    private function number(string $key, string $label, mixed $defaultValue = null): array
    {
        $field = ['key' => $key, 'label' => $label, 'type' => 'number', 'required' => true];
        if ($defaultValue !== null) { $field['default_value'] = $defaultValue; }
        return $field;
    }
}
