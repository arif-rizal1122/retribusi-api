<?php

namespace App\Http\Controllers\Api\V1\Dlh;

use App\Http\Controllers\Controller;
use App\Models\TaxObject;
use App\Services\Dlh\DlhTrashBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DlhTrashBillingController extends Controller
{
    protected DlhTrashBillingService $billingService;

    public function __construct(DlhTrashBillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Hitung simulasi tarif retribusi persampahan
     */
    public function calculateTariff(Request $request)
    {
        $validated = $request->validate([
            'building_category' => 'required|string|in:SOSIAL_IBADAH,RUMAH_SEDERHANA,RUMAH_MENENGAH,RUKO_NIAGA,RESTORAN,PASAR,INDUSTRI',
            'status_hunian' => 'required|string|in:BERPENGHUNI,KOSONG',
        ]);

        $dummyObject = new TaxObject([
            'building_category' => $validated['building_category'],
            'status_hunian' => $validated['status_hunian'],
        ]);

        $amount = $this->billingService->calculateMonthlyAmount($dummyObject);

        return response()->json([
            'status' => 'success',
            'data' => [
                'building_category' => $validated['building_category'],
                'status_hunian' => $validated['status_hunian'],
                'monthly_amount' => $amount,
                'due_day_of_month' => 15,
                'penalty_rate' => '1% per bulan keterlambatan (maks. 24%)',
            ],
        ]);
    }

    /**
     * Alihkan/update data penghuni persil (Penyewa / Pemilik baru)
     */
    public function transferOccupant(Request $request, $taxObjectId)
    {
        $taxObject = TaxObject::findOrFail($taxObjectId);

        $validated = $request->validate([
            'tipe_penghuni' => 'required|string|in:PEMILIK,PENYEWA',
            'status_hunian' => 'required|string|in:BERPENGHUNI,KOSONG',
            'penanggung_jawab_nama' => 'required|string|max:255',
            'penanggung_jawab_kontak' => 'nullable|string|max:50',
        ]);

        $updated = $this->billingService->transferOccupant($taxObject, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Data penghuni persil berhasil diperbarui.',
            'data' => $updated,
        ]);
    }

    /**
     * Penerbitan massal tagihan retribusi persampahan bulanan (Auto-Billing Door-to-Door)
     */
    public function generateMonthlyBills(Request $request)
    {
        $validated = $request->validate([
            'period' => 'nullable|date_format:Y-m',
            'district' => 'nullable|string',
            'sub_district' => 'nullable|string',
        ]);

        $periodDate = !empty($validated['period'])
            ? Carbon::createFromFormat('Y-m', $validated['period'])
            : Carbon::now();

        $query = TaxObject::where(function ($q) {
            $q->where('retribution_classification_id', 198)
              ->orWhere('opd_id', 4);
        })
        ->where('is_active', true)
        ->where('status_hunian', 'BERPENGHUNI'); // Rumah kosong tidak ditagih

        if (!empty($validated['district'])) {
            $query->where('district', $validated['district']);
        }
        if (!empty($validated['sub_district'])) {
            $query->where('sub_district', $validated['sub_district']);
        }

        $taxObjects = $query->get();
        $generatedCount = 0;
        $totalAmountBilled = 0;

        foreach ($taxObjects as $obj) {
            $bill = $this->billingService->generateMonthlyBill($obj, $periodDate);
            if ($bill->wasRecentlyCreated) {
                $generatedCount++;
                $totalAmountBilled += $bill->amount;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Proses penerbitan tagihan bulanan selesai. {$generatedCount} tagihan baru diterbitkan.",
            'data' => [
                'period' => $periodDate->format('Y-m'),
                'total_persil_evaluated' => $taxObjects->count(),
                'new_bills_generated' => $generatedCount,
                'total_amount_billed' => (float) $totalAmountBilled,
            ],
        ]);
    }

    /**
     * Inkuiri Tagihan Persampahan (Portal Warga / Mobile)
     */
    public function inquiry(Request $request)
    {
        $query = $request->query('query');
        if (empty($query)) {
            return response()->json(['status' => 'error', 'message' => 'Parameter query pencarian wajib diisi.'], 422);
        }

        $taxObject = TaxObject::withoutGlobalScopes()
            ->where(function ($q) {
                $q->where('opd_id', 4)
                  ->orWhere('retribution_classification_id', 198);
            })
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('address', 'LIKE', "%{$query}%")
                  ->orWhere('penanggung_jawab_nama', 'LIKE', "%{$query}%");
                if (is_numeric($query)) {
                    $q->orWhere('id', (int) $query);
                }
            })
            ->first();

        if (!$taxObject) {
            return response()->json(['status' => 'error', 'message' => 'Objek persampahan tidak ditemukan.'], 404);
        }

        $bills = \App\Models\Bill::withoutGlobalScopes()
            ->where('opd_id', 4)
            ->where(function ($q) use ($taxObject) {
                $q->where('tax_object_id', $taxObject->id)
                  ->orWhere('taxpayer_id', $taxObject->taxpayer_id);
            })
            ->orderBy('id', 'desc')
            ->get();

        $activeBill = $bills->where('status', '!=', 'PAID')->first() ?? $bills->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $taxObject->id,
                'name' => $taxObject->name,
                'address' => $taxObject->address,
                'building_category' => $taxObject->building_category ?? 'RUMAH_SEDERHANA',
                'tipe_penghuni' => $taxObject->tipe_penghuni ?? 'PEMILIK',
                'status_hunian' => $taxObject->status_hunian ?? 'BERPENGHUNI',
                'taxpayer_name' => $taxObject->penanggung_jawab_nama ?? $taxObject->name,
                'active_bill' => $activeBill ? [
                    'id' => $activeBill->id,
                    'bill_number' => $activeBill->bill_number,
                    'period' => $activeBill->period ?? date('Y-m'),
                    'amount' => (float) $activeBill->amount,
                    'penalty_amount' => (float) $activeBill->penalty_amount,
                    'total_amount' => (float) ($activeBill->amount + $activeBill->penalty_amount + $activeBill->admin_fee),
                    'status' => $activeBill->status,
                    'due_date' => $activeBill->due_date ? Carbon::parse($activeBill->due_date)->toDateString() : null,
                ] : null,
                'bill_history' => $bills->take(5)->map(fn ($b) => [
                    'id' => $b->id,
                    'bill_number' => $b->bill_number,
                    'period' => $b->period,
                    'total_amount' => (float) $b->amount,
                    'status' => $b->status,
                    'due_date' => $b->due_date,
                ])->values(),
            ],
        ]);
    }
}
