<?php

namespace App\Services\Payment\Snap;

use Illuminate\Http\Request;

class SnapCanonicalRequest
{
    public function stringToSignFromRequest(Request $request): string
    {
        return $this->stringToSign(
            $request->method(),
            '/' . ltrim($request->path(), '/'),
            $this->canonicalBodyFromRaw($request->getContent()),
            (string) $request->header('X-TIMESTAMP')
        );
    }

    /**
     * Kumpulkan kandidat stringToSign untuk kompatibilitas lintas implementasi.
     *
     * BRI (dan beberapa bank) menghitung stringToSign dengan variasi:
     * - path: dengan /snap, tanpa /snap, dengan /api/snap, tanpa /api
     * - body: raw persis (spesifikasi SNAP) vs canonical re-encode
     * - access token: format SNAP B2B menyisipkan token dari header Authorization
     *   di antara path dan hash body (terbukti 05 Agu 2026 via debug-payload BRI)
     *
     * Kami coba SEMUA kombinasi agar signature BRI yang sah tetap diterima,
     * sambil tetap mewajibkan signature valid (tidak ada bypass keamanan).
     */
    public function stringToSignCandidates(Request $request): array
    {
        $method = strtoupper($request->method());
        $timestamp = (string) $request->header('X-TIMESTAMP');
        $rawBody = (string) $request->getContent();
        $canonicalBody = $this->canonicalBodyFromRaw($rawBody);
        $accessToken = $this->bearerToken((string) $request->header('Authorization', ''));

        $paths = $this->pathVariants('/' . ltrim($request->path(), '/'));

        $candidates = [];
        foreach ($paths as $path) {
            foreach ([$rawBody, $canonicalBody] as $body) {
                // Format dasar SNAP: METHOD:PATH:BODYHASH:TIMESTAMP
                $candidates[] = $this->stringToSign($method, $path, $body, $timestamp);

                // Format SNAP B2B BRI (terbukti 05 Agu 2026 via debug-payload):
                // METHOD:PATH:AccessToken:BODYHASH:TIMESTAMP — token dari header Authorization
                if ($accessToken !== '') {
                    $candidates[] = $this->stringToSignWithToken($method, $path, $body, $timestamp, $accessToken);
                }
            }
        }

        return array_values(array_unique($candidates));
    }

    private function bearerToken(string $authorizationHeader): string
    {
        if (preg_match('/^Bearer\s+(.+)$/i', trim($authorizationHeader), $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /**
     * stringToSign versi SNAP B2B: METHOD:PATH:AccessToken:BODYHASH:TIMESTAMP
     */
    public function stringToSignWithToken(string $method, string $path, string $canonicalBody, string $timestamp, string $accessToken): string
    {
        return strtoupper($method) . ':' . $path . ':' . $accessToken . ':' . strtolower(hash('sha256', $canonicalBody)) . ':' . $timestamp;
    }

    /**
     * Variasi path: asli, tanpa /api, tanpa /snap, tanpa /api/snap.
     * Contoh: /api/snap/v1.0/access-token/b2b
     *   -> /api/snap/v1.0/access-token/b2b
     *   -> /snap/v1.0/access-token/b2b
     *   -> /v1.0/access-token/b2b
     */
    private function pathVariants(string $path): array
    {
        $variants = [$path];

        // Iteratif: terus hapus prefix /api/ dan /snap sampai stabil.
        $current = $path;
        $changed = true;
        while ($changed) {
            $changed = false;
            if (str_starts_with($current, '/api/')) {
                $current = substr($current, 4);
                $changed = true;
            } elseif (str_starts_with($current, '/snap')) {
                $current = substr($current, 5);
                $changed = true;
            }
            if ($current !== $path) {
                $variants[] = $current;
            }
        }

        // Fallback manual: hapus /api/snap sekaligus (untuk path yang aneh)
        $apiSnap = strpos($path, '/api/snap');
        if ($apiSnap !== false) {
            $variants[] = '/snap' . substr($path, $apiSnap + 9);
        }

        return array_values(array_unique($variants));
    }

    public function stringToSign(string $method, string $path, string $canonicalBody, string $timestamp): string
    {
        return strtoupper($method) . ':' . $path . ':' . strtolower(hash('sha256', $canonicalBody)) . ':' . $timestamp;
    }

    public function canonicalBodyFromRaw(?string $body): string
    {
        if ($body === null || trim($body) === '') {
            return '';
        }

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $body;
        }

        return json_encode($decoded, JSON_UNESCAPED_SLASHES);
    }

    public function canonicalBodyFromArray(array $body): string
    {
        return json_encode($body, JSON_UNESCAPED_SLASHES);
    }
}
