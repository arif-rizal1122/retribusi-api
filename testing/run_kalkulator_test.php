<?php
/**
 * Script Pengujian Otomatis Formula API Kalkulator (Tahap 7)
 * Dijalankan via CLI untuk menembak endpoint lokal /api/simulate-tax
 */

// Handle environment argument
$env = $argv[1] ?? 'local';
if ($env === 'dev') {
    $baseUrl = "https://api-dev.sipanda.online";
} elseif ($env === 'prod') {
    $baseUrl = "https://apimpad.baubaukota.go.id";
} else {
    $baseUrl = "http://localhost:8000";
}

$apiUrl = "$baseUrl/api/tax-formulas";
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$formulas = json_decode($response, true)['data'] ?? [];

$md = "# 🧮 Laporan Hasil Uji Otomatis API Kalkulator Pajak\n\n";
$md .= "**Waktu Eksekusi**: " . date('Y-m-d H:i:s') . "\n";
$md .= "Pengujian dieksekusi secara otomatis menembak server `Localhost:8000` via endpoint POST `/api/simulate-tax` untuk masing-masing klasifikasi.\n\n";

if(empty($formulas)) {
    $md .= "⚠️ **Error**: Tidak dapat mengambil daftar formula dari API. Pastikan server berjalan di port 8000.\n";
}

foreach ($formulas as $f) {
    if (empty($f['calculation_formula'])) continue;

    $md .= "### " . $f['name'] . " (`" . $f['code'] . "`)\n";
    $md .= "- **Formula Server**: `" . $f['calculation_formula'] . "`\n";
    
    // Build dummy variables based on schema
    $vars = [];
    $schema = is_string($f['form_schema']) ? json_decode($f['form_schema'], true) : $f['form_schema'];
    if (is_array($schema)) {
        foreach($schema as $field) {
            $key = $field['key'];
            if ($field['type'] === 'number') {
                if ($key === 'luas_tanah') $vars[$key] = 120;
                elseif ($key === 'luas_bangunan') $vars[$key] = 60;
                elseif ($key === 'volume') $vars[$key] = 50;
                elseif ($key === 'ukuran' || $key === 'luas_lantai') $vars[$key] = 100;
                elseif ($key === 'omzet' || $key === 'nilai_jual' || $key === 'nsr' || $key === 'tagihan_listrik' || $key === 'npop') $vars[$key] = 5000000;
                elseif ($key === 'npoptkp') $vars[$key] = 1000000;
                elseif ($key === 'njoptkp') $vars[$key] = 10000000;
                elseif ($key === 'harga_patokan' || $key === 'hda') $vars[$key] = 80000;
                elseif ($key === 'indeks_lokalitas' || $key === 'indeks_terintegrasi' || $key === 'indeks_bg') $vars[$key] = 1;
                elseif ($key === 'shst') $vars[$key] = 5560000;
                else $vars[$key] = 15000;
            } else {
                if (str_contains($key, 'kelas')) $vars[$key] = '080';
                else $vars[$key] = 'Testing Data';
            }
        }
    }
    
    // Execute POST /api/simulate-tax
    $postData = json_encode([
        'classification_id' => $f['id'],
        'variables' => $vars
    ]);
    
    $ch2 = curl_init("$baseUrl/api/simulate-tax");
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    $simRes = curl_exec($ch2);
    $httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    
    $simData = json_decode($simRes, true);
    
    $md .= "- **Dummy Set Variabel**: `" . json_encode($vars) . "`\n";
    if ($httpCode >= 200 && $httpCode < 300 && isset($simData['result'])) {
        $md .= "- **Status Pengujian**: ✅ **LULUS**\n";
        $md .= "- **Hasil Parsing Kalkulator**: **" . $simData['formatted'] . "**\n\n";
    } else {
        $md .= "- **Status Pengujian**: ❌ **GAGAL (HTTP $httpCode)**\n";
        $md .= "- **Pesan Error Server**: `" . htmlspecialchars(substr($simRes, 0, 150)) . "`\n\n";
    }
}

$savePath = __DIR__.'/results/07_Hasil_Kalkulator_Semua_Pajak.md';
if (!is_dir(dirname($savePath))) {
    mkdir(dirname($savePath), 0755, true);
}
file_put_contents($savePath, $md);
echo "Pengujian Kalkulator selesai. Hasil disimpan di testing/results/\n";
