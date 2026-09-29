<?php

namespace App\Console\Commands;

use App\Models\ObjectNumberRegistry;
use App\Services\LegacySectorMapService;
use App\Services\ObjectNumberRegistryService;
use App\Services\WilayahCodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * GENERATE nomor NOPD/NORD 14 digit untuk objek yang BELUM punya nomor sah.
 *
 * 1. Terbitkan nomor baru: 7472 . SS (sektor) . KKVV (wilayah) . UUUU (urut)
 * 2. Simpan NOPD baru ke tax_objects.nop (kolom desain nomor objek)
 * 3. Simpan NOP lama (hash) ke tax_objects.metadata.nop_placeholder_lama sebagai jejak
 * 4. TIDAK menghapus, TIDAK mengubah kolom lain
 */
class GenerateNomorObjekCommand extends Command
{
    protected $signature = 'mpad:generate-nomor
        {--dry-run : Hitung saja, tidak menulis}
        {--chunk=200 : Ukuran batch}
        {--limit=0 : Batasi jumlah objek (0 = semua)}
        {--tanpa-update-tax-object : Hanya isi registry, jangan sentuh kolom nop tax_objects}';

    protected $description = 'Generate NOPD/NORD 14 digit untuk objek tanpa nomor sah (sektor dari legacy_table, wilayah dari district/sub_district)';

    public function handle(
        ObjectNumberRegistryService $registry,
        LegacySectorMapService $sektorMap,
        WilayahCodeService $wilayah,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(25, (int) $this->option('chunk'));
        $limit = (int) $this->option('limit');
        $updateTaxObject = ! $this->option('tanpa-update-tax-object');

        $this->info(($dryRun ? '[DRY RUN] ' : '').'Generate NOPD/NORD untuk objek tanpa nomor sah');

        $stat = [
            'diperiksa' => 0, 'digenerate' => 0, 'tanpa_sektor' => 0, 'tanpa_wilayah' => 0,
            'gagal' => 0, 'tax_object_diupdate' => 0,
        ];
        $perSektor = [];
        $perWilayah = [];

        DB::table('tax_objects')
            ->select('id', 'nop', 'name', 'taxpayer_id', 'opd_id', 'zone_id', 'metadata')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use (
                &$stat, &$perSektor, &$perWilayah,
                $registry, $sektorMap, $wilayah, $dryRun, $updateTaxObject, $limit
            ) {
                foreach ($rows as $row) {
                    if ($limit > 0 && $stat['diperiksa'] >= $limit) {
                        return false;
                    }

                    $nop = trim((string) $row->nop);

                    // Hanya proses yang TIDAK punya nomor sah (hash/kosong/tidak standar)
                    if ($nop !== '' && ! preg_match('/^[0-9a-f]{32}$/i', $nop)
                        && preg_match('/^[0-9]{13}$|^[0-9]{14}$|^[0-9]{18}$/', $nop)) {
                        continue; // sudah punya nomor -> jangan diganggu
                    }
                    if ($nop !== '' && preg_match('/^[0-9]+(\.[0-9]+){2,}$/', $nop)) {
                        continue; // nomor bertitik -> jangan diganggu
                    }

                    $stat['diperiksa']++;

                    $meta = json_decode((string) ($row->metadata ?? '{}'), true) ?: [];

                    // SEKTOR: dari legacy_table (pasti). Cadangan: tarif -> nonaktif (ambigu = tolak).
                    $sektor = $sektorMap->sektorDariTabelLegacy((string) ($meta['legacy_table'] ?? ''));
                    if ($sektor === null) {
                        $stat['tanpa_sektor']++;

                        continue;
                    }

                    // WILAYAH: sub_district (kelurahan, 10 digit) lebih tepat; cadangan district (kecamatan)
                    $kodeWilayah = null;
                    foreach (['sub_district', 'district'] as $kunci) {
                        $nilai = trim((string) ($meta[$kunci] ?? ''));
                        if ($nilai === '') {
                            continue;
                        }
                        $hasil = $wilayah->dariLegacyKelurahan($nilai);
                        if ($hasil !== null) {
                            $kodeWilayah = $hasil['kode_wilayah'];

                            break;
                        }
                        $kec = $wilayah->dariLegacyKecamatan($nilai);
                        if ($kec !== null) {
                            $kodeWilayah = $kec.'00'; // sentinel kelurahan belum terpetakan

                            break;
                        }
                    }

                    if ($kodeWilayah === null) {
                        $stat['tanpa_wilayah']++;

                        continue;
                    }

                    if ($dryRun) {
                        $stat['digenerate']++;
                        $perSektor[$sektor] = ($perSektor[$sektor] ?? 0) + 1;
                        $perWilayah[$kodeWilayah] = ($perWilayah[$kodeWilayah] ?? 0) + 1;

                        continue;
                    }

                    try {
                        $record = $registry->terbitkan($sektor, $kodeWilayah, [
                            'tax_object_id' => $row->id,
                            'taxpayer_id' => $row->taxpayer_id,
                            'opd_id' => $row->opd_id,
                            'legacy_source' => 'M-PAD_REGENERATE',
                            'metadata' => [
                                'nop_placeholder_lama' => preg_match('/^[0-9a-f]{32}$/i', $nop) ? $nop : null,
                                'legacy_table' => $meta['legacy_table'] ?? null,
                                'legacy_id' => $meta['legacy_id'] ?? null,
                                'nama_objek' => mb_substr((string) $row->name, 0, 200),
                            ],
                        ]);

                        if ($updateTaxObject) {
                            $meta['nop_placeholder_lama'] = preg_match('/^[0-9a-f]{32}$/i', $nop) ? $nop : null;
                            $meta['nopd_nord_baru'] = $record->nomor;
                            $meta['nopd_digenerate_at'] = now()->toIso8601String();

                            DB::table('tax_objects')->where('id', $row->id)->update([
                                'nop' => $record->nomor,
                                'metadata' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                            ]);
                            $stat['tax_object_diupdate']++;
                        }

                        $stat['digenerate']++;
                        $perSektor[$sektor] = ($perSektor[$sektor] ?? 0) + 1;
                        $perWilayah[$kodeWilayah] = ($perWilayah[$kodeWilayah] ?? 0) + 1;
                    } catch (\Throwable $e) {
                        $stat['gagal']++;
                        if ($stat['gagal'] <= 5) {
                            $this->warn('  gagal id='.$row->id.' -> '.$e->getMessage());
                        }
                    }
                }

                return true;
            }, 'id');

        $this->newLine();
        $this->table(['Keterangan', 'Jumlah'], [
            ['Objek diperiksa (tanpa nomor sah)', $stat['diperiksa']],
            ['Nomor DIGENERATE', $stat['digenerate']],
            ['Tax_objects.nop diupdate', $stat['tax_object_diupdate']],
            ['Tanpa sektor (dilewati)', $stat['tanpa_sektor']],
            ['Tanpa wilayah (dilewati)', $stat['tanpa_wilayah']],
            ['Gagal', $stat['gagal']],
        ]);

        if ($perSektor !== []) {
            ksort($perSektor);
            $isi = [];
            foreach ($perSektor as $s => $n) {
                $info = app(\App\Services\ObjectNumberService::class)->sektor($s);
                $isi[] = [$s, $info['nama'], $n];
            }
            $this->table(['Sektor', 'Nama', 'Jumlah'], $isi);
        }

        if ($perWilayah !== []) {
            ksort($perWilayah);
            $isi = [];
            foreach ($perWilayah as $k => $n) {
                $nama = $wilayah->nama($k);
                $isi[] = [$k, $nama['kecamatan'] ?? '?', $nama['kelurahan'] ?? '(sentinel)', $n];
            }
            $this->table(['Kode wilayah', 'Kecamatan', 'Kelurahan', 'Jumlah'], $isi);
        }

        if ($dryRun) {
            $this->comment('DRY RUN: tidak ada data ditulis. tax_objects tidak disentuh.');
        } else {
            $this->info('Total registry: '.ObjectNumberRegistry::count().' baris');
        }

        return self::SUCCESS;
    }
}
