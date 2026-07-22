<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Services\BillCreationService;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $billingService;
    protected $billCreationService;

    public function __construct(
        BillingService $billingService,
        BillCreationService $billCreationService
    )
    {
        $this->billingService = $billingService;
        $this->billCreationService = $billCreationService;
    }

    /**
     * List payments (OPD-scoped for officers, Taxpayer-scoped for citizens)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Payment::with(['taxObject', 'taxpayer', 'bill', 'approvedBy']);

        if ($user instanceof \App\Models\Taxpayer) {
            $query->where('taxpayer_id', $user->id);
        } else if (!$user->isSuperAdmin()) {
            $query->whereHas('taxObject', function($q) use ($user) {
                $q->where('opd_id', $user->opd_id);
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest()->paginate($request->get('per_page', 15)));
    }

    /**
     * List the authenticated citizen's payment history without exposing
     * callback payloads or internal approval metadata.
     */
    public function history(Request $request)
    {
        $taxpayer = $request->user();
        abort_unless($taxpayer instanceof Taxpayer, 403, 'Endpoint riwayat pembayaran ini hanya untuk wajib pajak.');

        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', 'in:pending,success,failed'],
        ]);

        $query = Payment::with([
            'bill.retributionType',
            'bill.classification',
            'taxObject',
        ])->where('taxpayer_id', $taxpayer->id);

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $payments = $query
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 20);

        $data = collect($payments->items())->map(function (Payment $payment) {
            $bill = $payment->bill;
            $retributionType = $bill?->retributionType;
            $taxObject = $payment->taxObject;

            return [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'reference_number' => $payment->reference_number,
                'receipt_number' => $payment->receipt_number,
                'payment_method' => $payment->payment_method,
                'channel' => $payment->channel,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
                'status_label' => $this->citizenPaymentStatusLabel($payment->status),
                'billing_period' => $payment->billing_period,
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'created_at' => $payment->created_at?->toIso8601String(),
                'proof_url' => $payment->proof_url,
                'bill' => $bill ? [
                    'id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'status' => $bill->status,
                    'period' => $bill->period,
                    'total_amount' => $bill->total_amount,
                ] : null,
                'tax_object' => $taxObject ? [
                    'id' => $taxObject->id,
                    'name' => $taxObject->name,
                ] : null,
                'retribution_type' => $retributionType ? [
                    'id' => $retributionType->id,
                    'name' => $retributionType->name,
                    'icon' => $retributionType->icon,
                ] : null,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    /**
     * Get pending billing periods for a tax object
     */
    public function getPendingPeriods(TaxObject $taxObject)
    {
        return response()->json([
            'data' => $this->billingService->getPendingPeriods($taxObject)
        ]);
    }

    private function citizenPaymentStatusLabel(string $status): string
    {
        return match ($status) {
            'success' => 'Pembayaran berhasil',
            'failed' => 'Pembayaran ditolak',
            default => 'Menunggu verifikasi',
        };
    }

    /**
     * Record a payment (Claim by Citizen or Entry by Officer)
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            $isCitizen = $user instanceof \App\Models\Taxpayer;

            if ($isCitizen && $request->has('bill_ids')) {
                return $this->storeCitizenBillClaims($request, $user);
            }

            $request->validate([
                'tax_object_id' => 'required|exists:tax_objects,id',
                'billing_period' => 'required|string|max:255',
                'payment_method' => 'required|string|in:cash,qris,va,transfer',
                'amount' => 'required|numeric|min:0',
                'proof_url' => $isCitizen ? 'required|string' : 'nullable|string',
            ]);

            $taxObject = TaxObject::findOrFail($request->tax_object_id);

            // Authority check
            if (!$user->isSuperAdmin()) {
                if ($isCitizen) {
                    if ($taxObject->taxpayer_id !== $user->id) {
                        return response()->json(['message' => 'Ini bukan objek pajak Anda'], 403);
                    }
                } else {
                    if ($user->role !== 'opd' && $user->role !== 'petugas') {
                        return response()->json(['message' => 'Role tidak memiliki wewenang mencatat pembayaran'], 403);
                    }
                    if ($taxObject->opd_id !== $user->opd_id) {
                        return response()->json(['message' => 'Unauthorized OPD'], 403);
                    }
                    if ($user->role === 'petugas') {
                        $hasAssignment = $user->assignments()->where(function ($q) use ($taxObject) {
                            $q->where('retribution_type_id', $taxObject->retribution_type_id);
                            $q->where(function ($sq) use ($taxObject) {
                                $sq->whereNull('retribution_classification_id')
                                    ->orWhere('retribution_classification_id', $taxObject->retribution_classification_id);
                            });
                        })->exists();

                        if (!$hasAssignment) {
                            return response()->json(['message' => 'Anda tidak ditugaskan untuk mengelola klasifikasi objek pajak ini'], 403);
                        }
                    }
                }
            }

            // Check if already paid or pending claim
            $existing = Payment::where('tax_object_id', $taxObject->id)
                ->where('billing_period', $request->billing_period)
                ->whereIn('status', ['success', 'pending'])
                ->first();

            if ($existing) {
                if ($existing->status === 'success') {
                    return response()->json(['message' => 'Periode ini sudah lunas'], 422);
                }
                return response()->json(['message' => 'Sudah ada klaim pembayaran untuk periode ini yang menunggu verifikasi'], 422);
            }

            $bill = null;
            if ($request->route('bill')) {
                $bill = Bill::find($request->route('bill'));
            }

            if (!$bill) {
                $bill = Bill::where('tax_object_id', $taxObject->id)
                    ->where('period', $request->billing_period)
                    ->first();
            }

            if (!$bill) {
                $bill = $this->billCreationService->createForTaxObject(
                    $taxObject->loadMissing(['retributionType', 'classification', 'taxpayer']),
                    $isCitizen ? null : $user,
                    $request->billing_period,
                    $taxObject->metadata ?? [],
                    ['source' => $isCitizen ? 'citizen_payment_claim' : 'officer_payment_entry'],
                    null,
                    $isCitizen ? 'citizen_payment_claim' : 'officer_payment_entry',
                    null,
                    true
                );
            }

            // --- Advanced Billing V2: Real-time Data Sync before Payment ---
            if ($bill) {
                $pendingPeriods = $this->billingService->getPendingPeriods($taxObject);
                $periodData = $pendingPeriods->firstWhere('period', $bill->period);

                if ($periodData) {
                    // Sync latest calculated penalty and amount to the Bill record
                    $bill->update([
                        'penalty_amount' => $periodData['penalty_amount'],
                        'amount' => $periodData['amount'],
                    ]);
                    
                    // Logic: If user is paying the total amount (including penalties), ensure it matches
                    // For now, we update the record to reflect the truth at the time of payment.
                    \Log::info("Synced Bill #{$bill->bill_number} before payment. Penalty: {$bill->penalty_amount}");
                }
            }
            // -----------------------------------------------------------------

            $payment = Payment::create([
                'bill_id' => $bill ? $bill->id : null,
                'tax_object_id' => $taxObject->id,
                'taxpayer_id' => $taxObject->taxpayer_id,
                'transaction_id' => 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(8)),
                'payment_method' => $request->payment_method,
                'amount' => $request->amount,
                'status' => $isCitizen ? 'pending' : 'success', 
                'billing_period' => $request->billing_period,
                'paid_at' => Carbon::now(),
                'approved_by' => $isCitizen ? null : $user->id,
                'proof_url' => $request->proof_url,
            ]);

            if (!$isCitizen && $bill && $bill->status !== 'lunas') {
                $bill->update(['status' => 'lunas']);
            }

            return response()->json([
                'message' => $isCitizen ? 'Klaim pembayaran berhasil dikirim. Menunggu verifikasi petugas.' : 'Pembayaran berhasil dicatat',
                'data' => $payment->load(['taxObject', 'taxpayer'])
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Payment Store Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal mencatat pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function storeCitizenBillClaims(Request $request, Taxpayer $taxpayer)
    {
        $validated = $request->validate([
            'bill_ids' => ['required', 'array', 'min:1', 'max:20'],
            'bill_ids.*' => ['integer', 'distinct', 'exists:bills,id'],
            'payment_method' => ['required', 'in:transfer'],
            'proof_url' => ['required', 'string'],
        ]);

        $billIds = collect($validated['bill_ids'])
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        $payments = DB::transaction(function () use ($billIds, $taxpayer, $validated) {
            $bills = Bill::where('taxpayer_id', $taxpayer->id)
                ->whereIn('id', $billIds)
                ->whereIn('status', ['pending', 'overdue', 'unpaid'])
                ->lockForUpdate()
                ->get()
                ->sortBy('id')
                ->values();

            if ($bills->count() !== $billIds->count()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'bill_ids' => 'Satu atau lebih tagihan tidak dapat diajukan untuk pembayaran.',
                ]);
            }

            $hasExistingPayment = Payment::whereIn('bill_id', $billIds)
                ->whereIn('status', ['pending', 'success'])
                ->exists();

            if ($hasExistingPayment) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'bill_ids' => 'Satu atau lebih tagihan sudah lunas atau sedang menunggu verifikasi pembayaran.',
                ]);
            }

            return $bills->map(function (Bill $bill) use ($taxpayer, $validated) {
                return Payment::create([
                    'bill_id' => $bill->id,
                    'tax_object_id' => $bill->tax_object_id,
                    'taxpayer_id' => $taxpayer->id,
                    'transaction_id' => 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(8)),
                    'payment_method' => $validated['payment_method'],
                    'amount' => $bill->total_amount,
                    'status' => 'pending',
                    'billing_period' => $bill->period,
                    'paid_at' => null,
                    'approved_by' => null,
                    'proof_url' => $validated['proof_url'],
                    'channel' => 'MOBILE_CLAIM',
                ]);
            });
        });

        return response()->json([
            'message' => 'Bukti pembayaran berhasil dikirim. Menunggu verifikasi petugas.',
            'data' => $payments->values(),
        ], 201);
    }

    /**
     * Verify/Approve a pending payment (Officer only)
     */
    public function updateStatus(Request $request, Payment $payment)
    {
        $user = $request->user();
        
        if ($user instanceof \App\Models\Taxpayer) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$user->isSuperAdmin() && $payment->taxObject->opd_id !== $user->opd_id) {
            return response()->json(['message' => 'Unauthorized OPD'], 403);
        }

        $request->validate([
            'status' => 'required|string|in:success,failed',
            'notes' => 'nullable|string'
        ]);

        $payment->update([
            'status' => $request->status,
            'approved_by' => $user->id,
            'metadata' => array_merge($payment->metadata ?? [], ['verification_notes' => $request->notes])
        ]);

        if ($request->status === 'success') {
            $bill = $payment->bill;
            if (!$bill) {
                $bill = Bill::where('tax_object_id', $payment->tax_object_id)
                    ->where('period', $payment->billing_period)
                    ->first();
            }
            if ($bill) {
                $bill->update(['status' => 'lunas']);
            }
        }

        return response()->json([
            'message' => 'Status pembayaran berhasil diperbarui',
            'data' => $payment->load(['taxObject', 'taxpayer'])
        ]);
    }
}
