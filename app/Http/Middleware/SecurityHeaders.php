<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('X-Mpad-Security', 'Active');

        // Cross-Origin-Opener-Policy: 'same-origin-allow-popups' WAJIB untuk
        // alur Google Sign-In berbasis popup. Dengan COOP default, peramban
        // memblokir window.close() dari popup OAuth sehingga muncul peringatan
        // "Cross-Origin-Opener-Policy policy would block the window.closed call".
        // Nilai ini adalah anjuran resmi Google: popup tetap boleh berinteraksi,
        // namun halaman lain tetap terisolasi.
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; frame-src 'none'; object-src 'none'");

        return $response;
    }
}
