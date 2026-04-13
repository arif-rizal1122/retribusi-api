<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Opd;

$user = User::where('email', 'bapenda@baubaukota.go.id')->first();
if ($user) {
    echo "User: " . $user->email . "\n";
    echo "Role: " . $user->role . "\n";
    echo "OPD ID: " . $user->opd_id . "\n";
    if ($user->opd_id) {
        $opd = Opd::find($user->opd_id);
        echo "OPD Name: " . ($opd->name ?? 'Unknown') . "\n";
    }
}

$opd5 = Opd::find(5);
if ($opd5) {
    echo "OPD ID 5 Name: " . $opd5->name . "\n";
}
