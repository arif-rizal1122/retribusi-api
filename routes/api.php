<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\RetributionTypeController;
use App\Http\Controllers\TaxpayerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RetributionClassificationController;
use App\Http\Controllers\RetributionRateController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaxObjectController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\PbbClassificationController;
use App\Http\Controllers\PbbBapendaController;
use App\Http\Controllers\TaxEducationController;
use App\Http\Controllers\BillboardAuditController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\Api\SimpadKoneksiController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public auth routes with strict throttle (prevent brute force)
Route::group(['middleware' => 'throttle:10,1'], function () {
    Route::post('/opd/register', [OpdController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/citizen/login', [AuthController::class, 'citizenLogin']);
    Route::post('/citizen/register', [AuthController::class, 'registerCitizen']);
});

// Other public routes
Route::get('/opds', [OpdController::class, 'index']); // Public access
Route::get('/citizen/bills', [BillController::class, 'citizenBills']); // Public access for demo
Route::get('/verify/bill/{number}', [\App\Http\Controllers\PublicVerificationController::class, 'verifyBill']);
Route::get('/verify/payment/{number}', [\App\Http\Controllers\PublicVerificationController::class, 'verifyPayment']);

// Public: Documents (PDF)
Route::get('/public/pdf/npwpd/{id}', [\App\Http\Controllers\PdfController::class, 'generateNpwpd']);
Route::get('/public/pdf/skrd/{billId}', [\App\Http\Controllers\DocumentController::class, 'skrd']);
Route::get('/public/pdf/skpd/{billId}', [\App\Http\Controllers\DocumentController::class, 'skpd']);
Route::get('/public/pdf/sspd/{billId}', [\App\Http\Controllers\DocumentController::class, 'sspd']);
Route::get('/public/pdf/sppt/{billId}', [\App\Http\Controllers\DocumentController::class, 'sppt']);
Route::get('/public/pdf/surat-teguran/{noticeId}', [\App\Http\Controllers\DocumentController::class, 'suratTeguran']);

// Tax Simulation (public, no auth needed)
Route::post('/simulate-tax', function (Request $request) {
    $request->validate([
        'classification_id' => 'nullable|exists:retribution_classifications,id',
        'type_id' => 'nullable|exists:retribution_types,id',
        'calculation_formula' => 'nullable|string',
        'variables' => 'required|array',
    ]);
    
    $formula = $request->calculation_formula;
    $name = 'Simulasi';
    $isPbb = false;
    
    if ($request->classification_id) {
        $classification = \App\Models\RetributionClassification::with('retributionType')->findOrFail($request->classification_id);
        if (!$formula) $formula = $classification->calculation_formula;
        $name = $classification->name;
        
        $typeName = strtolower($classification->retributionType->name ?? '');
        $catName = strtolower($classification->retributionType->category ?? '');
        $className = strtolower($classification->name ?? '');
        $classCode = strtolower($classification->code ?? '');

        if (str_contains($typeName, 'pbb') || str_contains($catName, 'pajak bumi') || 
            str_contains($className, 'pbb') || str_contains($classCode, 'pbb')) {
            $isPbb = true;
        }
    } elseif ($request->type_id) {
        $type = \App\Models\RetributionType::findOrFail($request->type_id);
        $name = $type->name;
        $typeName = strtolower($type->name ?? '');
        $catName = strtolower($type->category ?? '');

        if (str_contains($typeName, 'pbb') || str_contains($catName, 'pajak bumi')) {
            $isPbb = true;
        }
    }

    if ($isPbb && !$request->calculation_formula) {
        $pbbService = app(\App\Services\PbbCalculationService::class);
        $vars = $request->variables;
        $resultData = $pbbService->calculate(
            (float) ($vars['luas_bumi'] ?? $vars['luas_tanah'] ?? 0),
            (string) ($vars['kelas_bumi'] ?? ''),
            (float) ($vars['luas_bangunan'] ?? 0),
            (string) ($vars['kelas_bangunan'] ?? ''),
            (float) ($vars['njoptkp'] ?? 10000000),
            (float) ($vars['tariff'] ?? 0.001)
        );
        
        return response()->json([
            'classification' => $name,
            'variables' => $vars,
            'result' => $resultData['pbb_terhutang'],
            'details' => $resultData,
            'formatted' => 'Rp ' . number_format($resultData['pbb_terhutang'], 0, ',', '.'),
        ]);
    }
    
    if (!$formula) {
        return response()->json(['error' => 'Rumus perhitungan tidak ditemukan.'], 422);
    }
    
    $parser = new \App\Services\FormulaParserService();
    $result = $parser->calculate($formula, $request->variables);
    
    return response()->json([
        'classification' => $name,
        'formula' => $formula,
        'variables' => $request->variables,
        'result' => $result,
        'formatted' => 'Rp ' . number_format($result, 0, ',', '.'),
    ]);
});

// Public: Get classifications with formulas for simulation
Route::get('/tax-formulas', function () {
    $classifications = \App\Models\RetributionClassification::whereNotNull('calculation_formula')
        ->where('calculation_formula', '!=', '')
        ->with('retributionType:id,name')
        ->get(['id', 'name', 'code', 'calculation_formula', 'retribution_type_id', 'form_schema']);
    
    return response()->json(['data' => $classifications]);
});


// Public: PBB NJOP Classifications & Calculation
Route::get('/pbb/classifications', [PbbClassificationController::class, 'index']);
Route::get('/pbb/classifications/{type}/{code}', [PbbClassificationController::class, 'showByCode']);
Route::post('/pbb/lookup-class', [PbbClassificationController::class, 'lookupByValue']);
Route::post('/pbb/calculate', [PbbClassificationController::class, 'calculate']);

// Public: PBB Bapenda Inquiry (cek tagihan tanpa login) - Throttled
Route::post('/pbb/bapenda/inquiry', [PbbBapendaController::class, 'inquiry'])->middleware('throttle:10,1');

// Protected routes
Route::group(['middleware' => ['auth:sanctum', 'scope_user']], function () {
    // Shared Routes (Admin, Petugas, Citizen)
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/password', [AuthController::class, 'changePassword']);
    Route::put('/user/location', [AuthController::class, 'updateLocation']);
    Route::post('/upload', [\App\Http\Controllers\UploadController::class, 'uploadImage']);
    Route::get('/me', [\App\Http\Controllers\MeController::class, 'show']);
    Route::post('/me/update', [\App\Http\Controllers\MeController::class, 'update']);
    
    // Citizen Service Registration (Shared, but usually for citizens)
    Route::group(['prefix' => 'citizen/services'], function () {
        Route::get('/', [\App\Http\Controllers\CitizenServiceController::class, 'index']);
        Route::get('/pending-periods', [\App\Http\Controllers\CitizenServiceController::class, 'getPendingPeriods']);
        Route::get('/{id}', [\App\Http\Controllers\CitizenServiceController::class, 'show']);
        Route::post('/{id}/register', [\App\Http\Controllers\CitizenServiceController::class, 'register']);
        Route::get('/{id}/bills', [\App\Http\Controllers\CitizenServiceController::class, 'bills']);
    });

    // Share Routes for Bills & TTE
    Route::get('/bills/{bill}', [BillController::class, 'show']);
    Route::get('/bills/{bill}/skrd', [BillController::class, 'exportSKRD']);
    Route::get('/bills/{bill}/sspd', [BillController::class, 'exportSSPD']);
    Route::get('/bills/{bill}/sppt', [BillController::class, 'exportSPPT']);
    Route::get('/tte/verify/{number}', [\App\Http\Controllers\Api\EregistryController::class, 'verify'])->withoutMiddleware('auth:sanctum');

    // Citizen Specific Actions
    Route::group(['prefix' => 'citizen'], function () {
        Route::post('/reports', [\App\Http\Controllers\MonthlyReportController::class, 'store']);
        Route::get('/reports', [\App\Http\Controllers\MonthlyReportController::class, 'index']);
        Route::post('/complaints', [ComplaintController::class, 'store']);
        Route::get('/complaints', [ComplaintController::class, 'index']);
    });

    // PBB Bapenda Citizen Actions
    Route::group(['prefix' => 'pbb/bapenda'], function () {
        Route::post('/link-nop', [PbbBapendaController::class, 'linkNop']);
        Route::delete('/unlink-nop/{id}', [PbbBapendaController::class, 'unlinkNop']);
        Route::get('/my-objects', [PbbBapendaController::class, 'myObjects']);
        Route::get('/my-transactions', [PbbBapendaController::class, 'myTransactions']);
        Route::post('/pay', [PbbBapendaController::class, 'pay']);
        Route::get('/download-sppt', [PbbBapendaController::class, 'downloadSPPT']);
    });

    // ------------------------------------------------------------------------
    // Admin & Petugas ONLY (Restricted by EnsureAdmin middleware)
    // ------------------------------------------------------------------------
    Route::middleware('admin')->group(function () {
        // Simpad Koneksi (Legacy Migration)
        Route::prefix('simpad-koneksi')->group(function () {
             Route::get('/taxpayers/{npwpd}', [SimpadKoneksiController::class, 'getTaxpayer']);
             Route::get('/objects/{type}', [SimpadKoneksiController::class, 'getObjects']);
             Route::get('/officers', [SimpadKoneksiController::class, 'getOfficers']);
             Route::post('/sync-object', [SimpadKoneksiController::class, 'syncObject']);
        });

        Route::apiResource('petugas-tasks', \App\Http\Controllers\PetugasTaskController::class);
        Route::apiResource('spot-checks', \App\Http\Controllers\SpotCheckController::class);
        Route::patch('spot-checks/{id}/status', [\App\Http\Controllers\SpotCheckController::class, 'updateStatus']);
        Route::get('spot-checks/tax-object/{id}/estimation', [\App\Http\Controllers\SpotCheckController::class, 'getEstimatedRevenue']);
        
        Route::get('/analytics/realization', [AnalyticsController::class, 'getRealization']);
        Route::get('/analytics/heatmap', [AnalyticsController::class, 'getHeatmapData']);
        Route::get('/analytics/object-performance', [AnalyticsController::class, 'getObjectPerformance']);
        Route::get('/analytics/classification-performance', [AnalyticsController::class, 'getClassificationPerformance']);
        Route::apiResource('retribution-types', RetributionTypeController::class);
        Route::get('/taxpayers/search/{nik}', [\App\Http\Controllers\TaxpayerSearchController::class, 'searchByNik']);
        Route::apiResource('taxpayers', TaxpayerController::class);
        Route::apiResource('tax-objects', TaxObjectController::class);
        Route::apiResource('bills', BillController::class)->only(['index', 'store']);
        Route::get('/tax-objects/{taxObject}/pending-periods', [PaymentController::class, 'getPendingPeriods']);
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::post('/bills/{bill}/pay', [PaymentController::class, 'store']);
        Route::put('/payments/{payment}/status', [PaymentController::class, 'updateStatus']);
        Route::put('/verifications/{verification}/status', [VerificationController::class, 'updateStatus']);
        Route::apiResource('verifications', VerificationController::class)->only(['index', 'show', 'store']);
        Route::apiResource('zones', ZoneController::class);
        Route::apiResource('retribution-classifications', RetributionClassificationController::class);
        Route::apiResource('retribution-rates', RetributionRateController::class);
        Route::apiResource('opds', OpdController::class)->except(['create', 'edit', 'index']);
        Route::apiResource('users', UserController::class);

        Route::prefix('dashboard')->group(function () {
            Route::get('/stats', [DashboardController::class, 'getStats']);
            Route::get('/revenue-trend', [DashboardController::class, 'getRevenueTrend']);
            Route::get('/map-potentials', [DashboardController::class, 'getMapPotentials']);
        });

        Route::prefix('pengawas')->group(function () {
            Route::get('/audit-logs', [\App\Http\Controllers\Pengawas\AuditLogController::class, 'index']);
            Route::get('/anomalies', [\App\Http\Controllers\Pengawas\SurveillanceController::class, 'getAnomalies']);
            Route::get('/compliance-stats', [\App\Http\Controllers\Pengawas\SurveillanceController::class, 'getComplianceStats']);
            Route::get('/petugas-locations', [\App\Http\Controllers\Pengawas\SurveillanceController::class, 'getPetugasLocations']);
            Route::get('/enforcements', [\App\Http\Controllers\Pengawas\EnforcementNoticeController::class, 'index']);
            Route::post('/enforcements', [\App\Http\Controllers\Pengawas\EnforcementNoticeController::class, 'store']);
            Route::post('/enforcements/{id}', [\App\Http\Controllers\Pengawas\EnforcementNoticeController::class, 'update']);
            Route::post('/enforcements/{id}/approve', [\App\Http\Controllers\Pengawas\EnforcementNoticeController::class, 'approve']);
            Route::post('/enforcements/{id}/reject', [\App\Http\Controllers\Pengawas\EnforcementNoticeController::class, 'reject']);
            Route::get('/enforcements/history/{tax_object_id}', [\App\Http\Controllers\Pengawas\EnforcementNoticeController::class, 'getHistory']);
            Route::get('/enforcements/{id}/pdf', [\App\Http\Controllers\Pengawas\EnforcementNoticeController::class, 'generatePDF']);
            Route::get('/penindakan', [\App\Http\Controllers\Pengawas\PenindakanController::class, 'index']);
            Route::post('/penindakan/issue-skpdkb', [\App\Http\Controllers\Pengawas\PenindakanController::class, 'generateSKPDKB']);
        });

        Route::prefix('reports')->group(function () {
            Route::get('/summary', [ReportController::class, 'getSummary']);
            Route::get('/recent', [ReportController::class, 'getRecent']);
            Route::get('/petugas-performance', [ReportController::class, 'getPetugasPerformance']);
            Route::get('/monthly', [\App\Http\Controllers\MonthlyReportController::class, 'index']);
            Route::put('/monthly/{report}/validate', [\App\Http\Controllers\MonthlyReportController::class, 'validateReport']);
            Route::get('/bpk', [ReportController::class, 'getMonthlyReport']);
            Route::get('/sipd', [ReportController::class, 'getSipdReport']);
        });

        Route::prefix('amnesty')->group(function () {
            Route::get('/', [\App\Http\Controllers\PenaltyWaiverController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\PenaltyWaiverController::class, 'store']);
            Route::post('/{id}/approve', [\App\Http\Controllers\PenaltyWaiverController::class, 'approve']);
            Route::post('/{id}/reject', [\App\Http\Controllers\PenaltyWaiverController::class, 'reject']);
            Route::get('/{id}/document', [\App\Http\Controllers\PenaltyWaiverController::class, 'generateDocument']);
        });

        Route::prefix('tte')->group(function () {
            Route::get('/documents', [\App\Http\Controllers\Api\EregistryController::class, 'index']);
            Route::post('/sign', [BillController::class, 'signTTE']);
        });

        Route::prefix('pbb/bapenda')->group(function () {
            Route::post('/reversal', [PbbBapendaController::class, 'reversal']);
            Route::get('/transactions', [PbbBapendaController::class, 'transactions']);
            Route::get('/stats', [PbbBapendaController::class, 'stats']);
            Route::post('/sync-all', [PbbBapendaController::class, 'syncAllObjects']);
        });

        // Bank H2H Monitoring Logs
        Route::prefix('bank-h2h')->group(function () {
            Route::get('/logs', [\App\Http\Controllers\Api\V1\Bank\BankH2HController::class, 'logs']);
            Route::post('/reconcile', [\App\Http\Controllers\Api\V1\Bank\BankH2HController::class, 'reconcile']);
        });

        // Official BAPENDA Documents
        Route::prefix('documents')->group(function () {
            // Pendaftaran
            Route::get('/skt/{taxpayerId}', [\App\Http\Controllers\DocumentController::class, 'skt']);
            // Pendataan
            Route::get('/lkok/{taxObjectId}', [\App\Http\Controllers\DocumentController::class, 'lkok']);
            // Penetapan
            Route::get('/skrd/{billId}', [\App\Http\Controllers\DocumentController::class, 'skrd']);
            Route::get('/skpd/{billId}', [\App\Http\Controllers\DocumentController::class, 'skpd']);
            Route::get('/sppt/{billId}', [\App\Http\Controllers\DocumentController::class, 'sppt']);
            Route::post('/skpdkbt/{billId}', [\App\Http\Controllers\DocumentController::class, 'skpdkbt']);
            Route::post('/skpdn/{billId}', [\App\Http\Controllers\DocumentController::class, 'skpdn']);
            // Penagihan
            Route::get('/sspd/{billId}', [\App\Http\Controllers\DocumentController::class, 'sspd']);
            Route::get('/ssrd/{billId}', [\App\Http\Controllers\DocumentController::class, 'ssrd']);
            Route::get('/strd/{billId}', [\App\Http\Controllers\DocumentController::class, 'strd']);
            Route::get('/spp/{noticeId}', [\App\Http\Controllers\DocumentController::class, 'spp']);
            Route::get('/spmp/{noticeId}', [\App\Http\Controllers\DocumentController::class, 'spmp']);
        });

        // Modul Penyuluhan & Sosialisasi
        Route::apiResource('tax-educations', TaxEducationController::class);
        Route::post('tax-educations/{taxEducation}/broadcast', [TaxEducationController::class, 'broadcast']);

        // Modul Penertiban Reklame (Visual Audit)
        Route::prefix('billboards')->group(function () {
            Route::post('/{taxObject}/photo', [BillboardAuditController::class, 'uploadPhoto']);
            Route::post('/{taxObject}/verify', [BillboardAuditController::class, 'verify']);
        });

        // Modul Pengaduan (Admin & Pengawas)
        Route::get('/complaints', [ComplaintController::class, 'index']);
        Route::get('/complaints/{complaint}', [ComplaintController::class, 'show']);
        Route::put('/complaints/{complaint}/status', [ComplaintController::class, 'updateStatus']);
    });
});

// ------------------------------------------------------------------------
// BANK H2H GATEWAY (Restricted by BankSecurityCheck middleware)
// ------------------------------------------------------------------------
Route::middleware('bank_h2h')->prefix('v1/bank')->group(function () {
    Route::post('/inquiry', [\App\Http\Controllers\Api\V1\Bank\BankH2HController::class, 'inquiry']);
    Route::post('/payment', [\App\Http\Controllers\Api\V1\Bank\BankH2HController::class, 'payment']);
    Route::post('/reversal', [\App\Http\Controllers\Api\V1\Bank\BankH2HController::class, 'reversal']);
});
