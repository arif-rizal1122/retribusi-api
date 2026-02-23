<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\PenaltyWaiver;
use App\Models\User;
use App\Models\Taxpayer;
use App\Models\Opd;
use App\Models\RetributionType;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TaxAmnestySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get Actors
        $petugas = User::where('role', 'petugas')->first();
        if (!$petugas) {
            $petugas = User::create([
                'name' => 'Petugas Seed',
                'email' => 'petugas.seed@bapenda.go.id',
                'password' => bcrypt('password123'),
                'role' => 'petugas',
                'status' => 'active',
            ]);
        }

        $pengawas = User::where('role', 'kabid_pengawas')->first();
        if (!$pengawas) {
            // Should exist from SurveillanceAccountSeeder, but just in case
            $pengawas = User::create([
                'name' => 'Kabid Pengawas',
                'email' => 'kabid@retribusi.id',
                'password' => bcrypt('password123'),
                'role' => 'kabid_pengawas',
                'status' => 'active',
            ]);
        }

        // 2. Get or Create Taxpayer & Metadata
        $opd = Opd::first();
        $type = RetributionType::first();
        $taxpayer = Taxpayer::first();

        if (!$taxpayer) {
            $taxpayer = Taxpayer::create([
                'name' => 'Wajib Pajak Contoh',
                'nik' => '7471000000000001',
                'email' => 'wp.contoh@gmail.com',
                'phone' => '081234567890',
                'address' => 'Jl. Merdeka No. 45',
                'password' => bcrypt('password123'),
            ]);
        }

        // 3. Create Bills with Penalties (Overdue bills)
        // We need 3 scenarios: Pending, Approved, Rejected

        // Scenario A: Pending Request
        $billPending = Bill::create([
            'taxpayer_id' => $taxpayer->id,
            'opd_id' => $opd ? $opd->id : 1,
            'retribution_type_id' => $type ? $type->id : 1,
            'bill_number' => 'TAG-' . time() . '-001',
            'amount' => 500000,
            'penalty_amount' => 10000, // Dennda 2%
            'status' => 'overdue',
            'period' => Carbon::now()->subMonths(2)->format('Y-m'),
            'due_date' => Carbon::now()->subMonths(1),
            'metadata' => json_encode(['description' => 'Tagihan terlambat 2 bulan']),
        ]);

        PenaltyWaiver::create([
            'bill_id' => $billPending->id,
            'requested_by' => $petugas->id,
            'reason' => 'Wajib Pajak mengalami kesulitan keuangan pasca pandemi (Pengajuan Keringanan)',
            'reduction_type' => 'percentage',
            'reduction_value' => 100, // Request 100% waiver
            'status' => 'pending',
        ]);

        // Scenario B: Approved Request
        $billApproved = Bill::create([
            'taxpayer_id' => $taxpayer->id,
            'opd_id' => $opd ? $opd->id : 1,
            'retribution_type_id' => $type ? $type->id : 1,
            'bill_number' => 'TAG-' . time() . '-002',
            'amount' => 1000000,
            'penalty_amount' => 50000,
            'waived_penalty_amount' => 50000, // Penalty removed
            'status' => 'pending', // Status might change to pending payment after waiver? Or remains overdue but with reduced amount. keeping as pending for now.
            'period' => Carbon::now()->subMonths(6)->format('Y-m'),
            'due_date' => Carbon::now()->subMonths(5),
            'metadata' => json_encode(['description' => 'Tagihan terlambat 6 bulan']),
        ]);

        PenaltyWaiver::create([
            'bill_id' => $billApproved->id,
            'requested_by' => $petugas->id,
            'approved_by' => $pengawas->id,
            'reason' => 'Kesalahan sistem dalam penetapan denda tahun lalu.',
            'reduction_type' => 'fixed_amount',
            'reduction_value' => 50000,
            'status' => 'approved',
            'approval_notes' => 'Disetujui. Denda dihapuskan sesuai SK Walikota No. XX.',
            'updated_at' => Carbon::now(),
        ]);

        // Scenario C: Rejected Request
        $billRejected = Bill::create([
            'taxpayer_id' => $taxpayer->id,
            'opd_id' => $opd ? $opd->id : 1,
            'retribution_type_id' => $type ? $type->id : 1,
            'bill_number' => 'TAG-' . time() . '-003',
            'amount' => 750000,
            'penalty_amount' => 15000,
            'status' => 'overdue',
            'period' => Carbon::now()->subMonths(3)->format('Y-m'),
            'due_date' => Carbon::now()->subMonths(2),
            'metadata' => json_encode(['description' => 'Tagihan terlambat 3 bulan']),
        ]);

        PenaltyWaiver::create([
            'bill_id' => $billRejected->id,
            'requested_by' => $petugas->id,
            'approved_by' => $pengawas->id,
            'reason' => 'Permohonan penghapusan denda karena lupa bayar.',
            'reduction_type' => 'percentage',
            'reduction_value' => 100,
            'status' => 'rejected',
            'approval_notes' => 'Ditolak. Alasan tidak memenuhi syarat urgensi atau force majeure.',
            'updated_at' => Carbon::now(),
        ]);

        $this->command->info('Tax Amnesty / Penalty Waiver seeds created successfully!');
    }
}
