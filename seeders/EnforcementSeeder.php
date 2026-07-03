<?php

namespace Database\Seeders;

use App\Models\EnforcementNotice;
use App\Models\TaxObject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnforcementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $petugas = User::where('email', 'petugas@bapenda.go.id')->first();
        if (!$petugas) {
            $this->command->error('Petugas petugas@bapenda.go.id not found!');
            return;
        }

        $taxObject = TaxObject::first();
        if (!$taxObject) {
            $this->command->error('No tax object found to assign!');
            return;
        }

        // Create a sample enforcement notice assigned to this petugas
        EnforcementNotice::updateOrCreate(
            ['number' => 'ST-001/BAPENDA/2026'],
            [
                'tax_object_id' => $taxObject->id,
                'assigned_to' => $petugas->id,
                'type' => 'teguran_1',
                'status' => 'approved',
                'notes' => 'Teguran pertama untuk penunggakan retribusi pasar.',
                'created_by' => 1, // Super Admin
                'approved_by' => 1,
                'due_date' => now()->addDays(7),
            ]
        );

        $this->command->info('Enforcement notice created and assigned to ' . $petugas->email);
    }
}
