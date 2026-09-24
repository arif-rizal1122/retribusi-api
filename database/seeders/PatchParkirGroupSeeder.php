<?php
// Skema B: tambah `group` ke field parkir supaya FE render heading per kelompok kendaraan.
// FE tetap menerima field datar -> validasi & penyimpanan tidak berubah (backward compatible).

namespace Database\Seeders;

use App\Models\RetributionClassification;
use Illuminate\Database\Seeder;

class PatchParkirGroupSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'kapasitas_r2'      => 'Kendaraan Roda 2',
            'tarif_r2'          => 'Kendaraan Roda 2',
            'kapasitas_r4'      => 'Kendaraan Roda 4+',
            'tarif_r4'          => 'Kendaraan Roda 4+',
            'jumlah_juru_parkir' => 'Pengelolaan Parkir',
            'jumlah_titik'      => 'Pengelolaan Parkir',
            'omzet'             => 'Pengelolaan Parkir',
        ];

        foreach (['RET-PRK', 'PBJT-PRK'] as $code) {
            $c = RetributionClassification::withoutGlobalScopes()->where('code', $code)->first();
            if (!$c) { $this->command?->warn("NOT FOUND: $code"); continue; }

            $fs = $c->form_schema;
            if (is_string($fs)) { $fs = json_decode($fs, true); }
            if (!is_array($fs)) { $fs = []; }

            $berubah = 0;
            foreach ($fs as $i => $f) {
                $k = $f['key'] ?? null;
                if ($k && isset($map[$k])) {
                    if (($fs[$i]['group'] ?? null) !== $map[$k]) { $berubah++; }
                    $fs[$i]['group'] = $map[$k];
                }
            }

            $c->update(['form_schema' => $fs]);

            $cek = $c->fresh()->form_schema;
            if (is_string($cek)) { $cek = json_decode($cek, true); }
            $this->command?->info("$code -> $berubah field diberi group");
            foreach ((array) $cek as $f) {
                $this->command?->info('   ' . ($f['key'] ?? '?') . ' | group=' . ($f['group'] ?? '-'));
            }
        }
    }
}
