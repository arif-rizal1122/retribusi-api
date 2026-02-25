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
     * Get pending billing periods for a tax object
     */
    public function getPendingPeriods(TaxObject $taxObject)
    {
        return response()->json([
            'data' => $this->billingService->getPendingPeriods($taxObject)
        ]);
    }

    /**
     * Record a payment
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'tax_object_id' => 'required|exists:tax_objects,id',
            'billing_period' => 'required|string|max:255',
            'payment_method' => 'required|string|in:cash,qris,va',
            'amount' => 'required|numeric|min:0',
            'proof_url' => 'nullable|string',
        ]);

        $taxObject = TaxObject::findOrFail($request->tax_object_id);

        // Authority check
        if (!$user->isSuperAdmin()) {
            if ($user->role !== 'opd' && $user->role !== 'petugas') {
                return response()->json(['message' => 'Role tidak memiliki wewenang mencatat pembayaran'], 403);
            }
            if ($taxObject->opd_id !== $user->opd_id) {
                return response()->json(['message' => 'Unauthorized OPD'], 403);
            }
            if ($user->role === 'petugas') {
                $hasAssignment = $user->assignments()->where(function($q) use ($taxObject) {
                    $q->where('retribution_type_id', $taxObject->retribution_type_id);
                    $q->where(function($sq) use ($taxObject) {
                        $sq->whereNull('retribution_classification_id')
                           ->orWhere('retribution_classification_id', $taxObject->retribution_classification_id);
                    });
                })->exists();

                if (!$hasAssignment) {
                    return response()->json(['message' => 'Anda tidak ditugaskan untuk mengelola klasifikasi objek pajak ini'], 403);
                }
            }
        }

        // Check if already paid
        $existing = Payment::where('tax_object_id', $taxObject->id)
            ->where('billing_period', $request->billing_period)
            ->where('status', 'success')
            ->first();

        // Sync bill if found (whether existing or new)
        $bill = null;
        if ($request->route('bill')) {
            $bill = Bill::find($request->route('bill'));
        }

        if (!$bill) {
            $bill = Bill::where('tax_object_id', $taxObject->id)
                ->where('period', $request->billing_period)
                ->first();
        }

        if ($existing) {
            if ($bill && $bill->status !== 'lunas') {
                $bill->update(['status' => 'lunas']);
                if (!$existing->bill_id) {
                    $existing->update(['bill_id' => $bill->id]);
                }
            }
            return response()->json(['message' => 'Periode ini sudah lunas'], 422);
        }

        $payment = Payment::create([
            'bill_id' => $bill ? $bill->id : null,
            'tax_object_id' => $taxObject->id,
            'taxpayer_id' => $taxObject->taxpayer_id,
            'transaction_id' => 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(8)),
            'payment_method' => $request->payment_method,
            'amount' => $request->amount,
            'status' => 'success', // Manual entry by officer is marked success
            'billing_period' => $request->billing_period,
            'paid_at' => Carbon::now(),
            'approved_by' => $user->id,
            'proof_url' => $request->proof_url,
        ]);

        if ($bill && $bill->status !== 'lunas') {
            $bill->update(['status' => 'lunas']);
        }

        return response()->json([
            'message' => 'Pembayaran periode ' . $request->billing_period . ' berhasil dicatat',
            'data' => $payment->load(['taxObject', 'taxpayer'])
        ], 201);
    }
}
