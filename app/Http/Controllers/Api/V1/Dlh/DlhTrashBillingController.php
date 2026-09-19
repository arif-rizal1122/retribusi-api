<?php

namespace App\Http\Controllers\Api\V1\Dlh;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
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
        $this->authorizeDlhOperator($request);

        $taxObject = TaxObject::withoutGlobalScopes()
            ->whereKey($taxObjectId)
            ->where(function ($q) {
                $q->where('opd_id', 4)
                    ->orWhere('retribution_classification_id', 198);
            })
            ->firstOrFail();

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
        $this->authorizeDlhOperator($request);

        $validated = $request->validate([
            'period' => 'nullable|date_format:Y-m',
            'district' => 'nullable|string',
            'sub_district' => 'nullable|string',
        ]);

        $periodDate = ! empty($validated['period'])
            ? Carbon::createFromFormat('Y-m', $validated['period'])
            : Carbon::now();

        $query = TaxObject::where(function ($q) {
            $q->where('retribution_classification_id', 198)
                ->orWhere('opd_id', 4);
        })
            ->where('is_active', true)
            ->where('status_hunian', 'BERPENGHUNI'); // Rumah kosong tidak ditagih

        if (! empty($validated['district'])) {
            $query->where('district', $validated['district']);
        }
        if (! empty($validated['sub_district'])) {
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
        $validated = $request->validate([
            // NOP is the stable building/object identifier currently available in
            // tax_objects. Names, addresses, NIK and the database id are not
            // inquiry keys because they are ambiguous or expose unrelated data.
            'query' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'],
        ]);
        $buildingId = trim($validated['query']);
        $user = $request->user();

        $taxObject = TaxObject::withoutGlobalScopes()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('opd_id', 4)
                    ->orWhere('retribution_classification_id', 198);
            })
            ->whereRaw('LOWER(nop) = ?', [strtolower($buildingId)])
            // A citizen may inspect only their own building. Internal users keep
            // the operational inquiry capability behind the authenticated route.
            ->when($user instanceof Taxpayer, fn ($q) => $q->where('taxpayer_id', $user->id))
            ->first();

        if (! $taxObject) {
            return response()->json([
                'status' => 'error',
                'message' => 'ID Bangunan persampahan tidak ditemukan atau bukan milik akun ini.',
            ], 404);
        }

        $bills = Bill::withoutGlobalScopes()
            ->where('opd_id', 4)
            ->where('tax_object_id', $taxObject->id)
            ->orderBy('id', 'desc')
            ->get();

        $activeBill = $bills->first(function (Bill $bill) {
            $status = strtoupper((string) $bill->getRawOriginal('status'));

            return in_array($status, ['UNPAID', 'PENDING', 'OVERDUE'], true);
        }) ?? $bills->first();

        $billPayload = function (Bill $bill): array {
            $rawStatus = strtoupper((string) $bill->getRawOriginal('status'));

            return [
                'id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'period' => $bill->period ?? date('Y-m'),
                'amount' => (float) $bill->amount,
                'penalty_amount' => (float) $bill->penalty_amount,
                'total_amount' => (float) $bill->total_amount,
                'status' => $rawStatus,
                'due_date' => $bill->due_date?->toDateString(),
            ];
        };

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $taxObject->id,
                'object_code' => $taxObject->nop,
                'name' => $taxObject->name,
                'address' => $taxObject->address,
                'building_category' => $taxObject->building_category ?? 'RUMAH_SEDERHANA',
                'tipe_penghuni' => $taxObject->tipe_penghuni ?? 'PEMILIK',
                'status_hunian' => $taxObject->status_hunian ?? 'BERPENGHUNI',
                'taxpayer_name' => $taxObject->penanggung_jawab_nama ?? $taxObject->name,
                'active_bill' => $activeBill ? $billPayload($activeBill) : null,
                'bill_history' => $bills->take(5)->map($billPayload)->values(),
            ],
        ]);
    }

    private function authorizeDlhOperator(Request $request): void
    {
        $user = $request->user();
        $isDlhOperator = $user instanceof User
            && ($user->isSuperAdmin() || (int) $user->opd_id === 4);

        abort_unless($isDlhOperator, 403, 'Aksi ini hanya dapat dilakukan oleh petugas internal DLH.');
    }
}
