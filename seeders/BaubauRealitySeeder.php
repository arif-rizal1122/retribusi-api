<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Zone;
use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use Carbon\Carbon;
use App\Models\User;

class BaubauRealitySeeder extends Seeder
{
    public function run(): void
    {
        $bapenda = Opd::where('code', 'BAPENDA')->first();
        if (!$bapenda) return;

        $admin = User::where('email', 'admin@bapenda.go.id')->first();
        $petugas = User::where('email', 'petugas@bapenda.go.id')->first();

        $w1 = RetributionType::where('name', 'Wilayah I')->first();
        $w2 = RetributionType::where('name', 'Wilayah II')->first();

        $mblb = RetributionClassification::where('name', 'Pajak MBLB')->first();
        $parkir = RetributionClassification::where('name', 'Retribusi Parkir')->first();
        $pbb = RetributionClassification::where('name', 'PBB-P2')->first();
        $reklame = RetributionClassification::where('name', 'Pajak Reklame')->first();
        $hotel = RetributionClassification::where('name', 'PBJT - Jasa Perhotelan')->first();
        $makan = RetributionClassification::where('name', 'PBJT - Makan dan Minum')->first();

        // Create Assignments for Petugas if not exists
        if ($petugas && $w2) {
            \App\Models\UserRetributionAssignment::updateOrCreate(
                ['user_id' => $petugas->id, 'retribution_type_id' => $w2->id, 'retribution_classification_id' => $makan->id ?? null]
            );
            \App\Models\UserRetributionAssignment::updateOrCreate(
                ['user_id' => $petugas->id, 'retribution_type_id' => $w2->id, 'retribution_classification_id' => $hotel->id ?? null]
            );
        }

        $dataset = [
            // Wilayah I
            ['name' => 'Gedung Billboard Utama', 'class' => $reklame, 'type' => $w1, 'lat' => -5.4611, 'lng' => 122.6011, 'owner' => 'AdMedia Baubau'],
            ['name' => 'Lahan PBB Sektor Wolio', 'class' => $pbb, 'type' => $w1, 'lat' => -5.4622, 'lng' => 122.5999, 'owner' => 'H. Mustafa'],
            ['name' => 'Tambang Galian Gol. C', 'class' => $mblb, 'type' => $w1, 'lat' => -5.4850, 'lng' => 122.6500, 'owner' => 'PT Baubau Mandiri'],
            
            // Wilayah II
            ['name' => 'Hotel Zenith Baubau', 'class' => $hotel, 'type' => $w2, 'lat' => -5.4644, 'lng' => 122.6033, 'owner' => 'PT Zenith'],
            ['name' => 'RM Lesehan Bungi', 'class' => $makan, 'type' => $w2, 'lat' => -5.4388, 'lng' => 122.6688, 'owner' => 'Ibu Rahma'],
            ['name' => 'Cinema XXI Lippo', 'class' => $makan, 'type' => $w2, 'lat' => -5.4618, 'lng' => 122.6042, 'owner' => 'Lippo Leisure'],
            ['name' => 'Bakso Lapangan Tembak', 'class' => $makan, 'type' => $w2, 'lat' => -5.4599, 'lng' => 122.6022, 'owner' => 'Bpk. Darmono'],
        ];

        foreach ($dataset as $idx => $data) {
            // Assign 40% to Petugas, rest to Admin
            $assignedTo = ($idx % 3 == 0 && $petugas) ? $petugas : $admin;

            $tp = Taxpayer::updateOrCreate(
                ['name' => $data['owner']],
                [
                    'nik' => '747201' . rand(10000000, 99999999), 
                    'is_active' => true,
                    'created_by' => $assignedTo->id
                ]
            );

            $obj = TaxObject::updateOrCreate(
                ['name' => $data['name']],
                [
                    'taxpayer_id' => $tp->id,
                    'opd_id' => $bapenda->id,
                    'retribution_type_id' => $data['type']->id,
                    'retribution_classification_id' => $data['class']->id ?? null,
                    'latitude' => $data['lat'],
                    'longitude' => $data['lng'],
                    'nop' => 'NOP-BAU-' . rand(1000, 9999),
                    'status' => 'active'
                ]
            );

            // Create 3 bills for each
            for ($m = 1; $m <= 3; $m++) {
                $status = ($m == 1) ? 'paid' : 'pending';
                $bill = Bill::create([
                    'bill_number' => 'BL-' . strtoupper(uniqid()),
                    'taxpayer_id' => $tp->id,
                    'tax_object_id' => $obj->id,
                    'opd_id' => $bapenda->id,
                    'retribution_type_id' => $data['type']->id,
                    'retribution_classification_id' => $data['class']->id ?? null,
                    'user_id' => $assignedTo->id,
                    'amount' => rand(100000, 5000000),
                    'period' => Carbon::now()->subMonths($m)->format('F Y'),
                    'status' => $status,
                    'due_date' => Carbon::now()->addDays(30)
                ]);

                if ($status === 'paid') {
                    Payment::create([
                        'bill_id' => $bill->id,
                        'taxpayer_id' => $tp->id,
                        'tax_object_id' => $obj->id,
                        'amount' => $bill->amount,
                        'status' => 'success',
                        'paid_at' => ($m == 1) ? Carbon::now()->subDays(rand(0, 5)) : Carbon::now()->subMonths($m)->addDays(5),
                        'billing_period' => $bill->period,
                        'payment_method' => 'qris',
                        'transaction_id' => 'TRX-' . strtoupper(uniqid()),
                        'approved_by' => $assignedTo->id
                    ]);
                }
            }
        }
    }
}
