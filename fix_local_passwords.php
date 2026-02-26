<?php
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::beginTransaction();
try {
    $users = User::all();
    foreach ($users as $user) {
        $password = 'password123';
        if ($user->email === 'superadmin@sipanda.online') {
            $password = 'password';
        }
        
        $user->password = Hash::make($password);
        $user->save();
        echo "Updated password for: {$user->email} (Password: {$password})\n";
    }
    DB::commit();
    echo "Done resetting local passwords.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
