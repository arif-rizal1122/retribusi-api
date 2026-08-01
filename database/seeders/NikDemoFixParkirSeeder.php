<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fix partial: lengkapi bills + payment untuk objek Parkir NIK 0000000000000001
 * yang terlanjur ter-insert di attempt pertama (sebelum error penalty_type).
 */
class NikDemoFixParkirSeeder extends Seeder
{
    public function run(): void
    {
        $tid = 74;
        $objek = DB::table('tax_objects')->where('taxpayer_id', $tid)
            ->where('retribution_classification_id', 172)
            ->first();
        if (!$objek) {
            $this->command->error('Objek parkir tidak ditemukan');
            return;
        }

        $existingBills = DB::table('bills')->where('tax_object_id', $objek->id)->count();
        if ($existingBills > 0) {
            $this->command->warn("Objek parkir #{$objek->id} sudah punya {$existingBills} bill — skip");
            return;
        }

        $now = now();
        $amount = 1500000;
        $countBill = 0;
        $countPay = 0;

        for ($m = 1; $m <= 2; $m++) {
            $period = $now->copy()->subMonths($m)->format('Y-m');
            $billId = DB::table('bills')->insertGetId([
                'taxpayer_id' => $tid,
                'tax_object_id' => $objek->id,
                'opd_id' => 5,
                'user_id' => null,
                'retribution_type_id' => 16,
                'retribution_classification_id' => 172,
                'bill_number' => 'MPAD-' . str_replace('-', '', $period) . '-' . $tid . '-PRK',
                'amount' => $amount,
                'admin_fee' => 0,
                'penalty_amount' => 0,
                'penalty_at_payment' => null,
                'waived_penalty_amount' => 0,
                'penalty_type' => 'none',
                'fixed_fine_amount' => 0,
                'surcharge_amount' => 0,
                'status' => $m === 1 ? 'paid' : 'pending',
                'bank_code' => $m === 1 ? 'QRIS' : null,
                'postponed_at' => null,
                'reason_postponed' => null,
                'period' => $period,
                'period_start' => $period . '-01',
                'period_end' => $period . '-28',
                'metadata' => json_encode(['seed' => 'nik_demo', 'layanan' => 'PRK']),
                'due_date' => $now->copy()->subMonths($m)->endOfMonth(),
                'expiry_time' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'spot_check_id' => null,
            ]);
            $countBill++;

            if ($m === 1) {
                $paidAt = $now->copy()->subMonths($m)->addDays(5);
                DB::table('payments')->insert([
                    'bill_id' => $billId,
                    'bank_config_id' => 6,
                    'billing_period' => $period,
                    'taxpayer_id' => $tid,
                    'tax_object_id' => $objek->id,
                    'transaction_id' => 'TXN-' . strtoupper(Str::random(10)),
                    'reference_number' => 'REF-' . strtoupper(Str::random(8)),
                    'receipt_number' => 'RCP-' . strtoupper(Str::random(8)),
                    'payment_method' => 'qris',
                    'channel' => 'online',
                    'raw_callback_data' => json_encode(['seed' => 'nik_demo']),
                    'amount' => $amount,
                    'tendered_amount' => $amount,
                    'change_amount' => 0,
                    'status' => 'approved',
                    'approved_by' => 1,
                    'proof_url' => null,
                    'paid_at' => $paidAt,
                    'created_at' => $paidAt,
                    'updated_at' => $paidAt,
                ]);
                $countPay++;
            }
        }

        $this->command->info("OK: {$countBill} bill + {$countPay} payment untuk parkir (objek #{$objek->id})");
    }
}
