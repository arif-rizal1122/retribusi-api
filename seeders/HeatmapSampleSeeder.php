<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\RetributionType;
use App\Models\Opd;
use Carbon\Carbon;

class HeatmapSampleSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create necessary relations
        $opd = Opd::first() ?: Opd::create(['name' => 'BAPENDA', 'email' => 'bapenda@baubau.go.id']);
        $type = RetributionType::where('name', 'Pajak Restoran')->first() ?: RetributionType::create([
            'opd_id' => $opd->id,
            'name' => 'Pajak Restoran',
            'category' => 'pajak'
        ]);
        $taxpayer = Taxpayer::first() ?: Taxpayer::create([
            'name' => 'Budi Santoso',
            'nik' => '7472012345678901',
            'phone' => '08123456789'
        ]);

        // Sample coordinates in Baubau area
        $locations = [
            ['name' => 'RM Sederhana Baubau', 'lat' => -5.4645, 'lng' => 122.6050, 'revenue' => 15000000],
            ['name' => 'Hotel Nirwana', 'lat' => -5.4850, 'lng' => 122.5880, 'revenue' => 45000000],
            ['name' => 'Cafe Pantai Kamali', 'lat' => -5.4590, 'lng' => 122.6020, 'revenue' => 12000000],
            ['name' => 'Kopi Kita Baubau', 'lat' => -5.4660, 'lng' => 122.5980, 'revenue' => 8500000],
            ['name' => 'Warung Ikan Bakar Murhum', 'lat' => -5.4740, 'lng' => 122.6100, 'revenue' => 22000000],
        ];

        foreach ($locations as $loc) {
            $obj = TaxObject::create([
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $type->id,
                'opd_id' => $opd->id,
                'name' => $loc['name'],
                'address' => 'Jl. Sampel No. ' . rand(1, 100),
                'latitude' => $loc['lat'],
                'longitude' => $loc['lng'],
                'status' => 'active'
            ]);

            $bill = Bill::create([
                'tax_object_id' => $obj->id,
                'taxpayer_id' => $taxpayer->id,
                'opd_id' => $opd->id,
                'retribution_type_id' => $type->id,
                'bill_number' => 'BILL-' . strtoupper(uniqid()),
                'amount' => $loc['revenue'],
                'due_date' => Carbon::now()->subDays(5),
                'status' => 'paid'
            ]);

            Payment::create([
                'bill_id' => $bill->id,
                'amount' => $loc['revenue'],
                'paid_at' => Carbon::now()->subDays(2),
                'status' => 'success',
                'payment_method' => 'cash'
            ]);
        }
    }
}
