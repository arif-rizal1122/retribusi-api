<?php
// Tambah field roda 2 & roda 4 ke RET-PRK (Retribusi Pelayanan Parkir, OPD Dishub).
// Field existing DIPERTAHANKAN, hanya ditambahi field roda + tarifnya.
// `jumlah_titik` lama diganti perannya oleh kapasitas_r2/kapasitas_r4 (tetap disimpan
// sebagai total untuk kompatibilitas data lama).

namespace Database\Seeders;

use App\Models\RetributionClassification;
use Illuminate\Database\Seeder;

class ParkingRodaRetPkrSeeder extends Seeder
{
    public function run(): void
    {
        $c = RetributionClassification::withoutGlobalScopes()
            ->where('code', 'RET-PRK')
            ->first();

        if (!$c) {
            $this->command?->warn('RET-PRK tidak ditemukan');
            return;
        }

        $lama = $c->form_schema;
        if (is_string($lama)) { $lama = json_decode($lama, true); }
        if (!is_array($lama)) { $lama = []; }

        // Buang field lama yang digantikan (jumlah_titik tunggal) — sisanya dipertahankan.
        $dipertahankan = array_values(array_filter(
            $lama,
            fn($f) => ($f['key'] ?? '') !== 'jumlah_titik'
        ));

        $fieldRoda = [
            $this->number('kapasitas_r2', 'Jumlah Slot / Petak Kendaraan Roda 2'),
            $this->money('tarif_r2', 'Tarif Parkir Roda 2 (Rp)', 2000),
            $this->number('kapasitas_r4', 'Jumlah Slot / Petak Kendaraan Roda 4+'),
            $this->money('tarif_r4', 'Tarif Parkir Roda 4+ (Rp)', 3000),
            $this->number('jumlah_juru_parkir', 'Jumlah Juru Parkir (Jukir)'),
            $this->number('jumlah_titik', 'Total Seluruh Titik / Petak (roda 2 + roda 4)', null, false),
        ];

        $baru = array_merge($dipertahankan, $fieldRoda);

        $c->update(['form_schema' => $baru]);

        $cek = $c->fresh()->form_schema;
        if (is_string($cek)) { $cek = json_decode($cek, true); }
        $kunci = array_map(fn($f) => $f['key'] ?? '?', $cek ?? []);
        $ok = in_array('kapasitas_r2', $kunci, true) && in_array('tarif_r4', $kunci, true)
              && in_array('tipe_parkir', $kunci, true) ? 'OK' : 'GAGAL';

        $this->command?->info("RET-PRK -> $ok");
        $this->command?->info('   LAMA : ' . json_encode(array_map(fn($f) => $f['key'] ?? '?', $lama)));
        $this->command?->info('   BARU : ' . json_encode($kunci));
    }

    private function money(string $key, string $label, mixed $defaultValue = null): array
    {
        return $this->number($key, $label, $defaultValue);
    }

    private function number(string $key, string $label, mixed $defaultValue = null, bool $required = true): array
    {
        $field = ['key' => $key, 'label' => $label, 'type' => 'number', 'required' => $required];
        if ($defaultValue !== null) { $field['default_value'] = $defaultValue; }
        return $field;
    }
}
