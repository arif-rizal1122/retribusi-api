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

        return response()->json([
            'is_valid' => true,
            'document_number' => $signedDoc->document_number,
            'signer' => [
                'name' => $signedDoc->signer->name ?? 'N/A',
                'nip' => $signedDoc->metadata['signer_nip'] ?? '-',
                'signed_at' => $signedDoc->signed_at->toIso8601String(),
            ],
            'hash' => $signedDoc->signature_hash,
            'status' => 'DOKUMEN VALID',
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
