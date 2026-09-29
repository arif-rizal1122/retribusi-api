<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Engine kodifikasi NOPD / NORD 14 digit Kota Baubau.
 *
 * Format: 7472 . SS . KKVV . UUUU
 *   7472 = kode kota
 *   SS   = sektor (01-09 NOPD, 20-31 NORD)
 *   KK   = kode kecamatan (71-78)
 *   VV   = kode kelurahan (01-15, atau 00 bila belum dipecah)
 *   UUUU = nomor urut titik (0001-9999)
 *
 * Service ini murni (tidak menyentuh DB) sehingga mudah diuji.
 */
class ObjectNumberService
{
    public function kodeKota(): string
    {
        return (string) config('kodifikasi.kode_kota', '7472');
    }

    public function panjang(): int
    {
        return (int) config('kodifikasi.panjang', 14);
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
     * Susun nomor 14 digit dari komponennya.
     */
    public function generate(string $sektor, string $kodeWilayah, int $urut): string
    {
        $this->sektor($sektor);

        $sektor = str_pad($sektor, 2, '0', STR_PAD_LEFT);
        $kodeWilayah = $this->normalisasiKodeWilayah($kodeWilayah);
        $this->validasiUrut($urut);

        $nomor = $this->kodeKota().$sektor.$kodeWilayah.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);

        if (strlen($nomor) !== $this->panjang()) {
            throw new InvalidArgumentException("Nomor hasil generate tidak 14 digit: {$nomor}");
        }

        return $nomor;
    }

    /**
     * Validasi nomor 14 digit lengkap.
     */
    public function isValid(?string $nomor): bool
    {
        if (! is_string($nomor) || ! preg_match('/^\d{14}$/', $nomor)) {
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
     * Uraikan nomor 14 digit menjadi komponennya.
     *
     * @throws InvalidArgumentException
     */
    public function parse(string $nomor): array
    {
        if (! is_string($nomor) || ! preg_match('/^\d{14}$/', $nomor)) {
            throw new InvalidArgumentException('Nomor objek wajib 14 digit angka.');
        }

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

        $urut = (int) substr($nomor, 10, 4);
        $this->validasiUrut($urut);

        return [
            'nomor' => $nomor,
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
     * Bentuk tampilan bertitik: 7472.01.7101.0001
     */
    public function formatTampilan(string $nomor): string
    {
        if (! preg_match('/^\d{14}$/', $nomor)) {
            return $nomor;
        }

        return substr($nomor, 0, 4).'.'.substr($nomor, 4, 2).'.'.substr($nomor, 6, 4).'.'.substr($nomor, 10, 4);
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

    private function validasiUrut(int $urut): void
    {
        $min = (int) config('kodifikasi.urut_min', 1);
        $max = (int) config('kodifikasi.urut_max', 9999);

        if ($urut < $min || $urut > $max) {
            throw new InvalidArgumentException("Nomor urut titik harus antara {$min} dan {$max}.");
        }
    }
}
