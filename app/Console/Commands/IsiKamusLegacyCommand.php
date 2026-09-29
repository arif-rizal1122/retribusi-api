<?php

namespace App\Console\Commands;

use App\Models\RefLegacyDictionary;
use App\Services\LegacySectorMapService;
use App\Services\WilayahCodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Isi KAMUS REFERENSI LEGACY (read-only reference dictionary) dari tabel SW_PATDA.
 *
 * TIDAK mengimpor transaksi. Hanya menyerap atribut objek:
 *   legal_entity_name (CPM_NAMA_WP), commercial_brand_name (CPM_NAMA_OP),
 *   legacy_code (CPM_NOP), legacy_npwpd (CPM_NPWPD), alamat, kode wilayah, spesifikasi teknis.
 */
class IsiKamusLegacyCommand extends Command
{
    protected $signature = 'mpad:isi-kamus-legacy
        {--dry-run : Hitung saja, tidak menulis}
        {--tabel=* : Batasi ke tabel tertentu (mis. PATDA_HOTEL_PROFIL)}
        {--chunk=500 : Ukuran batch}';

    protected $description = 'Isi ref_legacy_dictionary dari tabel PATDA_*_PROFIL SW_PATDA (kamus read-only, tanpa impor transaksi)';

    private const KOLOM = [
        'CPM_ID', 'CPM_NPWPD', 'CPM_NAMA_WP', 'CPM_ALAMAT_WP', 'CPM_NAMA_OP', 'CPM_ALAMAT_OP',
        'CPM_KELURAHAN_OP', 'CPM_KECAMATAN_OP', 'CPM_NOP', 'CPM_TELEPON_OP', 'CPM_TGL_PENGUKUHAN', 'CPM_NO_SITU',
    ];

    public function handle(LegacySectorMapService $sektorMap, WilayahCodeService $wilayah): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(50, (int) $this->option('chunk'));
        $tabelPilihan = (array) $this->option('tabel');

        $tabelSemua = $tabelPilihan !== [] ? $tabelPilihan : $sektorMap->semuaTabel();

        $lg = DB::connection('mysql_legacy');
        $ringkasan = [];

        foreach ($tabelSemua as $tabel) {
            $sektor = $sektorMap->sektorDariTabelLegacy($tabel);

            if ($sektor === null) {
                $this->warn('  lewati '.$tabel.' (tidak ada di peta sektor)');

                continue;
            }

            $ada = $lg->select('SELECT COUNT(*) n FROM information_schema.tables WHERE table_schema=? AND table_name=?', [$lg->getDatabaseName(), $tabel])[0]->n;
            if ($ada == 0) {
                $this->line('  '.$tabel.': tabel tidak ada, dilewati');

                continue;
            }

            $kolomAda = array_map(
                fn ($c) => $c->c,
                $lg->select('SELECT column_name c FROM information_schema.columns WHERE table_schema=? AND table_name=?', [$lg->getDatabaseName(), $tabel])
            );
            $pilih = array_values(array_filter(self::KOLOM, fn ($k) => in_array($k, $kolomAda, true)));

            $total = 0;
            $masuk = 0;
            $dilewati = 0;
            $tanpaNop = 0;
            $tanpaWilayah = 0;

            $lg->table($tabel)->select($pilih)->orderBy('CPM_ID')->chunk($chunk, function ($rows) use (
                &$total, &$masuk, &$dilewati, &$tanpaNop, &$tanpaWilayah,
                $tabel, $sektor, $wilayah, $dryRun
            ) {
                foreach ($rows as $r) {
                    $total++;

                    $nop = trim((string) ($r->CPM_NOP ?? ''));
                    if ($nop === '' || $nop === '-' || mb_strtolower($nop) === 'xddx') {
                        $tanpaNop++;

                        continue;
                    }

                    $kodeWilayah = null;
                    $legacyKel = trim((string) ($r->CPM_KELURAHAN_OP ?? ''));
                    if ($legacyKel !== '') {
                        $kode = $wilayah->dariLegacyKelurahan($legacyKel);
                        $kodeWilayah = $kode['kode_wilayah'] ?? null;
                    }
                    if ($kodeWilayah === null) {
                        $tanpaWilayah++;
                    }

                    if ($dryRun) {
                        if (! RefLegacyDictionary::where('legacy_source', 'SW_PATDA')
                            ->where('legacy_code', $nop)->exists()) {
                            $masuk++;
                        } else {
                            $dilewati++;
                        }

                        continue;
                    }

                    $spec = array_filter([
                        'kecamatan' => trim((string) ($r->CPM_KECAMATAN_OP ?? '')) ?: null,
                        'kelurahan' => $legacyKel ?: null,
                        'telepon' => trim((string) ($r->CPM_TELEPON_OP ?? '')) ?: null,
                        'no_situ' => trim((string) ($r->CPM_NO_SITU ?? '')) ?: null,
                        'tgl_pengukuhan' => trim((string) ($r->CPM_TGL_PENGUKUHAN ?? '')) ?: null,
                        'legacy_table' => $tabel,
                        'legacy_id' => trim((string) ($r->CPM_ID ?? '')) ?: null,
                    ]);

                    RefLegacyDictionary::updateOrCreate(
                        ['legacy_source' => 'SW_PATDA', 'legacy_code' => $nop],
                        [
                            'legacy_npwpd' => trim((string) ($r->CPM_NPWPD ?? '')) ?: null,
                            'legal_entity_name' => trim((string) ($r->CPM_NAMA_WP ?? '')) ?: null,
                            'commercial_brand_name' => trim((string) ($r->CPM_NAMA_OP ?? '')) ?: null,
                            'sector_code' => $sektor,
                            'kode_wilayah' => $kodeWilayah,
                            'address_text' => trim((string) ($r->CPM_ALAMAT_OP ?? '')) ?: null,
                            'technical_specs' => $spec,
                            'is_active' => true,
                        ]
                    );
                    $masuk++;
                }
            });

            $ringkasan[] = [$tabel, $sektor, $total, $masuk, $dilewati, $tanpaNop, $tanpaWilayah];
        }

        $this->newLine();
        $this->table(
            ['Tabel legacy', 'Sektor', 'Baris', 'Masuk', 'Lewat', 'Tanpa NOP', 'Tanpa wilayah'],
            $ringkasan
        );

        if ($dryRun) {
            $this->comment('DRY RUN: tidak ada data ditulis.');
        } else {
            $this->info('Total ref_legacy_dictionary: '.RefLegacyDictionary::count().' baris');
        }

        return self::SUCCESS;
    }
}
