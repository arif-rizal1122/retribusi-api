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

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/opd/register', [OpdController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/citizen/login', [AuthController::class, 'citizenLogin']);
Route::post('/citizen/register', [AuthController::class, 'registerCitizen']);
Route::get('/opds', [OpdController::class, 'index']); // Public access
Route::get('/citizen/bills', [BillController::class, 'citizenBills']); // Public access for demo
Route::get('/verify/bill/{number}', [\App\Http\Controllers\PublicVerificationController::class, 'verifyBill']);
Route::get('/verify/payment/{number}', [\App\Http\Controllers\PublicVerificationController::class, 'verifyPayment']);

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
        if (str_contains($typeName, 'pbb') || str_contains($catName, 'pajak bumi')) {
            $isPbb = true;
        }
    } elseif ($request->type_id) {
        $type = \App\Models\RetributionType::findOrFail($request->type_id);
        $name = $type->name;
        if (str_contains(strtolower($type->name), 'pbb') || str_contains(strtolower($type->category ?? ''), 'pajak bumi')) {
            $isPbb = true;
        }
    }

    if ($isPbb) {
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

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/analytics/realization', [AnalyticsController::class, 'getRealization']);
    Route::get('/analytics/heatmap', [AnalyticsController::class, 'getHeatmapData']);
    
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    // Auth Profile & Password
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/password', [AuthController::class, 'changePassword']);

    // Me / Self Profile (New for Mobile & better control)
    Route::get('/me', [\App\Http\Controllers\MeController::class, 'show']);
    Route::post('/me/update', [\App\Http\Controllers\MeController::class, 'update']);
    
    // Citizen Service Registration
    Route::prefix('citizen/services')->group(function () {
        Route::get('/', [\App\Http\Controllers\CitizenServiceController::class, 'index']);
        Route::get('/pending-periods', [\App\Http\Controllers\CitizenServiceController::class, 'getPendingPeriods']);
        Route::get('/{id}', [\App\Http\Controllers\CitizenServiceController::class, 'show']);
        Route::post('/{id}/register', [\App\Http\Controllers\CitizenServiceController::class, 'register']);
        Route::get('/{id}/bills', [\App\Http\Controllers\CitizenServiceController::class, 'bills']);
    });
    
    // Retribution Types (OPD-scoped)
    Route::apiResource('retribution-types', RetributionTypeController::class);
    
    // Taxpayer Search (New)
    Route::get('/taxpayers/search/{nik}', [\App\Http\Controllers\TaxpayerSearchController::class, 'searchByNik']);

    // Taxpayers (OPD-scoped)
    Route::apiResource('taxpayers', TaxpayerController::class);

    // Tax Objects (OPD-scoped)
    Route::apiResource('tax-objects', TaxObjectController::class);

    // Payments & Dynamic Billing (Virtual Ledger)
    Route::apiResource('bills', BillController::class)->only(['index', 'show', 'store']);
    Route::get('/tax-objects/{taxObject}/pending-periods', [PaymentController::class, 'getPendingPeriods']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::post('/bills/{bill}/pay', [PaymentController::class, 'store']); // Backward compatibility
    Route::get('/bills/{bill}/skrd', [BillController::class, 'exportSKRD']);
    Route::get('/bills/{bill}/sspd', [BillController::class, 'exportSSPD']);
    
    // Verifications
    Route::put('/verifications/{verification}/status', [VerificationController::class, 'updateStatus']);
    Route::apiResource('verifications', VerificationController::class)->only(['index', 'show', 'store']);

    // Zones
    Route::apiResource('zones', ZoneController::class);

    // Retribution Classifications
    Route::apiResource('retribution-classifications', RetributionClassificationController::class);

    // Retribution Rates
    Route::apiResource('retribution-rates', RetributionRateController::class);

    // OPD Management (super_admin only in controller)
    Route::apiResource('opds', OpdController::class)->except(['create', 'edit', 'index']);

    // User Management
    Route::apiResource('users', UserController::class);

    // Dashboard Analytics
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats']);
        Route::get('/revenue-trend', [DashboardController::class, 'getRevenueTrend']);
        Route::get('/map-potentials', [DashboardController::class, 'getMapPotentials']);
    });

    // Pengawas / Surveillance Routes
    Route::prefix('pengawas')->group(function () {
        Route::get('/audit-logs', [\App\Http\Controllers\Pengawas\AuditLogController::class, 'index']);
        Route::get('/anomalies', [\App\Http\Controllers\Pengawas\SurveillanceController::class, 'getAnomalies']);
        Route::get('/compliance-stats', [\App\Http\Controllers\Pengawas\SurveillanceController::class, 'getComplianceStats']);
        
        // Enforcement
        Route::get('/enforcements', [\App\Http\Controllers\Pengawas\EnforcementNoticeController::class, 'index']);
        Route::post('/enforcements', [\App\Http\Controllers\Pengawas\EnforcementNoticeController::class, 'store']);
        Route::post('/enforcements/{id}', [\App\Http\Controllers\Pengawas\EnforcementNoticeController::class, 'update']);
        Route::post('/enforcements/{id}/approve', [\App\Http\Controllers\Pengawas\EnforcementNoticeController::class, 'approve']);
        Route::get('/enforcements/history/{tax_object_id}', [\App\Http\Controllers\Pengawas\EnforcementNoticeController::class, 'getHistory']);
        Route::get('/enforcements/{id}/pdf', [\App\Http\Controllers\Pengawas\EnforcementNoticeController::class, 'generatePDF']);
        
        // Penindakan (SOP 02)
        Route::get('/penindakan', [\App\Http\Controllers\Pengawas\PenindakanController::class, 'index']);
        Route::post('/penindakan/issue-skpdkb', [\App\Http\Controllers\Pengawas\PenindakanController::class, 'generateSKPDKB']);
    });

    // Reporting
    Route::prefix('reports')->group(function () {
        Route::get('/summary', [ReportController::class, 'getSummary']);
        Route::get('/recent', [ReportController::class, 'getRecent']);
        Route::get('/petugas-performance', [ReportController::class, 'getPetugasPerformance']);
        
        // Monthly Turnover Reports (SPTPD)
        Route::get('/monthly', [\App\Http\Controllers\MonthlyReportController::class, 'index']);
        Route::put('/monthly/{report}/validate', [\App\Http\Controllers\MonthlyReportController::class, 'validateReport']);
    });

    // Citizen Specific Actions
    Route::prefix('citizen')->group(function () {
        Route::post('/reports', [\App\Http\Controllers\MonthlyReportController::class, 'store']);
        Route::get('/reports', [\App\Http\Controllers\MonthlyReportController::class, 'index']);
    });

    // Penalty Waivers (Tax Amnesty)
    Route::prefix('amnesty')->group(function () {
        Route::get('/', [\App\Http\Controllers\PenaltyWaiverController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\PenaltyWaiverController::class, 'store']);
        Route::post('/{id}/approve', [\App\Http\Controllers\PenaltyWaiverController::class, 'approve']);
        Route::post('/{id}/reject', [\App\Http\Controllers\PenaltyWaiverController::class, 'reject']);
    });

    // E-Registry & TTE
    Route::prefix('tte')->group(function () {
        Route::get('/documents', [\App\Http\Controllers\Api\EregistryController::class, 'index']);
        Route::post('/sign', [\App\Http\Controllers\BillController::class, 'signTTE']);
        Route::get('/verify/{number}', [\App\Http\Controllers\Api\EregistryController::class, 'verify'])->withoutMiddleware('auth:sanctum');
    });
});
