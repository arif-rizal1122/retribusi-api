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
use App\Http\Controllers\MutationController;
use App\Http\Controllers\PbbClassificationController;
use App\Http\Controllers\PbbBapendaController;
use App\Http\Controllers\TaxEducationController;
use App\Http\Controllers\BillboardAuditController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\Api\SimpadKoneksiController;
use App\Http\Controllers\Api\RatingController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public auth routes with strict throttle (prevent brute force)
Route::group(['middleware' => 'throttle:10,1'], function () {
    Route::post('/opd/register', [OpdController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/citizen/login', [AuthController::class, 'citizenLogin']); // Keep for backward compatibility
    Route::post('/citizen/request-otp', [AuthController::class, 'requestCitizenOtp']);
    Route::post('/citizen/verify-otp', [AuthController::class, 'verifyCitizenOtp']);
    Route::post('/citizen/register', [AuthController::class, 'registerCitizen']);
    Route::post('/nik-lookup', \App\Http\Controllers\NikLookupController::class)->middleware('throttle:30,1');
});

// Other public routes
Route::get('/opds', [OpdController::class, 'index']); // Public access
Route::get('/verify/bill/{number}', [\App\Http\Controllers\PublicVerificationController::class, 'verifyBill']);
Route::get('/verify/payment/{number}', [\App\Http\Controllers\PublicVerificationController::class, 'verifyPayment']);

// Public: Documents (PDF)
Route::get('/public/pdf/npwpd/{id}', [\App\Http\Controllers\PdfController::class, 'generateNpwpd']);
Route::get('/public/pdf/skrd/{billId}', [\App\Http\Controllers\DocumentController::class, 'skrd']);
Route::get('/public/pdf/skpd/{billId}', [\App\Http\Controllers\DocumentController::class, 'skpd']);
Route::get('/public/pdf/sspd/{billId}', [\App\Http\Controllers\DocumentController::class, 'sspd']);
Route::get('/public/pdf/sppt/{billId}', [\App\Http\Controllers\DocumentController::class, 'sppt']);
Route::get('/public/pdf/surat-teguran/{noticeId}', [\App\Http\Controllers\DocumentController::class, 'suratTeguran']);

// Public: SSPD-BPHTB (verifikasi & unduh PDF tanpa login)
Route::get('/public/sspd/verify/{sspdNumber}', [\App\Http\Controllers\H2HBphtbController::class, 'verifySspd']);
Route::get('/public/sspd/pdf/{sspdNumber}', [\App\Http\Controllers\H2HBphtbController::class, 'sspdPdfByNumber']);

// Tax Simulation (public, no auth needed)
// Public: Cek NIB & Candidate Penyempurnaan Data Diri (PTSP-13)
Route::get('/public/nib/lookup', [\App\Http\Controllers\NibPublicController::class, 'lookup']);
Route::get('/public/nib/check', [\App\Http\Controllers\NibPublicController::class, 'check']);
Route::post('/public/nib/candidate', [\App\Http\Controllers\NibPublicController::class, 'storeCandidate']);

// Public: Banner beranda mobile
Route::get('/public/banners', [\App\Http\Controllers\BannerController::class, 'indexPublic']);

// Public self-registration portal (pendaftaran warga)
Route::prefix('public')->group(function () {
    Route::get('/retribution-types', [\App\Http\Controllers\PublicRegistrationController::class, 'getTypes']);
    Route::get('/retribution-classifications', [\App\Http\Controllers\PublicRegistrationController::class, 'getClassifications']);
    Route::get('/opds', [\App\Http\Controllers\PublicRegistrationController::class, 'getOpds']);
    Route::get('/taxpayers/check-nik/{nik}', [\App\Http\Controllers\PublicRegistrationController::class, 'checkNik']);
    Route::post('/register-taxpayer', [\App\Http\Controllers\PublicRegistrationController::class, 'register']);
    Route::get('/mblb-vehicles', [\App\Http\Controllers\Api\MblbVehicleController::class, 'index']);
});

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

    $vars = $request->variables;
    $hasDirectPbbFormulaVariables = $isPbb && $formula && array_key_exists('njop', $vars);

    if ($isPbb && !$request->calculation_formula && !$hasDirectPbbFormulaVariables) {
        $pbbService = app(\App\Services\PbbCalculationService::class);
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

    // Dashboard agregat M39 (semua role internal; scope OPD via middleware opd_scope)
    Route::middleware('opd_scope')->prefix('dashboard')->group(function () {
        Route::get('/pad-summary', [\App\Http\Controllers\Api\DashboardController::class, 'padSummary']);
        Route::get('/opd-services', [\App\Http\Controllers\Api\DashboardController::class, 'opdServices']);
        Route::get('/sector-data', [\App\Http\Controllers\Api\DashboardController::class, 'sectorData']);
        Route::get('/daily-tasks', [\App\Http\Controllers\Api\DashboardController::class, 'dailyTasks']);
        Route::get('/anomalies', [\App\Http\Controllers\Api\DashboardController::class, 'anomalies']);
    });
    
    // M43 — Verifikasi teknis proteksi kebakaran oleh Damkar (opd_id=13, verifikator PBG, NON-retribusi)
    Route::prefix('damkar')->group(function () {
        Route::get('/verifications', [\App\Http\Controllers\DamkarVerificationController::class, 'index']);
        Route::post('/verifications/{id}/decision', [\App\Http\Controllers\DamkarVerificationController::class, 'decision']);
    });

    // Modul Parkir Dishub — Kasir Cepat Jukir & Quadruple-Lock System
    Route::prefix('parking')->group(function () {
        Route::get('/locations', [\App\Http\Controllers\ParkingController::class, 'locations']);
        Route::post('/sessions', [\App\Http\Controllers\ParkingController::class, 'recordSession']);
        Route::get('/shift-summary', [\App\Http\Controllers\ParkingController::class, 'shiftSummary']);
        Route::post('/shift/open', [\App\Http\Controllers\ParkingController::class, 'openShift']);
        Route::post('/shift/close', [\App\Http\Controllers\ParkingController::class, 'closeShift']);
        Route::get('/dashboard', [\App\Http\Controllers\ParkingController::class, 'dashboard']);

        // Quadruple-Lock System Endpoints
        Route::get('/jukir/profile', [\App\Http\Controllers\ParkingController::class, 'jukirProfile']);
        Route::post('/jukir/topup', [\App\Http\Controllers\ParkingController::class, 'topupDeposit']);
        Route::post('/sessions/prepaid-cash', [\App\Http\Controllers\ParkingController::class, 'recordPrepaidCash']);
        Route::get('/inspector/spot-check', [\App\Http\Controllers\ParkingController::class, 'spotCheck']);
        Route::post('/inspector/sanction', [\App\Http\Controllers\ParkingController::class, 'submitSanction']);
        Route::get('/admin/gis-heatmap', [\App\Http\Controllers\ParkingController::class, 'gisHeatmap']);
        Route::get('/admin/anomalies', [\App\Http\Controllers\ParkingController::class, 'anomalies']);
        Route::post('/citizen/claim-receipt', [\App\Http\Controllers\ParkingController::class, 'claimReceipt']);
    });
    
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
        Route::get('/bills', [BillController::class, 'citizenBills']);
        Route::get('/tax-objects', [\App\Http\Controllers\CitizenServiceController::class, 'taxObjects']);

        // ASET DAERAH (JU-ASET) — kategori, unit, form template, sewa
        Route::get('/asset/categories', [\App\Http\Controllers\CitizenAssetController::class, 'categories']);
        Route::get('/asset/items', [\App\Http\Controllers\CitizenAssetController::class, 'items']);
        Route::get('/asset/items/{id}/availability', [\App\Http\Controllers\CitizenAssetController::class, 'availability']);
        Route::get('/asset/form-template/{categoryId}', [\App\Http\Controllers\CitizenAssetController::class, 'formTemplate']);
        Route::post('/asset/rentals', [\App\Http\Controllers\CitizenAssetController::class, 'storeRental']);
        Route::get('/asset/rentals', [\App\Http\Controllers\CitizenAssetController::class, 'myRentals']);
        Route::get('/asset/rentals/{id}', [\App\Http\Controllers\CitizenAssetController::class, 'showRental']);

        // Pembayaran
        Route::get('/payments/history', [PaymentController::class, 'history']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::post('/payment-requests', [\App\Http\Controllers\Api\V1\Payment\CitizenPaymentRequestController::class, 'store']);
        Route::get('/payment-requests/{paymentRequest}', [\App\Http\Controllers\Api\V1\Payment\CitizenPaymentRequestController::class, 'show']);
        Route::post('/payment-requests/{paymentRequest}/refresh', [\App\Http\Controllers\Api\V1\Payment\CitizenPaymentRequestController::class, 'refresh']);
        Route::post('/payment-requests/{paymentRequest}/cancel', [\App\Http\Controllers\Api\V1\Payment\CitizenPaymentRequestController::class, 'cancel']);

        // Merchant AFT (Kalkulator Bisnis)
        Route::prefix('merchant')->group(function () {
            Route::post('/submit-omzet', [\App\Http\Controllers\Api\Merchant\KalkulatorBisnisController::class, 'submitOmzet']);
            Route::get('/aft-history', [\App\Http\Controllers\Api\Merchant\KalkulatorBisnisController::class, 'getAftHistory']);
            Route::get('/aft-availability', [\App\Http\Controllers\Api\Merchant\KalkulatorBisnisController::class, 'getAftAvailability']);
        });

        Route::post('/reports', [\App\Http\Controllers\MonthlyReportController::class, 'store']);
        Route::get('/reports', [\App\Http\Controllers\MonthlyReportController::class, 'index']);
        Route::post('/complaints', [ComplaintController::class, 'store']);
        Route::get('/complaints', [ComplaintController::class, 'citizenIndex']);

        // Rating layanan (PENILAIAN-1) — 9 Agu 2026
        Route::post('/ratings', [RatingController::class, 'store']);
        Route::get('/ratings', [RatingController::class, 'citizenIndex']);

        // Pendaftaran NOP Mandiri (Warga)
        Route::post('/pbb/nop-applications', [\App\Http\Controllers\PbbNopApplicationController::class, 'store']);
        Route::get('/pbb/nop-applications', [\App\Http\Controllers\PbbNopApplicationController::class, 'index']);

        // QR Wajib Pajak
        Route::get('/my-qr', [\App\Http\Controllers\QrController::class, 'citizenMyQr']);

        // Permohonan Perubahan Data (warga)
        Route::get('/change-requests', [\App\Http\Controllers\TaxObjectChangeRequestController::class, 'citizenIndex']);
    });

    // PBB Bapenda Citizen Actions
    Route::group(['prefix' => 'pbb/bapenda'], function () {
        Route::post('/link-nop', [PbbBapendaController::class, 'linkNop']);
        Route::delete('/unlink-nop/{id}', [PbbBapendaController::class, 'unlinkNop']);
        Route::get('/my-objects', [PbbBapendaController::class, 'myObjects']);
        Route::get('/my-transactions', [PbbBapendaController::class, 'myTransactions']);
        Route::post('/pay', [PbbBapendaController::class, 'pay']);
        Route::get('/download-sppt', [PbbBapendaController::class, 'downloadSPPT']);
        Route::get('/transactions/{transaction}/receipt', [PbbBapendaController::class, 'downloadReceipt']);
        Route::post('/payment-request', [PbbBapendaController::class, 'paymentRequest']);
    });

    // H2H BPHTB — Wajib Pajak / Notaris / PPAT (scope_user)
    Route::prefix('h2h/bphtb')->group(function () {
        Route::get('/', [\App\Http\Controllers\H2HBphtbController::class, 'submissions']);
        Route::get('/mine', [\App\Http\Controllers\H2HBphtbController::class, 'mySubmissions']);
        Route::post('/{submission}/documents', [\App\Http\Controllers\H2HBphtbController::class, 'uploadDocument']);
        Route::delete('/{submission}/documents/{document}', [\App\Http\Controllers\H2HBphtbController::class, 'deleteDocument']);
        Route::post('/{submission}/pay', [\App\Http\Controllers\H2HBphtbController::class, 'pay']);
        Route::get('/{submission}', [\App\Http\Controllers\H2HBphtbController::class, 'show']);
    });

    // Citizen can edit/cancel their own pending service registration objects.
    Route::match(['put', 'patch', 'post'], '/tax-objects/{taxObject}', [TaxObjectController::class, 'update']);
    Route::delete('/tax-objects/{taxObject}', [TaxObjectController::class, 'destroy']);

    // Resolve lokasi (reverse geocode) — map picker mobile
    Route::post('/location/resolve', [\App\Http\Controllers\LocationController::class, 'resolve']);

    // ------------------------------------------------------------------------
    // Admin & Petugas ONLY (Restricted by EnsureAdmin middleware)
    // ------------------------------------------------------------------------
    Route::middleware('admin')->group(function () {
        // Banner (CRUD admin) — tabel banners sudah ada di prod
        Route::get('/banners', [\App\Http\Controllers\BannerController::class, 'index']);
        Route::post('/banners', [\App\Http\Controllers\BannerController::class, 'store']);
        Route::put('/banners/{id}', [\App\Http\Controllers\BannerController::class, 'update']);
        Route::delete('/banners/{id}', [\App\Http\Controllers\BannerController::class, 'destroy']);

        // WA Gateway Management (Baileys)
        Route::prefix('admin/wa-gateway')->group(function () {
            Route::get('/status', [\App\Http\Controllers\Api\V1\Admin\WaGatewayController::class, 'status']);
            Route::get('/qr', [\App\Http\Controllers\Api\V1\Admin\WaGatewayController::class, 'qr']);
            Route::post('/send-test', [\App\Http\Controllers\Api\V1\Admin\WaGatewayController::class, 'sendTest']);
            Route::post('/logout', [\App\Http\Controllers\Api\V1\Admin\WaGatewayController::class, 'logout']);
        });

        // H2H Monitor (read-only dashboard — Fase 1)
        Route::prefix('admin/h2h-monitor')->group(function () {
            Route::get('/overview', [\App\Http\Controllers\Api\V1\Admin\H2HMonitorController::class, 'overview']);
            Route::get('/history/{connector}', [\App\Http\Controllers\Api\V1\Admin\H2HMonitorController::class, 'history']);
        });

        // Pendaftaran NOP (Warga — verifikasi admin)
        Route::get('/nop-applications', [\App\Http\Controllers\PbbNopApplicationController::class, 'index']);
        Route::get('/nop-applications/{id}', [\App\Http\Controllers\PbbNopApplicationController::class, 'show']);
        Route::post('/nop-applications/{id}/status', [\App\Http\Controllers\PbbNopApplicationController::class, 'updateStatus']);

        // H2H BPN / BPHTB
        Route::prefix('h2h/bphtb')->group(function () {
            Route::get('/mappings', [\App\Http\Controllers\H2HBphtbController::class, 'mappings']);
            Route::post('/simulate', [\App\Http\Controllers\H2HBphtbController::class, 'simulate']);
            Route::post('/submit', [\App\Http\Controllers\H2HBphtbController::class, 'submit']);
            Route::post('/{submission}/verify', [\App\Http\Controllers\H2HBphtbController::class, 'verify']);
            Route::post('/{submission}/issue-sspd', [\App\Http\Controllers\H2HBphtbController::class, 'issueSspd']);
            Route::post('/{submission}/sspd', [\App\Http\Controllers\H2HBphtbController::class, 'issueSspd']);
            Route::get('/{submission}/sspd/pdf', [\App\Http\Controllers\H2HBphtbController::class, 'sspdPdf']);
        });

        // Integrasi NIB-BPN & NOP PBB-P2 (Aplikasi Peta Pajak — Standar Output KPK & ATR/BPN)
        Route::prefix('peta-pajak')->group(function () {
            Route::get('/stats', [\App\Http\Controllers\PetaPajakController::class, 'stats']);
            Route::get('/parcels', [\App\Http\Controllers\PetaPajakController::class, 'parcels']);
            Route::get('/mappings', [\App\Http\Controllers\PetaPajakController::class, 'mappings']);
            Route::post('/match', [\App\Http\Controllers\PetaPajakController::class, 'match']);
            Route::post('/auto-match', [\App\Http\Controllers\PetaPajakController::class, 'autoMatch']);
            Route::get('/export-kpk', [\App\Http\Controllers\PetaPajakController::class, 'exportKpk']);
            Route::post('/import-dhkp', [\App\Http\Controllers\PetaPajakController::class, 'importDhkp']);
            Route::get('/dhkp-template', [\App\Http\Controllers\PetaPajakController::class, 'dhkpTemplate']);
            Route::get('/tax-gap', [\App\Http\Controllers\PetaPajakController::class, 'taxGap']);
        });

        // H2H NIB-NOP Mapping (Sinkronisasi BHUMI BPN)
        Route::prefix('h2h/nib-nop')->group(function () {
            Route::get('/mappings', [\App\Http\Controllers\NibNopMappingController::class, 'index']);
            Route::post('/mappings', [\App\Http\Controllers\NibNopMappingController::class, 'store']);
            Route::post('/mappings/{id}/sync', [\App\Http\Controllers\NibNopMappingController::class, 'syncOne']);
            Route::get('/stats', [\App\Http\Controllers\NibNopMappingController::class, 'stats']);
        });

        // Notaris / PPAT Approval
        Route::prefix('notaris-approvals')->group(function () {
            Route::get('/', [\App\Http\Controllers\NotarisApprovalController::class, 'index']);
            Route::post('/{id}/approve', [\App\Http\Controllers\NotarisApprovalController::class, 'approve']);
            Route::post('/{id}/reject', [\App\Http\Controllers\NotarisApprovalController::class, 'reject']);
        });

        // Permohonan Perubahan Data (admin)
        Route::get('/change-requests', [\App\Http\Controllers\TaxObjectChangeRequestController::class, 'index']);
        Route::post('/change-requests/{changeRequest}/status', [\App\Http\Controllers\TaxObjectChangeRequestController::class, 'updateStatus']);

        // Pendaftaran NOP alias (tab admin PBB)
        Route::get('/pbb/bapenda/nop-applications', [\App\Http\Controllers\PbbNopApplicationController::class, 'index']);
        Route::post('/pbb/bapenda/nop-applications/{id}/status', [\App\Http\Controllers\PbbNopApplicationController::class, 'updateStatus']);

        // Pelaporan E-SPTPD
        Route::post('/sptpd', [\App\Http\Controllers\SptpdController::class, 'store']);

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

        // ASET DAERAH (JU-ASET) — Admin: dashboard, kelola kategori & unit, verifikasi sewa
        Route::get('/asset/dashboard', [\App\Http\Controllers\AdminAssetController::class, 'dashboard']);
        Route::get('/asset/rentals', [\App\Http\Controllers\AdminAssetController::class, 'rentals']);
        Route::post('/asset/rentals/{id}/survey', [\App\Http\Controllers\AdminAssetController::class, 'submitSurvey']);
        Route::post('/asset/rentals/{id}/contract', [\App\Http\Controllers\AdminAssetController::class, 'generateContract']);
        Route::post('/asset/rentals/{id}/verify', [\App\Http\Controllers\AdminAssetController::class, 'verify']);
        Route::post('/asset/rentals/{id}/approve', [\App\Http\Controllers\AdminAssetController::class, 'approve']);
        Route::post('/asset/rentals/{id}/reject', [\App\Http\Controllers\AdminAssetController::class, 'reject']);
        Route::post('/asset/rentals/{id}/complete', [\App\Http\Controllers\AdminAssetController::class, 'complete']);
        Route::get('/asset/categories', [\App\Http\Controllers\AdminAssetController::class, 'categories']);
        Route::post('/asset/categories', [\App\Http\Controllers\AdminAssetController::class, 'storeCategory']);
        Route::put('/asset/categories/{id}', [\App\Http\Controllers\AdminAssetController::class, 'updateCategory']);
        Route::delete('/asset/categories/{id}', [\App\Http\Controllers\AdminAssetController::class, 'deleteCategory']);
        Route::get('/asset/items', [\App\Http\Controllers\AdminAssetController::class, 'items']);
        Route::post('/asset/items', [\App\Http\Controllers\AdminAssetController::class, 'storeItem']);
        Route::put('/asset/items/{id}', [\App\Http\Controllers\AdminAssetController::class, 'updateItem']);
        Route::delete('/asset/items/{id}', [\App\Http\Controllers\AdminAssetController::class, 'deleteItem']);
        
        Route::get('/analytics/realization', [AnalyticsController::class, 'getRealization']);
        Route::get('/analytics/heatmap', [AnalyticsController::class, 'getHeatmapData']);
        Route::get('/analytics/object-performance', [AnalyticsController::class, 'getObjectPerformance']);
        Route::get('/analytics/classification-performance', [AnalyticsController::class, 'getClassificationPerformance']);
        Route::get('/analytics/lra-report', [AnalyticsController::class, 'getLraReport']);
        Route::apiResource('retribution-types', RetributionTypeController::class);
        Route::get('/taxpayers/search/{nik}', [\App\Http\Controllers\TaxpayerSearchController::class, 'searchByNik']);
        Route::apiResource('taxpayers', TaxpayerController::class);
        Route::apiResource('tax-objects', TaxObjectController::class);
        Route::apiResource('bills', BillController::class)->only(['index', 'store']);
        Route::post('/bills/checkout', [BillController::class, 'checkout']);

        // Mutasi Objek Pajak (Balik Nama / Transfer Aset)
        Route::get('/mutations', [MutationController::class, 'index']);
        Route::post('/mutations', [MutationController::class, 'store']);
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

        // Dashboard Khusus Admin PTSP (DPMPTSP) — RBAC role admin_ptsp
        Route::prefix('ptsp')->group(function () {
            Route::get('/dashboard/stats', [\App\Http\Controllers\PtspDashboardController::class, 'stats']);
            Route::get('/dashboard/growth', [\App\Http\Controllers\PtspDashboardController::class, 'growth']);
            Route::get('/dashboard/related-services', [\App\Http\Controllers\PtspDashboardController::class, 'relatedServices']);
            Route::get('/dashboard/taxpayers', [\App\Http\Controllers\PtspDashboardController::class, 'taxpayers']);
            Route::get('/candidates', [\App\Http\Controllers\PtspCandidateController::class, 'index']);
            Route::post('/candidates/{id}/nib', [\App\Http\Controllers\PtspCandidateController::class, 'markNib']);
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

        // Rating layanan (admin/pengawas) — PENILAIAN-1
        Route::get('/ratings', [RatingController::class, 'index']);

        // M36 - Deteksi titik adopsi kalkulator (dashboard cakupan)
        Route::get('/business-units', [\App\Http\Controllers\BusinessUnitController::class, 'index']);

        // M37-2 - Integrasi akun OPD pengelola per sektor usaha (Dok 143)
        // Scope OPD via middleware opd_scope (konsisten dengan pola dashboard agregat)
        Route::prefix('opd')->middleware('opd_scope')->group(function () {
            Route::get('/sector-data', [\App\Http\Controllers\OpdSectorDataController::class, 'index']);
        });
    });
});

// ------------------------------------------------------------------------

// ------------------------------------------------------------------------
// BANK SULTRA — callback VA (dipanggil Bank Sultra saat pembayaran masuk)
// ------------------------------------------------------------------------
Route::prefix('h2h/bank-sultra')->group(function () {
    Route::post('/callback', [\App\Http\Controllers\Api\H2h\BpdWebhookController::class, 'incomingTransfer']);
});

// H2H BPN — endpoint eksternal yang dipanggil sistem BPN (KKP).
// Autentikasi via header X-H2H-Key (dicek di dalam controller).
// M21-1466 (F3.2): bridging status lunas ke BPN (SPLP).
// ------------------------------------------------------------------------
Route::prefix('h2h/bpn')->group(function () {
    Route::post('/check-pbb', [\App\Http\Controllers\H2HBpnController::class, 'checkPbb']);
    Route::post('/verify-payment', [\App\Http\Controllers\H2HBpnController::class, 'verifyPayment']);
    Route::post('/splp/update', [\App\Http\Controllers\H2HBpnController::class, 'updateSplp']);
    Route::post('/spatial/reverse-lookup', [\App\Http\Controllers\H2HBpnController::class, 'spatialReverseLookup']);
    Route::post('/spatial/sync', [\App\Http\Controllers\H2HBpnController::class, 'syncSpatial']);
});

// Endpoint reverse lookup spasial untuk mobile warga dan admin web
Route::post('/h2h/bhumi/reverse-lookup', [\App\Http\Controllers\H2HBpnController::class, 'spatialReverseLookup']);

// ------------------------------------------------------------------------
// BANK H2H GATEWAY (Restricted by BankSecurityCheck middleware)
// ------------------------------------------------------------------------
Route::middleware('bank_h2h')->prefix('v1/bank')->group(function () {
    Route::post('/inquiry', [\App\Http\Controllers\Api\V1\Bank\BankH2HController::class, 'inquiry']);
    Route::post('/payment', [\App\Http\Controllers\Api\V1\Bank\BankH2HController::class, 'payment']);
    Route::post('/reversal', [\App\Http\Controllers\Api\V1\Bank\BankH2HController::class, 'reversal']);
});

// ------------------------------------------------------------------------
// BANK H2H GATEWAY SNAP BI (BRI) — callback WAJIB (jangan dihapus)
// ------------------------------------------------------------------------
Route::group(['prefix' => 'snap', 'namespace' => '\App\Http\Controllers\Api\V1\Payment'], function () {
    // Auth (B2B Access Token)
    Route::post('/v1.1/access-token/b2b', 'SnapBIController@getAccessToken');

    // QRIS
    Route::post('/v1.1/qr/qr-mpm-notify', 'SnapBIController@qrisNotify');

    // BRIVA
    Route::post('/v1.0/access-token/b2b', 'SnapBIController@getAccessToken');
    Route::post('/v1.0/transfer-va/inquiry', 'SnapBIController@brivaInquiry');
    Route::post('/v1.0/transfer-va/payment', 'SnapBIController@brivaPayment');

    // BTN Virtual Account
    Route::post('/v1/transfer-va/inquiry', 'BtnSnapController@inquiry');
    Route::post('/v1/transfer-va/payment', 'BtnSnapController@payment');

    // BNI VA Billing (callback v1.1 - instruksi BNI 30 Agu 2026)
    Route::post('/v1.1/transfer-va/inquiry', 'BniSnapController@inquiry');
    Route::post('/v1.1/transfer-va/payment', 'BniSnapController@payment');
});

// ------------------------------------------------------------------------
// AUTO DEDUCT & CORRECTION
// ------------------------------------------------------------------------
Route::middleware('auth:sanctum')->prefix('auto-deduct')->group(function () {
    Route::post('/record', [\App\Http\Controllers\AutoDeductController::class, 'recordTransaction']);
    Route::post('/process', [\App\Http\Controllers\AutoDeductController::class, 'processPayment']);
    Route::get('/status', [\App\Http\Controllers\AutoDeductController::class, 'getStatus']);
    Route::get('/notifications', [\App\Http\Controllers\AutoDeductController::class, 'getNotifications']);
    Route::post('/reminders', [\App\Http\Controllers\AutoDeductController::class, 'sendReminders']);
});

Route::middleware('auth:sanctum')->prefix('corrections')->group(function () {
    Route::post('/{transaction}/void', [\App\Http\Controllers\CorrectionController::class, 'void']);
    Route::post('/{transaction}/adjust-price', [\App\Http\Controllers\CorrectionController::class, 'adjustPrice']);
    Route::post('/{transaction}/refund', [\App\Http\Controllers\CorrectionController::class, 'refund']);
    Route::get('/{transaction}/history', [\App\Http\Controllers\CorrectionController::class, 'history']);
});

Route::post('/auto-deduct/webhook', [\App\Http\Controllers\AutoDeductWebhookController::class, 'handle'])
    ->middleware('auto_deduct_access:webhook');

// ------------------------------------------------------------------------
// DEPLOY HOOK (Staging only - protected by X-Deploy-Secret header)
// ------------------------------------------------------------------------
Route::post('/admin/deploy-hook', [\App\Http\Controllers\Admin\DeployHookController::class, 'handle']);
