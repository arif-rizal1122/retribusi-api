<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class MediaProxyController extends Controller
{
    /**
     * Proxy eksternal media (seperti Cloudinary CDN) untuk menghindari
     * pemblokiran AdBlocker browser, mixed content, atau isu DNS lokal ISP.
     *
     * Mendukung PDF Cloudinary: akun dapat mengaktifkan pembatasan
     * "Allow delivery of PDF and ZIP files" sehingga URL polos dibalas
     * 401 "deny or ACL failure". Untuk berkas seperti itu, tanda tangan
     * dilampirkan di sisi server (kredensial tidak pernah ke browser).
     */
    public function proxy(Request $request)
    {
        $url = $request->query('url');

        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Invalid or missing media URL'], Response::HTTP_BAD_REQUEST);
        }

        $parsed = parse_url($url);
        $host = strtolower($parsed['host'] ?? '');

        // Keamanan: Hanya izinkan domain media tepercaya
        $allowedHosts = [
            'res.cloudinary.com',
            'cloudinary.com',
            'images.unsplash.com',
            'tile.openstreetmap.org',
            'mt0.google.com',
            'mt1.google.com',
            'mt2.google.com',
            'mt3.google.com',
        ];

        $isAllowed = false;
        foreach ($allowedHosts as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                $isAllowed = true;
                break;
            }
        }

        if (! $isAllowed) {
            return response()->json(['error' => 'Host not allowed for media proxy'], Response::HTTP_FORBIDDEN);
        }

        try {
            $cacheKey = 'media_proxy_'.md5($url);

            // Caching media selama 1 hari untuk efisiensi bandwidth
            $cached = Cache::remember($cacheKey, 86400, function () use ($url) {
                $response = Http::timeout(20)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                    ])
                    ->get($url);

                if ($response->successful()) {
                    return [
                        'content_type' => $response->header('Content-Type') ?: 'image/jpeg',
                        'body' => base64_encode($response->body()),
                    ];
                }

                // Upstream menolak (mis. 401 Cloudinary karena pembatasan PDF).
                // Coba lagi memakai URL bertanda tangan dari sisi server.
                $signed = $this->tandaTanganCloudinary($url);
                if ($signed && $signed !== $url) {
                    $retry = Http::timeout(25)
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        ])
                        ->get($signed);

                    if ($retry->successful()) {
                        return [
                            'content_type' => $retry->header('Content-Type') ?: 'application/octet-stream',
                            'body' => base64_encode($retry->body()),
                        ];
                    }
                }

                return null;
            });

            if (! $cached) {
                return response()->json(['error' => 'Failed to fetch media from upstream'], Response::HTTP_BAD_GATEWAY);
            }

            return response(base64_decode($cached['body']))
                ->header('Content-Type', $cached['content_type'])
                ->header('Cache-Control', 'public, max-age=86400')
                ->header('Access-Control-Allow-Origin', '*');

        } catch (\Throwable $e) {
            return response()->json(['error' => 'Proxy error: '.$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Bangun URL Cloudinary bertanda tangan dari URL polos.
     *
     * Dipakai saat resource_type 'image' + format PDF (atau ZIP) ditolak
     * upstream. Contoh masukan:
     *   https://res.cloudinary.com/<cloud>/image/upload/v123/<folder>/<nama>.pdf
     *
     * Mengembalikan URL api.cloudinary.com/.../download?...&signature=...
     * yang berlaku 1 jam, atau null bila pola URL tidak dikenali.
     */
    private function tandaTanganCloudinary(string $url): ?string
    {
        try {
            $disk = config('filesystems.disks.cloudinary', []);
            $cloudName = $disk['cloud'] ?? null;

            if (! $cloudName) {
                return null;
            }

            $path = parse_url($url, PHP_URL_PATH) ?: '';
            $parts = array_values(array_filter(explode('/', $path)));

            // Harus berbentuk: <cloud>/<resource_type>/<type>/[vNNN]/<public_id>.<ext>
            if (count($parts) < 4) {
                return null;
            }

            $resourceType = $parts[1] ?? 'image';
            $type = $parts[2] ?? 'upload';

            // Buang segmen versi (v1234567) bila ada
            $sisa = array_slice($parts, 3);
            if (isset($sisa[0]) && preg_match('/^v\d+$/', $sisa[0])) {
                array_shift($sisa);
            }

            $berkas = implode('/', $sisa);
            if ($berkas === '') {
                return null;
            }

            $ext = pathinfo($berkas, PATHINFO_EXTENSION);
            $publicId = $ext !== ''
                ? substr($berkas, 0, -(strlen($ext) + 1))
                : $berkas;

            /** @var \Cloudinary\Cloudinary $cloudinary */
            $cloudinary = app(\Cloudinary\Cloudinary::class);

            return $cloudinary->uploadApi()->privateDownloadUrl($publicId, $ext, [
                'resource_type' => $resourceType,
                'type' => $type,
                'attachment' => false,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('MediaProxy: gagal menandatangani URL Cloudinary', [
                'url' => $url,
                'pesan' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
