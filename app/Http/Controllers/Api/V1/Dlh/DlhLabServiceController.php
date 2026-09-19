<?php

namespace App\Http\Controllers\Api\V1\Dlh;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\DlhLabOrder;
use App\Models\DlhLabOrderParameter;
use App\Models\DlhLabParameter;
use App\Models\Taxpayer;
use App\Models\User;
use App\Support\CitizenBillStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DlhLabServiceController extends Controller
{
    /**
     * Ambil daftar 7 parameter laboratorium lingkungan aktif
     */
    public function indexParameters()
    {
        $parameters = DlhLabParameter::where('is_active', true)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $parameters,
        ]);
    }

    /**
     * Buat pemesanan/order pengujian sampel laboratorium baru
     */
    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'applicant_name' => 'required|string|max:255',
            'institution_name' => 'nullable|string|max:255',
            'contact_phone' => 'required|string|max:50',
            'applicant_address' => 'nullable|string',
            'sample_matrix' => 'required|string|in:AIR_LIMBAH,AIR_SUNGAI,AIR_BERSIH,AIR_LAUT',
            'sampling_location' => 'nullable|string|max:255',
            'sampling_date' => 'required|date',
            'parameter_ids' => 'required|array|min:1',
            'parameter_ids.*' => 'integer|distinct|exists:dlh_lab_parameters,id',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $parameterIds = collect($validated['parameter_ids'])->map(fn ($id) => (int) $id)->unique()->values();
            $parameters = DlhLabParameter::whereIn('id', $parameterIds)
                ->where('is_active', true)
                ->get();

            if ($parameters->count() !== $parameterIds->count()) {
                throw ValidationException::withMessages([
                    'parameter_ids' => 'Semua parameter pengujian harus aktif dan tersedia.',
                ]);
            }

            $user = $request->user();

            $totalAmount = $parameters->sum('tariff');
            $orderNumber = 'LAB-DLH-'.date('ymd').'-'.strtoupper(Str::random(5));

            $order = DlhLabOrder::create([
                'order_number' => $orderNumber,
                // Taxpayer is the citizen auth model. Internal users may still
                // create operational orders, but they must not impersonate a taxpayer.
                'taxpayer_id' => $user instanceof Taxpayer ? $user->getKey() : null,
                'applicant_name' => $validated['applicant_name'],
                'institution_name' => $validated['institution_name'] ?? null,
                'contact_phone' => $validated['contact_phone'],
                'applicant_address' => $validated['applicant_address'] ?? null,
                'sample_matrix' => $validated['sample_matrix'],
                'sampling_location' => $validated['sampling_location'] ?? null,
                'sampling_date' => $validated['sampling_date'],
                'total_amount' => $totalAmount,
                'status' => 'DRAFT',
            ]);

            // Simpan pivot parameter order
            $attachData = [];
            foreach ($parameters as $param) {
                $attachData[$param->id] = [
                    'tariff_snapshot' => $param->tariff,
                    'quality_standard_value' => $param->standard_reference,
                    'compliance_status' => 'PENDING',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            $order->parameters()->attach($attachData);

            return response()->json([
                'status' => 'success',
                'message' => 'Permohonan uji laboratorium berhasil didaftarkan.',
                'data' => [
                    'order' => $order->fresh(),
                    'parameters' => $parameters,
                ],
            ], 201);
        });
    }

    /**
     * Terbitkan tagihan (Billing/SKRD) untuk permohonan laboratorium
     */
    public function generateBilling(Request $request, $orderId)
    {
        $order = $this->findOrderForRequest($request, $orderId);
        if (! $order) {
            return response()->json(['status' => 'error', 'message' => 'Order tidak ditemukan.'], 404);
        }

        return DB::transaction(function () use ($order) {
            // Serialize billing per order. The bill_id check must happen after
            // this lock so two retries cannot both create a SKRD.
            $lockedOrder = DlhLabOrder::whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->bill_id) {
                $existingBill = Bill::find($lockedOrder->bill_id);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Tagihan sudah pernah diterbitkan.',
                    'data' => $this->billPayload($existingBill),
                ]);
            }

            $taxpayerId = $lockedOrder->taxpayer_id;
            if (! $taxpayerId) {
                $taxpayer = Taxpayer::withoutGlobalScopes()->firstOrCreate(
                    [
                        'opd_id' => 4,
                        'name' => $lockedOrder->applicant_name,
                    ],
                    [
                        'phone' => $lockedOrder->contact_phone,
                        'address' => $lockedOrder->applicant_address ?? 'Kota Baubau',
                        'is_active' => true,
                    ]
                );
                $taxpayerId = $taxpayer->id;
                $lockedOrder->update(['taxpayer_id' => $taxpayerId]);
            }

            $billNumber = 'SKRD-LAB-'.date('ymd').'-'.strtoupper(Str::random(5));
            $dueDate = Carbon::now()->addDays(7)->toDateString();

            $bill = Bill::create([
                'taxpayer_id' => $taxpayerId,
                'opd_id' => 4, // DLH
                'retribution_type_id' => 17, // Retribusi
                'bill_number' => $billNumber,
                'amount' => $lockedOrder->total_amount,
                'admin_fee' => 0.00,
                'penalty_amount' => 0.00,
                'status' => 'UNPAID',
                'period' => date('Y-m'),
                'period_start' => Carbon::now()->startOfMonth()->toDateString(),
                'period_end' => Carbon::now()->endOfMonth()->toDateString(),
                'due_date' => $dueDate,
                'metadata' => json_encode([
                    'type' => 'LAB_ENVIRONMENTAL',
                    'order_number' => $lockedOrder->order_number,
                    'applicant_name' => $lockedOrder->applicant_name,
                    'institution' => $lockedOrder->institution_name,
                    'matrix' => $lockedOrder->sample_matrix,
                ]),
            ]);

            $lockedOrder->update([
                'bill_id' => $bill->id,
                'status' => 'BILLING_ISSUED',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Tagihan SKRD Laboratorium berhasil diterbitkan.',
                'data' => $this->billPayload($bill),
            ]);
        });
    }

    /**
     * Analis laboratorium menginput nilai hasil pengujian dan kesesuaian baku mutu
     */
    public function uploadResult(Request $request, $orderId)
    {
        abort_unless($request->user() instanceof User, 403, 'Input hasil hanya dapat dilakukan oleh petugas laboratorium.');

        $validated = $request->validate([
            'results' => 'required|array|min:1',
            'results.*.parameter_id' => 'required|integer',
            'results.*.result_value' => 'required|string',
            'results.*.compliance_status' => 'required|string|in:COMPLIANT,EXCEEDED',
            'lhu_document_url' => 'nullable|string',
        ]);

        $order = $this->findOrderForRequest($request, $orderId);
        if (! $order) {
            return response()->json(['status' => 'error', 'message' => 'Order tidak ditemukan.'], 404);
        }

        return DB::transaction(function () use ($order, $validated) {
            foreach ($validated['results'] as $item) {
                DlhLabOrderParameter::where('lab_order_id', $order->id)
                    ->where('parameter_id', $item['parameter_id'])
                    ->update([
                        'result_value' => $item['result_value'],
                        'compliance_status' => $item['compliance_status'],
                        'updated_at' => now(),
                    ]);
            }

            $order->update([
                'status' => 'COMPLETED',
                'lhu_document_url' => $validated['lhu_document_url'] ?? $order->lhu_document_url,
                'tte_status' => 'PENDING', // Menunggu tanda tangan digital Kepala UPTD
                'completed_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Hasil uji laboratorium berhasil direkam. LHU siap untuk otorisasi TTE.',
            ]);
        });
    }

    /**
     * Tampilkan detail order laboratorium
     */
    public function showOrder(Request $request, $orderId)
    {
        $order = $this->findOrderForRequest($request, $orderId, ['bill', 'orderParameters.parameter']);
        if (! $order) {
            return response()->json(['status' => 'error', 'message' => 'Order tidak ditemukan.'], 404);
        }

        return $this->orderDetailResponse($order);
    }

    /**
     * Tampilkan detail order berdasarkan nomor yang diberikan kepada warga.
     */
    public function showOrderByNumber(Request $request, string $orderNumber)
    {
        $order = $this->findOrderByNumberForRequest($request, $orderNumber, ['bill', 'orderParameters.parameter']);
        if (! $order) {
            return response()->json(['status' => 'error', 'message' => 'Order tidak ditemukan.'], 404);
        }

        return $this->orderDetailResponse($order);
    }

    private function orderDetailResponse(DlhLabOrder $order)
    {
        $parameters = $order->orderParameters->map(function ($op) {
            return [
                'code' => $op->parameter?->code,
                'name' => $op->parameter?->name,
                'method' => $op->parameter?->method,
                'unit' => $op->parameter?->unit,
                'tariff_snapshot' => $op->tariff_snapshot,
                'result_value' => $op->result_value,
                'quality_standard_value' => $op->quality_standard_value,
                'compliance_status' => $op->compliance_status,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => $order,
                'bill' => $this->billPayload($order->bill),
                'parameters' => $parameters,
            ],
        ]);
    }

    /**
     * Expose the shared citizen bill status contract without rewriting storage.
     *
     * Lab billing historically returned raw values such as UNPAID/PAID. Keep
     * the existing bill fields and add the canonical status presentation used
     * by the mobile payment flow.
     */
    private function billPayload(?Bill $bill): ?array
    {
        if (! $bill) {
            return null;
        }

        $hasPendingClaim = $bill->payments()
            ->whereRaw('LOWER(status) = ?', ['pending'])
            ->exists();
        $hasActiveRequest = $bill->paymentRequestItems()
            ->whereHas('paymentRequest', function ($query) {
                $query->whereRaw('LOWER(status) = ?', ['pending'])
                    ->where('expires_at', '>', now());
            })
            ->exists();
        $status = CitizenBillStatus::normalize(
            (string) $bill->getRawOriginal('status'),
            $hasPendingClaim,
            $hasActiveRequest,
            (bool) $bill->due_date?->isPast(),
        );

        $payload = $bill->toArray();
        $payload['amount'] = (float) $bill->amount;
        $payload['status'] = $status;
        $payload['status_label'] = CitizenBillStatus::label($status, $hasActiveRequest);
        $payload['can_pay'] = CitizenBillStatus::canPay($status);

        return $payload;
    }

    /**
     * Verifikasi Publik Dokumen LHU (Scan QR Code LHU Fisik)
     */
    public function verifyPublic($orderNumber)
    {
        $order = DlhLabOrder::with('orderParameters.parameter')
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            return response()->json(['status' => 'error', 'message' => 'Dokumen LHU tidak ditemukan.'], 404);
        }

        $parameters = $order->orderParameters->map(function ($op) {
            return [
                'name' => $op->parameter?->name,
                'unit' => $op->parameter?->unit,
                'result_value' => $op->result_value,
                'quality_standard_value' => $op->quality_standard_value,
                'compliance_status' => $op->compliance_status,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'institution' => 'UPTD Laboratorium Lingkungan Hidup Kota Baubau',
                'order_number' => $order->order_number,
                'applicant_name' => $order->applicant_name,
                'institution_name' => $order->institution_name,
                'sample_matrix' => $order->sample_matrix,
                'sampling_location' => $order->sampling_location,
                'sampling_date' => $order->sampling_date ? Carbon::parse($order->sampling_date)->toDateString() : null,
                'status' => $order->status,
                'tte_status' => $order->tte_status,
                'completed_at' => $order->completed_at,
                'parameters' => $parameters,
            ],
        ]);
    }

    /**
     * Citizen orders are owner-scoped; internal users retain the existing
     * operational access used by the laboratory workflow.
     */
    private function findOrderForRequest(Request $request, $orderId, array $relations = []): ?DlhLabOrder
    {
        return $this->orderQueryForRequest($request, $relations)->whereKey($orderId)->first();
    }

    private function findOrderByNumberForRequest(Request $request, string $orderNumber, array $relations = []): ?DlhLabOrder
    {
        return $this->orderQueryForRequest($request, $relations)
            ->where('order_number', $orderNumber)
            ->first();
    }

    private function orderQueryForRequest(Request $request, array $relations = [])
    {
        $query = DlhLabOrder::query()->with($relations);
        $user = $request->user();

        if ($user instanceof Taxpayer) {
            $query->where('taxpayer_id', $user->getKey());
        }

        return $query;
    }
}
