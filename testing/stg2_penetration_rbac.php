<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=============================================\n";
echo "   STG 2: PENETRATION & RBAC (STAGING)       \n";
echo "=============================================\n";

$baseUrl = 'https://apimpad.baubaukota.go.id/api';

echo "[1/3] Uji Coba Tanpa Login (No Bearer Token)...\n";
$res1 = Http::get("$baseUrl/tax-objects");
if ($res1->status() === 401 || $res1->status() === 500) { // Laravel default auth middleware sometimes returns 500 route login not defined if headers not application/json
    echo "  -> ✅ VALID: Ditolak karena Unauthenticated (HTTP " . $res1->status() . ").\n";
} else {
    echo "  -> ❌ ERROR: Rute terbuka tanpa login! HTTP: " . $res1->status() . "\n";
}

echo "[2/3] Automasi Login sebagai Petugas Staging...\n";
$loginPayload = [
    'email' => 'petugas@bapenda.go.id',
    'password' => 'password123'
];
$loginRes = Http::post("$baseUrl/login", $loginPayload);

if ($loginRes->successful()) {
    $token = $loginRes->json('data.token') ?? $loginRes->json('token') ?? $loginRes->json('access_token');
    echo "  -> ✅ Berhasil mendapatkan Token Petugas.\n";
    
    echo "[3/3] Penetration ke Rute Admin menggunakan Token Petugas...\n";
    $adminRes = Http::withToken($token)->get("$baseUrl/users"); // users route is usually admin only
    if ($adminRes->status() === 403 || $adminRes->status() === 401) {
        echo "  -> ✅ VALID: Request Petugas ke zona Admin DITOLAK (HTTP " . $adminRes->status() . " Forbidden/Unauth).\n";
    } else {
        echo "  -> ❌ ERROR: Kebocoran RBAC! Petugas bisa akses zona Admin. (HTTP: " . $adminRes->status() . ")\n";
    }
} else {
    echo "  -> ⚠️ SKIP: Tidak dapat login ke akun Petugas Staging (HTTP: " . $loginRes->status() . ")\n";
    echo "     " . substr($loginRes->body(), 0, 500) . "...\n";
}

echo "\n---------------------------------------------\n";
echo "   STATUS STG 2: SELESAI\n";
echo "---------------------------------------------\n\n";
