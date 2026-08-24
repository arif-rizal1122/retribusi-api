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

    /**
     * List semua submission BPHTB (role petugas/admin Bapenda).
     * GET /api/h2h/bphtb
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $this->isBphtbBackoffice($user)) {
            return response()->json(['message' => 'Hanya petugas/admin Bapenda yang dapat mengakses daftar BPHTB.'], 403);
        }

        $query = BphtbSubmission::query()->with(['bill', 'documents']);

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search !== '') {
            $query->where(function ($q) use ($request) {
                $q->where('billing_code', 'like', "%{$request->search}%")
                  ->orWhere('nib', 'like', "%{$request->search}%")
                  ->orWhere('nop', 'like', "%{$request->search}%")
                  ->orWhere('nama_wp', 'like', "%{$request->search}%")
                  ->orWhere('nik_wp', 'like', "%{$request->search}%");
            });
        }

        return response()->json($query->orderByDesc('id')->paginate(15));
    }

    /**
     * GET /api/h2h/bphtb
     * Backoffice melihat semua submission; PPAT/notaris melihat miliknya.
     */
    public function submissions(Request $request)
    {
        if ($this->isBphtbBackoffice($request->user())) {
            return $this->index($request);
        }

        return $this->mySubmissions($request);
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
            'luas_tanah' => 'nullable|numeric|min:0',
            'luas_bangunan' => 'nullable|numeric|min:0',
            'njop_per_m2' => 'nullable|numeric|min:0',
            'alamat_objek' => 'nullable|string',
            'jenis_perolehan' => 'nullable|string',
            'no_sertifikat' => 'nullable|string',
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
                    'luas_tanah' => $request->luas_tanah,
                    'luas_bangunan' => $request->luas_bangunan,
                    'njop_per_m2' => $request->njop_per_m2,
                    'alamat_objek' => $request->alamat_objek,
                    'jenis_perolehan' => $request->jenis_perolehan,
                    'no_sertifikat' => $request->no_sertifikat,
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
            'document_type' => 'required|in:ajb,ktp,npwp,sppt,akta,sk,foto_objek,sketsa_lokasi,kwitansi,surat_ukur,bukti_bayar_pbb,other',
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
     * F5 — Verifikasi dokumen oleh Bapenda + terbitkan/tautkan NOP.
     * Konsep Pak Massad: NOP diterbitkan Bapenda setelah notaris unggah dokumen.
     * POST /api/h2h/bphtb/{submission}/verify
     * Body: { nop?: string, notes?: string }
     */
    public function verify(Request $request, $submission)
    {
        $user = $request->user();
        $isBackoffice = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

        if (! $isBackoffice && ! in_array($user?->role, ['petugas', 'kepala_bapenda', 'kabid_pengawas', 'kasubid_pengawas'])) {
            return response()->json(['message' => 'Hanya petugas/admin Bapenda yang dapat memverifikasi berkas.'], 403);
        }

        $submission = BphtbSubmission::findOrFail($submission);

        if ($submission->status === 'sspd_issued') {
            return response()->json(['message' => 'Berkas sudah selesai (SSPD terbit), tidak bisa diverifikasi ulang.'], 422);
        }

        $request->validate([
            'nop' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        // NOP: prioritas dari request, fallback dari mapping BPN, lalu existing
        $nop = $request->nop ?: $submission->nop;
        if (! $nop) {
            $mapping = BpnH2hMapping::where('nib', $submission->nib)->first();
            $nop = $mapping?->nop;
        }
        if (! $nop) {
            return response()->json([
                'message' => 'NOP wajib diisi (atau tautkan NIB ke mapping BPN) untuk menerbitkan NOP.',
            ], 422);
        }

        $submission->update([
            'nop' => $nop,
            'status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $user?->id,
            'verified_notes' => $request->notes,
        ]);

        // Tautkan NOP ke mapping NIB (jika belum ada mapping, buatkan)
        BpnH2hMapping::updateOrCreate(
            ['nib' => $submission->nib],
            ['nop' => $nop]
        );

        Log::info('BPHTB verified + NOP issued', [
            'submission_id' => $submission->id,
            'nop' => $nop,
            'by' => $user?->id,
        ]);

        return response()->json([
            'message' => 'Berkas terverifikasi, NOP diterbitkan.',
            'data' => $submission->fresh(),
        ]);
    }

    /**
     * F4.1 — Terbitkan SSPD digital (role admin/petugas Bapenda).
     * Prasyarat: submission lunas. Output: nomor SSPD + QR hash (TTE digital).
     */
    public function issueSspd(Request $request, $submission)
    {
        $user = $request->user();
        $isBackoffice = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

        if (! $isBackoffice && ! in_array($user?->role, ['petugas', 'kepala_bapenda', 'kabid_pengawas', 'kasubid_pengawas'])) {
            return response()->json(['message' => 'Hanya petugas/admin Bapenda yang dapat menerbitkan SSPD.'], 403);
        }

        $submission = BphtbSubmission::with('bill')->findOrFail($submission);

        if (! $submission->isPaid()) {
            return response()->json(['message' => 'SSPD hanya dapat diterbitkan setelah pembayaran lunas.'], 422);
        }
        if ($submission->sspd_number) {
            return response()->json(['message' => 'SSPD sudah diterbitkan.', 'data' => $submission], 200);
        }

        // Nomor SSPD: SSPD-BPHTB-YYYY-XXXXXX
        $year = now()->year;
        $seq = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        $sspdNumber = "SSPD-BPHTB-{$year}-{$seq}";
        $qrHash = hash('sha256', $sspdNumber . '|' . $submission->id . '|' . config('app.key'));

        $submission->update([
            'status' => 'sspd_issued',
            'sspd_number' => $sspdNumber,
            'sspd_issued_at' => now(),
            'sspd_issued_by' => $user?->id,
            'sspd_qr_hash' => $qrHash,
        ]);

        Log::info('BPHTB SSPD issued', [
            'submission_id' => $submission->id,
            'sspd_number' => $sspdNumber,
            'by' => $user?->id,
        ]);

        return response()->json([
            'message' => 'SSPD digital berhasil diterbitkan.',
            'data' => $submission->fresh(),
        ], 201);
    }

    /**
     * F7 — Unduh SSPD-BPHTB dalam format PDF (Bapenda layout).
     * GET /api/h2h/bphtb/{submission}/sspd/pdf
     * Hanya tersedia setelah SSPD diterbitkan (sspd_number terisi).
     */
    public function sspdPdf(Request $request, $submission)
    {
        $submission = BphtbSubmission::with('bill')->findOrFail($submission);

        if (! $submission->sspd_number) {
            return response()->json(['message' => 'SSPD belum diterbitkan.'], 422);
        }

        $bill = $submission->bill;
        $metadata = $bill ? (array) ($bill->metadata ?? []) : [];

        $npoptkp = (float) ($metadata['npoptkp'] ?? 60000000);
        $npop    = (float) ($metadata['final_npop'] ?? $submission->final_npop ?? $bill?->amount ?? 0);
        $taxable = (float) ($metadata['taxable'] ?? max($npop - $npoptkp, 0));
        $bphtb   = (float) ($bill?->amount ?? 0);

        // Detail objek — fallback ke 0 bila tidak diisi.
        $luasTanah     = (float) ($submission->luas_tanah ?? 0);
        $luasBangunan  = (float) ($submission->luas_bangunan ?? 0);
        $njopPerM2     = (float) ($submission->njop_per_m2 ?? 0);
        $nilaiTanah    = $luasTanah * $njopPerM2;
        $nilaiBangunan = $luasBangunan * $njopPerM2;
        $totalNjopPbb  = $nilaiTanah + $nilaiBangunan;

        // QR code dari sspd_qr_hash (SVG base64, PHP murni — tanpa imagick).
        $qrImage = null;
        if ($submission->sspd_qr_hash) {
            try {
                $verifyUrl = config('app.url') . '/api/public/sspd/verify/' . $submission->sspd_number;
                $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(160)
                    ->margin(1)
                    ->generate($verifyUrl);
                $qrImage = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
            } catch (\Throwable $e) {
                $qrImage = null;
            }
        }

        $data = [
            'submission'        => $submission,
            'sspd_number'       => $submission->sspd_number,
            'nama_wp'           => $submission->nama_wp,
            'nik_wp'            => $submission->nik_wp,
            'alamat_wp'         => $submission->alamat_wp,
            'nop'               => $submission->nop,
            'alamat_objek'      => $submission->alamat_objek,
            'luas_tanah'        => $luasTanah,
            'luas_bangunan'     => $luasBangunan,
            'njop_per_m2'       => $njopPerM2,
            'nilai_tanah'       => $nilaiTanah,
            'nilai_bangunan'    => $nilaiBangunan,
            'total_njop_pbb'    => $totalNjopPbb,
            'jenis_perolehan'   => $submission->jenis_perolehan,
            'no_sertifikat'     => $submission->no_sertifikat,
            'harga_transaksi'   => $npop,
            'npop'              => $npop,
            'npoptkp'           => $npoptkp,
            'npopkp'            => $taxable,
            'bphtb'             => $bphtb,
            'jumlah_disetor'    => $bphtb,
            'terbilang'         => \App\Services\OfficialDocumentService::terbilang((int) round($bphtb)),
            'qr_image'          => $qrImage,
            'issued_at'         => $submission->sspd_issued_at ?? now(),
            'paid_at'           => $submission->paid_at,
        ];

        $filename = 'SSPD-BPHTB-' . str_replace(['/', '\\'], '-', $submission->sspd_number) . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.sspd_bphtb', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }

    /**
     * F7-publik — Unduh SSPD-BPHTB PDF via nomor SSPD (tanpa login).
     * GET /api/public/sspd/pdf/{sspdNumber}
     */
    public function sspdPdfByNumber(Request $request, $sspdNumber)
    {
        $submission = BphtbSubmission::where('sspd_number', $sspdNumber)->first();

        if (! $submission) {
            return response()->json(['message' => 'SSPD tidak ditemukan.'], 404);
        }

        return $this->sspdPdf($request, $submission->id);
    }

    /**
     * F4.1 — Verifikasi keaslian SSPD digital (publik, tanpa login).
     * GET /api/public/sspd/verify/{sspdNumber}
     */
    public function verifySspd(Request $request, $sspdNumber)
    {
        $submission = BphtbSubmission::where('sspd_number', $sspdNumber)
            ->with('bill')
            ->first();

        if (! $submission || ! $submission->sspd_issued_at) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Nomor SSPD tidak ditemukan atau belum diterbitkan.',
            ], 404);
        }

        return response()->json([
            'status' => 'valid',
            'message' => 'SSPD terverifikasi — dokumen asli diterbitkan Bapenda Kota Baubau.',
            'data' => [
                'sspd_number' => $submission->sspd_number,
                'sspd_issued_at' => $submission->sspd_issued_at?->toIso8601String(),
                'status_submission' => $submission->status,
                'nop' => $submission->nop,
                'nib' => $submission->nib,
                'nama_wp' => $submission->nama_wp,
                'nik_wp' => $this->maskNik($submission->nik_wp),
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

    private function isBphtbBackoffice($user): bool
    {
        $allowedRoles = ['petugas', 'opd', 'kepala_bapenda', 'kabid_pengawas', 'kasubid_pengawas'];

        return $user
            && method_exists($user, 'isSuperAdmin')
            && ($user->isSuperAdmin() || in_array($user->role, $allowedRoles, true));
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

    private function maskNik(?string $nik): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $nik);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) <= 10) {
            return substr($digits, 0, 4).str_repeat('*', max(strlen($digits) - 4, 0));
        }

        return substr($digits, 0, 6).str_repeat('*', max(strlen($digits) - 10, 0)).substr($digits, -4);
    }
}
