<?php
echo "=============================================\n";
echo "   STG 1: HEALTH CHECK EKOSISTEM STAGING     \n";
echo "=============================================\n";

$domains = [
    'API Backend' => 'https://api.sipanda.online/up',
    'Dashboard Admin' => 'https://admin.sipanda.online',
    'Aplikasi Petugas' => 'https://petugas.sipanda.online',
    // Cek juga API basic tanpa /up untuk amannya
    'API Root' => 'https://api.sipanda.online/api/v1/auth/me' 
];

$allPassed = true;

foreach ($domains as $name => $url) {
    echo "Memeriksa [$name] di URL: $url\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // Ignore SSL errors if any (stg envs sometimes self-sign)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 405) { // 401/404 is fine for APIs, means it's responding
        echo "  -> ✅ ONLINE (HTTP $httpCode)\n";
    } else {
        echo "  -> ❌ OFFLINE / GAGAL (HTTP $httpCode) | Pesan Curl: $error\n";
        $allPassed = false;
    }
}

echo "\n---------------------------------------------\n";
echo "   STATUS STG 1: " . ($allPassed ? "LULUS (PASSED)" : "PERLU PERHATIAN (FAILED)") . "\n";
echo "---------------------------------------------\n\n";
