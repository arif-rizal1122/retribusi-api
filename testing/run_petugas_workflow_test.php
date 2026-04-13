<?php
/**
 * Script Pengujian Workflow Petugas (Non-Browser) - Expanded
 * Mensimulasikan aplikasi `retribusi-petugas` menembak backend secara lengkap.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Opd;
use App\Models\RetributionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

function runApiReq($method, $uri, $user = null, $data = []) {
    Auth::guard('sanctum')->forgetUser();
    $request = Request::create($uri, $method, $data);
    $request->headers->set('Accept', 'application/json');
    if ($user) {
        $request->setUserResolver(fn() => $user);
        Auth::guard('sanctum')->setUser($user);
    }
    $response = app()->handle($request);
    return ['code' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true)];
}

$report = "=== 👮 Laporan Simulasi Lengkap Petugas Lapangan (E2E API) ===\n\n";
$report .= "**Waktu Eksekusi**: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $rtype = RetributionType::first();
    $opd = Opd::find($rtype->opd_id);
    
    $password = 'password123';
    $petugas1 = User::updateOrCreate(
        ['email' => 'petugas1@test.com'],
        ['name' => 'Petugas Satu', 'password' => bcrypt($password), 'role' => 'petugas', 'opd_id' => $opd->id, 'status' => 'active']
    );
    // Ensure the OPD is approved for login check
    if ($opd && $opd->status !== 'approved') {
        $opd->status = 'approved';
        $opd->save();
    }
    
    $petugas2 = User::updateOrCreate(
        ['email' => 'petugas2@test.com'],
        ['name' => 'Petugas Dua', 'password' => bcrypt($password), 'role' => 'petugas', 'opd_id' => $opd->id, 'status' => 'active']
    );

    echo "[INFO] Menjalankan Skenario 0: Login Petugas...\n";
    $report .= "## Skenario 0: Autentikasi (Login Petugas)\n";
    $resLogin = runApiReq('POST', '/api/login', null, ['email' => $petugas1->email, 'password' => $password]);
    if ($resLogin['code'] === 200 && isset($resLogin['body']['token'])) {
        $report .= "✅ SUKSES: Login berhasil. Token diterbitkan: " . substr($resLogin['body']['token'], 0, 10) . "...\n";
    } else {
        $report .= "❌ GAGAL: Login gagal. Code: " . $resLogin['code'] . " " . json_encode($resLogin['body']) . "\n";
    }

    echo "[INFO] Menjalankan Skenario 1: Dashboard Stats...\n";
    $report .= "\n## Skenario 1: Dashboard Stats (Cek Achievement)\n";
    $resStats = runApiReq('GET', '/api/dashboard/stats', $petugas1);
    if ($resStats['code'] === 200 && isset($resStats['body']['petugas_achievement'])) {
        $report .= "✅ SUKSES: Stats terambil. Achievement petugas terdeteksi.\n";
    } else {
        $report .= "❌ GAGAL: Tidak bisa mengambil stats. Code: " . $resStats['code'] . "\n";
    }

    echo "[INFO] Menjalankan Skenario 2: Create Wajib Pajak...\n";
    $report .= "\n## Skenario 2: Pendaftaran Wajib Pajak Baru di Lapangan\n";
    $payloadCreate = [
        'nik' => '1234567890123' . rand(100, 999), 
        'name' => 'Wajib Pajak Baru (Simulasi Petugas)',
        'address' => 'Jl. Lapangan Testing No ' . rand(1, 100),
        'retribution_type_ids' => [$rtype->id],
        'object_name' => 'Objek Pajak Baru',
        'latitude' => -5.46,
        'longitude' => 122.60
    ];
    $res1 = runApiReq('POST', '/api/taxpayers', $petugas1, $payloadCreate);
    if ($res1['code'] === 201) {
        $report .= "✅ SUKSES: Petugas berhasil mendaftarkan Wajib Pajak & Objek Pajak baru.\n";
        $newTpId = $res1['body']['data']['id'];
    } else {
        $report .= "❌ GAGAL: Gagal mendaftarkan Wajib Pajak. Return: " . $res1['code'] . " " . json_encode($res1['body']) . "\n";
        $newTpId = null;
    }

    echo "[INFO] Menjalankan Skenario 3: List & Detail WP (Isolasi Data)...\n";
    $report .= "\n## Skenario 3: Eksplorasi & Isolasi Data\n";
    $resList1 = runApiReq('GET', '/api/taxpayers', $petugas1);
    if ($resList1['code'] === 200) {
        $found = false;
        foreach ($resList1['body']['data'] as $tp) {
            if ($tp['name'] === 'Wajib Pajak Baru (Simulasi Petugas)') $found = true;
        }
        if ($found) {
            $report .= "✅ SUKSES: Petugas 1 melihat datanya sendiri (Fix Logika Berhasil).\n";
        } else {
            $report .= "❌ GAGAL: Petugas 1 TIDAK BISA melihat data yang baru ia daftarkan.\n";
        }
    }
    // Petugas 2 isolation check
    $resList2 = runApiReq('GET', '/api/taxpayers', $petugas2);
    $found2 = false;
    if ($resList2['code'] === 200) {
        foreach ($resList2['body']['data'] as $tp) {
            if ($tp['name'] === 'Wajib Pajak Baru (Simulasi Petugas)') $found2 = true;
        }
    }
    if (!$found2) {
        $report .= "✅ SUKSES: Petugas 2 tidak bisa melihat data Petugas 1 (Isolasi Terjaga).\n";
    } else {
        $report .= "❌ KEBOCORAN: Petugas 2 bisa mengintip data Petugas 1.\n";
    }

    echo "[INFO] Menjalankan Skenario 4: Cek Tagihan (Bills)...\n";
    $report .= "\n## Skenario 4: Cek Tagihan (Billing Verification)\n";
    $resBills = runApiReq('GET', '/api/bills', $petugas1);
    if ($resBills['code'] === 200) {
         $report .= "✅ SUKSES: Akses rute Tagihan berhasil. Ditemukan " . count($resBills['body']['data']) . " tagihan terpantau.\n";
    } else {
         $report .= "❌ GAGAL: Gagal mengakses rute Tagihan. Code: {$resBills['code']}\n";
    }

    echo "[INFO] Menjalankan Skenario 5: Cek Peta Geospasial...\n";
    $report .= "\n## Skenario 5: Cek Peta (Map Potentials)\n";
    $resMap = runApiReq('GET', '/api/dashboard/map-potentials', $petugas1);
    if ($resMap['code'] === 200 && is_array($resMap['body'])) {
         $countMap = count($resMap['body']);
         $report .= "✅ SUKSES: Peta terisi. " . $countMap . " titik koordinat objek pajak terdeteksi.\n";
    } else {
         $report .= "❌ GAGAL: Gagal memuat data peta. Code: " . $resMap['code'] . "\n";
    }

    echo "[INFO] Menjalankan Skenario 6: Modul Tugas (Tasks)...\n";
    $report .= "\n## Skenario 6: Daftar Tugas Lapangan\n";
    $resTasks = runApiReq('GET', '/api/petugas-tasks', $petugas1);
    if ($resTasks['code'] === 200) {
         $report .= "✅ SUKSES: Modul penugasan aktif.\n";
    } else {
         $report .= "⚠️ PERINGATAN: Modul penugasan mengembalikan kode " . $resTasks['code'] . "\n";
    }

} catch (\Exception $e) {
    $report .= "\n\n❌ FATAL EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}

echo "[INFO] Selesai. Menulis laporan ke results/12_Laporan_Aksi_Petugas.md\n";
file_put_contents(__DIR__.'/results/12_Laporan_Aksi_Petugas.md', $report);
