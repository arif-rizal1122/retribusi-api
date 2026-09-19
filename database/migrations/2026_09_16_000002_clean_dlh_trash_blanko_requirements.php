<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\RetributionClassification;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update klasifikasi RET-SMP / ID 198 (Retribusi Persampahan)
        $dlhClass = RetributionClassification::where('id', 198)
            ->orWhere('code', 'RET-SMP')
            ->first();

        if ($dlhClass) {
            $dlhClass->update([
                'opd_id' => 4,
                'name' => 'Retribusi Persampahan & Kebersihan',
                'calculation_formula' => 'tarif_flat',
                'form_schema' => json_encode([
                    ['key' => 'building_category', 'label' => 'Kategori Bangunan', 'type' => 'select', 'required' => true,
                     'options' => ['SOSIAL_IBADAH', 'RUMAH_SEDERHANA', 'RUMAH_MENENGAH', 'RUKO_NIAGA', 'RESTORAN', 'PASAR', 'INDUSTRI']],
                    ['key' => 'tipe_penghuni', 'label' => 'Tipe Penghuni', 'type' => 'select', 'required' => true, 'options' => ['PEMILIK', 'PENYEWA']],
                    ['key' => 'status_hunian', 'label' => 'Status Hunian', 'type' => 'select', 'required' => true, 'options' => ['BERPENGHUNI', 'KOSONG']],
                    ['key' => 'penanggung_jawab_nama', 'label' => 'Nama Penanggung Jawab', 'type' => 'text', 'required' => false],
                    ['key' => 'penanggung_jawab_kontak', 'label' => 'No. Telepon / WA', 'type' => 'text', 'required' => false],
                ]),
                // Buang Kartu Pelanggan / Kontrak — hanya foto bangunan (opsional)
                'requirements' => [
                    ['key' => 'foto_lokasi', 'name' => 'Foto Tempat / Bangunan', 'required' => false, 'label' => 'Foto Tempat / Bangunan']
                ],
            ]);
        }

        // 2. Bersihkan persyaratan kartu pelanggan / kontrak dari seluruh klasifikasi persampahan jika ada duplikat
        DB::table('retribution_classifications')
            ->where('opd_id', 4)
            ->orWhere('code', 'RET-SMP')
            ->update([
                'requirements' => json_encode([
                    ['key' => 'foto_lokasi', 'name' => 'Foto Tempat / Bangunan', 'required' => false, 'label' => 'Foto Tempat / Bangunan']
                ]),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
