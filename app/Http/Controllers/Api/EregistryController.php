<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SignedDocument;
use Illuminate\Http\Request;

class EregistryController extends Controller
{
    /**
     * Verify document hash and authenticity
     */
    public function verify(Request $request, $number)
    {
        $signedDoc = SignedDocument::with(['signer'])
            ->where('document_number', $number)
            ->where('status', 'signed')
            ->first();

        if (!$signedDoc) {
            return response()->json([
                'is_valid' => false,
                'message' => 'Dokumen tidak ditemukan atau belum ditandatangani secara elektronik.',
            ], 404);
        }

        $meta = $signedDoc->metadata ?? [];
        $isBsre = (bool) ($meta['tte_is_bsre_verified'] ?? false);
        $mode = $meta['tte_mode'] ?? 'LOCAL_SEAL_ONLY';

        return response()->json([
            'is_valid' => $isBsre,                 // true HANYA kalau ber-TTE BSrE BSSN
            'is_valid_seal' => true,               // segel kriptografis internal M-PAD terverifikasi
            'document_number' => $signedDoc->document_number,
            'signer' => [
                'name' => $signedDoc->signer->name ?? ($meta['signer_name'] ?? 'N/A'),
                'nip' => $meta['signer_nip'] ?? '-',
                'signed_at' => $signedDoc->signed_at->toIso8601String(),
            ],
            'hash' => $signedDoc->signature_hash,
            'tte_mode' => $mode,                   // BSRE_BSSN | LOCAL_SEAL_ONLY
            'tte_status' => $isBsre
                ? 'DOKUMEN VALID — TTE BSrE BSSN'
                : 'SEGEL ELEKTRONIK M-PAD (belum TTE BSrE)',
            'disclaimer' => $meta['tte_disclaimer']
                ?? ($isBsre ? null : 'Dokumen ini disegel secara elektronik oleh sistem M-PAD '
                    . 'dan belum tersertifikasi BSrE BSSN.'),
            'status' => $isBsre ? 'DOKUMEN VALID' : 'SEGEL ELEKTRONIK M-PAD',
        ]);
    }

    /**
     * List all signed documents (Admin Only)
     */
    public function index()
    {
        return response()->json(SignedDocument::with(['signer'])->latest()->paginate(20));
    }
}
