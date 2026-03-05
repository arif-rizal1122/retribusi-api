<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\TaxObject;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
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
     * Get pending billing periods for a tax object
     */
    public function getPendingPeriods(TaxObject $taxObject)
    {
        return response()->json([
            'data' => $this->billingService->getPendingPeriods($taxObject)
        ]);
    }

    /**
     * Record a payment (Claim by Citizen or Entry by Officer)
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            $isCitizen = $user instanceof \App\Models\Taxpayer;

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
