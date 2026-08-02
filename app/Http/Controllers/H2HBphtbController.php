<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BpnH2hMapping;
use App\Models\BphtbDocument;
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
     * Tampilkan detail submission + status pembayaran + dokumen.
     */
    public function show(Request $request, $submission)
    {
        $submission = BphtbSubmission::with(['bill', 'documents'])->findOrFail($submission);

        return response()->json([
            'data' => $submission,
            'bill' => $submission->bill,
            'documents' => $submission->documents,
        ]);
    }

    /**
     * Dashboard PPAT — daftar submission milik notaris yang login.
     */
    public function mySubmissions(Request $request)
    {
        $query = BphtbSubmission::query()
            ->with(['bill', 'documents'])
            ->where('ppat_user_id', $request->user()?->id);

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search !== '') {
            $query->where(function ($q) use ($request) {
                $q->where('billing_code', 'like', "%{$request->search}%")
                  ->orWhere('nib', 'like', "%{$request->search}%")
                  ->orWhere('nama_wp', 'like', "%{$request->search}%")
                  ->orWhere('nik_wp', 'like', "%{$request->search}%")
                  ->orWhere('nop', 'like', "%{$request->search}%");
            });
        }

        return response()->json($query->orderByDesc('id')->paginate(15));
    }

    /**
     * Upload dokumen pendukung submission (AJB, KTP, NPWP, SPPT, akta, SK).
     */
    public function uploadDocument(Request $request, $submission)
    {
        $submission = BphtbSubmission::findOrFail($submission);

        $user = $request->user();
        $isOwner = $submission->ppat_user_id === $user?->id;
        $isBackoffice = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

        if (! $isOwner && ! $isBackoffice) {
            return response()->json(['message' => 'Anda tidak berhak mengunggah dokumen submission ini.'], 403);
        }

        $request->validate([
            'document_type' => 'required|in:ajb,ktp,npwp,sppt,akta,sk,other',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $fileName = 'bphtb-' . $submission->id . '-' . time() . '-' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('bphtb_documents/' . $submission->id, $fileName, 'public');

        $document = BphtbDocument::create([
            'bphtb_submission_id' => $submission->id,
            'document_type' => $request->document_type,
            'file_path' => $filePath,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => $user?->id,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Dokumen berhasil diunggah.',
            'data' => $document,
        ], 201);
    }

    /**
     * Hapus dokumen pendukung.
     */
    public function deleteDocument(Request $request, $submission, $document)
    {
        $submission = BphtbSubmission::findOrFail($submission);

        $user = $request->user();
        $isOwner = $submission->ppat_user_id === $user?->id;
        $isBackoffice = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

        if (! $isOwner && ! $isBackoffice) {
            return response()->json(['message' => 'Anda tidak berhak menghapus dokumen submission ini.'], 403);
        }

        $document = BphtbDocument::where('bphtb_submission_id', $submission->id)->findOrFail($document);

        if (\Storage::disk('public')->exists($document->file_path)) {
            \Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json(['message' => 'Dokumen dihapus.']);
    }

    /**
     * F4.1 — Terbitkan SKB digital (role admin/petugas Bapenda).
     * Prasyarat: submission lunas. Output: nomor SKB + QR hash (TTE digital).
     */
    public function issueSkb(Request $request, $submission)
    {
        $user = $request->user();
        $isBackoffice = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

        if (! $isBackoffice && ! in_array($user?->role, ['petugas', 'kepala_bapenda', 'kabid_pengawas', 'kasubid_pengawas'])) {
            return response()->json(['message' => 'Hanya petugas/admin Bapenda yang dapat menerbitkan SKB.'], 403);
        }

        $submission = BphtbSubmission::with('bill')->findOrFail($submission);

        if (! $submission->isPaid()) {
            return response()->json(['message' => 'SKB hanya dapat diterbitkan setelah pembayaran lunas.'], 422);
        }
        if ($submission->skb_number) {
            return response()->json(['message' => 'SKB sudah diterbitkan.', 'data' => $submission], 200);
        }

        // Nomor SKB: SKB-BPHTB-YYYY-XXXXXX
        $year = now()->year;
        $seq = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        $skbNumber = "SKB-BPHTB-{$year}-{$seq}";
        $qrHash = hash('sha256', $skbNumber . '|' . $submission->id . '|' . config('app.key'));

        $submission->update([
            'status' => 'skb_issued',
            'skb_number' => $skbNumber,
            'skb_issued_at' => now(),
            'skb_issued_by' => $user?->id,
            'skb_qr_hash' => $qrHash,
        ]);

        Log::info('BPHTB SKB issued', [
            'submission_id' => $submission->id,
            'skb_number' => $skbNumber,
            'by' => $user?->id,
        ]);

        return response()->json([
            'message' => 'SKB digital berhasil diterbitkan.',
            'data' => $submission->fresh(),
        ], 201);
    }

    /**
     * F4.1 — Verifikasi keaslian SKB digital (publik, tanpa login).
     * GET /api/public/skb/verify/{skbNumber}
     */
    public function verifySkb(Request $request, $skbNumber)
    {
        $submission = BphtbSubmission::where('skb_number', $skbNumber)
            ->with('bill')
            ->first();

        if (! $submission || ! $submission->skb_issued_at) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Nomor SKB tidak ditemukan atau belum diterbitkan.',
            ], 404);
        }

        return response()->json([
            'status' => 'valid',
            'message' => 'SKB terverifikasi — dokumen asli diterbitkan Bapenda Kota Baubau.',
            'data' => [
                'skb_number' => $submission->skb_number,
                'skb_issued_at' => $submission->skb_issued_at?->toIso8601String(),
                'status_submission' => $submission->status,
                'nop' => $submission->nop,
                'nib' => $submission->nib,
                'nama_wp' => $submission->nama_wp,
                'nik_wp' => $submission->nik_wp,
                'npoptkp' => $submission->bill?->metadata['npoptkp'] ?? null,
                'taxable' => $submission->bill?->metadata['taxable'] ?? null,
                'tax' => $submission->bill?->amount,
                'ntpd' => $submission->ntpd,
                'paid_at' => $submission->paid_at?->toIso8601String(),
            ],
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
