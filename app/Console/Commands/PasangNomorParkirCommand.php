<?php

namespace App\Console\Commands;

use App\Services\ObjectNumberRegistryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Pasang NORD 14-digit untuk objek PBJT Parkir.
 *
 * Objek PBJT Parkir = `tax_objects.retribution_classification_id = 172`
 * ("PBJT - Jasa Parkir", retribution_type_id = 16 / Pajak).
 * Sektor NORD PBJT Parkir = "04" (lihat config/kodifikasi.php).
 *
 * Memakai ObjectNumberRegistryService::pastikanNomor() yang sudah menegakkan
 * aturan utama: nomor lama yang SAH dipasang apa adanya, yang belum ada
 * digenerate. Idempoten — dijalankan ulang tidak mengubah nomor yang sudah ada.
 *
 * Hash/kode lama yang tidak standar disimpan sebagai jejak di
 * metadata->nop_placeholder_lama (tidak menghapus data apa pun).
 */
class PasangNomorParkirCommand extends Command
{
    protected $signature = 'mpad:pasang-nomor-parkir
                            {--dry-run : Tampilkan rencana tanpa menulis}
                            {--hanya-aktif : Hanya proses objek berstatus active}';

    protected $description = 'Pasang NORD 14-digit untuk objek PBJT Parkir (klasifikasi 172, sektor 04)';

    private const KLASIFIKASI_PARKIR = 172;

    private const SEKTOR = '04';

    public function handle(ObjectNumberRegistryService $registry): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $hanyaAktif = (bool) $this->option('hanya-aktif');

        $q = DB::table('tax_objects')
            ->where('retribution_classification_id', self::KLASIFIKASI_PARKIR);

        if ($hanyaAktif) {
            $q->where('status', 'active');
        }

        $objek = $q->get(['id', 'nop', 'name', 'district', 'sub_district', 'metadata', 'taxpayer_id', 'opd_id', 'status']);

        $this->info('Objek PBJT Parkir ditemukan: '.$objek->count());

        $sudah = 0;
        $dipasang = 0;
        $digenerate = 0;
        $dilewati = 0;
        $gagal = 0;

        $baris = [];

        foreach ($objek as $o) {
            if (DB::table('object_number_registry')->where('tax_object_id', $o->id)->exists()) {
                $sudah++;
                $baris[] = [$o->id, substr((string) $o->name, 0, 34), 'SUDAH ADA', '-'];

                continue;
            }

            $wilayah = $this->petakanWilayah($o->district, $o->sub_district);

            if ($wilayah === null) {
                // Wilayah tak terpetakan (mis. "Kota Baubau"/"Kecamatan Uji Coba")
                // → pakai wilayah default Wolio/Bataraguru (7101) HANYA bila objek berstatus active,
                //   sisanya dilewati agar tidak menebak.
                if ($o->status === 'active') {
                    $wilayah = ['kode_kecamatan' => '71', 'kode_kelurahan' => '01', 'catatan' => 'default Wolio/Bataraguru'];
                } else {
                    $dilewati++;
                    $baris[] = [$o->id, substr((string) $o->name, 0, 34), 'LEWAT (wilayah)', $o->district.' / '.$o->sub_district];

                    continue;
                }
            }

            $kodeWilayah = $wilayah['kode_kecamatan'].$wilayah['kode_kelurahan'];
            $nop = trim((string) $o->nop);
            $nomorSah = $this->nomorLamaSah($nop);

            if ($dryRun) {
                $aksi = $nomorSah !== null ? 'PASANG' : 'GENERATE';
                $baris[] = [$o->id, substr((string) $o->name, 0, 34), $aksi, $nomorSah ?? '7472'.self::SEKTOR.$kodeWilayah.'????'];
                $nomorSah !== null ? $dipasang++ : $digenerate++;

                continue;
            }

            try {
                $rec = $registry->pastikanNomor(
                    $nomorSah,
                    self::SEKTOR,
                    $kodeWilayah,
                    [
                        'tax_object_id' => $o->id,
                        'taxpayer_id' => $o->taxpayer_id,
                        'opd_id' => $o->opd_id,
                        'jenis' => 'NOPD',
                    ]
                );

                // Kalau digenerate: tulis nomor ke tax_objects.nop + simpan jejak lama
                if ($nomorSah === null) {
                    $md = json_decode((string) $o->metadata, true) ?: [];
                    if ($nop !== '') {
                        $md['nop_placeholder_lama'] = $nop;
                    }
                    DB::table('tax_objects')->where('id', $o->id)->update([
                        'nop' => $rec->nomor_baku ?: $rec->nomor,
                        'metadata' => json_encode($md),
                    ]);
                    $digenerate++;
                    $baris[] = [$o->id, substr((string) $o->name, 0, 34), 'GENERATE', $rec->nomor_baku ?: $rec->nomor];
                } else {
                    $dipasang++;
                    $baris[] = [$o->id, substr((string) $o->name, 0, 34), 'DIPASANG', $rec->nomor];
                }
            } catch (\Throwable $e) {
                $gagal++;
                $baris[] = [$o->id, substr((string) $o->name, 0, 34), 'GAGAL', substr($e->getMessage(), 0, 45)];
            }
        }

        $this->newLine();
        $this->table(['ID', 'Nama objek', 'Aksi', 'Nomor'], $baris);

        $this->newLine();
        $this->line('Sudah punya nomor : '.$sudah);
        $this->line('Dipasang (lama)   : '.$dipasang);
        $this->line('Digenerate (baru) : '.$digenerate);
        $this->line('Dilewati          : '.$dilewati);
        $this->line('Gagal             : '.$gagal);

        if ($dryRun) {
            $this->comment('DRY RUN: tidak ada data ditulis.');
        }

        return self::SUCCESS;
    }

    /**
     * Nomor lama yang SAH dan dipasang apa adanya.
     * Hash 32-hex & bentuk non-standar → null (harus digenerate).
     */
    private function nomorLamaSah(string $nop): ?string
    {
        if ($nop === '' || $nop === '-') {
            return null;
        }

        if (preg_match('/^[0-9a-f]{32}$/i', $nop)) {
            return null;
        }

        // Bertitik 9pajak: 8.01.710.004.0354
        if (preg_match('/^\d{1,2}\.\d{2}\.\d{3}\.\d{3}\.\d{4}$/', $nop)) {
            return $nop;
        }

        // Polos 13/14 digit (PATDA / sudah 14-digit) dan 17-18 digit (keluarga PBB)
        if (preg_match('/^\d{13,14}$/', $nop) || preg_match('/^\d{17,18}$/', $nop)) {
            return $nop;
        }

        return null;
    }

    /**
     * Petakan nama kecamatan/kelurahan objek → kode baku M-PAD (71-78 / 01-15).
     */
    private function petakanWilayah(?string $kecamatan, ?string $kelurahan): ?array
    {
        $norm = fn (?string $s) => strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $s));

        $kec = $norm($kecamatan);
        $kel = $norm($kelurahan);

        if ($kec === '' && $kel === '') {
            return null;
        }

        $rows = DB::table('ref_wilayah')->get(['kode_kecamatan', 'kode_kelurahan', 'kecamatan', 'kelurahan']);

        if ($kel !== '') {
            foreach ($rows as $r) {
                $rk = $norm($r->kelurahan);
                if ($rk !== '' && ($rk === $kel || str_contains($rk, $kel) || str_contains($kel, $rk))) {
                    return ['kode_kecamatan' => $r->kode_kecamatan, 'kode_kelurahan' => $r->kode_kelurahan];
                }
            }
        }

        if ($kec !== '') {
            foreach ($rows as $r) {
                $rc = $norm($r->kecamatan);
                if ($rc !== '' && ($rc === $kec || str_contains($rc, $kec) || str_contains($kec, $rc))) {
                    return ['kode_kecamatan' => $r->kode_kecamatan, 'kode_kelurahan' => $r->kode_kelurahan];
                }
            }
        }

        return null;
    }
}
