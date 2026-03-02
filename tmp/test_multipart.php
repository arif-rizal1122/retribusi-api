<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$petugas = App\Models\User::where('email', 'petugas@bapenda.go.id')->first();
$token = $petugas->createToken('test')->plainTextToken;

$response = Illuminate\Support\Facades\Http::withToken($token)
    ->acceptJson()
    ->asMultipart()
    ->post('http://localhost:8000/api/taxpayers', [
        'nik' => '1232131312121212',
        'name' => '121212',
        'address' => '12121',
        'object_name' => 'tes',
        'retribution_type_ids' => [22] // Adding this to see if it works with the file
    ]);

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
