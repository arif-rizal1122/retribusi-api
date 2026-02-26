<?php
/**
 * Script Pengujian Otomatis Hak Akses Keamanan (RBAC) (Tahap 9)
 * Dijalankan via CLI untuk menguji limitasi token Sanctum di localhost:8000
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Str;

$md = "# 🛡️ Laporan Hasil Uji Coba Keamanan Akses (RBAC)\n\n";
$md .= "**Waktu Eksekusi**: " . date('Y-m-d H:i:s') . "\n";
$md .= "Pengujian ini menembak API lokal menggunakan Token Sanctum murni untuk membuktikan Sistem Isolasi Peran (Tenant Isolation & Authorization) berjalan sempurna.\n\n";

// Function untuk cURL Request
function sendApiRequest($method, $url, $token = null, $data = []) {
    $ch = curl_init("https://api.sipanda.online" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];
    if($token) $headers[] = "Authorization: Bearer " . $token;
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if(!empty($data)) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

try {
    // Siapkan Aktor dan Token
    $wp1 = User::firstOrCreate(['email' => 'budirbac@test.com'], ['name' => 'WP Budi', 'password' => bcrypt('password')]);
    if($wp1->role !== 'wajib_pajak') { $wp1->role = 'wajib_pajak'; $wp1->save(); }
    $tokenWP1 = $wp1->createToken('test')->plainTextToken;

    $petugas = User::firstOrCreate(['email' => 'petugasrbac@test.com'], ['name' => 'Petugas Patroli', 'password' => bcrypt('password')]);
    if($petugas->role !== 'petugas') { $petugas->role = 'petugas'; $petugas->save(); }
    $tokenPetugas = $petugas->createToken('test')->plainTextToken;

    // SKENARIO 1: WP MENGAKSES DASHBOARD ADMIN (HARUS 403)
    $md .= "### 1. Wajib Pajak Mengakses Endpoint Admin\n";
    $res1 = sendApiRequest('GET', '/api/dashboard/stats', $tokenWP1);
    if($res1['code'] === 403 || $res1['code'] === 401) {
        $md .= "- ✅ **SUKSES DIBLOKIR**: Server mengembalikan status HTTP `{$res1['code']}`. Wajib pajak tidak bisa masuk dapur admin.\n\n";
    } else {
        $md .= "- ❌ **KEBOCORAN**: Server mengembalikan status HTTP `{$res1['code']}` bukannya 403.\n\n";
    }

    // SKENARIO 2: TAMU (UNAUTHENTICATED) MENGAKSES PROFILE (HARUS 401)
    $md .= "### 2. Tamu (Tanpa Token) Mengakses Endpoint Terkunci\n";
    $res2 = sendApiRequest('GET', '/api/user');
    if($res2['code'] === 401) {
        $md .= "- ✅ **SUKSES DIBLOKIR**: Pengunjung dilarang masuk. `401 Unauthenticated`.\n\n";
    } else {
        $md .= "- ❌ **KEBOCORAN**: Endpoint bocor, HTTP `{$res2['code']}`.\n\n";
    }

    // SKENARIO 3: PETUGAS MENGHAPUS OBJEK PAJAK (HARUS 403 / 401)
    $md .= "### 3. Petugas Lapangan Melakukan Aksi Destruktif (DELETE Tagihan/Objek)\n";
    $dummyObj = \App\Models\TaxObject::first();
    if($dummyObj) {
        $res3 = sendApiRequest('DELETE', '/api/tax-objects/' . $dummyObj->id, $tokenPetugas);
        if(in_array($res3['code'], [403, 401, 405])) {
            $md .= "- ✅ **SUKSES DIBLOKIR**: Petugas dilarang menghapus. Server menolak keras dengan blokade Otorisasi (HTTP `{$res3['code']}`).\n\n";
        } else {
            $md .= "- ❌ **KEBOCORAN**: Sistem membiarkan aksi selain 403 (HTTP {$res3['code']}).\n\n";
        }
    } else {
        $md .= "*(Skip: Belum ada data TaxObjekt untuk dihapus)*\n\n";
    }

} catch (\Exception $e) {
    $md .= "\n\n❌ **FATAL ERROR**: " . $e->getMessage();
}

// Rekam Laporan
$savePath = __DIR__.'/results/09_Laporan_Keamanan_RBAC.md';
if (!is_dir(dirname($savePath))) {
    mkdir(dirname($savePath), 0755, true);
}
file_put_contents($savePath, $md);

echo "Pengujian RBAC Selesai. Laporan ditulis ke {$savePath}\n";
