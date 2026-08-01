<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seed demo NIK 0000000000000001 — beberapa pembayaran pajak berdasarkan layanan.
 *
 * Layanan yang di-seed (semua opd_id 5 = Bapenda):
 *  1. PBJT - Jasa Parkir          (class 172)
 *  2. PBJT - Makan dan Minum      (class 192)
 *  3. PBJT - Jasa Perhotelan      (class 193)
 *  4. Pajak Reklame               (class 188)
 *  5. Retribusi Persampahan       (class 198)
 *  6. PBB-P2                      (class 186)
 *
 * Idempotent: kalau objek pajak dengan nama penanda sudah ada → skip.
 */
class NikDemoSeeder extends Seeder
{
    protected const NIK = '0000000000000001';

    public function run(): void
    {
        $taxpayer = DB::table('taxpayers')->where('nik', self::NIK)->orderBy('id')->first();
        if (!$taxpayer) {
            $this->command->error("Taxpayer NIK " . self::NIK . " tidak ditemukan. Jalankan BapendaTestingSeeder dulu.");
            return;
        }

        $tid = $taxpayer->id;
        $this->command->info("Taxpayer: #{$tid} {$taxpayer->name}");

        // [classification_id, kode, nama objek, formula label, amount/bill]
        $layanan = [
            ['class' => 172, 'type' => 16, 'kode' => 'PRK', 'objek' => 'Area Parkir Mall Lippo Baubau',
             'desc' => 'PBJT Jasa Parkir — omzet bulanan', 'bill' => 1500000, 'method' => 'qris', 'bank' => 6],
            ['class' => 192, 'type' => 16, 'kode' => 'FOOD', 'objek' => 'RM. Nelayan Bahari Jl. Sultan Hasanuddin',
             'desc' => 'PBJT Makan dan Minum — omzet bulanan', 'bill' => 2750000, 'method' => 'va_sultra', 'bank' => 1],
            ['class' => 193, 'type' => 16, 'kode' => 'HTL', 'objek' => 'Hotel Nirwana Baubau',
             'desc' => 'PBJT Jasa Perhotelan — omzet bulanan', 'bill' => 4200000, 'method' => 'transfer', 'bank' => 3],
            ['class' => 188, 'type' => 16, 'kode' => 'REK', 'objek' => 'Billboard Jl. Betoambari Depan Bank Sultra',
             'desc' => 'Pajak Reklame — sewa media', 'bill' => 750000, 'method' => 'qris', 'bank' => 6],
            ['class' => 198, 'type' => 17, 'kode' => 'SMP', 'objek' => 'Sampah Rumah Tangga Jl. Gajah Mada',
             'desc' => 'Retribusi Persampahan — tarif flat', 'bill' => 50000, 'method' => 'cash', 'bank' => 1],
            ['class' => 186, 'type' => 16, 'kode' => 'PBB', 'objek' => 'PBB Ruko Jl. Beto Ambiri No. 10',
             'desc' => 'PBB-P2 — SPPT tahunan', 'bill' => 630000, 'method' => 'va_sultra', 'bank' => 1],
        ];

        $now = now();
        $countObjek = 0;
        $countBill = 0;
        $countPay = 0;

        foreach ($layanan as $i => $lv) {
            // ── Tax Object ──
            $exists = DB::table('tax_objects')
                ->where('taxpayer_id', $tid)
                ->where('name', $lv['objek'])
                ->exists();
            if ($exists) {
                $this->command->warn("  skip objek (sudah ada): {$lv['objek']}");
                continue;
            }

            $nop = '7471' . str_pad((string)(100000000 + $tid * 100 + $i), 10, '0', STR_PAD_LEFT);
            $objekId = DB::table('tax_objects')->insertGetId([
                'nop' => $nop,
                'is_active' => 1,
                'taxpayer_id' => $tid,
                'retribution_type_id' => $lv['type'],
                'retribution_classification_id' => $lv['class'],
                'opd_id' => 5,
                'zone_id' => null,
                'name' => $lv['objek'],
                'address' => $lv['objek'],
                'district' => 'Kota Baubau',
                'sub_district' => 'Baubau',
                'latitude' => null,
                'longitude' => null,
                'nomor_formulir' => null,
                'transaction_type' => 'perekaman_data',
                'metadata' => json_encode(['seed' => 'nik_demo', 'layanan' => $lv['kode']]),
                'status' => 'active',
                'audit_status' => 'normal',
                'nama_penandatangan' => $taxpayer->name,
                'tanggal_pernyataan' => $now->toDateString(),
                'tanda_tangan_url' => null,
                'approved_at' => $now,
                'approved_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'installation_date' => null,
                'last_photo_url' => null,
                'is_verified_physically' => 0,
            ]);
            $countObjek++;

            // ── 2 Bills per layanan: 1 lunas (paid), 1 pending ──
            for ($m = 1; $m <= 2; $m++) {
                $period = $now->copy()->subMonths($m)->format('Y-m');
                $billNumber = 'MPAD-' . str_replace('-', '', $period) . '-' . $tid . '-' . $lv['kode'];
                $billId = DB::table('bills')->insertGetId([
                    'taxpayer_id' => $tid,
                    'tax_object_id' => $objekId,
                    'opd_id' => 5,
                    'user_id' => null,
                    'retribution_type_id' => $lv['type'],
                    'retribution_classification_id' => $lv['class'],
                    'bill_number' => $billNumber,
                    'amount' => $lv['bill'],
                    'admin_fee' => 0,
                    'penalty_amount' => 0,
                    'penalty_at_payment' => null,
                    'waived_penalty_amount' => 0,
                    'penalty_type' => 'none',
                    'fixed_fine_amount' => 0,
                    'surcharge_amount' => 0,
                    'status' => $m === 1 ? 'paid' : 'pending',
                    'bank_code' => $m === 1 ? $this->bankCode($lv['bank']) : null,
                    'postponed_at' => null,
                    'reason_postponed' => null,
                    'period' => $period,
                    'period_start' => $period . '-01',
                    'period_end' => $period . '-28',
                    'metadata' => json_encode(['seed' => 'nik_demo', 'layanan' => $lv['kode']]),
                    'due_date' => $now->copy()->subMonths($m)->endOfMonth(),
                    'expiry_time' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'spot_check_id' => null,
                ]);
                $countBill++;

                // ── Payment untuk bill lunas ──
                if ($m === 1) {
                    $paidAt = $now->copy()->subMonths($m)->addDays(5);
                    DB::table('payments')->insert([
                        'bill_id' => $billId,
                        'bank_config_id' => $lv['bank'],
                        'billing_period' => $period,
                        'taxpayer_id' => $tid,
                        'tax_object_id' => $objekId,
                        'transaction_id' => 'TXN-' . strtoupper(Str::random(10)),
                        'reference_number' => 'REF-' . strtoupper(Str::random(8)),
                        'receipt_number' => 'RCP-' . strtoupper(Str::random(8)),
                        'payment_method' => $lv['method'],
                        'channel' => $lv['method'] === 'cash' ? 'teller' : 'online',
                        'raw_callback_data' => json_encode(['seed' => 'nik_demo']),
                        'amount' => $lv['bill'],
                        'tendered_amount' => $lv['bill'],
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

            $this->command->info("  + {$lv['kode']} — {$lv['objek']} (objek + 2 bill, 1 paid)");
        }

        $this->command->info("SELESAI: {$countObjek} objek, {$countBill} bill, {$countPay} payment untuk NIK " . self::NIK);
    }

    private function bankCode(int $bankId): string
    {
        return match ($bankId) {
            1 => 'SULTRA',
            3 => 'BRI',
            6 => 'QRIS',
            default => 'SULTRA',
        };
    }
}
