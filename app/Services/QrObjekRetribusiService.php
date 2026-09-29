<?php

namespace App\Services;

use App\Models\TaxObject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Identitas fisik ber-QR untuk objek retribusi daerah (NORD).
 *
 * Stiker QR ditempel pada objek fisik: kios pasar, pos juru parkir, persil
 * sampah, lapak TPI. Warga/petugas cukup memindai untuk membuka tagihan.
 *
 * Token acak 16 karakter dipakai pada URL, BUKAN nomor NORD itu sendiri —
 * supaya nomor tidak bisa dipalsukan hanya dari melihat/memfoto stiker.
 */
class QrObjekRetribusiService
{
    /** Sekuriti: hanya token acak yang sah yang bisa dicari. */
    public function buatToken(): string
    {
        return strtolower(Str::random(16));
    }

    /**
     * Ambil atau buat token QR untuk sebuah objek.
     * Idempoten: token yang sudah ada TIDAK diganti.
     */
    public function tokenUntuk(TaxObject|int $objek): string
    {
        $o = $objek instanceof TaxObject ? $objek : TaxObject::findOrFail($objek);

        if (! empty($o->qr_token)) {
            return (string) $o->qr_token;
        }

        // Hindari tabrakan token (unique index tetap penjaga terakhir)
        for ($i = 0; $i < 10; $i++) {
            $token = $this->buatToken();
            $ada = DB::table('tax_objects')->where('qr_token', $token)->exists();
            if (! $ada) {
                DB::table('tax_objects')->where('id', $o->id)->update(['qr_token' => $token]);

                return $token;
            }
        }

        throw new \RuntimeException('Gagal membuat token QR unik setelah 10 percobaan.');
    }

    /** URL pemindaian (diarahkan ke aplikasi warga). */
    public function urlScan(string $token): string
    {
        $basis = rtrim((string) config('kodifikasi.qr.basis_url', 'https://mpad.baubaukota.go.id'), '/');

        return $basis.'/scan/'.$token;
    }

    /**
     * Isi teks QR. Mode 'url' (default) untuk pemindaian ponsel, 'teks' untuk
     * cetak lama yang hanya butuh nomor.
     */
    public function isiQr(TaxObject|int $objek, string $mode = 'url'): array
    {
        $o = $objek instanceof TaxObject ? $objek : TaxObject::findOrFail($objek);
        $token = $this->tokenUntuk($o);

        $reg = DB::table('object_number_registry')
            ->where('tax_object_id', $o->id)
            ->where('status', 'aktif')
            ->orderByRaw("CASE WHEN nomor_sumber = 'generated' THEN 0 ELSE 1 END")
            ->first();

        $nomorService = app(ObjectNumberService::class);
        $nomor = $reg->nomor ?? null;
        $tampilan = ($nomor && $nomorService->isValid($nomor)) ? $nomorService->formatTampilan($nomor) : $nomor;

        $teks = $mode === 'url' ? $this->urlScan($token) : (string) ($tampilan ?: $token);

        return [
            'tax_object_id' => $o->id,
            'nama_objek' => $o->name,
            'qr_token' => $token,
            'nord' => $nomor,
            'nord_tampilan' => $tampilan,
            'mode' => $mode,
            'isi_qr' => $teks,
        ];
    }

    /** Hasilkan gambar QR (PNG data-URI) siap ditempel di dokumen/stiker. */
    public function gambarPng(TaxObject|int $objek, int $ukuran = 320, string $mode = 'url'): string
    {
        $isi = $this->isiQr($objek, $mode)['isi_qr'];

        $png = QrCode::format('png')->size($ukuran)->margin(1)->generate($isi);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * Cari objek dari token hasil pemindaian.
     *
     * @return array<string,mixed>|null
     */
    public function cariToken(string $token): ?array
    {
        $token = trim($token);

        if (strlen($token) < 8) {
            return null; // token terlalu pendek — bukan token sah
        }

        $o = DB::table('tax_objects')->where('qr_token', $token)->first();

        if (! $o) {
            return null;
        }

        $reg = DB::table('object_number_registry')
            ->where('tax_object_id', $o->id)
            ->where('status', 'aktif')
            ->orderByRaw("CASE WHEN nomor_sumber = 'generated' THEN 0 ELSE 1 END")
            ->first();

        $nomorService = app(ObjectNumberService::class);
        $nomor = $reg->nomor ?? null;

        return [
            'tax_object_id' => $o->id,
            'nama_objek' => $o->name,
            'alamat' => $o->address,
            'nord' => $nomor,
            'nord_tampilan' => ($nomor && $nomorService->isValid($nomor)) ? $nomorService->formatTampilan($nomor) : $nomor,
            'wilayah' => trim(($o->district ?? '').' / '.($o->sub_district ?? ''), ' /'),
            'latitude' => $o->latitude,
            'longitude' => $o->longitude,
        ];
    }
}
