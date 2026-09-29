<?php

namespace App\Http\Controllers;

use App\Models\TaxObject;
use App\Services\QrObjekRetribusiService;
use Illuminate\Http\Request;

/**
 * Identitas fisik ber-QR objek retribusi daerah (NORD).
 */
class QrObjekRetribusiController extends Controller
{
    public function __construct(private QrObjekRetribusiService $qr) {}

    /** Data QR objek (isi + gambar siap cetak). */
    public function tampil(Request $request, int $id)
    {
        $data = $request->validate([
            'mode' => 'nullable|in:url,teks',
            'ukuran' => 'nullable|integer|min:128|max:1024',
        ]);

        $o = TaxObject::findOrFail($id);
        $mode = $data['mode'] ?? 'url';
        $ukuran = (int) ($data['ukuran'] ?? config('kodifikasi.qr.ukuran_default', 320));

        return response()->json([
            'success' => true,
            'data' => array_merge($this->qr->isiQr($o, $mode), [
                'gambar' => $this->qr->gambarPng($o, $ukuran, $mode),
            ]),
        ]);
    }

    /** Cari objek dari token hasil pemindaian (publik — halaman scan warga). */
    public function cari(string $token)
    {
        $hasil = $this->qr->cariToken($token);

        if (! $hasil) {
            return response()->json([
                'success' => false,
                'message' => 'Token QR tidak dikenali. Pastikan stiker masih berlaku.',
            ], 404);
        }

        return response()->json(['success' => true, 'data' => $hasil]);
    }

    /** Buat/ambil token QR objek (idempoten). */
    public function pastikan(Request $request, int $id)
    {
        $o = TaxObject::findOrFail($id);
        $token = $this->qr->tokenUntuk($o);

        return response()->json([
            'success' => true,
            'data' => ['tax_object_id' => $o->id, 'qr_token' => $token],
        ]);
    }
}
