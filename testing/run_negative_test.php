<?php
/**
 * Script Pengujian Otomatis Bounding/Validation Error (Tahap 10)
 * Menguji sistem menolak request sampah / salah logika.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Bill;
use App\Models\Payment;
// Handle environment argument
$env = $argv[1] ?? 'local';
if ($env === 'dev') {
    $baseUrl = "https://api-dev.sipanda.online";
} elseif ($env === 'prod') {
    $baseUrl = "https://apimpad.baubaukota.go.id";
} else {
    $baseUrl = "http://localhost:8000";
}

$md = "# 🛑 Laporan Hasil Uji Coba Input Invalid (Negative Testing)\n\n";
$md .= "**Waktu Eksekusi**: " . date('Y-m-d H:i:s') . "\n";
$md .= "Pengujian ini sengaja merusak input API untuk memastikan Controller menolak transaksi berakibat fatal ke Database.\n\n";

function sendApi($method, $url, $baseUrl, $token, $data) {
    $ch = curl_init($baseUrl . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json', "Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

try {
    $admin = User::firstOrCreate(['email' => 'admin.negatif@test.com'], ['name' => 'Admin Negatif', 'password' => bcrypt('password')]);
    if($admin->role !== 'super_admin') { $admin->role = 'super_admin'; $admin->save(); }
    $tokenAdmin = $admin->createToken('test')->plainTextToken;

    $petugas = User::firstOrCreate(['email' => 'petugas.negatif@test.com'], ['name' => 'Petugas Negatif', 'password' => bcrypt('password')]);
    if($petugas->role !== 'petugas') { $petugas->role = 'petugas'; $petugas->save(); }
    $tokenPetugas = $petugas->createToken('test')->plainTextToken;

    // SKENARIO 1: PEMBAYARAN KOSONG/MINUS (HARUS 422)
    $md .= "### 1. Injeksi Pembayaran Negatif (Rp -5.000.000)\n";
    // Cari atau buat sembarang bill unpaid dummy
    $dummyBill = Bill::where('status', 'unpaid')->first() ?? Bill::where('status', 'pending')->first();
    // In production, we assume some bills exist or we create one manually if needed without factory
    
    if ($dummyBill) {
        $res1 = sendApi('POST', '/api/payments', $baseUrl, $tokenPetugas, [
            'bill_id' => $dummyBill->id,
            'amount' => -5000000,
            'payment_method' => 'cash',
            'taxpayer_id' => $dummyBill->taxpayer_id,
            'tax_object_id' => $dummyBill->tax_object_id,
            'billing_period' => '2026'
        ]);
        if($res1['code'] === 422) {
            $md .= "- ✅ **SUKSES DITOLAK**: Laravel Form Request mendeteksi nilai tidak valid, HTTP `422 Unprocessable Entity`.\n\n";
        } else {
             $md .= "- ❌ **BUG**: Nominal Minus lolos! HTTP `{$res1['code']}`.\n\n";
        }
    }

    // SKENARIO 2: MEMBAYAR TAGIHAN YANG SUDAH LUNAS (HARUS DITOLAK LOGIKA 400 ATAU 422)
    $md .= "### 2. Double Payment / Membayar Ulang SKPD Lunas\n";
    $paidBill = Bill::where('status', 'paid')->first() ?? Bill::where('status', 'lunas')->first();

    if ($paidBill) {
        $res2 = sendApi('POST', '/api/payments', $baseUrl, $tokenPetugas, [
            'bill_id' => $paidBill->id,
            'amount' => $paidBill->amount,
            'payment_method' => 'cash',
            'taxpayer_id' => $paidBill->taxpayer_id,
            'tax_object_id' => $paidBill->tax_object_id,
            'billing_period' => '2026'
        ]);
        if(in_array($res2['code'], [400, 403, 422])) {
            $md .= "- ✅ **SUKSES DITOLAK**: Sistem menolak pembayaran ganda/ilegal (HTTP `{$res2['code']}`).\n\n";
        } else {
             $md .= "- ❌ **BUG BAHAYA**: Dobel Payment Lolos! Uang masuk tercatat ganda. HTTP `{$res2['code']}`.\n\n";
        }
    }

    // SKENARIO 3: BIKIN USER DENGAN PASSWORD KOSONG (HARUS 422)
    $md .= "### 3. Payload Bolong (Required Validation)\n";
    $res3 = sendApi('POST', '/api/users', $baseUrl, $tokenAdmin, [
        'name' => 'Si Bolong',
        'email' => 'bolong@test.com'
        // Password sengaja tidak dikirim
    ]);
    if($res3['code'] === 422) {
        $md .= "- ✅ **SUKSES DITOLAK**: Framework membentengi Database, menolak Insert data cacat. HTTP `422 Unprocessable Entity` atas hilangnya parameter fundamental.\n\n";
    } else {
         $md .= "- ❌ **BUG**: Data kurang field lolos ke Controller logic (HTTP `{$res3['code']}`).\n\n";
    }

} catch (\Exception $e) {
    $md .= "\n\n❌ **FATAL ERROR**: " . $e->getMessage();
}

$savePath = __DIR__.'/results/10_Laporan_Validasi_Negatif.md';
if (!is_dir(dirname($savePath))) {
    mkdir(dirname($savePath), 0755, true);
}
file_put_contents($savePath, $md);

echo "Pengujian Validasi Data Selesai. Laporan ditulis ke {$savePath}\n";
