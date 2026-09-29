<?php

namespace App\Services;

use App\Models\TaxObject;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Perhitungan Pajak MBLB (Mineral Bukan Logam dan Batuan) — sektor NOPD 09.
 *
 * Rumus Perda: (volume × harga_patokan) × tarif 15%
 *
 * Triangulasi volume: laporan pengusaha dibandingkan dengan catatan ritase
 * truk di pos jaga gerbang tambang. Bila selisih melewati ambang, laporan
 * ditandai perlu diperiksa (bukan langsung ditolak) — keputusan di petugas.
 */
class SptpdMblbService
{
    /** Tarif pajak MBLB sesuai Perda (15%). */
    public function tarif(): float
    {
        return (float) config('kodifikasi.mblb.tarif', 0.15);
    }

    /**
     * Hitung pajak dari volume dan harga patokan.
     *
     * @param  float  $volume  volume terpakai (m3 atau ton)
     * @param  float  $harga   harga patokan daerah per satuan
     */
    public function hitung(float $volume, float $harga): array
    {
        if ($volume < 0) {
            throw new InvalidArgumentException('Volume tidak boleh negatif.');
        }
        if ($harga < 0) {
            throw new InvalidArgumentException('Harga patokan tidak boleh negatif.');
        }

        $dasar = $volume * $harga;
        $pajak = $dasar * $this->tarif();

        return [
            'volume' => round($volume, 2),
            'harga' => round($harga, 2),
            'dasar_pengenaan' => round($dasar, 2),
            'tarif' => $this->tarif(),
            'pajak' => round($pajak, 2),
        ];
    }

    /**
     * Hitung pajak dari LAPORAN pengusaha pada metadata objek.
     *
     * @param  array<string,mixed>  $metadata
     */
    public function hitungDariMetadata(array $metadata): array
    {
        $volume = (float) ($metadata['volume'] ?? 0);
        $harga = (float) ($metadata['harga'] ?? 0);

        if ($harga <= 0) {
            throw new InvalidArgumentException(
                'Harga patokan daerah belum diisi pada objek ini. Hubungi Bapenda untuk penetapan harga.'
            );
        }

        return $this->hitung($volume, $harga);
    }

    /**
     * Hitung volume dari RITASE truk pos jaga.
     *
     * @param  array<int,array{kapasitas:float,jumlah:int}>  $ritase
     */
    public function volumeDariRitase(array $ritase): float
    {
        $total = 0.0;
        foreach ($ritase as $r) {
            $kapasitas = (float) ($r['kapasitas'] ?? 0);
            $jumlah = (int) ($r['jumlah'] ?? 0);

            if ($kapasitas < 0 || $jumlah < 0) {
                throw new InvalidArgumentException('Kapasitas dan jumlah ritase tidak boleh negatif.');
            }

            $total += $kapasitas * $jumlah;
        }

        return round($total, 2);
    }

    /**
     * Triangulasi: bandingkan volume laporan pengusaha dengan volume ritase pos jaga.
     *
     * Tidak menolak — hanya menandai. Ambang selisih diambil dari config
     * (default 10%). Bila ritase kosong (pos jaga belum mencatat), status
     * 'tanpa-pembanding' — laporan tetap boleh lanjut.
     *
     * @return array{status:string,volume_laporan:float,volume_ritase:float,selisih:float,selisih_persen:float,catatan:string}
     */
    public function triangulasi(float $volumeLaporan, float $volumeRitase): array
    {
        $ambang = (float) config('kodifikasi.mblb.ambang_selisih', 0.10);

        if ($volumeRitase <= 0) {
            return [
                'status' => 'tanpa-pembanding',
                'volume_laporan' => round($volumeLaporan, 2),
                'volume_ritase' => 0.0,
                'selisih' => round($volumeLaporan, 2),
                'selisih_persen' => 0.0,
                'catatan' => 'Pos jaga belum mencatat ritase untuk periode ini. Laporan lanjut tanpa pembanding.',
            ];
        }

        $selisih = $volumeLaporan - $volumeRitase;
        $persen = abs($selisih) / $volumeRitase;

        if ($persen <= $ambang) {
            return [
                'status' => 'sesuai',
                'volume_laporan' => round($volumeLaporan, 2),
                'volume_ritase' => round($volumeRitase, 2),
                'selisih' => round($selisih, 2),
                'selisih_persen' => round($persen * 100, 2),
                'catatan' => 'Volume laporan selaras dengan catatan ritase pos jaga.',
            ];
        }

        $arah = $selisih > 0 ? 'lebih tinggi' : 'lebih rendah';

        return [
            'status' => 'perlu-diperiksa',
            'volume_laporan' => round($volumeLaporan, 2),
            'volume_ritase' => round($volumeRitase, 2),
            'selisih' => round($selisih, 2),
            'selisih_persen' => round($persen * 100, 2),
            'catatan' => sprintf(
                'Volume laporan %s %.2f%% dibanding ritase pos jaga (selisih %.2f). Perlu pemeriksaan petugas.',
                $arah,
                $persen * 100,
                abs($selisih)
            ),
        ];
    }

    /**
     * Ringkas: laporan pengusaha + ritase pos jaga -> hasil siap terbit tagihan.
     *
     * @param  array<int,array{kapasitas:float,jumlah:int}>  $ritase
     */
    public function laporanLengkap(TaxObject|int $objek, array $ritase = []): array
    {
        $o = $objek instanceof TaxObject ? $objek : TaxObject::findOrFail($objek);
        $metadata = is_array($o->metadata) ? $o->metadata : (json_decode((string) $o->metadata, true) ?: []);

        $perhitungan = $this->hitungDariMetadata($metadata);
        $volumeRitase = $this->volumeDariRitase($ritase);
        $tri = $this->triangulasi((float) $perhitungan['volume'], $volumeRitase);

        return [
            'tax_object_id' => $o->id,
            'nama_objek' => $o->name,
            'jenis_mineral' => $metadata['jenis_mineral'] ?? null,
            'satuan' => $metadata['satuan_volume'] ?? 'm3',
            'perhitungan' => $perhitungan,
            'ritase' => $ritase,
            'volume_ritase' => $volumeRitase,
            'triangulasi' => $tri,
            'boleh_terbit' => $tri['status'] !== 'perlu-diperiksa' || (bool) config('kodifikasi.mblb.terbit_walau_selisih', false),
        ];
    }
}
