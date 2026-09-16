<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Sesuai Perda No. 1 Tahun 2024 & UU HKPD No. 1 Tahun 2022:
     * 1. Pelayanan Pasar (ID 202): nama_pasar wajib berupa DROPDOWN pilihan (bukan text input),
     *    dan hilangkan field ketik manual lokasi_pasar karena pasar di Baubau definitif.
     * 2. Pajak MBLB (ID 189): Pajak dipungut atas objek kegiatan ekstraksi/volume material galian,
     *    bukan atas izinnya. Field izin (IUP/SIPB) bersifat opsional dan tidak menghambat pendaftaran.
     */
    public function up(): void
    {
        // 1. Update Retribusi Pelayanan Pasar (ID 202 / RET-PSR)
        $pasarSchema = [
            [
                'key' => 'nama_pasar',
                'label' => 'Nama Pasar',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['label' => 'Pasar Wameo', 'value' => 'Pasar Wameo'],
                    ['label' => 'Pasar Karya Nugraha', 'value' => 'Pasar Karya Nugraha'],
                    ['label' => 'Pasar Laelangi', 'value' => 'Pasar Laelangi'],
                    ['label' => 'Pasar Moko', 'value' => 'Pasar Moko'],
                    ['label' => 'Pasar Baruga', 'value' => 'Pasar Baruga'],
                    ['label' => 'Pasar Modern Ngkari-ngkari', 'value' => 'Pasar Modern Ngkari-ngkari'],
                ],
            ],
            [
                'key' => 'jenis_fasilitas',
                'label' => 'Jenis Fasilitas',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['label' => 'Kios', 'value' => 'kios'],
                    ['label' => 'Los', 'value' => 'los'],
                    ['label' => 'Pelataran', 'value' => 'pelataran'],
                    ['label' => 'Gudang', 'value' => 'gudang'],
                    ['label' => 'Toilet / Sarana Penunjang', 'value' => 'toilet'],
                ],
            ],
            [
                'key' => 'luas',
                'label' => 'Luas Lapak / Kios (m2)',
                'type' => 'number',
                'required' => true,
            ],
        ];

        $pasarRequirements = [
            [
                'key' => 'foto_lokasi',
                'label' => 'Foto Kios / Los / Lapak',
                'name' => 'Foto Kios / Los / Lapak',
                'required' => true,
            ],
        ];

        DB::table('retribution_classifications')
            ->where('id', 202)
            ->orWhere('code', 'RET-PSR')
            ->update([
                'form_schema' => json_encode($pasarSchema),
                'requirements' => json_encode($pasarRequirements),
                'updated_at' => now(),
            ]);

        // 2. Update Pajak MBLB (ID 189 / MBLB)
        $mblbSchema = [
            [
                'key' => 'jenis_mineral',
                'label' => 'Jenis Komoditas Mineral',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['label' => 'Batu Gamping (Kapur)', 'value' => 'gamping'],
                    ['label' => 'Pasir Kuarsa / Pasir Pasang', 'value' => 'pasir'],
                    ['label' => 'Tanah Urug / Timbunan', 'value' => 'tanah_urug'],
                    ['label' => 'Tanah Liat', 'value' => 'tanah_liat'],
                    ['label' => 'Kerikil / Sirtu', 'value' => 'sirtu'],
                    ['label' => 'Batu Andesit / Belah', 'value' => 'andesit'],
                ],
            ],
            [
                'key' => 'volume',
                'label' => 'Estimasi Volume per Bulan',
                'type' => 'number',
                'required' => true,
            ],
            [
                'key' => 'satuan_volume',
                'label' => 'Satuan',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['label' => 'Meter Kubik (m3)', 'value' => 'm3'],
                    ['label' => 'Ton', 'value' => 'ton'],
                ],
            ],
            [
                'key' => 'nomor_izin',
                'label' => 'Nomor IUP / SIPB',
                'type' => 'text',
                'required' => false,
                'helper' => 'Opsional jika ada',
            ],
            [
                'key' => 'tanggal_izin',
                'label' => 'Masa Berlaku Izin',
                'type' => 'date',
                'required' => false,
                'helper' => 'Opsional jika ada',
            ],
            [
                'key' => 'luas_wip',
                'label' => 'Luas Wilayah Tambang (Ha)',
                'type' => 'number',
                'required' => false,
                'helper' => 'Opsional jika ada',
            ],
        ];

        $mblbRequirements = [
            [
                'key' => 'foto_titik',
                'label' => 'Foto Lokasi Penambangan / Titik Galian',
                'name' => 'Foto Lokasi Penambangan / Titik Galian',
                'required' => true,
            ],
            [
                'key' => 'dokumen_iup',
                'label' => 'Salinan Dokumen IUP / SIPB (Jika Ada)',
                'name' => 'Salinan Dokumen IUP / SIPB (Jika Ada)',
                'required' => false,
            ],
        ];

        DB::table('retribution_classifications')
            ->where('id', 189)
            ->orWhere('code', 'MBLB')
            ->update([
                'form_schema' => json_encode($mblbSchema),
                'requirements' => json_encode($mblbRequirements),
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible down migration
    }
};
