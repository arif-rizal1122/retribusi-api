<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use App\Services\BillCreationService;
use App\Services\BillPeriodService;
use App\Services\OfficialDocumentService;
use App\Services\Payment\Snap\BankSultraCitizenVaReadiness;
use App\Services\Payment\Snap\SnapBrivaReadiness;
use App\Support\CitizenBillStatus;
use App\Support\CitizenPaymentRequestStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BillController extends Controller
{
    protected $billCreationService;

    protected $periodService;

    public function __construct(
        BillCreationService $billCreationService,
        BillPeriodService $periodService,
        private readonly SnapBrivaReadiness $brivaReadiness,
        private readonly BankSultraCitizenVaReadiness $sultraVaReadiness
    ) {
        $this->billCreationService = $billCreationService;
        $this->periodService = $periodService;
    }

    /**
     * List bills (OPD-scoped)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Bill::with(['retributionType', 'user', 'opd', 'taxObject', 'taxpayer', 'classification']);

        if ($user && in_array($user->role, ['opd', 'petugas'])) {
            $query->where('opd_id', $user->opd_id);

            // If petugas, filter by assigned types and classifications, or bills issued by self
            if ($user->role === 'petugas') {
                $assignments = $user->assignments;
                if ($assignments->isNotEmpty()) {
                    $query->where(function ($q) use ($assignments, $user) {
                        $q->where('user_id', $user->id);
                        foreach ($assignments as $assignment) {
                            $q->orWhere(function ($sq) use ($assignment) {
                                $sq->where('retribution_type_id', $assignment->retribution_type_id);
                                if ($assignment->retribution_classification_id) {
                                    $sq->where('retribution_classification_id', $assignment->retribution_classification_id);
                                }
                            });
                        }
                    });
                } else {
                    // Petugas tanpa assignment eksplisit tetap dapat mengelola tagihan yang diterbitkannya sendiri
                    $query->where('user_id', $user->id);
                }
            }
        }

        if ($request->has('status')) {
            if ($request->status === 'overdue') {
                $query->where('status', 'pending')
                    ->where('due_date', '<', now());
            } elseif ($request->status === 'lunas') {
                $query->whereIn('status', ['lunas', 'paid']);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                    ->orWhereHas('taxpayer', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $billings = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($billings);
    }

    /**
     * Generate a single bill
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Support both new (tax_object_id) and legacy (taxpayer_id + retribution_type_id) flows
        $request->validate([
            'tax_object_id' => 'required_without_all:taxpayer_id,retribution_type_id|exists:tax_objects,id',
            'taxpayer_id' => 'required_without:tax_object_id|exists:taxpayers,id',
            'retribution_type_id' => 'required_without:tax_object_id|exists:retribution_types,id',
            'amount' => 'nullable|numeric|min:0',
            'period' => 'required|string',
            'due_date' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        if ($request->tax_object_id) {
            // New flow: bill is linked to a specific tax object
            $taxObject = TaxObject::with(['taxpayer', 'retributionType', 'classification'])->findOrFail($request->tax_object_id);

            if (! $user->isSuperAdmin() && $taxObject->opd_id !== $user->opd_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // [INTELLIGENCE] Auto-tag as manual audit result
            $metadata = $request->metadata ?? [];
            $metadata['is_manual'] = true;
            $metadata['source'] = 'admin_input';
            $metadata['created_at_role'] = $user->role;

            $bill = $this->billCreationService->createForTaxObject(
                $taxObject,
                $user,
                $request->period,
                $metadata,
                $metadata,
                $request->due_date,
                'admin_input',
                $request->filled('amount') ? (float) $request->amount : null,
                false
            );
        } else {
            // Legacy flow: bill is linked to taxpayer + retribution type (no specific object)
            $taxpayer = Taxpayer::find($request->taxpayer_id);

            if (! $user->isSuperAdmin() && $taxpayer->opd_id !== $user->opd_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $type = RetributionType::find($request->retribution_type_id);
            $periodInfo = $this->periodService->resolve($request->period, $type->billing_cycle ?? 'monthly');
            $metadata = $request->metadata ?? [];
            $metadata['source'] = $metadata['source'] ?? 'legacy_admin_input';
            $metadata['period_label'] = $periodInfo['label'];

            $bill = Bill::create([
                'user_id' => $user->id,
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => null,
                'opd_id' => $taxpayer->opd_id,
                'retribution_type_id' => $request->retribution_type_id,
                'bill_number' => 'INV-'.date('Ymd').'-'.strtoupper(Str::random(6)),
                'amount' => $request->amount ?? $type->base_amount,
                'status' => 'pending',
                'period' => $periodInfo['period'],
                'period_start' => $periodInfo['period_start'],
                'period_end' => $periodInfo['period_end'],
                'metadata' => $metadata,
                'due_date' => $request->due_date ? Carbon::parse($request->due_date) : $periodInfo['due_date'],
            ]);
        }

        return response()->json([
            'message' => 'Tagihan berhasil dibuat',
            'data' => $bill->load(['retributionType', 'opd', 'taxObject', 'taxpayer']),
        ], 201);
    }

    /**
     * Bulk generate bills for a retribution type based on tax objects
     */
    public function bulkStore(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'retribution_type_id' => 'required|exists:retribution_types,id',
            'retribution_classification_id' => 'nullable|exists:retribution_classifications,id',
            'period' => 'required|string',
            'due_date' => 'required|date',
        ]);

        $type = RetributionType::find($request->retribution_type_id);

        if (! $user->isSuperAdmin() && $type->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get all active tax objects for this type
        $query = TaxObject::where('retribution_type_id', $type->id)
            ->where('status', 'active')
            ->with(['retributionType', 'classification', 'taxpayer']);

        if ($request->retribution_classification_id) {
            $query->where('retribution_classification_id', $request->retribution_classification_id);
        }

        $objects = $query->get();

        $createdCount = 0;
        foreach ($objects as $obj) {
            $bill = $this->billCreationService->createForTaxObject(
                $obj,
                $user,
                $request->period,
                $obj->metadata ?? [],
                ['source' => 'bulk_admin_generate'],
                $request->due_date,
                'bulk_admin_generate',
                null,
                true
            );

            if ($bill->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        return response()->json([
            'message' => "Berhasil generate {$createdCount} tagihan",
            'count' => $createdCount,
        ]);
    }

    /**
     * Show bill details
     */
    public function show(Request $request, Bill $bill)
    {
        $user = $request->user();

        // Ownership / Authorization check
        if ($user instanceof User) {
            // Admin/Petugas: restrict by OPD if not super admin
            if (! $user->isSuperAdmin() && $bill->opd_id !== $user->opd_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif ($user instanceof Taxpayer) {
            // Citizen: restrict by their own record
            if ($bill->taxpayer_id !== $user->id) {
                return response()->json(['message' => 'Forbidden: This is not your bill.'], 403);
            }
        }

        return response()->json([
            'data' => $bill->load(['retributionType', 'opd', 'payments', 'taxObject', 'taxpayer']),
        ]);
    }

    /**
     * List bills owned by the authenticated citizen.
     */
    public function citizenBills(Request $request)
    {
        $taxpayer = $request->user();
        abort_unless($taxpayer instanceof Taxpayer, 403, 'Endpoint tagihan ini hanya untuk wajib pajak.');

        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $activePaymentRequest = fn ($paymentRequestQuery) => $paymentRequestQuery
            ->pending()
            ->where('expires_at', '>', now());

        $bills = Bill::with([
            'retributionType',
            'opd',
            'taxObject.classification',
            'classification',
            'paymentRequestItems' => fn ($query) => $query
                ->whereHas('paymentRequest', $activePaymentRequest)
                ->with(['paymentRequest' => $activePaymentRequest]),
        ])
            ->withExists([
                'payments as has_pending_payment_claim' => fn ($query) => $query->pending(),
                'paymentRequestItems as has_active_payment_request' => fn ($query) => $query->whereHas('paymentRequest', $activePaymentRequest),
            ])
            ->where('taxpayer_id', $taxpayer->id)
            ->latest()
            ->paginate($validated['per_page'] ?? 20);

        return response()->json([
            'data' => collect($bills->items())
                ->map(fn (Bill $bill) => $this->citizenBillPayload($bill))
                ->values(),
            'meta' => [
                'current_page' => $bills->currentPage(),
                'last_page' => $bills->lastPage(),
                'per_page' => $bills->perPage(),
                'total' => $bills->total(),
            ],
        ]);
    }

    private function citizenBillPayload(Bill $bill): array
    {
        $payload = $bill->toArray();
        $status = $this->citizenBillStatus($bill);

        unset($payload['has_pending_payment_claim'], $payload['has_active_payment_request']);

        $payload['status'] = $status;
        $payload['status_label'] = CitizenBillStatus::label($status, (bool) $bill->has_active_payment_request);
        $payload['can_pay'] = CitizenBillStatus::canPay($status);
        $payload['payment_options'] = $this->citizenPaymentOptions($bill, $payload['can_pay']);
        $payload['active_payment_request'] = $this->activePaymentRequestPayload($bill);

        return $payload;
    }

    private function activePaymentRequestPayload(Bill $bill): ?array
    {
        $paymentRequest = $bill->paymentRequestItems
            ->map(fn ($item) => $item->paymentRequest)
            ->filter()
            ->sortByDesc('id')
            ->first();

        if (! $paymentRequest) {
            return null;
        }

        $rawStatus = $paymentRequest->getRawOriginal('status');
        $status = CitizenPaymentRequestStatus::normalize($rawStatus);
        $isSultra = in_array(strtoupper((string) $paymentRequest->payment_channel), ['BANK_SULTRA', 'BANK SULTRA', 'SULTRA'], true);

        return [
            'id' => (string) $paymentRequest->id,
            'provider' => $paymentRequest->payment_channel,
            'method' => strtolower($paymentRequest->payment_channel) === 'bri' && strtoupper($paymentRequest->method) === 'VA'
                ? 'bri_va'
                : strtolower($paymentRequest->method),
            'status' => $status,
            'status_label' => CitizenPaymentRequestStatus::label($status, $isSultra),
            'va_number' => $paymentRequest->va_number,
            'expired_at' => $paymentRequest->expires_at?->toIso8601String(),
            'total_amount' => (float) $paymentRequest->amount_snapshot
                + (float) $paymentRequest->admin_fee_snapshot
                + (float) $paymentRequest->penalty_snapshot,
            'can_refresh' => $rawStatus === 'pending',
            'can_cancel' => $rawStatus === 'pending',
        ];
    }

    private function citizenPaymentOptions(Bill $bill, bool $canPay): array
    {
        $expiresAt = $bill->expiry_time;
        if (! $expiresAt && $bill->due_date?->isFuture()) {
            $expiresAt = $bill->due_date;
        }

        $bankAccounts = collect($bill->classification?->bank_accounts ?? [])
            ->map(function ($account) {
                if (! is_array($account)) {
                    return null;
                }

                $bankName = trim((string) ($account['bank_name'] ?? ''));
                $accountNumber = preg_replace('/\s+/', '', (string) ($account['account_number'] ?? ''));
                if ($bankName === '' || $accountNumber === '') {
                    return null;
                }

                return [
                    'bank_name' => $bankName,
                    'account_number' => $accountNumber,
                    'account_name' => trim((string) ($account['account_name'] ?? '')) ?: null,
                ];
            })
            ->filter()
            ->unique(fn (array $account) => strtolower($account['bank_name']).'|'.$account['account_number'])
            ->values();

        $brivaAvailable = $canPay && $this->brivaReadiness->available();
        $sultraVaAvailable = $canPay && $this->sultraVaReadiness->available();

        return [
            'manual_transfer' => [
                'available' => $canPay && $bankAccounts->isNotEmpty(),
                'bank_accounts' => $bankAccounts,
                'admin_fee' => (float) $bill->admin_fee,
                'total_amount' => (float) $bill->total_amount,
                'expires_at' => $expiresAt?->toIso8601String(),
                'instructions' => [
                    'Transfer hanya ke rekening yang diterbitkan untuk klasifikasi tagihan ini.',
                    'Pastikan nominal transfer sama dengan total tagihan.',
                    'Unggah bukti transfer untuk diverifikasi petugas.',
                ],
            ],
            'sultra_va' => [
                'available' => $sultraVaAvailable,
                'message' => $sultraVaAvailable
                    ? null
                    : $this->sultraVaReadiness->publicMessage(),
            ],
            'bri_va' => [
                'available' => $brivaAvailable,
                'message' => $brivaAvailable ? null : $this->brivaReadiness->publicMessage(),
            ],
        ];
    }

    private function citizenBillStatus(Bill $bill): string
    {
        return CitizenBillStatus::normalize(
            $bill->getRawOriginal('status'),
            (bool) $bill->has_pending_payment_claim,
            (bool) $bill->has_active_payment_request,
            (bool) $bill->due_date?->isPast(),
        );
    }

    /**
     * Export/Preview SKRD
     */
    public function exportSKRD(Request $request, Bill $bill, OfficialDocumentService $docService)
    {
        $this->authorizeBillDocument($request, $bill);

        $data = $docService->generateSKRD($bill->load(['retributionType', 'taxpayer']));

        return view('pdf.skrd', $data);
    }

    /**
     * Export/Preview SSPD
     */
    public function exportSSPD(Request $request, Bill $bill, OfficialDocumentService $docService)
    {
        $this->authorizeBillDocument($request, $bill);

        try {
            $data = $docService->generateSSPD($bill->load(['retributionType', 'taxpayer', 'payments']));

            return view('pdf.sspd', $data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Export/Preview SPPT (PBB)
     */
    public function exportSPPT(Request $request, Bill $bill, OfficialDocumentService $docService)
    {
        $this->authorizeBillDocument($request, $bill);

        try {
            // Verify if this is actually a PBB bill
            $name = strtolower($bill->retributionType->name ?? '');
            if (! str_contains($name, 'pbb') && ! str_contains($name, 'pajak bumi')) {
                return response()->json(['message' => 'Hanya tagihan PBB yang dapat mencetak SPPT.'], 400);
            }

            $data = $docService->generateSPPT($bill);

            if ($request->query('download') === 'pdf') {
                $pdf = Pdf::loadView('pdf.sppt', $data);

                return $pdf->download("SPPT-{$bill->taxObject->nop}-{$data['year']}.pdf");
            }

            return view('pdf.sppt', $data);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal generate SPPT: '.$e->getMessage()], 500);
        }
    }

    /**
     * Keep bill documents owner-scoped for citizens and OPD-scoped for staff.
     *
     * The document routes are shared by the mobile receipt flow and internal
     * staff. Global oversight roles can inspect any OPD; all other staff must
     * belong to the bill's OPD.
     */
    private function authorizeBillDocument(Request $request, Bill $bill): void
    {
        $user = $request->user();

        if ($user instanceof Taxpayer) {
            abort_unless(
                (int) $bill->taxpayer_id === (int) $user->id,
                403,
                'Unauthorized access to this document.'
            );

            return;
        }

        if (! $user instanceof User) {
            abort(403, 'Unauthorized access to this document.');
        }

        $globalRoles = [
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN,
            User::ROLE_WALIKOTA,
            User::ROLE_KEPALA_BAPENDA,
            User::ROLE_PENGAWAS,
            User::ROLE_KABID_PENGAWAS,
            User::ROLE_KASUBID_PENGAWAS,
            'viewer',
        ];

        if (in_array($user->role, $globalRoles, true)) {
            return;
        }

        abort_unless(
            $user->opd_id !== null && (int) $bill->opd_id === (int) $user->opd_id,
            403,
            'Unauthorized access to this document.'
        );
    }

    public function signTTE(Request $request, OfficialDocumentService $docService)
    {
        $validated = $request->validate([
            'bill_id' => 'required|exists:bills,id',
            'notes' => 'nullable|string',
        ]);

        $bill = Bill::findOrFail($validated['bill_id']);

        // Authorization check (Higher authority or OPD Admin)
        $user = $request->user();
        if (! $user->isSuperAdmin() && ! in_array($user->role, ['kadis', 'kabid', 'opd'])) {
            return response()->json(['message' => 'Unauthorized to sign. Higher authority required.'], 403);
        }

        try {
            $signedDoc = $docService->signDocument('bill', $bill->id, $user, $validated['notes'] ?? null);

            return response()->json([
                'message' => 'Dokumen berhasil ditandatangani secara elektronik.',
                'signed_document' => $signedDoc,
            ]);
        } catch (\Throwable $e) {
            Log::error('TTE Signing Error: '.$e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id,
                'bill_id' => $bill->id,
            ]);

            return response()->json(['message' => 'Gagal menandatangani: '.$e->getMessage()], 500);
        }
    }

    /**
     * Calculate checkout total for selected bills (partial payment support)
     * Mobile warga sends an array of bill_ids they want to pay
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'bill_ids' => 'required|array|min:1',
            'bill_ids.*' => 'exists:bills,id',
        ]);

        $user = $request->user();

        $query = Bill::with(['retributionType', 'taxObject'])
            ->whereIn('id', $request->bill_ids)
            ->where('status', 'pending');

        // If citizen, restrict to their own bills
        if ($user instanceof Taxpayer) {
            $query->where('taxpayer_id', $user->id);
        }

        $bills = $query->get();

        if ($bills->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada tagihan valid yang dipilih',
            ], 422);
        }

        $subtotal = $bills->sum('amount');
        $penaltyTotal = $bills->sum(fn ($b) => (float) ($b->penalty_amount ?? 0));
        $grandTotal = $subtotal + $penaltyTotal;

        return response()->json([
            'bills' => $bills->map(fn ($b) => [
                'id' => $b->id,
                'bill_number' => $b->bill_number,
                'retribution_type' => $b->retributionType->name ?? 'N/A',
                'tax_object' => $b->taxObject->name ?? 'N/A',
                'period' => $b->period,
                'amount' => (float) $b->amount,
                'penalty_amount' => (float) ($b->penalty_amount ?? 0),
                'total' => (float) $b->amount + (float) ($b->penalty_amount ?? 0),
            ]),
            'summary' => [
                'count' => $bills->count(),
                'subtotal' => $subtotal,
                'penalty_total' => $penaltyTotal,
                'grand_total' => $grandTotal,
                'formatted_total' => 'Rp '.number_format($grandTotal, 0, ',', '.'),
            ],
        ]);
    }

    /**
     * Generate QR for a bill (admin) — skema QR Payment M18 F1 (1 Agu 2026)
     * Returns QR URL (verify/bill) + PNG data URI untuk scan WP.
     * QR berisi bill_number saja (bukan NIK/NOP mentah).
     */
    public function generateQr(Request $request, Bill $bill)
    {
        $base = env('QR_BASE_URL', 'https://apimpad.baubaukota.go.id');
        $qrUrl = rtrim($base, '/').'/api/verify/bill/'.$bill->bill_number;

        // SVG QR — tanpa imagick, selalu tersedia (PHP murni)
        $qrImage = null;
        try {
            $qrSvg = QrCode::format('svg')
                ->size(400)
                ->margin(2)
                ->generate($qrUrl);
            $qrImage = 'data:image/svg+xml;base64,'.base64_encode($qrSvg);
        } catch (\Throwable $e) {
            $qrImage = null;
        }

        return response()->json([
            'success' => true,
            'bill_id' => $bill->id,
            'bill_number' => $bill->bill_number,
            'status' => $bill->status,
            'qr_url' => $qrUrl,
            'qr_image' => $qrImage,
        ]);
    }
}
