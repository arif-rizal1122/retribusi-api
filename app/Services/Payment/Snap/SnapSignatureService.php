<?php

namespace App\Services\Payment\Snap;

use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use Illuminate\Http\Request;

class SnapSignatureService
{
    public function __construct(private readonly SnapCanonicalRequest $canonicalRequest)
    {
    }

    public function verify(Request $request): void
    {
        $signature = (string) $request->header('X-SIGNATURE');
        $stringToSign = $this->canonicalRequest->stringToSignFromRequest($request);

        if (!$this->verifyString($stringToSign, $signature)) {
            throw SnapValidationException::unauthorized('Unauthorized. Signature invalid.');
        }
    }

    public function verifyString(string $stringToSign, string $signature): bool
    {
        $publicKey = $this->publicKey();
        $decodedSignature = base64_decode($signature, true);

        if ($decodedSignature === false) {
            $decodedSignature = $signature;
        }

        return openssl_verify($stringToSign, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    private function publicKey(): string
    {
        $inlineKey = (string) config('snap.security.bank_public_key', '');
        if ($inlineKey !== '') {
            return str_replace('\n', "\n", $inlineKey);
        }

        $path = (string) config('snap.security.bank_public_key_path', '');
        if ($path !== '' && is_readable($path)) {
            return (string) file_get_contents($path);
        }

        throw SnapValidationException::unauthorized('Unauthorized. SNAP public key is not configured.');
    }
}
