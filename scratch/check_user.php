<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'petugas@bapenda.go.id';
$password = 'password123';

$user = User::where('email', $email)->first();

if (!$user) {
    echo "User not found: $email\n";
    exit(1);
}

echo "User found: " . $user->name . "\n";
echo "Email: " . $user->email . "\n";
echo "Status: " . $user->status . "\n";
echo "Role: " . $user->role . "\n";

if (Hash::check($password, $user->password)) {
    echo "Password matches!\n";
} else {
    echo "Password does NOT match.\n";
}
