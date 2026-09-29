<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Engine kodifikasi NOPD / NORD Kota Baubau.
 *
 * Format LAMA (existing, dipertahankan apa adanya) — 14 digit:
 *   7472 . SS . KKVV . UUUU
 *   UUUU = nomor urut titik (0001-9999)
 *
 * Format BARU (digenerate) — 16 digit:
 *   7472 . SS . KKVV . UUUUUU
 *   UUUUUU = nomor urut titik (000001-999999)
 *
 * Keduanya didukung. Nomor existing TIDAK diubah panjangnya.
 *
 * Service ini murni (tidak menyentuh DB) sehingga mudah diuji.
 */
class ObjectNumberService
{
    public function kodeKota(): string
    {
        return (string) config('kodifikasi.kode_kota', '7472');
    }

    /** Panjang nomor default (existing / kompatibilitas). */
    public function panjang(): int
    {
        return (int) config('kodifikasi.panjang', 14);
    }

    /** Panjang nomor BARU yang digenerate. */
    public function panjangBaru(): int
    {
        return (int) config('kodifikasi.panjang_baru', 16);
    }

    /** Panjang slot urut format lama. */
    public function urutPanjangLama(): int
    {
        return (int) config('kodifikasi.urut_panjang_lama', 4);
    }

    /** Panjang slot urut format baru. */
    public function urutPanjangBaru(): int
    {
        return (int) config('kodifikasi.urut_panjang_baru', 6);
    }

    /** Panjang digit nomor urut berdasarkan panjang nomor total. */
    public function urutPanjangUntuk(int $panjangNomor): int
    {
        return $panjangNomor >= $this->panjangBaru() ? $this->urutPanjangBaru() : $this->urutPanjangLama();
    }

    /** @return array<string, array{nama:string, jenis:string, opd:string}> */
    public function sektorMap(): array
    {
        return (array) config('kodifikasi.sektor', []);
    }

    public function sektorValid(string $sektor): bool
    {
        return array_key_exists($sektor, $this->sektorMap());
    }

    public function sektor(string $sektor): array
    {
        $map = $this->sektorMap();
        if (! isset($map[$sektor])) {
            throw new InvalidArgumentException("Kode sektor '{$sektor}' tidak dikenal dalam matriks baku M-PAD.");
        }

        return $map[$sektor];
    }

    public function jenis(string $sektor): string
    {
        return $this->sektor($sektor)['jenis'];
    }

    public function isSektorPajak(string $sektor): bool
    {
        return $this->sektorValid($sektor) && $this->jenis($sektor) === 'NOPD';
    }

    public function isSektorRetribusi(string $sektor): bool
    {
        return $this->sektorValid($sektor) && $this->jenis($sektor) === 'NORD';
    }

    /**
     * Susun nomor dari komponennya.
     *
     * $panjang = 16 (default, format baru berurut 6 digit) atau 14 (format lama).
     * Nomor existing TIDAK pernah lewat sini — lihat ObjectNumberRegistryService::pasang().
     */
    public function generate(string $sektor, string $kodeWilayah, int $urut, ?int $panjang = null): string
    {
        $this->sektor($sektor);

        $panjang ??= $this->panjangBaru();
        $lebarUrut = $this->urutPanjangUntuk($panjang);

        $sektor = str_pad($sektor, 2, '0', STR_PAD_LEFT);
        $kodeWilayah = $this->normalisasiKodeWilayah($kodeWilayah);
        $this->validasiUrut($urut, $lebarUrut);

        $nomor = $this->kodeKota().$sektor.$kodeWilayah.str_pad((string) $urut, $lebarUrut, '0', STR_PAD_LEFT);

        if (strlen($nomor) !== $panjang) {
            throw new InvalidArgumentException("Nomor hasil generate tidak {$panjang} digit: {$nomor}");
        }

        return $nomor;
    }

    /**
     * Validasi nomor NOPD/NORD — menerima 14 digit (lama) maupun 16 digit (baru).
     */
    public function isValid(?string $nomor): bool
    {
        if (! is_string($nomor) || ! preg_match('/^\d{14}$|^\d{16}$/', $nomor)) {
            return false;
        }

        if (substr($nomor, 0, 4) !== $this->kodeKota()) {
            return false;
        }

        try {
            $this->parse($nomor);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Uraikan nomor menjadi komponennya. Mendukung 14 dan 16 digit.
     *
     * @throws InvalidArgumentException
     */
    public function parse(string $nomor): array
    {
        if (! is_string($nomor) || ! preg_match('/^\d{14}$|^\d{16}$/', $nomor)) {
            throw new InvalidArgumentException('Nomor objek wajib 14 atau 16 digit angka.');
        }

        $panjang = strlen($nomor);
        $lebarUrut = $this->urutPanjangUntuk($panjang);

        $kodeKota = substr($nomor, 0, 4);
        if ($kodeKota !== $this->kodeKota()) {
            throw new InvalidArgumentException("Kode kota '{$kodeKota}' tidak sesuai kode kota {$this->kodeKota()}.");
        }

        $sektor = substr($nomor, 4, 2);
        $info = $this->sektor($sektor);

        $kodeWilayah = substr($nomor, 6, 4);
        $kecamatan = substr($kodeWilayah, 0, 2);
        $kelurahan = substr($kodeWilayah, 2, 2);
        $this->validasiKodeWilayah($kecamatan, $kelurahan);

        $urut = (int) substr($nomor, 10, $lebarUrut);
        $this->validasiUrut($urut, $lebarUrut);

        return [
            'nomor' => $nomor,
            'panjang' => $panjang,
            'kode_kota' => $kodeKota,
            'sektor' => $sektor,
            'sektor_nama' => $info['nama'],
            'jenis' => $info['jenis'],
            'jenis_nama' => config('kodifikasi.jenis.'.$info['jenis'], $info['jenis']),
            'opd' => $info['opd'],
            'kode_wilayah' => $kodeWilayah,
            'kode_kecamatan' => $kecamatan,
            'kode_kelurahan' => $kelurahan,
            'urut' => $urut,
            'tampilan' => $this->formatTampilan($nomor),
        ];
    }

    /**
     * Bentuk tampilan bertitik.
     * 14 digit: 7472.01.7101.0001
     * 16 digit: 7472.01.7101.000001
     */
    public function formatTampilan(string $nomor): string
    {
        if (! preg_match('/^\d{14}$|^\d{16}$/', $nomor)) {
            return $nomor;
        }

        $lebarUrut = $this->urutPanjangUntuk(strlen($nomor));

        return substr($nomor, 0, 4).'.'.substr($nomor, 4, 2).'.'.substr($nomor, 6, 4).'.'.substr($nomor, 10, $lebarUrut);
    }

    /**
     * Hapus titik/spasi/pemisah dari input pengguna lalu validasi panjang.
     */
    public function normalisasiInput(string $nomor): string
    {
        return preg_replace('/[^0-9]/', '', $nomor) ?? '';
    }

    private function normalisasiKodeWilayah(string $kodeWilayah): string
    {
        $kodeWilayah = preg_replace('/[^0-9]/', '', $kodeWilayah) ?? '';

        if (strlen($kodeWilayah) === 2) {
            $kodeWilayah .= config('kodifikasi.kode_kelurahan_sentinel', '00');
        }

        if (strlen($kodeWilayah) !== 4) {
            throw new InvalidArgumentException('Kode wilayah harus 4 digit (2 digit kecamatan + 2 digit kelurahan).');
        }

        $this->validasiKodeWilayah(substr($kodeWilayah, 0, 2), substr($kodeWilayah, 2, 2));

        return $kodeWilayah;
    }

    private function validasiKodeWilayah(string $kecamatan, string $kelurahan): void
    {
        $min = (int) config('kodifikasi.kode_kecamatan_min', 71);
        $max = (int) config('kodifikasi.kode_kecamatan_max', 78);

        if (! ctype_digit($kecamatan) || (int) $kecamatan < $min || (int) $kecamatan > $max) {
            throw new InvalidArgumentException("Kode kecamatan '{$kecamatan}' di luar rentang {$min}-{$max}.");
        }

        $kelurahanMax = (int) config('kodifikasi.kode_kelurahan_max', 15);
        if (! ctype_digit($kelurahan) || (int) $kelurahan > $kelurahanMax) {
            throw new InvalidArgumentException("Kode kelurahan '{$kelurahan}' di luar rentang 00-{$kelurahanMax}.");
        }
    }

    /**
     * Validasi nomor urut titik sesuai lebar slot (4 digit lama, 6 digit baru).
     *
     * Pesan error dibuat jujur: kalau ember (sektor+kelurahan) sudah penuh,
     * jangan bilang "antara 1 dan 9999" — itu menyesatkan operator.
     */
    private function validasiUrut(int $urut, ?int $lebar = null): void
    {
        $lebar ??= $this->urutPanjangLama();
        $min = (int) config('kodifikasi.urut_min', 1);
        $max = $lebar >= $this->urutPanjangBaru()
            ? (int) config('kodifikasi.urut_max_baru', 999999)
            : (int) config('kodifikasi.urut_max', 9999);

        if ($urut < $min) {
            throw new InvalidArgumentException("Nomor urut titik minimal {$min}.");
        }

        if ($urut > $max) {
            throw new InvalidArgumentException(
                "Kapasitas ember nomor sudah penuh: nomor urut mencapai {$urut}, batas {$max} ".
                "untuk slot {$lebar} digit. Perluas slot urut atau pecah kode wilayah."
            );
        }
    }
}
