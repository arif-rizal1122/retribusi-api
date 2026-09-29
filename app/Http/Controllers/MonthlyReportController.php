<?php

namespace App\Http\Controllers;

use App\Models\MonthlyReport;
use App\Models\TaxObject;
use App\Models\SpotCheck;
use App\Models\PetugasTask;
use App\Services\CloudinaryService;
use App\Services\BillCreationService;
use App\Services\TaxCalculationService;
use App\Services\SpotCheckService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MonthlyReportController extends Controller
{
    protected $cloudinary;
    protected $billCreationService;
    protected $taxCalculationService;
    protected $spotCheckService;

    public function __construct(
        CloudinaryService $cloudinary,
        BillCreationService $billCreationService,
        TaxCalculationService $taxCalculationService,
        SpotCheckService $spotCheckService
    )
    {
        $this->cloudinary = $cloudinary;
        $this->billCreationService = $billCreationService;
        $this->taxCalculationService = $taxCalculationService;
        $this->spotCheckService = $spotCheckService;
    }

    /**
     * Submit turnover report (Citizen only)
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $request->validate([
                'tax_object_id' => 'required|exists:tax_objects,id',
                'period' => 'required|string', // Flexible period string (Daily, Weekly, Monthly, Yearly)
                'turnover_amount' => 'required|numeric|min:0',
                'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'notes' => 'nullable|string',
            ]);

            $taxObject = TaxObject::with(['retributionType', 'classification'])->findOrFail($request->tax_object_id);
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

            $taxAmount = $this->taxCalculationService->calculateAmount($taxObject, [
                'turnover_amount' => (float) $request->turnover_amount,
                'omzet' => (float) $request->turnover_amount,
                'period' => $request->period,
            ]);

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

            // Catat notifikasi otomatis ke Bapenda / Admin
            try {
                $taxpayerName = $taxObject->taxpayer?->name ?: 'Wajib Pajak';
                \App\Models\NotificationLog::create([
                    'taxpayer_id' => $user->id,
                    'user_id' => null,
                    'phone' => $taxObject->taxpayer?->phone ?? '-',
                    'channel' => 'in_app',
                    'type' => 'sptpd_submitted',
                    'template_code' => 'NOTIF-ADM-SPTPD',
                    'reference_type' => 'monthly_report',
                    'reference_id' => $report->id,
                    'message_preview' => "SPTPD Baru ({$taxObject->name} - {$taxpayerName}): Periode {$report->period}, Nilai Panen Rp " . number_format((float) $report->turnover_amount, 0, ',', '.') . " siap ditinjau untuk penetapan SKPD.",
                    'status' => 'delivered',
                    'idempotency_key' => "sptpd-submit-{$report->id}",
                    'sent_at' => now(),
                ]);
            } catch (\Throwable $ne) {
                \Log::warning('Failed logging admin notif for SPTPD: ' . $ne->getMessage());
            }

            return response()->json([
                'message' => 'Laporan omzet periode ' . $request->period . ' berhasil dikirim',
                'data' => $report
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Monthly Report Store Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal mengirim laporan: ' . $e->getMessage(),
                'error_detail' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * List reports for citizens (own) or admins (filtered with Anomaly Detection metrics)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = MonthlyReport::with(['taxObject.classification', 'taxObject.retributionType', 'taxpayer', 'validator']);

        if ($user instanceof \App\Models\Taxpayer) {
            $query->where('taxpayer_id', $user->id);
        } elseif ($user->role === 'citizen' || $user->role === 'wajib_pajak') {
            $taxpayerId = $user->taxpayer_id ?? $user->id;
            $query->where('taxpayer_id', $taxpayerId);
        } elseif (method_exists($user, 'isSuperAdmin') && !$user->isSuperAdmin()) {
            if (!empty($user->opd_id)) {
                $query->whereHas('taxObject', function ($q) use ($user) {
                    $q->where('opd_id', $user->opd_id);
                });
            }
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Period filter
        if ($request->filled('period')) {
            $query->where('period', 'like', $request->period . '%');
        }

        // Search by taxpayer or tax object
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('taxpayer', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('npwpd', 'like', "%{$search}%");
                })->orWhereHas('taxObject', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('nop', 'like', "%{$search}%");
                });
            });
        }

        // Classification filter
        if ($request->filled('classification_id')) {
            $query->whereHas('taxObject', function ($q) use ($request) {
                $q->where('retribution_classification_id', $request->classification_id);
            });
        }

        $reports = $query->latest()->paginate($request->get('per_page', 15));

        // Enrich items with Red Channel Anomaly Detection if viewed by admin/staff
        if ($user->role !== 'citizen') {
            $reports->getCollection()->transform(function ($report) {
                // 1. Calculate historical average of turnover for the same tax object
                $histAvg = MonthlyReport::where('tax_object_id', $report->tax_object_id)
                    ->where('id', '!=', $report->id)
                    ->whereIn('status', ['approved', 'pending'])
                    ->avg('turnover_amount') ?? 0;

                $varianceHistoryPct = 0;
                if ($histAvg > 0) {
                    $varianceHistoryPct = round((($report->turnover_amount - $histAvg) / $histAvg) * 100, 1);
                }

                // 2. Cross-check against Spot Check observation estimate if available
                $spotCheckEstimate = null;
                $varianceSpotCheckPct = null;
                try {
                    $spotData = $this->spotCheckService->calculateEstimatedMonthlyRevenue($report->tax_object_id);
                    if (!empty($spotData['estimated_monthly_turnover']) && $spotData['estimated_monthly_turnover'] > 0) {
                        $spotCheckEstimate = (float) $spotData['estimated_monthly_turnover'];
                        $varianceSpotCheckPct = round((($report->turnover_amount - $spotCheckEstimate) / $spotCheckEstimate) * 100, 1);
                    }
                } catch (\Throwable $e) {}

                // 3. Determine Risk Level
                // Red Channel (High Risk): Reported omzet dropped > 40% from historical avg or > 40% below spot check projection
                $riskLevel = 'low';
                $reasons = [];

                if ($histAvg > 0 && $varianceHistoryPct <= -40) {
                    $riskLevel = 'high';
                    $reasons[] = "Penurunan omzet drastis {$varianceHistoryPct}% dibandingkan rata-rata riwayat";
                } elseif ($histAvg > 0 && $varianceHistoryPct <= -25) {
                    $riskLevel = 'medium';
                    $reasons[] = "Penurunan omzet moderat {$varianceHistoryPct}%";
                }

                if ($spotCheckEstimate !== null && $varianceSpotCheckPct !== null && $varianceSpotCheckPct <= -35) {
                    $riskLevel = 'high';
                    $reasons[] = "Omzet dilaporkan {$varianceSpotCheckPct}% di bawah estimasi uji petik lapangan";
                }

                if ($report->status === 'red_channel') {
                    $riskLevel = 'high';
                }

                $report->historical_avg = round($histAvg, 0);
                $report->variance_history_pct = $varianceHistoryPct;
                $report->spot_check_estimate = $spotCheckEstimate;
                $report->variance_spotcheck_pct = $varianceSpotCheckPct;
                $report->risk_level = $riskLevel;
                $report->anomaly_reasons = $reasons;

                return $report;
            });
        }

        return response()->json($reports);
    }

    /**
     * Get aggregate statistics for Bapenda Revenue Control dashboard
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'citizen') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = MonthlyReport::query();
        if (!$user->isSuperAdmin() && $user->opd_id) {
            $query->whereHas('taxObject', function ($q) use ($user) {
                $q->where('opd_id', $user->opd_id);
            });
        }

        $totalReports = (clone $query)->count();
        $pendingCount = (clone $query)->where('status', 'pending')->count();
        $approvedCount = (clone $query)->where('status', 'approved')->count();
        $rejectedCount = (clone $query)->where('status', 'rejected')->count();
        $redChannelCount = (clone $query)->where('status', 'red_channel')->count();

        $totalTurnover = (clone $query)->whereIn('status', ['approved', 'pending'])->sum('turnover_amount');
        $totalTaxPotential = (clone $query)->whereIn('status', ['approved', 'pending'])->sum('tax_amount');

        return response()->json([
            'total_reports' => $totalReports,
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
            'rejected_count' => $rejectedCount,
            'red_channel_count' => $redChannelCount,
            'total_turnover' => (float) $totalTurnover,
            'total_tax_potential' => (float) $totalTaxPotential,
        ]);
    }

    /**
     * Validate report (Admin/Bapenda Verifikator)
     * Supports: approved, rejected, red_channel
     */
    public function validateReport(Request $request, MonthlyReport $report)
    {
        try {
            $user = $request->user();

            if ($user->role === 'citizen') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $request->validate([
                'status' => 'required|in:approved,rejected,red_channel',
                'notes' => 'nullable|string',
            ]);

            $report->update([
                'status' => $request->status,
                'notes' => $request->notes ?? $report->notes,
                'validated_at' => Carbon::now(),
                'validated_by' => $user->id,
            ]);

            // If marked as red_channel, flag and alert
            if ($request->status === 'red_channel') {
                return response()->json([
                    'message' => 'Laporan ditandai sebagai RED CHANNEL (Indikasi Anomali / Potensi Fraud). Disarankan untuk menugaskan Uji Petik.',
                    'data' => $report
                ]);
            }

            // If approved, automatically create a bill
            if ($request->status === 'approved') {
                $taxObject = $report->taxObject;
                $taxObject->loadMissing(['retributionType', 'classification', 'taxpayer']);

                $bill = $this->billCreationService->createForTaxObject(
                    $taxObject,
                    $user,
                    $report->period,
                    [
                        'turnover_amount' => (float) $report->turnover_amount,
                        'omzet' => (float) $report->turnover_amount,
                    ],
                    [
                        'source' => 'monthly_report',
                        'report_id' => $report->id,
                        'turnover_amount' => $report->turnover_amount,
                    ],
                    null,
                    'monthly_report',
                    (float) $report->tax_amount,
                    true
                );

                // Notify taxpayer via WhatsApp
                if ($taxpayer = $report->taxpayer) {
                    $waService = app(\App\Services\WhatsAppService::class);
                    $periodLabel = $bill->metadata['period_label'] ?? $bill->period;
                    $message = "Halo {$taxpayer->name},\n\nLaporan SPTPD Anda untuk " . $periodLabel . " telah DISETUJUI.\n\nNomor Tagihan: {$bill->bill_number}\nTotal: Rp " . number_format($bill->amount, 0, ',', '.') . "\nSilakan lakukan pembayaran sebelum " . Carbon::parse($bill->due_date)->isoFormat('D MMMM YYYY') . ".\n\nTerima kasih.";
                    $waService->sendMessage($taxpayer->phone ?? '', $message);
                }
            }

            return response()->json([
                'message' => 'Laporan berhasil di-' . $request->status,
                'data' => $report
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('Monthly Report Validation Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Gagal validasi laporan: ' . $e->getMessage(),
                'error_detail' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Request Spot Check Task from an anomalous SPTPD report
     */
    public function requestSpotCheck(Request $request, MonthlyReport $report)
    {
        $user = $request->user();
        if ($user->role === 'citizen') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'inspector_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'remarks' => 'nullable|string',
        ]);

        $spotCheck = SpotCheck::create([
            'taxpayer_id' => $report->taxpayer_id,
            'tax_object_id' => $report->tax_object_id,
            'inspector_id' => $request->inspector_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'supervisor_id' => $user->id,
            'status' => 'draft',
            'remarks' => "Rujukan Uji Petik dari E-SPTPD Periode {$report->period}. " . ($request->remarks ?? ''),
        ]);

        // Also update report notes
        $report->update([
            'notes' => ($report->notes ? $report->notes . ' | ' : '') . "Dirujuk ke Uji Petik #{$spotCheck->id}"
        ]);

        return response()->json([
            'message' => 'Surat tugas Uji Petik berhasil diterbitkan untuk objek pajak terkait.',
            'spot_check' => $spotCheck
        ], 201);
    }
}
