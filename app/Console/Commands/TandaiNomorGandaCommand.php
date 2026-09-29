<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Tandai baris registry GANDA sebagai SUPERSEDED.
 *
 * Latar (29 Sep 2026): run generate gelombang pertama gagal simpan karena kolom
 * nomor_baku masih char(14). Nomor terlanjur teralokasi ke registry, lalu proses
 * diulang dengan nomor baru. Hasilnya satu objek punya 2 baris nomor — 1 dipakai
 * (cocok dengan tax_objects.nop), 1 menganggur ("nomor hantu").
 *
 * Tindakan: baris menganggur ditandai status='SUPERSEDED' + catatan penggantinya.
 * Data TIDAK dihapus. Baris yang dipakai tetap 'aktif'.
 */
class TandaiNomorGandaCommand extends Command
{
    protected $signature = 'mpad:tandai-nomor-ganda
        {--dry-run : Hitung saja, tidak menulis}
        {--chunk=500 : Ukuran batch}';

    protected $description = 'Tandai baris nomor registry ganda sebagai SUPERSEDED (tidak menghapus data)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(100, (int) $this->option('chunk'));

        $this->info(($dryRun ? '[DRY RUN] ' : '').'Tandai nomor registry ganda sebagai SUPERSEDED');

        $ids = DB::table('object_number_registry')
            ->whereNotNull('tax_object_id')
            ->select('tax_object_id', DB::raw('COUNT(*) c'))
            ->groupBy('tax_object_id')
            ->having('c', '>', 1)
            ->pluck('tax_object_id');

        $stat = ['objek_diperiksa' => 0, 'ditandai' => 0, 'tetap_aktif' => 0, 'tanpa_nomor_cocok' => 0, 'sudah' => 0];

        foreach ($ids->chunk($chunk) as $group) {
            $rows = DB::table('object_number_registry')
                ->whereIn('tax_object_id', $group)
                ->orderBy('id')
                ->get()
                ->groupBy('tax_object_id');

            $nops = DB::table('tax_objects')->whereIn('id', $group)->pluck('nop', 'id');

            foreach ($rows as $objectId => $grp) {
                $stat['objek_diperiksa']++;

                $nop = trim((string) ($nops[$objectId] ?? ''));
                if ($nop === '') {
                    $stat['tanpa_nomor_cocok'] += $grp->count();

                    continue;
                }

                // Baris yang dipakai = nomornya sama dengan tax_objects.nop
                $aktif = $grp->first(fn ($r) => trim((string) $r->nomor) === $nop);
                if (! $aktif) {
                    $stat['tanpa_nomor_cocok'] += $grp->count();

                    continue;
                }

                foreach ($grp as $r) {
                    if ((int) $r->id === (int) $aktif->id) {
                        $stat['tetap_aktif']++;

                        continue;
                    }

                    if ($r->status === 'SUPERSEDED') {
                        $stat['sudah']++;

                        continue;
                    }

                    if ($dryRun) {
                        $stat['ditandai']++;

                        continue;
                    }

                    $meta = json_decode((string) ($r->metadata ?? '{}'), true) ?: [];
                    $meta['superseded_reason'] = 'Nomor ganda sisa run gagal simpan 29 Sep 2026; digantikan nomor aktif';
                    $meta['superseded_by_nomor'] = $aktif->nomor;
                    $meta['superseded_at'] = now()->toIso8601String();

                    DB::table('object_number_registry')->where('id', $r->id)->update([
                        'status' => 'SUPERSEDED',
                        'metadata' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ]);

                    $stat['ditandai']++;
                }
            }
        }

        $this->newLine();
        $this->table(
            ['Keterangan', 'Jumlah'],
            [
                ['Objek diperiksa', number_format($stat['objek_diperiksa'], 0, ',', '.')],
                ['Baris ditandai SUPERSEDED', number_format($stat['ditandai'], 0, ',', '.')],
                ['Baris tetap aktif', number_format($stat['tetap_aktif'], 0, ',', '.')],
                ['Sudah SUPERSEDED (dilewati)', number_format($stat['sudah'], 0, ',', '.')],
                ['Objek tanpa nomor cocok', number_format($stat['tanpa_nomor_cocok'], 0, ',', '.')],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN: tidak ada data ditulis.');
        } else {
            $this->info('Selesai. Data tidak dihapus — hanya status ditandai.');
        }

        return self::SUCCESS;
    }
}
