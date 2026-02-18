<?php

namespace App\Http\Controllers;

use App\Models\MonthlyReport;
use App\Models\TaxObject;
use App\Models\Bill;
use App\Services\CloudinaryService;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MonthlyReportController extends Controller
{
    protected $cloudinary;
    protected $billingService;

    public function __construct(CloudinaryService $cloudinary, BillingService $billingService)
    {
        $this->cloudinary = $cloudinary;
        $this->billingService = $billingService;
    }

    /**
     * Submit turnover report (Citizen only)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'tax_object_id' => 'required|exists:tax_objects,id',
            'period' => 'required|string', // Flexible period string (Daily, Weekly, Monthly, Yearly)
            'turnover_amount' => 'required|numeric|min:0',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'notes' => 'nullable|string',
        ]);

        $taxObject = TaxObject::with('retributionType')->findOrFail($request->tax_object_id);
        $type = $taxObject->retributionType;
        $cycle = $type->billing_cycle ?? 'monthly';
        
        // Basic format check based on cycle
        $isValidFormat = match ($cycle) {
            'daily' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->period),
            'weekly' => preg_match('/^\d{4}-W\d{2}$/', $request->period),
            'yearly' => preg_match('/^\d{4}$/', $request->period),
            default => preg_match('/^\d{4}-\d{2}$/', $request->period),
        };

        if (!$isValidFormat) {
            return response()->json(['message' => "Format periode tidak valid untuk siklus $cycle"], 422);
        }

        // Security check
        if ($taxObject->taxpayer_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized tax object'], 403);
        }

        // Check for existing report
        $existing = MonthlyReport::where('tax_object_id', $taxObject->id)
            ->where('period', $request->period)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Laporan untuk periode ' . $request->period . ' sudah ada'], 422);
        }

        // Calculate tax amount based on object's rate/formula
        // Here we mock the calculation or use the BillingService if it supports it
        // For PBJT (Standard 10%), but we should use the database values
        $rateValue = 0.1; // 10% default for PBJT if no rate found
        // In real scenario, we'd lookup the rate from retribution_rates
        
        $taxAmount = $request->turnover_amount * $rateValue;

        $attachmentUrl = null;
        if ($request->hasFile('attachment')) {
            $attachmentUrl = $this->cloudinary->upload($request->file('attachment'), 'monthly_reports');
        }

        $report = MonthlyReport::create([
            'taxpayer_id' => $user->id,
            'tax_object_id' => $taxObject->id,
            'period' => $request->period,
            'turnover_amount' => $request->turnover_amount,
            'tax_amount' => $taxAmount,
            'attachments' => $attachmentUrl ? [$attachmentUrl] : [],
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Laporan omzet periode ' . $request->period . ' berhasil dikirim',
            'data' => $report
        ], 201);
    }

    /**
     * List reports for citizens (own) or admins (filtered)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = MonthlyReport::with(['taxObject', 'taxpayer']);

        if ($user->role === 'citizen') {
            $query->where('taxpayer_id', $user->id);
        } elseif (!$user->isSuperAdmin()) {
            $query->whereHas('taxObject', function ($q) use ($user) {
                $q->where('opd_id', $user->opd_id);
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($reports);
    }

    /**
     * Validate report (Admin/OPD/Petugas)
     */
    public function validateReport(Request $request, MonthlyReport $report)
    {
        $user = $request->user();

        if ($user->role === 'citizen') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string',
        ]);

        $report->update([
            'status' => $request->status,
            'notes' => $request->notes ?? $report->notes,
            'validated_at' => Carbon::now(),
            'validated_by' => $user->id,
        ]);

        // If approved, automatically create a bill
        if ($request->status === 'approved') {
            $taxObject = $report->taxObject;
            
            $bill = Bill::create([
                'user_id' => $user->id,
                'taxpayer_id' => $report->taxpayer_id,
                'tax_object_id' => $report->tax_object_id,
                'opd_id' => $taxObject->opd_id,
                'retribution_type_id' => $taxObject->retribution_type_id,
                'retribution_classification_id' => $taxObject->retribution_classification_id,
                'bill_number' => 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'amount' => $report->tax_amount,
                'status' => 'pending',
                'period' => $this->billingService->getPeriodLabel(Carbon::parse($report->period), $taxObject->retributionType->billing_cycle ?? 'monthly'),
                'due_date' => Carbon::now()->addDays(30),
                'metadata' => [
                    'source' => 'monthly_report',
                    'report_id' => $report->id,
                    'turnover_amount' => $report->turnover_amount
                ]
            ]);

            // Notify taxpayer via WhatsApp
            if ($taxpayer = $report->taxpayer) {
                $waService = app(\App\Services\WhatsAppService::class);
                $message = "Halo {$taxpayer->name},\n\nLaporan SPTPD Anda untuk " . $bill->period . " telah DISETUJUI.\n\nNomor Tagihan: {$bill->bill_number}\nTotal: Rp " . number_format($bill->amount, 0, ',', '.') . "\nSilakan lakukan pembayaran sebelum " . Carbon::parse($bill->due_date)->isoFormat('D MMMM YYYY') . ".\n\nTerima kasih.";
                $waService->sendMessage($taxpayer->phone ?? '', $message);
            }
        }

        return response()->json([
            'message' => 'Laporan berhasil di-' . $request->status,
            'data' => $report
        ]);
    }
}
