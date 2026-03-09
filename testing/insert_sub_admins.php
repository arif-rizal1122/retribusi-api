<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$admin1 = User::updateOrCreate(
    ['email' => 'admin.wilayah1@m-pad.online'],
    [
        'name' => 'Admin Pengawas (Wilayah 1)',
        'password' => Hash::make('password123'),
        'role' => 'admin',
        'retribution_type_id' => 7, // Sesuaikan dengan ID Name: Wilayah I
    ]
);

$admin2 = User::updateOrCreate(
    ['email' => 'admin.wilayah2@m-pad.online'],
    [
        'name' => 'Admin Pengawas (Wilayah 2)',
        'password' => Hash::make('password123'),
        'role' => 'admin',
        'retribution_type_id' => 8, // Sesuaikan dengan ID Name: Wilayah II
    ]
);

echo "✅ Berhasil Membuat/Update Akun Sub Admin:\n";
echo "-------------------------------------------\n";
echo "1. Email: {$admin1->email} | Password: password123 | Type ID: {$admin1->retribution_type_id} (Wilayah 1)\n";
echo "2. Email: {$admin2->email} | Password: password123 | Type ID: {$admin2->retribution_type_id} (Wilayah 2)\n";
echo "\n*Catatan: Admin lama (superadmin dsb.) tetap dengan Type ID NULL (TIdak Dibatasi).*";
