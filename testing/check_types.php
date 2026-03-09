<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$types = \App\Models\RetributionType::where('is_active', true)->get();
foreach ($types as $idx => $t) {
    echo "ID: " . $t->id . " | Code: " . $t->code . " | Name: " . $t->name . "\n";
}

$oldAdmins = \App\Models\User::whereIn('role', ['admin', 'super_admin'])->get();
foreach ($oldAdmins as $adm) {
    echo "Admin: " . $adm->email . " | RetributionTypeID: " . ($adm->retribution_type_id ?? 'NULL (Unrestricted)') . "\n";
}
