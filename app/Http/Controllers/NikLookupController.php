<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DukcapilNikService;

/**
 * Lookup NIK → data autofill (nama, alamat).
 * Pola H2H autocomplete + manual fallback (28 Agu 2026):
 * - Jika PKS Dukcapil aktif (DUKCAPIL_BASE_URL terisi) & NIK valid → kembalikan data
 *   untuk autofill; frontend tampilkan field readonly hijau "Terverifikasi Dukcapil".
 * - Jika belum ada integrasi → available=false; frontend tetap izinkan isi manual
 *   dengan badge "Belum terverifikasi — isi manual". Sistem TIDAK memblokir.
 */
class NikLookupController extends Controller
{
    public function __invoke(Request $request, DukcapilNikService $dukcapil)
    {
        $request->validate([
            'nik' => 'required|string|size:16',
        ]);

        $nik = trim($request->nik);

        // Format lokal dulu — tanpa ini, jangan buang request ke Dukcapil
        if (! $dukcapil->validateFormat($nik)) {
            return response()->json([
                'available' => true,
                'valid'     => false,
                'reason'    => 'format_invalid',
                'message'   => 'Format NIK tidak valid (harus 16 digit).',
                'data'      => null,
            ], 422);
        }

        $result = $dukcapil->validate($nik);

        // PKS belum aktif / belum dikonfigurasi → cek data lokal M-PAD dulu, lalu manual fallback
        if (($result['available'] ?? false) === false) {
            $localTaxpayer = \App\Models\Taxpayer::where('nik', $nik)->first();
            if ($localTaxpayer) {
                return response()->json([
                    'available' => false,
                    'valid'     => true,
                    'reason'    => 'cached_local',
                    'message'   => 'Data Wajib Pajak ditemukan di database M-PAD lokal.',
                    'data'      => [
                        'nik'     => $nik,
                        'nama'    => $localTaxpayer->name,
                        'alamat'  => $localTaxpayer->address,
                        'source'  => 'local_database',
                    ],
                ]);
            }

            return response()->json([
                'available' => false,
                'valid'     => null,
                'reason'    => 'not_configured',
                'message'   => 'Layanan verifikasi Dukcapil belum aktif (menunggu PKS). Silakan lengkapi data secara manual.',
                'data'      => null,
            ]);
        }

        if (($result['valid'] ?? false) !== true) {
            return response()->json([
                'available' => true,
                'valid'     => false,
                'reason'    => $result['reason'] ?? 'invalid',
                'message'   => 'NIK tidak terverifikasi di Dukcapil. Silakan isi manual atau hubungi Dukcapil.',
                'data'      => null,
            ], 422);
        }

        $raw = $result['data'] ?? [];
        $data = [
            'nik'      => $nik,
            'nama'     => $raw['nama'] ?? $raw['name'] ?? null,
            'alamat'   => $raw['alamat'] ?? $raw['address'] ?? null,
            'tempat_lahir' => $raw['tempat_lahir'] ?? null,
            'tanggal_lahir' => $raw['tanggal_lahir'] ?? null,
            'jk'       => $raw['jenis_kelamin'] ?? $raw['jk'] ?? null,
            'source'   => 'dukcapil',
        ];

        return response()->json([
            'available' => true,
            'valid'     => true,
            'message'   => 'NIK terverifikasi.',
            'data'      => $data,
        ]);
    }
}
