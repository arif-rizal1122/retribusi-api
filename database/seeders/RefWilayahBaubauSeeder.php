<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed kode wilayah Kota Baubau untuk kodifikasi NOPD/NORD.
 *
 * SUMBER RESMI (bukan tebakan): tabel SW_PATDA lama
 *   PATDA_MST_KECAMATAN (8 baris) dan PATDA_MST_KELURAHAN (43 baris)
 * di database legacy sipanda_legacy_9pajak / sw_patda.
 *
 * Pemetaan kode:
 *   Kecamatan SW_PATDA 7472710 -> kode M-PAD 71  (7472 + 7X + 0, X = 1..8)
 *   Kelurahan SW_PATDA 7472710003 -> kode M-PAD 7103 (kecamatan 71 + kelurahan 03)
 * Sehingga kode_wilayah 4 digit = 2 digit kecamatan + 2 digit kelurahan,
 * persis seperti matriks baku NOPD/NORD (7472.SS.KKVV.UUUU).
 */
class RefWilayahBaubauSeeder extends Seeder
{
    /** kecamatan: kode M-PAD => [nama, kode asli SW_PATDA] */
    private const KECAMATAN = [
        '71' => ['WOLIO', '7472710'],
        '72' => ['BETOAMBARI', '7472720'],
        '73' => ['SORAWOLIO', '7472730'],
        '74' => ['BUNGI', '7472740'],
        '75' => ['KOKALUKUNA', '7472750'],
        '76' => ['MURHUM', '7472760'],
        '77' => ['BATU POARO', '7472770'],
        '78' => ['LEA-LEA', '7472780'],
    ];

    /** kelurahan: kode M-PAD (4 digit) => [nama, kode asli SW_PATDA] */
    private const KELURAHAN = [
        // Kecamatan Wolio (71)
        '7101' => ['BATARAGURU', '7472710001'],
        '7102' => ['TOMBA', '7472710002'],
        '7103' => ['WALE', '7472710003'],
        '7104' => ['BATULO', '7472710004'],
        '7105' => ['WANGKANAPI', '7472710005'],
        '7107' => ['KADOLOKATAPI', '7472710007'],
        '7111' => ['BUKIT WOLIO INDAH', '7472710011'],
        // Kecamatan Betoambari (72)
        '7201' => ['SULAA', '7472720001'],
        '7202' => ['WABOROBO', '7472720002'],
        '7203' => ['LABALAWA', '7472720003'],
        '7207' => ['KATOBENGKE', '7472720007'],
        '7215' => ['LIPUU', '7472720015'],
        // Kecamatan Sorawolio (73)
        '7301' => ['KAISABU BARU', '7472730001'],
        '7302' => ['KARYA BARU', '7472730002'],
        '7303' => ['GONDA BARU', '7472730003'],
        '7304' => ['BUGI', '7472730004'],
        // Kecamatan Bungi (74)
        '7403' => ['LIABUKU', '7472740003'],
        '7404' => ['NGKARINGKARI', '7472740004'],
        '7405' => ['KAMPEONAHU', '7472740005'],
        '7408' => ['WALIABUKU', '7472740008'],
        '7409' => ['TAMPUNA', '7472740009'],
        // Kecamatan Kokalukuna (75)
        '7501' => ['LIWUTO', '7472750001'],
        '7502' => ['SUKANAEYO', '7472750002'],
        '7503' => ['LAKOLOGOU', '7472750003'],
        '7504' => ['WARURUMA', '7472750004'],
        '7505' => ['KADOLOMOKO', '7472750005'],
        '7506' => ['KADOLO', '7472750006'],
        // Kecamatan Murhum (76)
        '7607' => ['WAJO', '7472760007'],
        '7608' => ['LAMANGGA', '7472760008'],
        '7609' => ['MELAI', '7472760009'],
        '7610' => ['BAADIA', '7472760010'],
        '7611' => ['TANGANAPADA', '7472760011'],
        // Kecamatan Batu Poaro (77)
        '7701' => ['KAOBULA', '7472770001'],
        '7702' => ['WAMEO', '7472770002'],
        '7703' => ['NGANGANA UMALA', '7472770003'],
        '7704' => ['LANTO', '7472770004'],
        '7705' => ['TARAFU', '7472770005'],
        '7706' => ['BONEBONE', '7472770006'],
        // Kecamatan Lea-Lea (78)
        '7801' => ['LOWULOWU', '7472780001'],
        '7802' => ['KALIALIA', '7472780002'],
        '7803' => ['KANTALAI', '7472780003'],
        '7806' => ['KOLESE', '7472780006'],
        '7807' => ['PALABUSA', '7472780007'],
    ];

    public function run(): void
    {
        $sekarang = now();

        foreach (self::KELURAHAN as $kode => $row) {
            $kodeKecamatan = substr($kode, 0, 2);
            $kecamatan = self::KECAMATAN[$kodeKecamatan] ?? null;

            if ($kecamatan === null) {
                continue;
            }

            $atribut = [
                'kode_kecamatan' => $kodeKecamatan,
                'kode_kelurahan' => substr($kode, 2, 2),
                'kecamatan' => $kecamatan[0],
                'kelurahan' => $row[0],
                'legacy_kode' => $row[1],
                'legacy_kode_kecamatan' => $kecamatan[1],
                'is_verified' => true,
                'sumber' => 'SW_PATDA PATDA_MST_KECAMATAN/PATDA_MST_KELURAHAN',
                'catatan' => '8 kecamatan dan 43 kelurahan Kota Baubau sesuai data resmi SW_PATDA.',
                'updated_at' => $sekarang,
            ];

            $sudahAda = DB::table('ref_wilayah')->where('kode', $kode)->exists();

            if ($sudahAda) {
                DB::table('ref_wilayah')->where('kode', $kode)->update($atribut);

                continue;
            }

            DB::table('ref_wilayah')->insert(array_merge($atribut, [
                'kode' => $kode,
                'created_at' => $sekarang,
            ]));
        }
    }
}
