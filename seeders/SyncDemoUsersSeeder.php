<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Opd;
use Illuminate\Support\Facades\Hash;

class SyncDemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $bapenda = Opd::where('code', 'BAPENDA')->first();
        
        // 1. Ensure admin@bapenda.go.id exists (Matches Login.tsx Quick Login)
        User::updateOrCreate(
            ['email' => 'admin@bapenda.go.id'],
            [
                'name' => 'Admin Bapenda Demo',
                'password' => Hash::make('password123'),
                'role' => 'opd',
                'opd_id' => $bapenda ? $bapenda->id : null,
                'status' => 'active',
            ]
        );

        // 2. Ensure superadmin@sipanda.online has correct password (Matches Login.tsx)
        User::updateOrCreate(
            ['email' => 'superadmin@sipanda.online'],
            [
                'name' => 'Dev Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );

        // 3. Ensure other users have standard password123
        $others = [
            'bapenda@baubaukota.go.id' => 'Admin BAPENDA',
            'petugas@bapenda.go.id' => 'Petugas BAPENDA',
            'kabid@retribusi.id' => 'Kabid Pengawas',
            'kasubid@retribusi.id' => 'Kasubid Pengawas',
            'admin@retribusi.id' => 'Super Admin',
        ];

        foreach ($others as $email => $name) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->update(['password' => Hash::make('password123'), 'status' => 'active']);
            }
        }

        echo "Demo users synchronized successfully.\n";
    }
}
