<?php

namespace App\Services\Dlh;

use App\Models\Bill;
use App\Models\RetributionRate;
use App\Models\TaxObject;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DlhTrashBillingService
{
    /**
     * Tarif acuan berdasarkan building_category sesuai Perda No. 1 Tahun 2024
     */
    protected array $rateCategoryMap = [
        'SOSIAL_IBADAH' => 0.00,
        'RUMAH_SEDERHANA' => 8500.00,
        'RUMAH_MENENGAH' => 15000.00,
        'RUKO_NIAGA' => 22000.00,
        'RESTORAN' => 45000.00,
        'PASAR' => 22000.00,
        'INDUSTRI' => 50000.00,
    ];

    /**
     * Hitung nominal retribusi bulanan untuk suatu objek persampahan
     */
    public function calculateMonthlyAmount(TaxObject $taxObject): float
    {
        // Rumah/ruko kosong tidak ditarik retribusi sampah aktif
        if ($taxObject->status_hunian === 'KOSONG') {
            return 0.00;
        }

        $category = $taxObject->building_category ?? 'RUMAH_SEDERHANA';

        // Cek apakah ada rate terkonfigurasi di database
        $dbRate = RetributionRate::where('opd_id', 4)
            ->where('is_active', true)
            ->where(function ($q) use ($category) {
                if ($category === 'SOSIAL_IBADAH') {
                    $q->where('name', 'LIKE', '%Ibadah%')->orWhere('amount', 0);
                } elseif ($category === 'RUMAH_SEDERHANA') {
                    $q->where('name', 'LIKE', '%Sederhana%');
                } elseif ($category === 'RUMAH_MENENGAH') {
                    $q->where('name', 'LIKE', '%Menengah%');
                } elseif ($category === 'RUKO_NIAGA') {
                    $q->where('name', 'LIKE', '%Ruko%');
                } elseif ($category === 'RESTORAN') {
                    $q->where('name', 'LIKE', '%Restoran%');
                }
            })
            ->first();

        if ($dbRate) {
            return (float) $dbRate->amount;
        }

        return $this->rateCategoryMap[$category] ?? 8500.00;
    }

    /**
     * Terbitkan tagihan bulanan untuk objek persampahan tertentu
     */
    public function generateMonthlyBill(TaxObject $taxObject, ?Carbon $periodDate = null): Bill
    {
        $date = $periodDate ? $periodDate->copy() : Carbon::now();
        $periodString = $date->format('Y-m'); // misal "2026-09"
        $periodStart = $date->copy()->startOfMonth()->toDateString();
        $periodEnd = $date->copy()->endOfMonth()->toDateString();
        $dueDate = $date->copy()->startOfMonth()->addDays(14)->toDateString(); // Jatuh tempo tanggal 15

        return DB::transaction(function () use ($taxObject, $periodString, $periodStart, $periodEnd, $dueDate) {
            // Serialize generation per object. The duplicate check must happen
            // after this lock so concurrent scheduler/admin retries cannot both
            // observe an empty period and insert two bills.
            $lockedTaxObject = TaxObject::withoutGlobalScopes()
                ->whereKey($taxObject->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $existing = Bill::where('tax_object_id', $lockedTaxObject->id)
                ->where('period', $periodString)
                ->first();

            if ($existing) {
                return $existing;
            }

            $amount = $this->calculateMonthlyAmount($lockedTaxObject);
            $billNumber = 'DLH-SMP-'.date('ymd').'-'.strtoupper(Str::random(6));

            $bill = Bill::create([
                'taxpayer_id' => $lockedTaxObject->taxpayer_id,
                'tax_object_id' => $lockedTaxObject->id,
                'opd_id' => 4, // DLH
                'retribution_type_id' => $lockedTaxObject->retribution_type_id ?? 17,
                'retribution_classification_id' => $lockedTaxObject->retribution_classification_id ?? 198,
                'bill_number' => $billNumber,
                'amount' => $amount,
                'admin_fee' => 0.00,
                'penalty_amount' => 0.00,
                'status' => $amount == 0 ? 'PAID' : 'UNPAID', // Bebas tarif langsung PAID
                'period' => $periodString,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'due_date' => $dueDate,
                'metadata' => json_encode([
                    'building_category' => $lockedTaxObject->building_category,
                    'tipe_penghuni' => $lockedTaxObject->tipe_penghuni,
                    'status_hunian' => $lockedTaxObject->status_hunian,
                    'penanggung_jawab_nama' => $lockedTaxObject->penanggung_jawab_nama,
                    'penanggung_jawab_kontak' => $lockedTaxObject->penanggung_jawab_kontak,
                    'district' => $lockedTaxObject->district,
                    'sub_district' => $lockedTaxObject->sub_district,
                ]),
            ]);

            return $bill;
        });
    }

    /**
     * Hitung dan terapkan sanksi denda 1% per bulan bila lewat jatuh tempo
     */
    public function applyOverduePenalty(Bill $bill): Bill
    {
        if ($bill->status === 'PAID' || $bill->amount <= 0) {
            return $bill;
        }

        $dueDate = Carbon::parse($bill->due_date);
        $now = Carbon::now();

        if ($now->gt($dueDate)) {
            // Hitung selisih bulan keterlambatan (minimal 1 bulan)
            $monthsLate = max(1, (int) floor($dueDate->diffInMonths($now)));
            // Maksimal akumulasi denda sesuai UU HKPD / Perda adalah 24 bulan (24%)
            $penaltyPercentage = min(24, $monthsLate * 1.0);
            $penaltyAmount = round(($bill->amount * $penaltyPercentage) / 100, 2);

            $bill->penalty_amount = $penaltyAmount;
            $bill->penalty_type = 'PERCENTAGE';
            $bill->save();
        }

        return $bill;
    }

    /**
     * Alihkan penanggung jawab tagihan persil (misal penyewa baru masuk atau ganti penghuni)
     */
    public function transferOccupant(TaxObject $taxObject, array $newData): TaxObject
    {
        return DB::transaction(function () use ($taxObject, $newData) {
            $taxObject->update([
                'tipe_penghuni' => $newData['tipe_penghuni'] ?? $taxObject->tipe_penghuni,
                'status_hunian' => $newData['status_hunian'] ?? 'BERPENGHUNI',
                'penanggung_jawab_nama' => $newData['penanggung_jawab_nama'] ?? $taxObject->penanggung_jawab_nama,
                'penanggung_jawab_kontak' => $newData['penanggung_jawab_kontak'] ?? $taxObject->penanggung_jawab_kontak,
            ]);

            return $taxObject->fresh();
        });
    }
}
