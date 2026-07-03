<?php

// Test Uji Petik (Spot Checks) Endpoint - No Screenshot Scheme

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

echo "=============================================\n";
echo "   NOSS TESTING: Modul Uji Petik (Perwali 58)\n";
echo "=============================================\n";

$baseUrl = config('app.url', 'http://retribusi-api.test');
if (!str_contains($baseUrl, 'http')) {
    $baseUrl = 'http://retribusi-api.test';
}

echo "[1/4] Mengautentikasi Super Admin...\n";
$superAdmin = User::where('role', 'super_admin')->first();
if (!$superAdmin) {
    echo "ERROR: Akun Super Admin tidak ditemukan. Gunakan DB Seeder yang tepat.\n";
    exit(1);
}

$tokenResult = $superAdmin->createToken('test-token');
$token = $tokenResult->plainTextToken;

function apiRequest($method, $endpoint, $payload, $token) {
    $request = \Illuminate\Http\Request::create($endpoint, $method, $payload);
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $request->headers->set('Accept', 'application/json');
    $response = app()->handle($request);
    
    return new class($response) {
        public $response;
        public function __construct($r) { $this->response = $r; }
        public function successful() { return $this->response->getStatusCode() >= 200 && $this->response->getStatusCode() < 300; }
        public function status() { return $this->response->getStatusCode(); }
        public function json($key = null) { 
            $data = json_decode($this->response->getContent(), true) ?: []; 
            if (!$key) return $data;
            $keys = explode('.', $key);
            foreach($keys as $k) {
                if(!isset($data[$k])) return null;
                $data = $data[$k];
            }
            return $data;
        }
        public function body() { return $this->response->getContent(); }
    };
}

$taxObject = \App\Models\TaxObject::first();
$taxpayer = \App\Models\Taxpayer::first();
$petugas = User::where('role', 'petugas')->first();

if (!$taxObject || !$petugas || !$taxpayer) {
    echo "SKIP: Objek Pajak, Wajib Pajak, atau Petugas tidak ditemukan untuk di-test.\n";
    exit(0);
}

echo "[2/4] Testing POST /api/spot-checks (Create Form Uji Petik)...\n";

$payload = [
    'tax_object_id' => $taxObject->id,
    'taxpayer_id' => $taxpayer->id,
    'inspector_id' => $petugas->id,
    'start_date' => date('Y-m-d H:i:s'),
    'end_date' => date('Y-m-d H:i:s', strtotime('+6 hours')),
    'is_weekend' => false,
    'items' => [
        [
            'observation_time' => '08:00',
            'visitor_count' => 10,
            'transaction_count' => 8,
            'estimated_value' => 150000
        ],
        [
            'observation_time' => '09:00',
            'visitor_count' => 20,
            'transaction_count' => 15,
            'estimated_value' => 300000
        ]
    ]
];

$response = apiRequest('POST', '/api/spot-checks', $payload, $token);
if ($response->successful()) {
    echo "  -> POST Success! Data created.\n";
    $spotCheckId = $response->json('data.id');
} else {
    echo "  -> POST Failed! Code: " . $response->status() . "\n";
    echo "     " . $response->body() . "\n";
    exit(1);
}

echo "[3/4] Testing PATCH /api/spot-checks/{id}/status (Approve Kertas Kerja)...\n";
$patchPayload = [
    'status' => 'approved',
    'supervisor_id' => $superAdmin->id
];
$respPatch = apiRequest('PATCH', "/api/spot-checks/{$spotCheckId}/status", $patchPayload, $token);

if ($respPatch->successful()) {
    echo "  -> PATCH Success! Status changes to Approved.\n";
} else {
    echo "  -> PATCH Failed! Code: " . $respPatch->status() . "\n";
    exit(1);
}

echo "[4/4] Testing GET /api/spot-checks/tax-object/{id}/estimation (Kalkulasi Pendapatan)...\n";
$respEst = apiRequest('GET', "/api/spot-checks/tax-object/{$taxObject->id}/estimation", [], $token);

if ($respEst->successful()) {
    echo "  -> GET Estimation Success!\n";
    print_r($respEst->json('data'));
} else {
    echo "  -> GET Estimation Failed! Code: " . $respEst->status() . "\n";
    exit(1);
}

echo "\n=============================================\n";
echo "   ✅ SEMUA PENGUJIAN UJI PETIK BERHASIL!\n";
echo "=============================================\n\n";

$superAdmin->tokens()->delete(); // Cleanup token
