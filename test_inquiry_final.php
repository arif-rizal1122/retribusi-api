<?php
require 'vendor/autoload.php';
use Illuminate\Support\Facades\Http;
$app = new \Illuminate\Container\Container();
\Illuminate\Support\Facades\Facade::setFacadeApplication($app);
$app->singleton('http', function() { return new \Illuminate\Http\Client\Factory(); });
$app->singleton('cache', function() { return new \Illuminate\Cache\CacheManager(app()); }); // Simplified for script
$app->singleton('log', function() { return new \Illuminate\Log\LogManager(app()); });

$base_url = 'http://103.182.72.241:8000/pospbb/Api_service';
$u = 'mpad';
$p = 'mpad2026';

echo "1. Testing Login...\n";
$loginResp = Http::asForm()->post("$base_url/login", ['USERNAME' => $u, 'PASSWORD' => $p]);
echo "Status: " . $loginResp->status() . " Body: " . $loginResp->body() . "\n";

$data = $loginResp->json();
if (!$loginResp->successful() || ($data['status'] ?? 0) !== 200) {
    echo "❌ Login failed.\n"; exit;
}
$token = $data['token'];

echo "\n2. Testing Inquiry (NOP 747271000100100290, Tahun 2026)...\n";
$inqResp = Http::asForm()->withToken($token)->post("$base_url/inquiry", [
    'NOP' => '747271000100100290',
    'TAHUN' => '2026'
]);
echo "Status: " . $inqResp->status() . " Body: " . $inqResp->body() . "\n";

$inqData = $inqResp->json();
if ($inqResp->successful() && ($inqData['status'] ?? 0) === 200) {
    echo "✅ SUCCESS: " . ($inqData['data']['nama_wp'] ?? 'N/A') . "\n";
} else {
    echo "❌ Inquiry failed.\n";
}
