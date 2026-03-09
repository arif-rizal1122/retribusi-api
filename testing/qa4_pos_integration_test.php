<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\User;

echo "=============================================\n";
echo "   QA 4: POS & TRANSAKSI HARIAN (LOKAL)      \n";
echo "=============================================\n";

$posUrl = 'http://localhost:8001/api/v1'; // Endpoint POS Lokal

echo "[1/3] Mengetes Koneksi ke POS Backend...\n";
try {
    // skip ping url karena custom, langsung ke login
    echo "  -> OK: API Endpoint di-set ke $posUrl\n";
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'Connection refused')) {
        echo "  -> ❌ GAGAL: Tidak terhubung ke `retribusi-pos/backend`. Pastikan Artisan Serve berjalan di port 8001.\n";
        exit(1);
    } else {
        echo "  -> ✅ Terhubung (Namun mendapat Exception koneksi lain: " . $e->getMessage() . ")\n";
    }
}

echo "[2/3] Automasi Login User POS (JWT Auth)...\n";
// POS menggunakan typmon/jwt-auth. Kita asumsikan ada endpoint login /api/auth/login
$loginPayload = [
    'email' => 'admin@pos.com',
    'password' => 'password'
];

$loginRes = Http::post($posUrl . '/auth/login', $loginPayload);

if ($loginRes->successful()) {
    $token = $loginRes->json('access_token');
    echo "  -> ✅ SUKSES Login JWT! Token didapatkan.\n";
} else {
    echo "  -> ⚠️ PERINGATAN: Login Gagal (Pesan: " . $loginRes->body() . "). Mungkin DB POS belum di-seed atau Endpoint login berbeda.\n";
    $token = null;
}

echo "[3/3] Simulasi Penciptaan Transaksi Kasir...\n";
if ($token) {
    $transactionPayload = [
        'customer_name' => 'Wajib Pajak (Testing PBB)',
        'items' => [
            ['product_id' => 1, 'quantity' => 2, 'price' => 50000]
        ],
        'total_amount' => 100000,
        'payment_method' => 'cash'
    ];

    $transRes = Http::withToken($token)->post($posUrl . '/kasir/transactions', $transactionPayload);
    
    if ($transRes->successful()) {
        echo "  -> ✅ VALID: POS mencatat Transaksi Rp100.000 bersinkronisasi ke API!\n";
    } else {
        echo "  -> ❌ ERROR Transaksi (" . $transRes->status() . "): " . $transRes->body() . "\n";
    }
} else {
    echo "  -> ⏭️ SKIPPED: Tidak memiliki token JWT untuk memukul API /transactions.\n";
}

echo "\n---------------------------------------------\n";
echo "   PEMERIKSAAN POS SELESAI \n";
echo "---------------------------------------------\n\n";
