<?php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$creds = [
    ['email' => 'admin@retribusi.id', 'password' => 'password123'],
    ['email' => 'superadmin@sipanda.online', 'password' => 'password'],
    ['email' => 'bapenda@baubaukota.go.id', 'password' => 'password123'],
    ['email' => 'petugas@bapenda.go.id', 'password' => 'password123'],
    ['email' => 'admin_bapenda_resmi@sipanda.online', 'password' => 'password123'], // testing common password
    ['email' => 'admin_bapenda_resmi@sipanda.online', 'password' => 'Bapenda123#'], // testing another common one
];

foreach ($creds as $c) {
    $user = User::where('email', $c['email'])->first();
    if (!$user) {
        echo "User {$c['email']} not found\n";
        continue;
    }
    $match = Hash::check($c['password'], $user->password);
    echo "User: {$c['email']}, Password: {$c['password']}, Match: " . ($match ? "YES" : "NO") . "\n";
}
