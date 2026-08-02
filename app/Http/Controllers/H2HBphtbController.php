<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BpnH2hMapping;
use App\Models\BphtbSubmission;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestItem;
use App\Models\RetributionClassification;
use App\Models\Taxpayer;
use App\Services\FormulaParserService;
use App\Services\Payment\Snap\SnapBrivaReadiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class H2HBphtbController extends Controller
{
    protected FormulaParserService $formulaService;

    public function __construct(FormulaParserService $formulaService)
    {
        $this->formulaService = $formulaService;
    }

    public function mappings(Request $request)
    {
        $query = BpnH2hMapping::query();
        if ($request->has('search')) {
            $query->where('nib', 'like', "%{$request->search}%")
                  ->orWhere('nop', 'like', "%{$request->search}%");
        }
        return response()->json($query->paginate(15));
    }

    public function simulate(Request $request)
    {
        $request->validate([
            'nib' => 'required|string',
            'npop' => 'required|numeric|min:0',
            'acquisition_type' => 'nullable|string'
        ]);

        $mapping = BpnH2hMapping::where('nib', $request->nib)->first();
        $zntValue = $mapping ? $mapping->znt_value : null;

        $type = $request->acquisition_type ?: 'umum';

        $calculation = $this->formulaService->calculateBPHTB($request->npop, $type, $zntValue);

        return response()->json([
            'mapping_found' => (bool)$mapping,
            'calculation' => $calculation
        ]);
    }

    public function submit(Request $request)
    {
        $request->validate([
            'nib' => 'required|string',
            'npop' => 'required|numeric|min:0',
            'acquisition_type' => 'nullable|string',
            'nik_wp' => 'required|string|size:16',
            'nama_wp' => 'required|string|max:255',
            'alamat_wp' => 'nullable|string',
        ]);

        $mapping = BpnH2hMapping::where('nib', $request->nib)->first();
        $zntValue = $mapping ? $mapping->znt_value : null;
        $type = $request->acquisition_type ?: 'umum';

        $calculation = $this->formulaService->calculateBPHTB($request->npop, $type, $zntValue);
        $billingCode = 'BPHTB-' . strtoupper(Str::random(10));

        try {
            $result = DB::transaction(function () use ($request, $mapping, $calculation, $billingCode) {
                // 1. Resolve / auto-create Taxpayer (wajib pajak/pembeli) by NIK
                $taxpayer = Taxpayer::firstOrCreate(
                    ['nik' => $request->nik_wp],
                    [
                        'name' => $request->nama_wp,
                        'address' => $request->alamat_wp ?? '',
                        'is_active' => 1,
                    ]
                );

                // 2. Retribution type BPHTB (classification id 187)
                $class = RetributionClassification::find(187);
                $typeId = $class?->retribution_type_id ?? 16;

                // 3. Buat Bill BPHTB (tagihan yang bisa dibayar via alur existing)
                $bill = Bill::create([
                    'user_id' => $request->user()?->id,
                    'taxpayer_id' => $taxpayer->id,
                    'retribution_type_id' => $typeId,
                    'retribution_classification_id' => 187,
                    'bill_number' => $billingCode,
                    'amount' => $calculation['tax'],
                    'status' => 'pending',
                    'metadata' => [
                        'bphtb' => true,
                        'nib' => $request->nib,
                        'nop' => $mapping?->nop,
                        'reported_npop' => $calculation['npop_reported'],
                        'final_npop' => $calculation['final_npop'],
                        'znt_applied' => $calculation['znt_applied'],
                        'status_flag' => $calculation['status_flag'],
                        'npoptkp' => $calculation['npoptkp'],
                        'taxable' => $calculation['taxable'],
                        'nama_wp' => $request->nama_wp,
                        'nik_wp' => $request->nik_wp,
                    ],
                ]);

                // 4. Submission terhubung bill
                $submission = BphtbSubmission::create([
                    'nib' => $request->nib,
                    'nop' => $mapping?->nop,
                    'nik_wp' => $request->nik_wp,
                    'nama_wp' => $request->nama_wp,
                    'alamat_wp' => $request->alamat_wp,
                    'ppat_user_id' => $request->user()?->id,
                    'reported_npop' => $calculation['npop_reported'],
                    'znt_applied' => $calculation['znt_applied'],
                    'final_npop' => $calculation['final_npop'],
                    'status_flag' => $calculation['status_flag'],
                    'billing_code' => $billingCode,
                    'status' => 'submitted',
                    'bill_id' => $bill->id,
                ]);

                return ['submission' => $submission, 'bill' => $bill];
            });
        } catch (\Throwable $e) {
            Log::error('BPHTB submit error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal submit e-BPHTB: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'e-BPHTB berhasil di-submit',
            'data' => $result['submission'],
            'bill' => $result['bill'],
            'calculation' => $calculation
        ], 201);
    }

    /**
     * Tampilkan detail submission + status pembayaran.
     */
    public function show(Request $request, $submission)
    {
        $submission = BphtbSubmission::with('bill')->findOrFail($submission);

        return response()->json([
            'data' => $submission,
            'bill' => $submission->bill,
        ]);
    }

    /**
     * Buat PaymentRequest BRIVA untuk submission BPHTB.
     * Diakses role notaris — PaymentRequest dibuat atas taxpayer (WP) dari Bill.
     */
    public function pay(Request $request, $submission)
    {
        $submission = BphtbSubmission::with('bill')->findOrFail($submission);

        if ($submission->status === 'paid') {
            return response()->json(['message' => 'Submission ini sudah lunas.'], 422);
        }
        if (! $submission->bill) {
            return response()->json(['message' => 'Tagihan BPHTB tidak ditemukan.'], 422);
        }

        $brivaReadiness = app(SnapBrivaReadiness::class);
        if (! $brivaReadiness->available()) {
            return response()->json(['message' => $brivaReadiness->publicMessage()], 422);
        }

        $bill = $submission->bill;
        $taxpayerId = $bill->taxpayer_id;

        // Reuse payment request aktif (anti duplikat)
        $existing = PaymentRequest::where('taxpayer_id', $taxpayerId)
            ->where('bill_id', $bill->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($existing) {
            return response()->json(['data' => $this->payload($existing)]);
        }

        $partnerServiceId = preg_replace('/\D/', '', (string) config('snap.briva.partner_service_id'));
        $prefix = preg_replace('/\D/', '', (string) config('snap.briva.va_prefix'));
        $customerNoLength = (int) config('snap.briva.customer_no_length', 20);
        $length = (int) config('snap.briva.va_length', 28);
        $expiresAt = now()->addMinutes((int) config('snap.briva.payment_request_expiry_minutes', 1440));

        try {
            $paymentRequest = DB::transaction(function () use ($bill, $taxpayerId, $partnerServiceId, $prefix, $customerNoLength, $length, $expiresAt) {
                $pr = PaymentRequest::create([
                    'bill_id' => $bill->id,
                    'tax_object_id' => $bill->tax_object_id,
                    'taxpayer_id' => $taxpayerId,
                    'payment_channel' => 'BRI',
                    'method' => 'VA',
                    'amount_snapshot' => (float) $bill->amount,
                    'admin_fee_snapshot' => (float) $bill->admin_fee,
                    'penalty_snapshot' => 0,
                    'expires_at' => $expiresAt,
                    'external_id' => 'BPHTB-' . Str::upper(Str::random(16)),
                    'status' => 'pending',
                ]);

                $pr->update([
                    'va_number' => $this->buildVaNumber($pr->id, $partnerServiceId, $prefix, $customerNoLength, $length),
                ]);

                PaymentRequestItem::create([
                    'payment_request_id' => $pr->id,
                    'bill_id' => $bill->id,
                    'amount_snapshot' => (float) ($bill->total_amount ?? $bill->amount),
                    'status' => 'pending',
                ]);

                return $pr->fresh();
            });
        } catch (\Throwable $e) {
            Log::error('BPHTB pay error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat pembayaran: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json(['data' => $this->payload($paymentRequest)], 201);
    }

    private function payload(PaymentRequest $pr): array
    {
        return [
            'id' => $pr->id,
            'payment_channel' => $pr->payment_channel,
            'method' => $pr->method,
            'va_number' => $pr->va_number,
            'amount' => $pr->amount_snapshot,
            'admin_fee' => $pr->admin_fee_snapshot,
            'total' => (float) $pr->amount_snapshot + (float) $pr->admin_fee_snapshot,
            'expires_at' => $pr->expires_at,
            'status' => $pr->status,
            'external_id' => $pr->external_id,
            'bill_id' => $pr->bill_id,
        ];
    }

    private function buildVaNumber(
        int $paymentRequestId,
        string $partnerServiceId,
        string $legacyPrefix,
        int $customerNoLength,
        int $vaLength
    ): string {
        if ($partnerServiceId !== '') {
            $customerNo = str_pad((string) $paymentRequestId, $customerNoLength, '0', STR_PAD_LEFT);
            $vaNumber = $partnerServiceId.$customerNo;
        } else {
            $vaNumber = $legacyPrefix.str_pad(
                (string) $paymentRequestId,
                $vaLength - strlen($legacyPrefix),
                '0',
                STR_PAD_LEFT
            );
        }

        if (! preg_match('/^\d+$/', $vaNumber) || strlen($vaNumber) > $vaLength) {
            throw new \RuntimeException('BRIVA VA format is invalid or exceeds configured length.');
        }

        return $vaNumber;
    }
}
