<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use App\Models\Opd;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $opd = Opd::updateOrCreate(
            ['code' => 'PERTANAHAN'],
            [
                'name' => 'Dinas Pertanahan Kota Baubau',
                'address' => 'Kompleks Perkantoran Palagimata, Kota Baubau',
                'phone' => '0402-2821111',
                'email' => 'pertanahan@baubaukota.go.id',
                'status' => 'approved',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin.pertanahan@baubaukota.go.id'],
            [
                'name' => 'Admin Dinas Pertanahan Kota Baubau',
                'password' => Hash::make('password123'),
                'role' => 'opd',
                'opd_id' => $opd->id,
                'status' => 'active',
                'metadata' => [
                    'role_code' => 'pertanahan',
                    'opd_role' => 'Dinas Pertanahan Kota Baubau',
                    'role_group' => 'opd',
                ],
            ]
        );

        User::updateOrCreate(
            ['email' => 'pertanahan@baubaukota.go.id'],
            [
                'name' => 'Dinas Pertanahan Kota Baubau',
                'password' => Hash::make('password123'),
                'role' => 'opd',
                'opd_id' => $opd->id,
                'status' => 'active',
                'metadata' => [
                    'role_code' => 'pertanahan',
                    'opd_role' => 'Dinas Pertanahan Kota Baubau',
                    'role_group' => 'opd',
                ],
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::whereIn('email', [
            'admin.pertanahan@baubaukota.go.id',
            'pertanahan@baubaukota.go.id',
        ])->delete();

        Opd::where('code', 'PERTANAHAN')->delete();
    }
};
