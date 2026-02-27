<?php
$user = App\Models\User::where('email', 'petugas@bapenda.go.id')->first();
if (!$user) { echo "User not found\n"; exit; }

$updated = \App\Models\Bill::whereNull('opd_id')->update(['opd_id' => $user->opd_id]);
echo "Updated $updated bills with opd_id = {$user->opd_id}\n";
