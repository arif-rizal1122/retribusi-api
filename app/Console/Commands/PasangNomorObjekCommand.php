<?php

namespace App\Console\Commands;

use App\Models\ObjectNumberRegistry;
use App\Services\KlasifikasiNomorService;
use App\Services\ObjectNumberRegistryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Pasang nomor objek yang SUDAH ADA ke registry (tidak generate ulang, tidak mengubah tax_objects).
 *
 * Aturan pengelola: objek yang sudah punya nomor cukup dipasang apa adanya.
 * Perintah ini TIDAK menyentuh tabel tax_objects — hanya INSERT ke object_number_registry.
 */
class PasangNomorObjekCommand extends Command
{
    protected $signature = 'mpad:pasang-nomor
        {--dry-run : Hitung saja, tidak menulis apa pun}
        {--chunk=500 : Ukuran batch}
        {--limit=0 : Batasi jumlah objek (0 = semua)}';

    protected $description = 'Pasang nomor objek yang sudah ada (existing) ke object_number_registry tanpa mengubah tax_objects';

    public function handle(
        ObjectNumberRegistryService $registry,
        KlasifikasiNomorService $klasifikasi,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(50, (int) $this->option('chunk'));
        $limit = (int) $this->option('limit');

        $this->info(($dryRun ? '[DRY RUN] ' : '').'Pasang nomor objek existing -> object_number_registry');

        $zones = $this->petaZonaKeKecamatan();

        $stat = ['diperiksa' => 0, 'dipasang' => 0, 'sudah_ada' => 0, 'dilewati_bukan_nomor' => 0, 'gagal' => 0];

        $query = DB::table('tax_objects')
            ->select('id', 'nop', 'taxpayer_id', 'opd_id', 'zone_id', 'latitude', 'longitude')
            ->whereNotNull('nop')
            ->where('nop', '<>', '')
            ->orderBy('id');

        $query->chunkById($chunk, function ($rows) use (&$stat, $registry, $klasifikasi, $zones, $dryRun, $limit) {
            foreach ($rows as $row) {
                if ($limit > 0 && $stat['diperiksa'] >= $limit) {
                    return false;
                }

                $stat['diperiksa']++;

                if (! $klasifikasi->sudahPunyaNomor($row->nop)) {
                    $stat['dilewati_bukan_nomor']++;

                    continue;
                }

                $nomor = trim((string) $row->nop);

                if (ObjectNumberRegistry::where('nomor', $nomor)->exists()) {
                    $stat['sudah_ada']++;

                    continue;
                }

                if ($dryRun) {
                    $stat['dipasang']++;

                    continue;
                }

                try {
                    $registry->pasang($nomor, [
                        'tax_object_id' => $row->id,
                        'taxpayer_id' => $row->taxpayer_id,
                        'opd_id' => $row->opd_id,
                        'kode_wilayah' => $this->kodeWilayahDariZona($row->zone_id, $zones),
                        'latitude' => $row->latitude,
                        'longitude' => $row->longitude,
                        'legacy_source' => 'M-PAD_TAX_OBJECTS',
                    ]);
                    $stat['dipasang']++;
                } catch (\Throwable $e) {
                    $stat['gagal']++;
                    $this->warn('  gagal id='.$row->id.' nop='.$nomor.' -> '.$e->getMessage());
                }
            }

            return true;
        }, 'id');

        $this->newLine();
        $this->table(['Keterangan', 'Jumlah'], [
            ['Objek diperiksa', $stat['diperiksa']],
            ['Nomor DIPASANG', $stat['dipasang']],
            ['Sudah terdaftar (dilewati)', $stat['sudah_ada']],
            ['Bukan nomor sah (dilewati)', $stat['dilewati_bukan_nomor']],
            ['Gagal', $stat['gagal']],
        ]);

        if ($dryRun) {
            $this->comment('DRY RUN: tidak ada data yang ditulis.');
        } else {
            $this->info('Total registry sekarang: '.ObjectNumberRegistry::count().' baris');
        }

        $this->line('tax_objects TIDAK diubah oleh perintah ini.');

        return self::SUCCESS;
    }

    /**
     * Zona kecamatan (mis. "Kecamatan Wolio"/kode WOL) -> kode kecamatan M-PAD (71-78).
     *
     * @return array<string, string>
     */
    private function petaZonaKeKecamatan(): array
    {
        $peta = [];

        $wilayah = DB::table('ref_wilayah')
            ->select('kode_kecamatan', 'kecamatan')
            ->distinct()
            ->get();

        foreach (DB::table('zones')->get() as $zone) {
            $nama = mb_strtoupper(trim((string) $zone->name));
            $nama = preg_replace('/^KECAMATAN\s+/', '', $nama) ?? $nama;
            $kodeZona = mb_strtoupper(trim((string) $zone->code));

            foreach ($wilayah as $w) {
                $kecamatan = mb_strtoupper(trim((string) $w->kecamatan));

                if ($nama === $kecamatan || $kodeZona === $kecamatan) {
                    $peta[(string) $zone->id] = (string) $w->kode_kecamatan;

                    break;
                }
            }
        }

        return $peta;
    }

    /**
     * @param  array<string, string>  $zones
     */
    private function kodeWilayahDariZona(mixed $zoneId, array $zones): ?string
    {
        if ($zoneId === null) {
            return null;
        }

        $kecamatan = $zones[(string) $zoneId] ?? null;

        // 4 digit: kode kecamatan + sentinel kelurahan 00 (kelurahan belum terpetakan)
        return $kecamatan === null ? null : $kecamatan.'00';
    }
}
