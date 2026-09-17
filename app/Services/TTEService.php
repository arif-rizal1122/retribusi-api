<?php

namespace App\Services;

use App\Models\AssetToken;
use App\Models\SignedDocument;
use App\Models\TteLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TTEService
{
    protected string $bsreUrl;
    protected ?string $bsreUser;
    protected ?string $bsrePass;
    protected int $bsreTimeout;

    public function __construct()
    {
        $this->bsreUrl = config('services.bsre.url', env('BSRE_API_URL', 'https://esign-sandbox.bsre.bssid'));
        $this->bsreUser = config('services.bsre.username', env('BSRE_USERNAME', 'baubau_mpad'));
        $this->bsrePass = config('services.bsre.password', env('BSRE_PASSWORD', 'BaubauTTE2026!'));
        $this->bsreTimeout = (int) env('BSRE_TIMEOUT', 3000); // 3 seconds timeout
    }

    /**
     * Sign an official document using BSrE REST API or Cryptographic Fallback
     */
    public function signDocument($documentable, ?User $signer = null, ?string $notes = null, string $signatureType = 'ELECTRONIC_SEAL', ?string $customDocNumber = null, ?int $opdId = null)
    {
        $docNumber = $customDocNumber ?? $documentable->bill_number ?? $documentable->number ?? 'DOC-' . strtoupper(uniqid());

        // Check if already signed and valid
        $existing = SignedDocument::where('document_type', get_class($documentable))
            ->where('document_id', $documentable->id)
            ->where('status', 'signed')
            ->first();

        if ($existing) {
            return $existing;
        }

        $signerName = $signer?->name ?? 'Pemerintah Kota Baubau';
        $signerNip = $signer?->nik ?? $signer?->nip ?? 'INSTITUSI-7471';
        $signerRole = $signer?->role ? strtoupper($signer->role) : 'SEGEL ELEKTRONIK RESMI';

        // Attempt H2H BSrE API request with fallback
        $signResult = $this->callBsreSignEndpoint($docNumber, $signerNip, $signatureType);

        // Generate SHA-256 signature hash & checksum
        $hash = $signResult['hash'] ?? hash('sha256', $docNumber . $signerNip . now()->toIso8601String() . Str::random(16));
        $checksum = hash('sha256', $docNumber . $hash);

        // Create the signed document entry in e-registry
        $signedDoc = SignedDocument::create([
            'document_type' => get_class($documentable),
            'document_id' => $documentable->id,
            'opd_id' => $opdId ?? $documentable->opd_id ?? null,
            'document_number' => $docNumber,
            'file_path' => $signResult['file_path'] ?? null,
            'signature_hash' => $hash,
            'sha256_checksum' => $checksum,
            'signed_by' => $signer?->id,
            'signer_name' => $signerName,
            'signer_nip' => $signerNip,
            'signer_role' => $signerRole,
            'signature_type' => $signatureType,
            'signed_at' => now(),
            'status' => 'signed',
            'metadata' => [
                'signer_name' => $signerName,
                'signer_nip' => $signerNip,
                'signer_role' => $signerRole,
                'notes' => $notes,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'bsre_status' => $signResult['status'] ?? 'fallback_local_seal',
                'bsre_reference' => $signResult['reference'] ?? null,
                // Kejujuran status: apakah TTE ini BENAR dari BSrE BSSN,
                // atau hanya segel kriptografis lokal M-PAD?
                'tte_is_bsre_verified' => ($signResult['status'] ?? '') === 'success',
                'tte_mode' => ($signResult['status'] ?? '') === 'success'
                    ? 'BSRE_BSSN'
                    : 'LOCAL_SEAL_ONLY',
                'tte_disclaimer' => ($signResult['status'] ?? '') === 'success'
                    ? null
                    : 'Dokumen ini disegel secara elektronik oleh sistem M-PAD. '
                      . 'Belum tersertifikasi BSrE BSSN. Keabsahan hukum penuh '
                      . 'menunggu aktivasi sertifikat elektronik BSrE.',
            ],
            'verification_url' => $this->verifyUrlFor($docNumber),
        ]);

        return $signedDoc;
    }

    /**
     * Khusus Aset BMD: Penandatanganan transisi dengan "Kode Numerik Resmi OPD"
     * Digunakan jika sertifikat TTE Kepala OPD masih dalam proses antrean BSrE
     */
    public function signAssetWithTransitionToken($documentable, string $opdCode, ?string $tenantIdentity = null, ?string $notes = null)
    {
        $docNumber = $documentable->bill_number ?? $documentable->number ?? ('AST-' . date('Ymd') . '-' . strtoupper(Str::random(6)));

        // Generate Secure Numeric Token OPD
        $tokenRecord = $this->generateAssetTransitionToken(
            opdCode: $opdCode,
            assetId: $documentable->asset_id ?? null,
            rentalId: $documentable->asset_rental_id ?? ($documentable instanceof \App\Models\AssetRental ? $documentable->id : null),
            billId: $documentable instanceof \App\Models\Bill ? $documentable->id : null,
            tenant: $tenantIdentity
        );

        $signerName = "Kepala {$opdCode} Kota Baubau (Otorisasi Terdaftar)";
        $signerNip = 'REG-OPD-' . $tokenRecord->token_code;
        $signerRole = "Pengguna Barang Milik Daerah ({$opdCode})";

        $hash = hash('sha256', $docNumber . $tokenRecord->token_code . now()->toIso8601String());
        $checksum = hash('sha256', $docNumber . $hash);

        $clause = "Disahkan oleh Kepala Dinas {$opdCode} Kota Baubau melalui Sistem Digital Terintegrasi M-PAD dengan Kode Registrasi Verifikasi OPD: {$tokenRecord->token_code}. Keabsahan dokumen dilindungi oleh Segel Elektronik Resmi Pemerintah Kota Baubau.";

        $signedDoc = SignedDocument::create([
            'document_type' => get_class($documentable),
            'document_id' => $documentable->id,
            'opd_id' => $documentable->opd_id ?? null,
            'document_number' => $docNumber,
            'file_path' => null,
            'signature_hash' => $hash,
            'sha256_checksum' => $checksum,
            'signed_by' => null,
            'signer_name' => $signerName,
            'signer_nip' => $signerNip,
            'signer_role' => $signerRole,
            'signature_type' => 'NUMERIC_TOKEN_TRANSITION',
            'signed_at' => now(),
            'status' => 'signed',
            'metadata' => [
                'token_code' => $tokenRecord->token_code,
                'opd_code' => $opdCode,
                'transitional_clause' => $clause,
                'tenant_identity' => $tenantIdentity,
                'notes' => $notes,
                'bsre_status' => 'institutional_seal_fallback',
                'tte_is_bsre_verified' => false,
                'tte_mode' => 'LOCAL_SEAL_ONLY',
                'tte_disclaimer' => 'Dokumen disahkan melalui kode registrasi OPD dan segel '
                    . 'elektronik lokal M-PAD. Belum ber-TTE BSrE BSSN.',
            ],
            'verification_url' => $this->verifyUrlFor($docNumber),
        ]);

        return $signedDoc;
    }

    /**
     * Generate unique numeric verification token for OPD Asset management
     */
    public function generateAssetTransitionToken(string $opdCode, ?int $assetId = null, ?int $rentalId = null, ?int $billId = null, ?string $tenant = null, int $validDays = 30): AssetToken
    {
        $yearMonth = date('Y-m');
        $randomHex = strtoupper(Str::random(8));
        $tokenCode = "{$opdCode}-AST-{$yearMonth}-{$randomHex}";

        return AssetToken::create([
            'token_code' => $tokenCode,
            'opd_code' => $opdCode,
            'asset_id' => $assetId,
            'asset_rental_id' => $rentalId,
            'bill_id' => $billId,
            'contract_number' => "KTR/{$opdCode}/" . date('Y') . '/' . strtoupper(Str::random(6)),
            'tenant_identity' => $tenant ?? 'Pemohon Terverifikasi',
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDays($validDays)->toDateString(),
            'is_active' => true,
            'metadata' => [
                'issued_at' => now()->toIso8601String(),
                'security_level' => 'SHA256_SEAL_PROTECTED',
            ],
        ]);
    }

    /**
     * REST API Client to BSrE BSSN with graceful fallback
     */

    /**
     * Bangun URL verifikasi publik dokumen.
     * PENTING: JANGAN pakai url() polos, karena APP_URL server ini
     * (api.sipanda.online) BUKAN domain publik yang dilihat warga.
     * Warga memindai QR -> harus mendarat di domain mpad.baubaukota.go.id.
     */
    protected function verifyUrlFor(string $docNumber): string
    {
        $base = config('services.bsre.verify_base_url')
            ?: env('BSRE_VERIFY_URL', 'https://mpad.baubaukota.go.id/verify');

        return rtrim($base, '/') . '/' . rawurlencode($docNumber);
    }
    protected function callBsreSignEndpoint(string $docNumber, string $signerNip, string $signatureType): array
    {
        $startTime = microtime(true);
        $endpoint = rtrim($this->bsreUrl, '/') . '/api/sign/pdf';

        $payload = [
            'document_number' => $docNumber,
            'nik' => $signerNip,
            'type' => $signatureType,
            'location' => 'Kota Baubau',
            'reason' => 'Pengesahan Bukti Lunas Pendapatan Asli Daerah M-PAD',
        ];

        try {
            // Attempt remote BSrE call
            $response = Http::timeout($this->bsreTimeout / 1000)
                ->withBasicAuth($this->bsreUser ?? '', $this->bsrePass ?? '')
                ->post($endpoint, $payload);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $body = $response->json();
                $this->logTte($docNumber, $endpoint, $payload, $response->status(), $body, $durationMs, 'success');

                return [
                    'status' => 'success',
                    'hash' => $body['id_dokumen'] ?? $body['hash'] ?? hash('sha256', $docNumber . now()->toIso8601String()),
                    'reference' => $body['id_dokumen'] ?? null,
                    'file_path' => $body['file_url'] ?? null,
                ];
            }

            // If BSrE returns non-200, log and use local seal fallback
            $this->logTte($docNumber, $endpoint, $payload, $response->status(), $response->json() ?? ['error' => $response->body()], $durationMs, 'fallback_local_seal');
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::warning("BSrE Connection Timeout/Offline for {$docNumber}: {$e->getMessage()}. Using local cryptographic seal.");
            $this->logTte($docNumber, $endpoint, $payload, 504, ['error' => $e->getMessage()], $durationMs, 'fallback_local_seal');
        }

        // Local fallback: Institutional cryptographic SHA-256 seal
        return [
            'status' => 'fallback_local_seal',
            'hash' => hash('sha256', "BSRE-SEAL-BAUBAU-{$docNumber}-" . now()->toIso8601String() . '-' . Str::random(12)),
            'reference' => 'LOCAL-SEAL-' . strtoupper(Str::random(10)),
            'file_path' => null,
        ];
    }

    /**
     * Record audit trail in tte_logs table
     */
    protected function logTte(string $docNumber, string $endpoint, array $payload, int $code, ?array $body, int $durationMs, string $status): void
    {
        try {
            TteLog::create([
                'document_number' => $docNumber,
                'endpoint' => $endpoint,
                'request_payload' => $payload,
                'response_code' => $code,
                'response_body' => $body,
                'duration_ms' => $durationMs,
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to write TteLog: " . $e->getMessage());
        }
    }

    /**
     * Verify a signature hash against the database
     */
    public function verifySignature(string $docNumber, string $hash): bool
    {
        $signedDoc = SignedDocument::where('document_number', $docNumber)
            ->where('signature_hash', $hash)
            ->where('status', 'signed')
            ->first();

        return (bool) $signedDoc;
    }
}
