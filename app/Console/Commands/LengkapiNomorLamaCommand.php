<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Lengkapi atribut nomor LAMA pada object_number_registry.
 *
 * Latar (29 Sep 2026): ETL memasang 63.406 nomor lama apa adanya, tetapi kolom
 * `jenis`, `sector_code`, dan `legacy_code` TIDAK ikut terisi. Akibatnya
 * pencarian loket (/object-numbers/lookup) tidak menemukan NOP lama, dan kartu
 * objek tidak tahu itu NOPD atau NORD.
 *
 * PENTING — sumber sektor: `retribution_classifications.name`, BUKAN
 * `retribution_types.id`. Kolom tipe hanya berisi kategori kasar
 * (16="Pajak", 17="Retribusi Jasa Umum"), sehingga memakainya akan keliru
 * menandai puluhan ribu objek ke satu sektor yang salah.
 *
 * Sifat perintah:
 *   - hanya MENGISI yang kosong (whereNull)
 *   - tidak menghapus baris, tidak mengubah nomor
 *   - idempoten: aman dijalankan berulang
 *   - sektor tak dikenal DIBIARKAN kosong (tidak ditebak)
 *   - PBB-P2 & BPHTB di luar lingkup -> tidak diberi sektor
 *
 * Pemakaian:
 *   php artisan mpad:lengkapi-nomor-lama --dry-run
 *   php artisan mpad:lengkapi-nomor-lama --limit=500
 *   php artisan mpad:lengkapi-nomor-lama
 */
class LengkapiNomorLamaCommand extends Command
{
    protected $signature = 'mpad:lengkapi-nomor-lama
                            {--dry-run : Hanya tampilkan rencana, tidak menulis}
                            {--legacy : Isi legacy_code + legacy_npwpd (bukan sektor)}
                            {--limit=0 : Batasi jumlah baris (0 = semua)}';

    protected $description = 'Isi atribut nomor lama (jenis/sector_code/legacy_code/legacy_npwpd) — aditif, idempoten';

    /**
     * Peta nama klasifikasi -> sektor kodifikasi 14 digit.
     *
     * Kunci dicocokkan sebagai substring huruf kecil dari
     * retribution_classifications.name. Sektor mengikuti matriks baku
     * config/kodifikasi.php. Lingkup: non-PBB/BPHTB.
     */
    private const PETA_KLASIFIKASI = [
        // ---- NOPD 01-09 ----
        'pbjt - makan' => '01',
        'pbjt - food' => '01',
        'makan dan minum' => '01',
        'jasa perhotelan' => '02',
        'kesenian dan hiburan' => '03',
        'jasa parkir' => '04',
        'tenaga listrik' => '05',
        'pajak reklame' => '06',
        'sarang burung walet' => '07',
        'air tanah' => '08',
        'mineral bukan logam' => '09',
        'mblb' => '09',

        // ---- NORD 20-31 ----
        'pelayanan persampahan' => '20',
        'persampahan' => '20',
        'kebersihan' => '20',
        'parkir di tepi jalan' => '21',
        'pelayanan pasar' => '22',
        'tempat kegiatan usaha' => '22',
        'pelayanan kesehatan' => '23',
        'pelelangan ikan' => '24',
        'kepelabuhanan' => '25',
        'tempat rekreasi' => '26',
        'pemanfaatan bmd' => '27',
        'pemakaian alat berat' => '28',
        'penyedotan kakus' => '29',
        'persetujuan bangunan gedung' => '30',
        'tenaga kerja asing' => '31',
        'ptka' => '31',
    ];

    /** Klasifikasi yang secara tegas DI LUAR lingkup kodifikasi ini. */
    private const DI_LUAR_LINGKUP = ['pbb-p2', 'bphtb', 'pbb p2'];

    /**
     * Isi legacy_code + legacy_npwpd untuk nomor lama (aditif, idempoten).
     *
     * legacy_code  <- nomor lama itu sendiri (alias pencarian loket)
     * legacy_npwpd <- NPWPD pemilik objek pajak, supaya petugas bisa mencari
     *                 dengan NPWPD lama sebagaimana tertulis di berkas fisik.
     */
    private function lengkapiLegacy(bool $dryRun, int $limit): array
    {
        $q = DB::table('object_number_registry as r')
            ->join('tax_objects as o', 'o.id', '=', 'r.tax_object_id')
            ->leftJoin('taxpayers as t', 't.id', '=', 'o.taxpayer_id')
            ->where('r.nomor_sumber', 'existing')
            ->where(function ($w) {
                $w->whereNull('r.legacy_code')->orWhereNull('r.legacy_npwpd');
            })
            ->select('r.id', 'r.nomor', 'r.legacy_code', 'r.legacy_npwpd', 't.npwpd as npwpd_pemilik');

        if ($limit > 0) {
            $q->limit($limit);
        }

        $baris = $q->get();
        $diisi = 0;
        $tanpaNpwpd = 0;

        $bar = $this->output->createProgressBar(max($baris->count(), 1));
        $bar->start();

        foreach ($baris as $r) {
            $perubahan = ['updated_at' => now()];

            if (empty($r->legacy_code)) {
                $perubahan['legacy_code'] = $r->nomor;
            }

            if (empty($r->legacy_npwpd)) {
                if (! empty($r->npwpd_pemilik)) {
                    $perubahan['legacy_npwpd'] = $r->npwpd_pemilik;
                } else {
                    $tanpaNpwpd++;
                }
            }

            if (! $dryRun && count($perubahan) > 1) {
                DB::table('object_number_registry')->where('id', $r->id)->update($perubahan);
            }

            $diisi++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return ['diperiksa' => $baris->count(), 'diisi' => $diisi, 'tanpa_npwpd' => $tanpaNpwpd];
    }

    public function handleLegacy(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info($dryRun ? '=== MODE UJI (dry-run) — tidak menulis ===' : '=== MODE TULIS ===');

        $total = DB::table('object_number_registry')
            ->where('nomor_sumber', 'existing')
            ->where(function ($w) {
                $w->whereNull('legacy_code')->orWhereNull('legacy_npwpd');
            })
            ->count();

        $this->line("Baris nomor lama tanpa legacy_code / legacy_npwpd: {$total}");

        if ($total === 0) {
            $this->info('Tidak ada yang perlu dilengkapi.');

            return self::SUCCESS;
        }

        $hasil = $this->lengkapiLegacy($dryRun, $limit);

        $this->table(['Keterangan', 'Jumlah'], [
            ['Diperiksa', $hasil['diperiksa']],
            ['Diisi', $hasil['diisi']],
            ['Pemilik tanpa NPWPD', $hasil['tanpa_npwpd']],
        ]);

        $this->warn($dryRun ? 'Mode uji — TIDAK ada perubahan tersimpan.' : 'Selesai — legacy_code + legacy_npwpd terisi.');

        return self::SUCCESS;
    }

    public function handle(): int
    {
        if ($this->option('legacy')) {
            return $this->handleLegacy();
        }

        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info($dryRun ? '=== MODE UJI (dry-run) — tidak menulis ===' : '=== MODE TULIS ===');

        $dasar = DB::table('object_number_registry as r')
            ->join('tax_objects as o', 'o.id', '=', 'r.tax_object_id')
            ->leftJoin('retribution_classifications as c', 'c.id', '=', 'o.retribution_classification_id')
            ->where('r.nomor_sumber', 'existing')
            ->whereNull('r.jenis');

        $total = (clone $dasar)->count();
        $this->line("Baris nomor lama tanpa jenis: {$total}");

        if ($total === 0) {
            $this->info('Tidak ada yang perlu dilengkapi. Selesai.');

            return self::SUCCESS;
        }

        $q = (clone $dasar)->select('r.id', 'r.nomor', 'c.name as klasifikasi');
        if ($limit > 0) {
            $q->limit($limit);
            $this->line("Dibatasi {$limit} baris.");
        }
        $baris = $q->get();

        $diisi = 0;
        $luarLingkup = 0;
        $takDikenal = 0;
        $perSektor = [];

        $bar = $this->output->createProgressBar($baris->count());
        $bar->start();

        foreach ($baris as $r) {
            $nama = strtolower((string) ($r->klasifikasi ?? ''));

            $sektor = null;
            foreach (self::PETA_KLASIFIKASI as $kunci => $kode) {
                if ($nama !== '' && str_contains($nama, $kunci)) {
                    $sektor = $kode;
                    break;
                }
            }

            $diLuar = false;
            foreach (self::DI_LUAR_LINGKUP as $kunci) {
                if ($nama !== '' && str_contains($nama, $kunci)) {
                    $diLuar = true;
                    break;
                }
            }

            if ($diLuar) {
                $luarLingkup++;
            } elseif ($sektor === null) {
                $takDikenal++;
            }

            $jenis = $sektor !== null ? ((int) $sektor <= 9 ? 'NOPD' : 'NORD') : null;

            $perubahan = [
                'legacy_code' => $r->nomor,   // alias pencarian loket — selalu diisi
                'updated_at' => now(),
            ];
            if ($jenis !== null) {
                $perubahan['jenis'] = $jenis;
            }
            if ($sektor !== null) {
                $perubahan['sector_code'] = $sektor;
                $perSektor[$sektor] = ($perSektor[$sektor] ?? 0) + 1;
            }

            if (! $dryRun) {
                DB::table('object_number_registry')->where('id', $r->id)->update($perubahan);
            }

            $diisi++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        ksort($perSektor);
        $tabelSektor = [];
        foreach ($perSektor as $kode => $n) {
            $nama = config("kodifikasi.sektor.{$kode}.nama") ?? '?';
            $tabelSektor[] = ["{$kode} — {$nama}", $n];
        }

        $this->table(['Keterangan', 'Jumlah'], [
            ['Diperiksa', $diisi],
            ['Di luar lingkup (PBB/BPHTB, dibiarkan)', $luarLingkup],
            ['Klasifikasi tak dikenal (hanya legacy_code)', $takDikenal],
        ]);

        if ($tabelSektor !== []) {
            $this->newLine();
            $this->table(['Sebaran sektor', 'Jumlah'], $tabelSektor);
        }

        $this->newLine();
        $this->warn($dryRun ? 'Mode uji — TIDAK ada perubahan tersimpan.' : 'Selesai — sektor + legacy_code sudah terisi.');

        return self::SUCCESS;
    }
}
