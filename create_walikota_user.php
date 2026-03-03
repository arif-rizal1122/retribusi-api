<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$userData = [
    'name' => 'Wali Kota Baubau',
    'email' => 'walikota@baubaukota.go.id',
    'nik' => '7404123456780001',
    'password' => Hash::make('Walikota123#'),
    'role' => 'walikota',
    'status' => 'active',
    'phone' => '081234567890',
    'address' => 'Kantor Wali Kota Baubau',
];

$user = User::where('email', $userData['email'])->first();

if ($user) {
    $user->update($userData);
    echo "User walikota berhasil diperbarui.\n";
} else {
    User::create($userData);
    echo "User walikota berhasil dibuat.\n";
}
echo "Email: " . $userData['email'] . "\n";
echo "Password: Walikota123#\n";
