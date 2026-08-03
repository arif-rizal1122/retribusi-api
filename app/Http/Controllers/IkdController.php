<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Fallback untuk tombol "Masuk dengan IKD" di mobile.
 *
 * Mobile (Login.tsx) redirect ke /api/auth/ikd/redirect.
 * Sebelum PKS Dukcapil (DUKCAPIL_BASE_URL) terisi, tombol ini
 * mengembalikan redirect ke halaman login mobile dengan pesan jelas,
 * bukan 404.
 */
class IkdController extends Controller
{
    public function redirect(Request $request)
    {
        $baseUrl = (string) config('services.dukcapil.base_url', '');
        $mobileUrl = (string) config('app.mobile_url', 'https://mpad.baubaukota.go.id');

        if ($baseUrl !== '') {
            // PKS sudah aktif — redirect ke halaman SSO Dukcapil
            $params = http_build_query([
                'redirect_uri' => $mobileUrl . '/ikd-callback',
                'client_id'    => (string) config('services.dukcapil.client_id', ''),
                'scope'        => 'openid profile',
                'response_type'=> 'code',
            ]);
            return redirect()->away($baseUrl . '/authorize?' . $params);
        }

        // PKS belum aktif — arahkan kembali ke login mobile dengan pesan
        return redirect()->away($mobileUrl . '/login?ikd_error=' . urlencode('Layanan IKD Dukcapil belum aktif. Silakan gunakan login NIK + nama ibu.'));
    }
}
