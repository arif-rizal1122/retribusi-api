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

        $paths = $this->pathVariants('/' . ltrim($request->path(), '/'));

        // BRI inquiry/payment format: METHOD:path:<accessToken>:<sha256(body)>:<timestamp>
        $authHeader = (string) $request->header('Authorization', '');
        $accessToken = preg_replace('/^Bearer\s+/i', '', $authHeader);

        $candidates = [];
        foreach ($paths as $path) {
            foreach ([$rawBody, $canonicalBody] as $body) {
                $candidates[] = $this->stringToSign($method, $path, $body, $timestamp);
                if ($accessToken !== '' && $accessToken !== $authHeader) {
                    // Format BRI: sisipkan access token antara path dan hash body
                    $candidates[] = $this->stringToSignWithToken($method, $path, $accessToken, $body, $timestamp);
                }
            }
        }

        return array_values(array_unique($candidates));
    }

    public function stringToSignWithToken(string $method, string $path, string $accessToken, string $canonicalBody, string $timestamp): string
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
