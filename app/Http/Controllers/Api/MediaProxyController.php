<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class MediaProxyController extends Controller
{
    /**
     * Proxy eksternal media (seperti Cloudinary CDN) untuk menghindari
     * pemblokiran AdBlocker browser, mixed content, atau isu DNS lokal ISP.
     */
    public function proxy(Request $request)
    {
        $url = $request->query('url');

        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
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
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return response()->json(['error' => 'Host not allowed for media proxy'], Response::HTTP_FORBIDDEN);
        }

        try {
            $cacheKey = 'media_proxy_' . md5($url);
            
            // Caching media selama 1 hari untuk efisiensi bandwidth
            $cached = Cache::remember($cacheKey, 86400, function () use ($url) {
                $response = Http::timeout(15)
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

                return null;
            });

            if (!$cached) {
                return response()->json(['error' => 'Failed to fetch media from upstream'], Response::HTTP_BAD_GATEWAY);
            }

            return response(base64_decode($cached['body']))
                ->header('Content-Type', $cached['content_type'])
                ->header('Cache-Control', 'public, max-age=86400')
                ->header('Access-Control-Allow-Origin', '*');

        } catch (\Throwable $e) {
            return response()->json(['error' => 'Proxy error: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
