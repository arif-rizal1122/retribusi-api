<?php

namespace App\Services\Payment\Snap;

use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SnapSignatureService
{
    public function __construct(
        private readonly SnapCanonicalRequest $canonicalRequest,
        private readonly ?SnapRequestCapture $capture = null
    ) {}

    public function verify(Request $request, string $serviceCode = '24'): void
    {
        $signature = (string) $request->header('X-SIGNATURE');
        $bankCode = $request->attributes->get('snap_bank_code');

        // Capture request BRI untuk analisis pola (hanya yang ada X-SIGNATURE)
        if ($signature !== '') {
            $this->capture?->capture($request, 'verify');
        }

        if (! $bankCode) {
            Log::warning('SNAP_SIG_DIAG: no bank_code', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'client_key' => $request->header('X-CLIENT-KEY'),
                'timestamp' => $request->header('X-TIMESTAMP'),
                'signature_len' => strlen($signature),
            ]);
            throw SnapValidationException::unauthorized('Unauthorized Signature', $serviceCode);
        }

        // Kumpulkan SEMUA kandidat stringToSign:
        // 1. Standar SNAP: method:path:sha256(body):timestamp (path/body variants)
        // 2. Format RSA BRI/BTN: clientKey|timestamp (tanpa body) — terbukti dipakai BRI 05 Agu 2026
        $candidates = $this->canonicalRequest->stringToSignCandidates($request);
        $clientKey = (string) $request->header('X-CLIENT-KEY');
        $timestamp = (string) $request->header('X-TIMESTAMP');
        $timestampVariants = $this->timestampVariants($timestamp);

        foreach ($timestampVariants as $t) {
            $candidates[] = $clientKey . '|' . $t;
            $candidates[] = strtoupper($request->method()) . ':' . $clientKey . ':' . $t;
        }

        // Coba verifikasi dengan SEMUA algoritma yang dikonfigurasi + RSA fallback.
        // Alur: coba hmac_sha512 dulu (config), kalau gagal coba rsa_sha256 dengan public key bank.
        foreach ($candidates as $stringToSign) {
            if ($this->verifyString($stringToSign, $signature, $bankCode, $serviceCode)) {
                return;
            }
            // Fallback: coba RSA kalau config utama bukan RSA
            if ($this->verifyRsa($stringToSign, $signature, $bankCode, $serviceCode)) {
                return;
            }
        }

        // DIAGNOSTIK: catat semua kandidat + expected signature supaya ketahuan mismatch BRI
        $this->capture?->capture($request, 'mismatch');
        Log::warning('SNAP_SIG_DIAG: signature mismatch', [
            'ip' => $request->ip(),
            'bank' => $bankCode,
            'method' => $request->method(),
            'path' => '/' . ltrim($request->path(), '/'),
            'client_key' => $clientKey,
            'timestamp' => $timestamp,
            'signature_received' => substr($signature, 0, 40) . '...',
            'signature_len' => strlen($signature),
            'raw_body' => substr((string) $request->getContent(), 0, 200),
            'candidates' => array_map(function ($s) use ($request) {
                return substr($s, 0, 60) . '... => ' . $this->expectedFor($s, $request->attributes->get('snap_bank_code'));
            }, array_slice($candidates, 0, 8)),
        ]);

        throw SnapValidationException::unauthorized('Unauthorized Signature', $serviceCode);
    }

    public function verifyString(string $stringToSign, string $signature, string $bankCode, string $serviceCode = '24'): bool
    {
        $algorithm = strtolower((string) config("snap.partners.{$bankCode}.signature_algorithm", 'rsa_sha256'));

        if ($algorithm === 'hmac_sha512') {
            $secret = (string) config("snap.partners.{$bankCode}.signature_secret", '');
            if ($secret === '') {
                return false;
            }

            $expectedSignature = base64_encode(hash_hmac('sha512', $stringToSign, $secret, true));

            return hash_equals($expectedSignature, $signature);
        }

        return $this->verifyRsa($stringToSign, $signature, $bankCode, $serviceCode);
    }

    public function verifyRsa(string $stringToSign, string $signature, string $bankCode, string $serviceCode = '24'): bool
    {
        try {
            $publicKey = $this->publicKey($bankCode, $serviceCode);
            $decodedSignature = base64_decode($signature, true);

            if ($decodedSignature === false) {
                $decodedSignature = $signature;
            }

            return openssl_verify($stringToSign, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
        } catch (SnapValidationException $e) {
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function timestampVariants(string $timestamp): array
    {
        $out = [$timestamp];
        // Z -> +00:00 / +07:00 / +08:00
        if (str_ends_with($timestamp, 'Z')) {
            $base = substr($timestamp, 0, -1);
            $out[] = $base . '+00:00';
            $out[] = $base . '.000Z';
            $out[] = $base . '.000+00:00';
        }
        // +07:00 -> Z (konversi UTC) + varian ms
        if (preg_match('/^(.+?)([+-]\d{2}:\d{2})$/', $timestamp, $m)) {
            $base = $m[1];
            $off = $m[2];
            $out[] = $base . '.000' . $off;
            $out[] = $base . 'Z';
            $out[] = $base . '.000Z';
            // UTC conversion
            try {
                $dt = \DateTime::createFromFormat('Y-m-d\TH:i:s', $base, new \DateTimeZone('UTC'));
                if ($dt) {
                    $hours = (int) substr($off, 1, 2);
                    $minutes = (int) substr($off, 4, 2);
                    $utc = clone $dt;
                    if ($off[0] === '+') {
                        $utc->sub(new \DateInterval("PT{$hours}H{$minutes}M"));
                    } else {
                        $utc->add(new \DateInterval("PT{$hours}H{$minutes}M"));
                    }
                    $out[] = $utc->format('Y-m-d\TH:i:s') . 'Z';
                    $out[] = $utc->format('Y-m-d\TH:i:s') . '.000Z';
                    $out[] = $utc->format('Y-m-d\TH:i:s') . '+00:00';
                }
            } catch (\Throwable $e) {
                // abaikan
            }
        }
        return array_values(array_unique($out));
    }

    private function expectedFor(string $stringToSign, string $bankCode): string
    {
        try {
            $algorithm = strtolower((string) config("snap.partners.{$bankCode}.signature_algorithm", 'rsa_sha256'));
            if ($algorithm === 'hmac_sha512') {
                $secret = (string) config("snap.partners.{$bankCode}.signature_secret", '');
                return substr(base64_encode(hash_hmac('sha512', $stringToSign, $secret, true)), 0, 40) . '...';
            }
            return 'rsa';
        } catch (\Throwable $e) {
            return 'ERR:' . $e->getMessage();
        }
    }

    private function publicKey(string $bankCode, string $serviceCode): string
    {
        $inlineKey = (string) config("snap.partners.{$bankCode}.public_key", '');
        if ($inlineKey !== '') {
            return str_replace('\\n', "\n", $inlineKey);
        }

        $path = (string) config("snap.partners.{$bankCode}.public_key_path", '');
        if ($path !== '' && is_readable($path)) {
            return (string) file_get_contents($path);
        }

        throw SnapValidationException::unauthorized('Unauthorized Signature', $serviceCode);
    }
}
