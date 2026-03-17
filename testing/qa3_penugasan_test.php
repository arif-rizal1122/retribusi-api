<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "=============================================\n";
echo "   QA 3: VALIDASI PENUGASAN PETUGAS          \n";
echo "=============================================\n";

function postRequest($endpoint, $payload, $token) {
    $request = \Illuminate\Http\Request::create($endpoint, 'POST', $payload);
    $request->headers->set('Authorization', 'Bearer ' . $token);
    $request->headers->set('Accept', 'application/json');
    $response = app()->handle($request);
    if (isset($response->exception) && $response->exception) {
        return [
            'status' => 500,
            'body' => ['message' => $response->exception->getMessage() . ' in ' . $response->exception->getFile() . ':' . $response->exception->getLine()]
        ];
    }
    return [
        'status' => $response->getStatusCode(),
        'body' => json_decode($response->getContent(), true)
    ];
}

$admin1 = User::where('email', 'admin.wilayah1@m-pad.online')->first();
$petugas2 = User::where('role', 'petugas')->where('retribution_type_id', 8)->first(); // Petugas milik Wilayah 2

if (!$admin1) {
    echo "ERROR: Admin Wilayah 1 tidak ditemukan.\n";
    exit(1);
}

if (!$petugas2) {
    // Buat dummy Petugas Wilayah 2
    $petugas2 = User::create([
        'name' => 'Petugas Wilayah 2',
        'email' => 'petugas2@test.com',
        'password' => bcrypt('password'),
        'role' => 'petugas',
        'retribution_type_id' => 8 // Wilayah 2
    ]);
}

$tokenAdmin1 = $admin1->createToken('testAdmin1')->plainTextToken;

echo "[1/2] Admin 1 mencoba memberikan tugas kepada Petugas Wilayah 2...\n";

$payload = [
    'user_id' => $petugas2->id,
    'due_date' => now()->addDays(2)->format('Y-m-d'),
    'notes' => 'Tolong survei ini'
];

$response = postRequest('/api/petugas-tasks', $payload, $tokenAdmin1);

if ($response['status'] === 403) {
    echo "  -> ✅ VALID: Sistem BERHASIL memblokir tugas lintas wilayah. (HTTP 403)\n";
    echo "     Pesan Sistem: " . ($response['body']['message'] ?? 'Forbidden') . "\n";
} else {
    echo "  -> ❌ ERROR: Sistem melepaskan tugas secara ilegal. Status HTTP: " . $response['status'] . "\n";
    print_r($response['body']);
}

echo "\n---------------------------------------------\n";
echo $response['status'] === 403 ? "   STATUS: LULUS (PASSED) \n" : "   STATUS: GAGAL (FAILED) \n";
echo "---------------------------------------------\n\n";

$admin1->tokens()->delete();
