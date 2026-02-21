<?php

namespace App\Services;

use App\Models\SignedDocument;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TTEService
{
    /**
     * Mock TTE Signing Process
     * In real life, this would call BSRE/DSIGN APIs
     */
    public function signDocument($documentable, User $signer, $notes = null)
    {
        $docNumber = $documentable->bill_number ?? $documentable->number ?? 'DOC-' . strtoupper(uniqid());
        
        // Check if already signed
        $existing = SignedDocument::where('document_type', get_class($documentable))
            ->where('document_id', $documentable->id)
            ->where('status', 'signed')
            ->first();
            
        if ($existing) {
            return $existing;
        }

        // Generate a mock signature hash
        $hash = hash('sha256', $docNumber . $signer->id . now()->toIso8601String() . Str::random(16));

        // Create the signed document entry
        $signedDoc = SignedDocument::create([
            'document_type' => get_class($documentable),
            'document_id' => $documentable->id,
            'document_number' => $docNumber,
            'signature_hash' => $hash,
            'signed_by' => $signer->id,
            'signed_at' => now(),
            'status' => 'signed',
            'metadata' => [
                'signer_name' => $signer->name,
                'signer_nip' => $signer->nik ?? 'N/A',
                'notes' => $notes,
                'ip_address' => request()->ip(),
            ],
            'verification_url' => url("/api/verify/tte/{$docNumber}"),
        ]);

        return $signedDoc;
    }

    /**
     * Verify a signature hash against the database
     */
    public function verifySignature($docNumber, $hash)
    {
        $signedDoc = SignedDocument::where('document_number', $docNumber)
            ->where('signature_hash', $hash)
            ->where('status', 'signed')
            ->first();

        return $signedDoc ? true : false;
    }
}
