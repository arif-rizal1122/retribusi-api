<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MarketStall;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DisperindagBillingSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Membuat data simulasi transaksi & penagihan Pasar Disperindag...');

        $adminUser = User::where('role', 'super_admin')->first() ?? User::first();
        $userId = $adminUser ? $adminUser->id : 1;

        $stalls = MarketStall::whereNotNull('tax_object_id')
            ->whereNotNull('taxpayer_id')
            ->get();

        $this->command->info("Ditemukan {$stalls->count()} lapak yang terhubung ke objek pajak...");

        $billCount = 0;
        $paymentCount = 0;

        foreach ($stalls as $stall) {
            $taxpayerId = $stall->taxpayer_id;
            $taxObjectId = $stall->tax_object_id;
            $amount = (float) $stall->tariff_amount;

            if ($amount <= 0) {
                continue;
            }

            $classificationId = $stall->stall_type === 'kios' ? 203 : 202;

            if ($stall->stall_type === 'kios') {
                // Kios: Penagihan Tahunan (2026)
                $period = 'Tahun 2026';
                $periodStart = Carbon::parse('2026-01-01');
                $periodEnd = Carbon::parse('2026-12-31');
                $dueDate = Carbon::parse('2026-04-30');

                // Tentukan status berdasarkan status lapak
                if ($stall->status === 'tersegel') {
                    $status = 'belum_bayar';
                    $penalty = round($amount * 0.04); // Denda 4%
                } elseif ($stall->status === 'kosong') {
                    continue;
                } else {
                    // 85% lunas, 15% belum bayar
                    $isPaid = ($stall->id % 7 !== 0);
                    $status = $isPaid ? 'lunas' : 'belum_bayar';
                    $penalty = 0;
                }

                $billNumber = 'SKRD-KIOS-2026-' . str_pad((string) $stall->id, 5, '0', STR_PAD_LEFT);

                $bill = Bill::updateOrCreate(
                    [
                        'tax_object_id' => $taxObjectId,
                        'period' => $period,
                    ],
                    [
                        'taxpayer_id' => $taxpayerId,
                        'opd_id' => 3,
                        'user_id' => $userId,
                        'retribution_type_id' => 17,
                        'retribution_classification_id' => $classificationId,
                        'bill_number' => $billNumber,
                        'amount' => $amount,
                        'admin_fee' => 0,
                        'penalty_amount' => $penalty,
                        'status' => $status,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'due_date' => $dueDate,
                        'created_at' => Carbon::parse('2026-01-10'),
                        'updated_at' => Carbon::parse('2026-01-10'),
                    ]
                );
                $billCount++;

                if ($status === 'lunas') {
                    Payment::updateOrCreate(
                        ['bill_id' => $bill->id],
                        [
                            'taxpayer_id' => $taxpayerId,
                            'tax_object_id' => $taxObjectId,
                            'billing_period' => $period,
                            'amount' => $amount,
                            'payment_method' => ($stall->id % 2 === 0) ? 'qris' : 'cash',
                            'status' => 'approved',
                            'approved_by' => $userId,
                            'paid_at' => Carbon::parse('2026-02-15')->addDays($stall->id % 28),
                            'created_at' => Carbon::parse('2026-02-15')->addDays($stall->id % 28),
                            'updated_at' => Carbon::parse('2026-02-15')->addDays($stall->id % 28),
                        ]
                    );
                    $paymentCount++;
                }
            } else {
                // Los: Penagihan Bulanan (Januari - Mei 2026)
                $months = [
                    ['name' => 'Januari 2026', 'due' => '2026-01-20', 'paid_date' => '2026-01-18'],
                    ['name' => 'Februari 2026', 'due' => '2026-02-20', 'paid_date' => '2026-02-17'],
                    ['name' => 'Maret 2026', 'due' => '2026-03-20', 'paid_date' => '2026-03-19'],
                    ['name' => 'April 2026', 'due' => '2026-04-20', 'paid_date' => '2026-04-18'],
                    ['name' => 'Mei 2026', 'due' => '2026-05-20', 'paid_date' => '2026-05-19'],
                ];

                foreach ($months as $mIdx => $mInfo) {
                    if ($stall->status === 'kosong') {
                        continue;
                    }

                    if ($stall->status === 'tersegel') {
                        $status = 'belum_bayar';
                        $penalty = round($amount * 0.02 * ($mIdx + 1));
                    } else {
                        // Bulan 1-4 hampir semua lunas, Mei berjalan
                        $isPaid = ($mIdx < 4) ? ($stall->id % 10 !== 0) : ($stall->id % 3 === 0);
                        $status = $isPaid ? 'lunas' : 'belum_bayar';
                        $penalty = 0;
                    }

                    $billNumber = 'SKRD-LOS-' . ($mIdx + 1) . '-2026-' . str_pad((string) $stall->id, 5, '0', STR_PAD_LEFT);

                    $bill = Bill::updateOrCreate(
                        [
                            'tax_object_id' => $taxObjectId,
                            'period' => $mInfo['name'],
                        ],
                        [
                            'taxpayer_id' => $taxpayerId,
                            'opd_id' => 3,
                            'user_id' => $userId,
                            'retribution_type_id' => 17,
                            'retribution_classification_id' => $classificationId,
                            'bill_number' => $billNumber,
                            'amount' => $amount,
                            'admin_fee' => 0,
                            'penalty_amount' => $penalty,
                            'status' => $status,
                            'due_date' => Carbon::parse($mInfo['due']),
                            'created_at' => Carbon::parse($mInfo['due'])->subDays(15),
                            'updated_at' => Carbon::parse($mInfo['due'])->subDays(15),
                        ]
                    );
                    $billCount++;

                    if ($status === 'lunas') {
                        Payment::updateOrCreate(
                            ['bill_id' => $bill->id],
                            [
                                'taxpayer_id' => $taxpayerId,
                                'tax_object_id' => $taxObjectId,
                                'billing_period' => $mInfo['name'],
                                'amount' => $amount,
                                'payment_method' => ($stall->id % 2 === 0) ? 'qris' : 'cash',
                                'status' => 'approved',
                                'approved_by' => $userId,
                                'paid_at' => Carbon::parse($mInfo['paid_date']),
                                'created_at' => Carbon::parse($mInfo['paid_date']),
                                'updated_at' => Carbon::parse($mInfo['paid_date']),
                            ]
                        );
                        $paymentCount++;
                    }
                }
            }
        }

        $this->command->info("Selesai! Berhasil membuat {$billCount} tagihan (SKRD) dan {$paymentCount} pembayaran Pasar Disperindag.");
    }
}
