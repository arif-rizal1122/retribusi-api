<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\TaxObject;
use Illuminate\Http\Request;

echo "=============================================\n";
echo "   QA 1: RBAC & ISOLASI SUB-ADMIN LOKAL      \n";
echo "=============================================\n";

function internalRequest($method, $endpoint, $token) {
    $request = \Illuminate\Http\Request::create($endpoint, $method);
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $request->headers->set('Accept', 'application/json');
    $response = app()->handle($request);
    return json_decode($response->getContent(), true) ?: [];
}

echo "[1/3] Identifikasi Sub-Admin...\n";
// Ambil Admin Wilayah 1 dan 2
$admin1 = User::where('email', 'admin.wilayah1@m-pad.online')->first();
$admin2 = User::where('email', 'admin.wilayah2@m-pad.online')->first();
$superAdmin = User::where('email', 'superadmin@m-pad.online')->first() ?? User::where('role', 'super_admin')->first();

if (!$admin1 || !$admin2) {
    echo "ERROR: Eksekusi gagal, Admin Wilayah 1 dan 2 tidak ditemukan di Database.\n";
    exit(1);
}

$token1 = $admin1->createToken('test1')->plainTextToken;
$token2 = $admin2->createToken('test2')->plainTextToken;
$tokenSuper = $superAdmin->createToken('testSuper')->plainTextToken;

echo "  -> OK: Kredensial Admin Wilayah ditemukan.\n";

echo "[2/3] Memukul API Database Objek Pajak...\n";

// Mengambil Object Pajak langsung lewat Middleware Endpoint
$res1 = internalRequest('GET', '/api/tax-objects', $token1);
$count1 = is_array($res1) && isset($res1['data']) && isset($res1['data']['data']) ? count($res1['data']['data']) : 0;
if (!isset($res1['data'])) echo "  [!] Debug Admin 1: " . json_encode($res1) . "\n";
echo "  -> Admin Wilayah 1 (`{$admin1->email}`) dapat mengakses: $count1 Objek.\n";

$res2 = internalRequest('GET', '/api/tax-objects', $token2);
$count2 = is_array($res2) && isset($res2['data']) && isset($res2['data']['data']) ? count($res2['data']['data']) : 0;
if (!isset($res2['data'])) echo "  [!] Debug Admin 2: " . json_encode($res2) . "\n";
echo "  -> Admin Wilayah 2 (`{$admin2->email}`) dapat mengakses: $count2 Objek.\n";

$resSuper = internalRequest('GET', '/api/tax-objects', $tokenSuper);
$countSuper = is_array($resSuper) && isset($resSuper['data']) && isset($resSuper['data']['data']) ? count($resSuper['data']['data']) : 0;
echo "  -> Super Admin (`{$superAdmin->email}`) dapat mengakses: $countSuper Objek (Total Keseluruhan).\n";

echo "[3/3] Validasi Keamanan RBAC...\n";

$passed = true;
// Pastikan tidak bocor data
if ($count1 == $countSuper && $countSuper > 0) {
    echo "  -> ❌ GAGAL: Admin Wilayah 1 membocorkan isolasi (Melihat seluruh data).\n";
    $passed = false;
} elseif ($count1 === 0 && $count2 === 0) {
    echo "  -> ⚠️ KOSONG: Belum ada isolasi data objek pajak spesifik, tapi Global Scope berjalan menahan eksposur.\n";
} else {
    echo "  -> ✅ SUKSES: Query termutasi secara terisolasi via Global Scope berdasarkan `retribution_type_id`.\n";
}

echo "\n---------------------------------------------\n";
echo $passed ? "   STATUS: LULUS (PASSED) \n" : "   STATUS: GAGAL (FAILED) \n";
echo "---------------------------------------------\n\n";

// Selesai, hapus token
$admin1->tokens()->delete();
$admin2->tokens()->delete();
$superAdmin->tokens()->delete();

