<?php

use App\Http\Controllers\Admin\DeployHookController;
use App\Http\Controllers\AdminAssetController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Api\EregistryController;
use App\Http\Controllers\Api\H2h\BpdWebhookController;
use App\Http\Controllers\Api\MblbVehicleController;
use App\Http\Controllers\Api\MediaProxyController;
use App\Http\Controllers\Api\Merchant\KalkulatorBisnisController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\SimpadKoneksiController;
use App\Http\Controllers\Api\TpiTransactionController;
use App\Http\Controllers\Api\V1\Admin\H2HMonitorController;
use App\Http\Controllers\Api\V1\Admin\WaGatewayController;
use App\Http\Controllers\Api\V1\Bank\BankH2HController;
use App\Http\Controllers\Api\V1\Dishub\HarborProxyGtController;
use App\Http\Controllers\Api\V1\Dishub\LakologouManifestController;
use App\Http\Controllers\Api\V1\Disperindag\MarketController;
use App\Http\Controllers\Api\V1\Dlh\DlhLabServiceController;
use App\Http\Controllers\Api\V1\Dlh\DlhMarketRemittanceController;
use App\Http\Controllers\Api\V1\Dlh\DlhReportController;
use App\Http\Controllers\Api\V1\Dlh\DlhSpatialMonitoringController;
use App\Http\Controllers\Api\V1\Dlh\DlhTrashBillingController;
use App\Http\Controllers\Api\V1\Payment\CitizenPaymentRequestController;
use App\Http\Controllers\Api\V1\Public\PublicTaxObjectBillController;
use App\Http\Controllers\Api\V1\Pupr\AssetCalendarController;
use App\Http\Controllers\Api\V1\Pupr\InspectionController;
use App\Http\Controllers\Api\V1\Pupr\TrontonBundlingController;
use App\Http\Controllers\Api\V1\Rusun\RusunRoomController;
use App\Http\Controllers\Api\V1\ServiceApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutoDeductController;
use App\Http\Controllers\AutoDeductWebhookController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BillboardAuditController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\BusinessUnitController;
use App\Http\Controllers\CitizenAssetController;
use App\Http\Controllers\CitizenServiceController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\DamkarVerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\H2HBphtbController;
use App\Http\Controllers\H2HBpnController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\MutationController;
use App\Http\Controllers\NibNopMappingController;
use App\Http\Controllers\NibPublicController;
use App\Http\Controllers\NikLookupController;
use App\Http\Controllers\NotarisApprovalController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\OpdSectorDataController;
use App\Http\Controllers\ParkingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PbbBapendaController;
use App\Http\Controllers\PbbClassificationController;
use App\Http\Controllers\PbbMutationController;
use App\Http\Controllers\PbbNopApplicationController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\PenaltyWaiverController;
use App\Http\Controllers\Pengawas\AuditLogController;
use App\Http\Controllers\Pengawas\EnforcementNoticeController;
use App\Http\Controllers\Pengawas\PenindakanController;
use App\Http\Controllers\Pengawas\SurveillanceController;
use App\Http\Controllers\PertanahanAnalyticsController;
use App\Http\Controllers\PetaPajakController;
use App\Http\Controllers\PetugasTaskController;
use App\Http\Controllers\PtspCandidateController;
use App\Http\Controllers\PtspDashboardController;
use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\PublicVerificationController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RetributionClassificationController;
use App\Http\Controllers\RetributionRateController;
use App\Http\Controllers\RetributionTypeController;
use App\Http\Controllers\SpatialController;
use App\Http\Controllers\SpotCheckController;
use App\Http\Controllers\SptpdController;
use App\Http\Controllers\TaxEducationController;
use App\Http\Controllers\TaxObjectChangeRequestController;
use App\Http\Controllers\TaxObjectController;
use App\Http\Controllers\TaxpayerController;
use App\Http\Controllers\TaxpayerSearchController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\ZoneController;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Services\FormulaParserService;
use App\Services\PbbCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    Route::post('/nik-lookup', NikLookupController::class)->middleware('throttle:30,1');
});

// Other public routes
Route::get('/opds', [OpdController::class, 'index']); // Public access
Route::get('/verify/bill/{number}', [PublicVerificationController::class, 'verifyBill']);
Route::get('/verify/payment/{number}', [PublicVerificationController::class, 'verifyPayment']);
// Verifikasi publik dokumen lain (SPP, teguran, WAIVER, SKT, SPMP, LKOK).
// Sebelumnya QR dokumen-dokumen ini mengarah ke route yang TIDAK ADA (404).
Route::get('/verify/doc/{type}/{number}', [PublicVerificationController::class, 'verifyDocument']);
Route::get('/verify/dlh-lab/{orderNumber}', [DlhLabServiceController::class, 'verifyPublic']);

// Public: Documents (PDF)
Route::get('/public/pdf/npwpd/{id}', [PdfController::class, 'generateNpwpd']);
Route::get('/public/pdf/skrd/{billId}', [DocumentController::class, 'skrd']);
Route::get('/public/pdf/skpd/{billId}', [DocumentController::class, 'skpd']);
Route::get('/public/pdf/sspd/{billId}', [DocumentController::class, 'sspd']);
Route::get('/public/media/proxy', [MediaProxyController::class, 'proxy']);

Route::get('/public/pdf/ssrd/{billId}', [DocumentController::class, 'ssrd']);
Route::get('/public/pdf/sipab/{billId}', [DocumentController::class, 'sipab']);
Route::get('/public/pdf/sppt/{billId}', [DocumentController::class, 'sppt']);
Route::get('/public/pdf/surat-teguran/{noticeId}', [DocumentController::class, 'suratTeguran']);

// Public: SSPD-BPHTB (verifikasi & unduh PDF tanpa login)
Route::get('/public/sspd/verify/{sspdNumber}', [H2HBphtbController::class, 'verifySspd']);
Route::get('/public/sspd/pdf/{sspdNumber}', [H2HBphtbController::class, 'sspdPdfByNumber']);

// Tax Simulation (public, no auth needed)
// Public: Cek NIB & Candidate Penyempurnaan Data Diri (PTSP-13)
Route::get('/public/nib/lookup', [NibPublicController::class, 'lookup']);
Route::get('/public/nib/check', [NibPublicController::class, 'check']);
Route::post('/public/nib/candidate', [NibPublicController::class, 'storeCandidate']);

// Public: Banner beranda mobile
Route::get('/public/banners', [BannerController::class, 'indexPublic']);

// Public: Zero-Login Stiker QR Persil & Pembayaran Mandiri
$publicObjectRoutes = function () {
    Route::get('/objects/{code}/bill', [PublicTaxObjectBillController::class, 'showBill']);
    Route::post('/objects/{code}/pay', [PublicTaxObjectBillController::class, 'generatePayInstructions']);
};
Route::prefix('public')->group($publicObjectRoutes);
Route::prefix('v1/public')->group($publicObjectRoutes);

// Public self-registration portal (pendaftaran warga)
Route::prefix('public')->group(function () {
    Route::get('/retribution-types', [PublicRegistrationController::class, 'getTypes']);
    Route::get('/retribution-classifications', [PublicRegistrationController::class, 'getClassifications']);
    Route::get('/opds', [PublicRegistrationController::class, 'getOpds']);
    Route::get('/taxpayers/check-nik/{nik}', [PublicRegistrationController::class, 'checkNik']);
    Route::post('/register-taxpayer', [PublicRegistrationController::class, 'register']);
    Route::get('/mblb-vehicles', [MblbVehicleController::class, 'index']);
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
        $classification = RetributionClassification::with('retributionType')->findOrFail($request->classification_id);
        if (! $formula) {
            $formula = $classification->calculation_formula;
        }
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
        $type = RetributionType::findOrFail($request->type_id);
        $name = $type->name;
        $typeName = strtolower($type->name ?? '');
        $catName = strtolower($type->category ?? '');

        if (str_contains($typeName, 'pbb') || str_contains($catName, 'pajak bumi')) {
            $isPbb = true;
        }
    }

    $vars = $request->variables;
    $hasDirectPbbFormulaVariables = $isPbb && $formula && array_key_exists('njop', $vars);

    if ($isPbb && ! $request->calculation_formula && ! $hasDirectPbbFormulaVariables) {
        $pbbService = app(PbbCalculationService::class);
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
            'formatted' => 'Rp '.number_format($resultData['pbb_terhutang'], 0, ',', '.'),
        ]);
    }

    if (! $formula) {
        return response()->json(['error' => 'Rumus perhitungan tidak ditemukan.'], 422);
    }

    $parser = new FormulaParserService;
    $result = $parser->calculate($formula, $request->variables);

    return response()->json([
        'classification' => $name,
        'formula' => $formula,
        'variables' => $request->variables,
        'result' => $result,
        'formatted' => 'Rp '.number_format($result, 0, ',', '.'),
    ]);
});

// Public: Get classifications with formulas for simulation
Route::get('/tax-formulas', function () {
    $classifications = RetributionClassification::whereNotNull('calculation_formula')
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
    Route::match(['put', 'post'], '/user/profile', [MeController::class, 'update']);
    Route::match(['put', 'post'], '/profile', [MeController::class, 'update']);
    Route::post('/user/password', [AuthController::class, 'changePassword']);
    Route::put('/user/location', [AuthController::class, 'updateLocation']);
    Route::post('/upload', [UploadController::class, 'uploadImage']);
    Route::get('/me', [MeController::class, 'show']);
    Route::match(['put', 'post'], '/me/update', [MeController::class, 'update']);

    // Dashboard agregat M39 (semua role internal; scope OPD via middleware opd_scope)
    Route::middleware('opd_scope')->prefix('dashboard')->group(function () {
        Route::get('/pad-summary', [App\Http\Controllers\Api\DashboardController::class, 'padSummary']);
        Route::get('/opd-services', [App\Http\Controllers\Api\DashboardController::class, 'opdServices']);
        Route::get('/sector-data', [App\Http\Controllers\Api\DashboardController::class, 'sectorData']);
        Route::get('/daily-tasks', [App\Http\Controllers\Api\DashboardController::class, 'dailyTasks']);
        Route::get('/anomalies', [App\Http\Controllers\Api\DashboardController::class, 'anomalies']);
    });

    // M43 — Verifikasi teknis proteksi kebakaran oleh Damkar (opd_id=13, verifikator PBG, NON-retribusi)
    Route::prefix('damkar')->group(function () {
        Route::get('/verifications', [DamkarVerificationController::class, 'index']);
        Route::post('/verifications/{id}/decision', [DamkarVerificationController::class, 'decision']);
    });

    // Modul Parkir Dishub — Kasir Cepat Jukir & Quadruple-Lock System
    Route::prefix('parking')->group(function () {
        Route::get('/locations', [ParkingController::class, 'locations']);
        Route::post('/sessions', [ParkingController::class, 'recordSession']);
        Route::get('/shift-summary', [ParkingController::class, 'shiftSummary']);
        Route::post('/shift/open', [ParkingController::class, 'openShift']);
        Route::post('/shift/close', [ParkingController::class, 'closeShift']);
        Route::get('/dashboard', [ParkingController::class, 'dashboard']);

        // Quadruple-Lock System Endpoints
        Route::get('/jukir/profile', [ParkingController::class, 'jukirProfile']);
        Route::post('/jukir/topup', [ParkingController::class, 'topupDeposit']);
        Route::post('/sessions/prepaid-cash', [ParkingController::class, 'recordPrepaidCash']);
        Route::get('/inspector/spot-check', [ParkingController::class, 'spotCheck']);
        Route::post('/inspector/sanction', [ParkingController::class, 'submitSanction']);
        Route::get('/admin/gis-heatmap', [ParkingController::class, 'gisHeatmap']);
        Route::get('/admin/anomalies', [ParkingController::class, 'anomalies']);
        Route::post('/citizen/claim-receipt', [ParkingController::class, 'claimReceipt']);

        // SPTPD PBJT Parkir — Pelaporan Bulanan Warga & Antrean Verifikasi Bapenda
        // Memakai monthly_reports existing (SPTPD universal) + parking_sessions untuk tapping box.
        Route::post('/declarations', [\App\Http\Controllers\Api\PbjtParkingDeclarationController::class, 'store']);
        Route::get('/declarations', [\App\Http\Controllers\Api\PbjtParkingDeclarationController::class, 'history']);
        Route::post('/declarations/fetch-tapbox', [\App\Http\Controllers\Api\PbjtParkingDeclarationController::class, 'fetchTapbox']);
        Route::get('/admin/declarations', [\App\Http\Controllers\Api\PbjtParkingDeclarationController::class, 'queue']);
        Route::post('/admin/declarations/{id}/approve', [\App\Http\Controllers\Api\PbjtParkingDeclarationController::class, 'approve']);
        Route::post('/admin/declarations/{id}/reject', [\App\Http\Controllers\Api\PbjtParkingDeclarationController::class, 'reject']);

        // Kawasan Terpadu Jembatan Batu & Proxy GT Kepelabuhanan (Perda 1/2024)
        Route::get('/kios-jembatan-batu', [ParkingController::class, 'kiosJembatanBatu']);
        Route::post('/kios-jembatan-batu/generate-bills', [ParkingController::class, 'generateKiosBills']);
        Route::get('/harbor/proxy-gt-rates', [ParkingController::class, 'proxyGtRates']);

        // MODUL DINAS PERHUBUNGAN (DISHUB KOTA BAUBAU) — Kepelabuhanan, Proxy GT & Manifes Lakologou
        Route::prefix('dishub')->group(function () {
            Route::get('/harbor/proxy-gt-rates', [HarborProxyGtController::class, 'indexRates']);
            Route::post('/harbor/session', [HarborProxyGtController::class, 'storeSession']);
            Route::get('/harbor/manifest', [HarborProxyGtController::class, 'getManifest']);
            Route::post('/lakologou/manifest', [LakologouManifestController::class, 'storeManifest']);
            Route::get('/lakologou/manifest', [LakologouManifestController::class, 'getSummary']);
        });
    });

    // Citizen Service Registration (Shared, but usually for citizens)
    Route::group(['prefix' => 'citizen/services'], function () {
        Route::get('/', [CitizenServiceController::class, 'index']);
        Route::get('/pending-periods', [CitizenServiceController::class, 'getPendingPeriods']);
        Route::get('/{id}', [CitizenServiceController::class, 'show']);
        Route::post('/{id}/register', [CitizenServiceController::class, 'register']);
        Route::get('/{id}/bills', [CitizenServiceController::class, 'bills']);
    });

    // Share Routes for Bills & TTE
    Route::get('/bills/{bill}', [BillController::class, 'show']);
    Route::get('/bills/{bill}/skrd', [BillController::class, 'exportSKRD']);
    Route::get('/bills/{bill}/sspd', [BillController::class, 'exportSSPD']);
    Route::get('/bills/{bill}/sppt', [BillController::class, 'exportSPPT']);
    Route::get('/tte/verify/{number}', [EregistryController::class, 'verify'])->withoutMiddleware('auth:sanctum');

    // Citizen Specific Actions
    Route::group(['prefix' => 'citizen'], function () {
        Route::get('/bills', [BillController::class, 'citizenBills']);
        Route::get('/tax-objects', [CitizenServiceController::class, 'taxObjects']);

        // ASET DAERAH (JU-ASET) — kategori, unit, form template, sewa
        Route::get('/asset/categories', [CitizenAssetController::class, 'categories']);
        Route::get('/asset/items', [CitizenAssetController::class, 'items']);
        Route::get('/asset/items/{id}/availability', [CitizenAssetController::class, 'availability']);
        Route::get('/asset/form-template/{categoryId}', [CitizenAssetController::class, 'formTemplate']);
        Route::post('/asset/rentals', [CitizenAssetController::class, 'storeRental']);
        Route::get('/asset/rentals', [CitizenAssetController::class, 'myRentals']);
        Route::get('/asset/rentals/{id}', [CitizenAssetController::class, 'showRental']);

        // Rusunawa Hierarchical Selector
        Route::get('/rusun/complexes', [RusunRoomController::class, 'complexes']);
        Route::get('/rusun/floors', [RusunRoomController::class, 'floors']);
        Route::get('/rusun/rooms', [RusunRoomController::class, 'availableRooms']);
        Route::get('/rusun/available-rooms', [RusunRoomController::class, 'availableRooms']);

        // Pembayaran
        Route::get('/payments/history', [PaymentController::class, 'history']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::post('/payment-requests', [CitizenPaymentRequestController::class, 'store']);
        Route::get('/payment-requests/{paymentRequest}', [CitizenPaymentRequestController::class, 'show']);
        Route::post('/payment-requests/{paymentRequest}/refresh', [CitizenPaymentRequestController::class, 'refresh']);
        Route::post('/payment-requests/{paymentRequest}/cancel', [CitizenPaymentRequestController::class, 'cancel']);

        // Merchant AFT (Kalkulator Bisnis)
        Route::prefix('merchant')->group(function () {
            Route::post('/submit-omzet', [KalkulatorBisnisController::class, 'submitOmzet']);
            Route::get('/aft-history', [KalkulatorBisnisController::class, 'getAftHistory']);
            Route::get('/aft-availability', [KalkulatorBisnisController::class, 'getAftAvailability']);
        });

        Route::post('/reports', [MonthlyReportController::class, 'store']);
        Route::get('/reports', [MonthlyReportController::class, 'index']);
        Route::post('/complaints', [ComplaintController::class, 'store']);
        Route::get('/complaints', [ComplaintController::class, 'citizenIndex']);

        // Rating layanan (PENILAIAN-1) — 9 Agu 2026
        Route::post('/ratings', [RatingController::class, 'store']);
        Route::get('/ratings', [RatingController::class, 'citizenIndex']);

        // Pendaftaran NOP Mandiri (Warga)
        Route::post('/pbb/nop-applications', [PbbNopApplicationController::class, 'store']);
        Route::get('/pbb/nop-applications', [PbbNopApplicationController::class, 'index']);

        // QR Wajib Pajak
        Route::get('/my-qr', [QrController::class, 'citizenMyQr']);

        // Permohonan Perubahan Data (warga)
        Route::get('/change-requests', [TaxObjectChangeRequestController::class, 'citizenIndex']);

        // Mutasi PBB — Pecah Kavling, Rektifikasi, Balik Nama, Gabung, Pembatalan
        // Prefix disamakan dengan FE: /api/citizen/pbb/mutations/*
        Route::prefix('pbb/mutations')->group(function () {
            Route::get('/my-applications', [PbbMutationController::class, 'myApplications']);
            Route::get('/my-assignments', [PbbMutationController::class, 'myAssignments']);
            Route::post('/calculate-proportional', [PbbMutationController::class, 'calculateProportionalDebt']);
            Route::post('/split-kavling', [PbbMutationController::class, 'storeSplitKavling']);
            Route::post('/rectification', [PbbMutationController::class, 'storeRectification']);
            Route::post('/transfer-ownership', [PbbMutationController::class, 'storeTransferOwnership']);
            Route::post('/amalgamation', [PbbMutationController::class, 'storeAmalgamation']);
            Route::post('/cancellation', [PbbMutationController::class, 'storeCancellation']);
            Route::get('/{id}', [PbbMutationController::class, 'show']);
            Route::get('/{id}/split-items', [PbbMutationController::class, 'splitItems']);
            Route::post('/{id}/split-items/{itemId}/terbit-nib', [PbbMutationController::class, 'terbitNib']);
        });
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
        Route::get('/', [H2HBphtbController::class, 'submissions']);
        Route::get('/mine', [H2HBphtbController::class, 'mySubmissions']);
        Route::post('/{submission}/documents', [H2HBphtbController::class, 'uploadDocument']);
        Route::delete('/{submission}/documents/{document}', [H2HBphtbController::class, 'deleteDocument']);
        Route::post('/{submission}/pay', [H2HBphtbController::class, 'pay']);
        Route::get('/{submission}', [H2HBphtbController::class, 'show']);
    });

    // Citizen can edit/cancel their own pending service registration objects.
    Route::match(['put', 'patch', 'post'], '/tax-objects/{taxObject}', [TaxObjectController::class, 'update']);
    Route::delete('/tax-objects/{taxObject}', [TaxObjectController::class, 'destroy']);

    // Resolve lokasi (reverse geocode) — map picker mobile
    Route::post('/location/resolve', [LocationController::class, 'resolve']);

    // ------------------------------------------------------------------------
    // Admin & Petugas ONLY (Restricted by EnsureAdmin middleware)
    // ------------------------------------------------------------------------
    Route::middleware('admin')->group(function () {
        // Banner (CRUD admin) — tabel banners sudah ada di prod
        Route::get('/banners', [BannerController::class, 'index']);
        Route::post('/banners', [BannerController::class, 'store']);
        Route::put('/banners/{id}', [BannerController::class, 'update']);
        Route::delete('/banners/{id}', [BannerController::class, 'destroy']);

        // WA Gateway Management (Baileys)
        Route::prefix('admin/wa-gateway')->group(function () {
            Route::get('/status', [WaGatewayController::class, 'status']);
            Route::get('/qr', [WaGatewayController::class, 'qr']);
            Route::post('/send-test', [WaGatewayController::class, 'sendTest']);
            Route::post('/logout', [WaGatewayController::class, 'logout']);
        });

        // H2H Monitor (read-only dashboard — Fase 1)
        Route::prefix('admin/h2h-monitor')->group(function () {
            Route::get('/overview', [H2HMonitorController::class, 'overview']);
            Route::get('/history/{connector}', [H2HMonitorController::class, 'history']);
        });

        // Pendaftaran NOP (Warga — verifikasi admin)
        Route::get('/nop-applications', [PbbNopApplicationController::class, 'index']);
        Route::get('/nop-applications/{id}', [PbbNopApplicationController::class, 'show']);
        Route::post('/nop-applications/{id}/status', [PbbNopApplicationController::class, 'updateStatus'])
            ->middleware('role:admin,kepala_bapenda,opd,petugas_survei');

        // H2H BPN / BPHTB
        Route::prefix('h2h/bphtb')->group(function () {
            Route::get('/mappings', [H2HBphtbController::class, 'mappings']);
            Route::post('/simulate', [H2HBphtbController::class, 'simulate']);
            Route::post('/submit', [H2HBphtbController::class, 'submit']);
            Route::post('/{submission}/verify', [H2HBphtbController::class, 'verify']);
            Route::post('/{submission}/issue-sspd', [H2HBphtbController::class, 'issueSspd']);
            Route::post('/{submission}/sspd', [H2HBphtbController::class, 'issueSspd']);
            Route::get('/{submission}/sspd/pdf', [H2HBphtbController::class, 'sspdPdf']);
        });

        // Integrasi NIB-BPN & NOP PBB-P2 (Aplikasi Peta Pajak — Standar Output KPK & ATR/BPN)
        Route::prefix('peta-pajak')->group(function () {
            Route::get('/stats', [PetaPajakController::class, 'stats']);
            Route::get('/parcels', [PetaPajakController::class, 'parcels']);
            Route::get('/mappings', [PetaPajakController::class, 'mappings']);
            Route::post('/match', [PetaPajakController::class, 'match']);
            Route::post('/auto-match', [PetaPajakController::class, 'autoMatch']);
            Route::get('/export-kpk', [PetaPajakController::class, 'exportKpk']);
            Route::post('/import-dhkp', [PetaPajakController::class, 'importDhkp']);
            Route::get('/dhkp-template', [PetaPajakController::class, 'dhkpTemplate']);
            Route::get('/tax-gap', [PetaPajakController::class, 'taxGap']);
            Route::get('/bounds', [PetaPajakController::class, 'bounds']);
            Route::get('/districts', [PetaPajakController::class, 'districts']);
            Route::get('/district-bounds', [PetaPajakController::class, 'districtBounds']);
            Route::get('/kelurahan-bounds', [PetaPajakController::class, 'kelurahanBounds']);
        });

        // H2H NIB-NOP Mapping (Sinkronisasi BHUMI BPN)
        Route::prefix('h2h/nib-nop')->group(function () {
            Route::get('/mappings', [NibNopMappingController::class, 'index']);
            Route::post('/mappings', [NibNopMappingController::class, 'store']);
            Route::post('/mappings/{id}/sync', [NibNopMappingController::class, 'syncOne']);
            Route::get('/stats', [NibNopMappingController::class, 'stats']);
        });

        // Notaris / PPAT Approval
        Route::prefix('notaris-approvals')->group(function () {
            Route::get('/', [NotarisApprovalController::class, 'index']);
            // Penyetuju aktivasi akun PPAT/Notaris = pimpinan (maker-checker).
            Route::post('/{id}/approve', [NotarisApprovalController::class, 'approve'])
                ->middleware('role:kepala_bapenda');
            Route::post('/{id}/reject', [NotarisApprovalController::class, 'reject'])
                ->middleware('role:kepala_bapenda');
        });

        // Permohonan Perubahan Data (admin)
        Route::get('/change-requests', [TaxObjectChangeRequestController::class, 'index']);
        Route::post('/change-requests/{changeRequest}/status', [TaxObjectChangeRequestController::class, 'updateStatus'])
            ->middleware('role:kepala_bapenda');

        // Pendaftaran NOP alias (tab admin PBB)
        Route::get('/pbb/bapenda/nop-applications', [PbbNopApplicationController::class, 'index']);
        Route::post('/pbb/bapenda/nop-applications/{id}/status', [PbbNopApplicationController::class, 'updateStatus'])
            ->middleware('role:admin,kepala_bapenda,opd,petugas_survei');

        // Pelaporan E-SPTPD
        Route::post('/sptpd', [SptpdController::class, 'store']);

        // Simpad Koneksi (Legacy Migration)
        Route::prefix('simpad-koneksi')->group(function () {
            Route::get('/taxpayers/{npwpd}', [SimpadKoneksiController::class, 'getTaxpayer']);
            Route::get('/objects/{type}', [SimpadKoneksiController::class, 'getObjects']);
            Route::get('/officers', [SimpadKoneksiController::class, 'getOfficers']);
            Route::post('/sync-object', [SimpadKoneksiController::class, 'syncObject']);
        });

        Route::apiResource('petugas-tasks', PetugasTaskController::class);
        Route::apiResource('spot-checks', SpotCheckController::class);
        Route::patch('spot-checks/{id}/status', [SpotCheckController::class, 'updateStatus']);
        Route::get('spot-checks/tax-object/{id}/estimation', [SpotCheckController::class, 'getEstimatedRevenue']);

        // ASET DAERAH (JU-ASET) — Admin: dashboard, kelola kategori & unit, verifikasi sewa
        Route::get('/asset/dashboard', [AdminAssetController::class, 'dashboard']);
        Route::get('/asset/officers', [AdminAssetController::class, 'officers']);
        Route::get('/asset/rentals', [AdminAssetController::class, 'rentals']);
        Route::get('/asset/rentals/{id}', [AdminAssetController::class, 'showRental']);
        Route::post('/asset/rentals/{id}/assign', [AdminAssetController::class, 'assignOfficer']);
        Route::post('/asset/rentals/{id}/survey', [AdminAssetController::class, 'submitSurvey']);
        Route::post('/asset/rentals/{id}/sedot-kakus-execution', [AdminAssetController::class, 'submitSedotKakusExecution']);
        Route::post('/asset/rentals/{id}/inspection', [AdminAssetController::class, 'submitInspection']);
        Route::get('/asset/rentals/{id}/document/sipab', [AdminAssetController::class, 'documentSipab']);
        Route::get('/asset/rentals/{id}/document/bast', [AdminAssetController::class, 'documentBast']);
        Route::post('/asset/rentals/{id}/contract', [AdminAssetController::class, 'generateContract']);
        Route::post('/asset/rentals/{id}/verify', [AdminAssetController::class, 'verify']);
        Route::post('/asset/rentals/{id}/approve', [AdminAssetController::class, 'approve']);
        Route::post('/asset/rentals/{id}/reject', [AdminAssetController::class, 'reject']);
        Route::post('/asset/rentals/{id}/complete', [AdminAssetController::class, 'complete']);
        Route::post('/asset/rentals/{id}/payment', [AdminAssetController::class, 'confirmPayment']);
        Route::get('/asset/categories', [AdminAssetController::class, 'categories']);
        Route::post('/asset/categories', [AdminAssetController::class, 'storeCategory']);
        Route::put('/asset/categories/{id}', [AdminAssetController::class, 'updateCategory']);
        Route::delete('/asset/categories/{id}', [AdminAssetController::class, 'deleteCategory']);
        Route::get('/asset/items', [AdminAssetController::class, 'items']);
        Route::post('/asset/items', [AdminAssetController::class, 'storeItem']);
        Route::put('/asset/items/{id}', [AdminAssetController::class, 'updateItem']);
        Route::delete('/asset/items/{id}', [AdminAssetController::class, 'deleteItem']);

        // Rusunawa Room Management (Disperkim) - Admin Endpoints
        Route::prefix('admin/rusun')->group(function () {
            Route::get('/rooms', [RusunRoomController::class, 'adminRooms']);
            Route::post('/rooms/{id}/maintenance', [RusunRoomController::class, 'setMaintenance']);
            Route::post('/rooms/{id}/available', [RusunRoomController::class, 'setAvailable']);
            Route::post('/rooms/{id}/set-maintenance', [RusunRoomController::class, 'setMaintenance']);
            Route::post('/rooms/{id}/set-available', [RusunRoomController::class, 'setAvailable']);
        });

        // Rusunawa Room Management (Disperkim) - Legacy / Direct alias
        Route::get('/rusun/rooms', [RusunRoomController::class, 'adminRooms']);
        Route::post('/rusun/rooms/{id}/maintenance', [RusunRoomController::class, 'setMaintenance']);
        Route::post('/rusun/rooms/{id}/available', [RusunRoomController::class, 'setAvailable']);
        Route::post('/rusun/rooms/{id}/set-maintenance', [RusunRoomController::class, 'setMaintenance']);
        Route::post('/rusun/rooms/{id}/set-available', [RusunRoomController::class, 'setAvailable']);

        // DINAS PUPR (Aset Alat Berat, Tronton Bundling, & Inspeksi Hour Meter)
        Route::prefix('pupr')->group(function () {
            Route::get('/calendar/{itemId}', [AssetCalendarController::class, 'getCalendar']);
            Route::post('/calendar/check-availability', [AssetCalendarController::class, 'checkAvailability']);
            Route::post('/tronton/calculate', [TrontonBundlingController::class, 'calculate']);
            Route::post('/inspection/pre', [InspectionController::class, 'storePreOperation']);
            Route::post('/inspection/post', [InspectionController::class, 'storePostOperation']);
            Route::get('/inspection/history/{rentalId}', [InspectionController::class, 'getHistory']);
        });

        Route::get('/analytics/realization', [AnalyticsController::class, 'getRealization']);
        Route::get('/analytics/heatmap', [AnalyticsController::class, 'getHeatmapData']);
        Route::get('/analytics/object-performance', [AnalyticsController::class, 'getObjectPerformance']);
        Route::get('/analytics/classification-performance', [AnalyticsController::class, 'getClassificationPerformance']);
        Route::get('/analytics/lra-report', [AnalyticsController::class, 'getLraReport']);
        Route::apiResource('retribution-types', RetributionTypeController::class);
        Route::get('/taxpayers/search/{nik}', [TaxpayerSearchController::class, 'searchByNik']);
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
            Route::get('/potentials', [DashboardController::class, 'getMapPotentials']);
            Route::get('/petugas-tasks', [PetugasTaskController::class, 'index']);
        });

        // Dashboard Khusus Admin PTSP (DPMPTSP) — RBAC role admin_ptsp
        Route::prefix('ptsp')->group(function () {
            Route::get('/dashboard/stats', [PtspDashboardController::class, 'stats']);
            Route::get('/dashboard/growth', [PtspDashboardController::class, 'growth']);
            Route::get('/dashboard/related-services', [PtspDashboardController::class, 'relatedServices']);
            Route::get('/dashboard/taxpayers', [PtspDashboardController::class, 'taxpayers']);
            Route::get('/candidates', [PtspCandidateController::class, 'index']);
            Route::post('/candidates/{id}/nib', [PtspCandidateController::class, 'markNib']);

            // ---- Modul Permohonan Layanan (Reklame 188, PBG 193, Air Tanah 197, TKA 194) ----
            Route::get('/service-applications', [ServiceApplicationController::class, 'ptspIndex']);
            Route::post('/service-applications/{id}/assign', [ServiceApplicationController::class, 'assignOfficer']);
            Route::post('/service-applications/{id}/field-check', [ServiceApplicationController::class, 'fieldCheck']);
            Route::post('/service-applications/{id}/decide', [ServiceApplicationController::class, 'decide']);
            Route::get('/officers', [ServiceApplicationController::class, 'officers']);
        });

        // Modul Permohonan Layanan — sisi PEMOHON (semua user terautentikasi)
        Route::get('/service-applications', [ServiceApplicationController::class, 'mine']);
        Route::post('/service-applications', [ServiceApplicationController::class, 'store']);
        Route::get('/service-applications/{id}', [ServiceApplicationController::class, 'show']);

        Route::prefix('pengawas')->group(function () {
            Route::get('/audit-logs', [AuditLogController::class, 'index']);
            Route::get('/anomalies', [SurveillanceController::class, 'getAnomalies']);
            Route::get('/compliance-stats', [SurveillanceController::class, 'getComplianceStats']);
            Route::get('/petugas-locations', [SurveillanceController::class, 'getPetugasLocations']);
            Route::get('/enforcements', [EnforcementNoticeController::class, 'index']);
            Route::post('/enforcements', [EnforcementNoticeController::class, 'store']);
            Route::post('/enforcements/{id}', [EnforcementNoticeController::class, 'update']);
            Route::post('/enforcements/{id}/approve', [EnforcementNoticeController::class, 'approve']);
            Route::post('/enforcements/{id}/reject', [EnforcementNoticeController::class, 'reject']);
            Route::get('/enforcements/history/{tax_object_id}', [EnforcementNoticeController::class, 'getHistory']);
            Route::get('/enforcements/{id}/pdf', [EnforcementNoticeController::class, 'generatePDF']);
            Route::get('/penindakan', [PenindakanController::class, 'index']);
            Route::post('/penindakan/issue-skpdkb', [PenindakanController::class, 'generateSKPDKB']);
        });

        Route::prefix('reports')->group(function () {
            Route::get('/summary', [ReportController::class, 'getSummary']);
            Route::get('/recent', [ReportController::class, 'getRecent']);
            Route::get('/petugas-performance', [ReportController::class, 'getPetugasPerformance']);
            Route::get('/monthly/stats', [MonthlyReportController::class, 'stats']);
            Route::get('/monthly', [MonthlyReportController::class, 'index']);
            Route::put('/monthly/{report}/validate', [MonthlyReportController::class, 'validateReport']);
            Route::post('/monthly/{report}/spot-check', [MonthlyReportController::class, 'requestSpotCheck']);
            Route::get('/bpk', [ReportController::class, 'getMonthlyReport']);
            Route::get('/sipd', [ReportController::class, 'getSipdReport']);
        });

        Route::prefix('amnesty')->group(function () {
            Route::get('/', [PenaltyWaiverController::class, 'index']);
            Route::post('/', [PenaltyWaiverController::class, 'store']);
            Route::post('/{id}/approve', [PenaltyWaiverController::class, 'approve'])
                ->middleware('role:kepala_bapenda');
            Route::post('/{id}/reject', [PenaltyWaiverController::class, 'reject'])
                ->middleware('role:kepala_bapenda');
            Route::get('/{id}/document', [PenaltyWaiverController::class, 'generateDocument']);
        });

        Route::prefix('tte')->group(function () {
            Route::get('/documents', [EregistryController::class, 'index']);
            Route::post('/sign', [BillController::class, 'signTTE']);
        });

        Route::prefix('pbb/bapenda')->group(function () {
            Route::post('/reversal', [PbbBapendaController::class, 'reversal'])
                ->middleware('role:kepala_bapenda');
            Route::get('/transactions', [PbbBapendaController::class, 'transactions']);
            Route::get('/stats', [PbbBapendaController::class, 'stats']);
            Route::post('/sync-all', [PbbBapendaController::class, 'syncAllObjects']);
        });

        // Bank H2H Monitoring Logs
        Route::prefix('bank-h2h')->group(function () {
            Route::get('/logs', [BankH2HController::class, 'logs']);
            Route::post('/reconcile', [BankH2HController::class, 'reconcile']);
        });

        // Official BAPENDA Documents
        Route::prefix('documents')->group(function () {
            // Pendaftaran
            Route::get('/skt/{taxpayerId}', [DocumentController::class, 'skt']);
            // Pendataan
            Route::get('/lkok/{taxObjectId}', [DocumentController::class, 'lkok']);
            // Penetapan
            Route::get('/skrd/{billId}', [DocumentController::class, 'skrd']);
            Route::get('/skpd/{billId}', [DocumentController::class, 'skpd']);
            Route::get('/sppt/{billId}', [DocumentController::class, 'sppt']);
            Route::post('/skpdkbt/{billId}', [DocumentController::class, 'skpdkbt']);
            Route::post('/skpdn/{billId}', [DocumentController::class, 'skpdn']);
            // Penagihan
            Route::get('/sspd/{billId}', [DocumentController::class, 'sspd']);
            Route::get('/ssrd/{billId}', [DocumentController::class, 'ssrd']);
            Route::get('/strd/{billId}', [DocumentController::class, 'strd']);
            Route::get('/spp/{noticeId}', [DocumentController::class, 'spp']);
            Route::get('/spmp/{noticeId}', [DocumentController::class, 'spmp']);
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
        Route::get('/business-units', [BusinessUnitController::class, 'index']);

        // M37-2 - Integrasi akun OPD pengelola per sektor usaha (Dok 143)
        // Scope OPD via middleware opd_scope (konsisten dengan pola dashboard agregat)
        Route::prefix('opd')->middleware('opd_scope')->group(function () {
            Route::get('/sector-data', [OpdSectorDataController::class, 'index']);
        });

        // Mutasi PBB — Petugas & Admin (daftar, verifikasi, survey, penolakan)
        Route::prefix('pbb/mutations')->group(function () {
            Route::get('/', [PbbMutationController::class, 'index']);
            Route::get('/my-assignments', [PbbMutationController::class, 'myAssignments']);
            Route::post('/calculate-proportional', [PbbMutationController::class, 'calculateProportionalDebt']);
            Route::get('/{id}/split-items', [PbbMutationController::class, 'splitItems']);
            Route::post('/{id}/split-items/{itemId}/terbit-nib', [PbbMutationController::class, 'terbitNib']);
            Route::post('/{id}/status', [PbbMutationController::class, 'updateStatus']);
            Route::get('/{id}', [PbbMutationController::class, 'show']);
        });

        // Analitik SLA & Penghambat Verifikasi — Dinas Pertanahan
        Route::prefix('pertanahan')->group(function () {
            Route::get('/sla', [PertanahanAnalyticsController::class, 'sla']);
            Route::get('/hambatan', [PertanahanAnalyticsController::class, 'hambatan']);
            Route::get('/ringkasan', [PertanahanAnalyticsController::class, 'ringkasan']);
        });
    });

    // ----------------------------------------------------------------
    // MODUL DINAS LINGKUNGAN HIDUP (DLH KOTA BAUBAU) — Perda 1/2024
    // Persampahan Door-to-Door, Karcis Pasar, UPTD Lab, & Spasial Map
    // ----------------------------------------------------------------
    $dlhRoutes = function () {
        // Lab Lingkungan (UPTD)
        Route::get('/lab/parameters', [DlhLabServiceController::class, 'indexParameters']);
        Route::post('/lab/orders', [DlhLabServiceController::class, 'storeOrder']);
        Route::get('/lab/orders/{id}', [DlhLabServiceController::class, 'showOrder']);
        Route::post('/lab/orders/{id}/billing', [DlhLabServiceController::class, 'generateBilling']);
        Route::post('/lab/orders/{id}/results', [DlhLabServiceController::class, 'uploadResult']);

        // Karcis Pasar Harian & Batch Remittance Juru Pungut
        Route::post('/market/tickets', [DlhMarketRemittanceController::class, 'issueMarketTicket']);
        Route::get('/market/holding-balance', [DlhMarketRemittanceController::class, 'getCollectorHoldingBalance']);
        Route::post('/market/remittance', [DlhMarketRemittanceController::class, 'submitRemittance']);
        Route::post('/market/remittance/{id}/verify', [DlhMarketRemittanceController::class, 'verifyRemittance']);

        // Peta Kepatuhan Spasial Persampahan (GeoJSON)
        Route::get('/spatial/compliance-map', [DlhSpatialMonitoringController::class, 'getComplianceMap']);

        // Laporan Eksekutif & Audit Juru Pungut BPK
        Route::get('/reports/executive-summary', [DlhReportController::class, 'getExecutiveSummary']);
        Route::get('/reports/collector-audit', [DlhReportController::class, 'getCollectorAuditReport']);

        // Persampahan Property-Centric Billing & Occupant Transfer
        Route::get('/trash/accounts', [DlhTrashBillingController::class, 'indexAccounts']);
        Route::get('/trash/tarif-categories', [DlhTrashBillingController::class, 'listTarifCategories']);
        Route::post('/trash/calculate', [DlhTrashBillingController::class, 'calculateTariff']);
        Route::post('/trash/transfer-occupant/{taxObjectId}', [DlhTrashBillingController::class, 'transferOccupant']);
        Route::post('/trash/generate-bills', [DlhTrashBillingController::class, 'generateMonthlyBills']);
        Route::get('/trash/inquiry', [DlhTrashBillingController::class, 'inquiry']);
        Route::post('/trash/empty-house-notice/{taxObjectId}', [DlhTrashBillingController::class, 'reportEmptyHouse']);
    };

    Route::prefix('dlh')->group($dlhRoutes);
    Route::prefix('v1/dlh')->group($dlhRoutes);
});

// ------------------------------------------------------------------------

// ------------------------------------------------------------------------
// MODUL DINAS PERDAGANGAN & PERINDUSTRIAN (DISPERINDAG) — Perda 1/2024
// Data pasar, kios, los, juru pungut, dan KPI summary (dapat diakses dashboard & publik)
// PENTING: blok ini pernah HILANG (tertimpa saat edit routes/api.php).
// Frontend admin memanggil /api/v1/disperindag/* — JANGAN dihapus lagi.
// ------------------------------------------------------------------------
$disperindagPublicRoutes = function () {
    Route::get('/summary', [MarketController::class, 'summary']);
    Route::get('/kpi', [MarketController::class, 'summary']);
    Route::get('/markets', [MarketController::class, 'markets']);
    Route::get('/zones', [MarketController::class, 'zones']);
    Route::get('/buildings', [MarketController::class, 'buildings']);
    Route::get('/stalls', [MarketController::class, 'stalls']);
    Route::get('/stalls/available', [MarketController::class, 'availableStalls']);
    Route::get('/officers', [MarketController::class, 'officers']);
    Route::get('/tickets', [MarketController::class, 'dailyTickets']);
};

$disperindagAuthRoutes = function () {
    Route::get('/officer-stats', [MarketController::class, 'officerStats']);
    Route::post('/tickets/daily', [MarketController::class, 'issueDailyTicket']);
    Route::post('/stalls/{id}/pay', [MarketController::class, 'payStall']);
    Route::put('/stalls/{id}/status', [MarketController::class, 'updateStallStatus']);
    Route::put('/stalls/{id}', [MarketController::class, 'updateStall']);
    Route::post('/stalls', [MarketController::class, 'createStall']);
    Route::post('/buildings', [MarketController::class, 'createBuilding']);
    Route::delete('/buildings', [MarketController::class, 'deleteBuilding']);
    Route::post('/buildings/tariff', [MarketController::class, 'updateBuildingTariff']);
    Route::post('/tariffs/batch-assign', [MarketController::class, 'batchAssignTariff']);
    Route::put('/officers/{id}', [MarketController::class, 'updateOfficer']);
    Route::post('/stalls/{id}/claim', [MarketController::class, 'claimStall']);
    Route::post('/stalls/{id}/verify-claim', [MarketController::class, 'verifyClaim']);
    Route::post('/stalls/{id}/unclaim', [MarketController::class, 'unclaimStall']);
    Route::post('/tax-objects/{taxObjectId}/unclaim', [MarketController::class, 'unclaimByTaxObject']);
};

Route::prefix('disperindag')->group($disperindagPublicRoutes);
Route::prefix('v1/disperindag')->group($disperindagPublicRoutes);

Route::prefix('disperindag')->middleware('auth:sanctum')->group($disperindagAuthRoutes);
Route::prefix('v1/disperindag')->middleware('auth:sanctum')->group($disperindagAuthRoutes);

// ------------------------------------------------------------------------
// BANK SULTRA — callback VA (dipanggil Bank Sultra saat pembayaran masuk)
// ------------------------------------------------------------------------
Route::prefix('h2h/bank-sultra')->group(function () {
    Route::post('/callback', [BpdWebhookController::class, 'incomingTransfer']);
});

// H2H BPN — endpoint eksternal yang dipanggil sistem BPN (KKP).
// Autentikasi via header X-H2H-Key (dicek di dalam controller).
// M21-1466 (F3.2): bridging status lunas ke BPN (SPLP).
// ------------------------------------------------------------------------
Route::prefix('h2h/bpn')->group(function () {
    Route::post('/check-pbb', [H2HBpnController::class, 'checkPbb']);
    Route::post('/verify-payment', [H2HBpnController::class, 'verifyPayment']);
    Route::post('/splp/update', [H2HBpnController::class, 'updateSplp']);
    Route::post('/spatial/reverse-lookup', [H2HBpnController::class, 'spatialReverseLookup']);
    Route::post('/spatial/sync', [H2HBpnController::class, 'syncSpatial']);
});

// Endpoint reverse lookup spasial untuk mobile warga dan admin web
Route::post('/h2h/bhumi/reverse-lookup', [H2HBpnController::class, 'spatialReverseLookup']);

// ------------------------------------------------------------------------
// Layer SmartGIS Bapenda — bidang spasial (peta pajak & modal "Peta Bidang")
// PENTING: route ini pernah HILANG -> modal tampil kosong (poligon "M0 0").
// JANGAN taruh di dalam prefix h2h/bpn, frontend mengakses /api/smartgis/...
// ------------------------------------------------------------------------
// PENTING URUTAN: daftar (/smartgis/bidang) HARUS di atas {nop},
// kalau dibalik maka {nop} menangkap string "bidang".
Route::get('/smartgis/bidang', [SpatialController::class, 'daftarBidang'])->middleware('throttle:60,1');
Route::get('/smartgis/bidang/{nop}', [SpatialController::class, 'bidangByNop'])->middleware('throttle:60,1');
Route::get('/smartgis/wilayah', [SpatialController::class, 'wilayah']);
Route::get('/smartgis/stats', [SpatialController::class, 'stats']);
Route::get('/spatial/bidang-bbox', [SpatialController::class, 'bidangBbox'])->middleware('throttle:60,1');
Route::post('/spatial/bidang-by-point', [SpatialController::class, 'bidangByPoint'])->middleware('throttle:30,1');

// ------------------------------------------------------------------------
// BANK H2H GATEWAY (Restricted by BankSecurityCheck middleware)
// ------------------------------------------------------------------------
Route::middleware('bank_h2h')->prefix('v1/bank')->group(function () {
    Route::post('/inquiry', [BankH2HController::class, 'inquiry']);
    Route::post('/payment', [BankH2HController::class, 'payment']);
    Route::post('/reversal', [BankH2HController::class, 'reversal']);
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
    Route::post('/record', [AutoDeductController::class, 'recordTransaction']);
    Route::post('/process', [AutoDeductController::class, 'processPayment']);
    Route::get('/status', [AutoDeductController::class, 'getStatus']);
    Route::get('/notifications', [AutoDeductController::class, 'getNotifications']);
    Route::post('/reminders', [AutoDeductController::class, 'sendReminders']);
});

Route::middleware('auth:sanctum')->prefix('corrections')->group(function () {
    Route::post('/{transaction}/void', [CorrectionController::class, 'void']);
    Route::post('/{transaction}/adjust-price', [CorrectionController::class, 'adjustPrice']);
    Route::post('/{transaction}/refund', [CorrectionController::class, 'refund']);
    Route::get('/{transaction}/history', [CorrectionController::class, 'history']);
});

Route::post('/auto-deduct/webhook', [AutoDeductWebhookController::class, 'handle'])
    ->middleware('auto_deduct_access:webhook');

// ------------------------------------------------------------------------
// MODUL TEMPAT PELELANGAN IKAN (TPI) & DINAS PERIKANAN (Perda 1/2024)
// Rujukan: 01_Dokumen_Arsitektur_Teknis_dan_Roadmap_TPI_MPAD.pdf & Dokumen (172/173)
// ------------------------------------------------------------------------
Route::get('/tpi/verify/{ntpd}', [TpiTransactionController::class, 'verifyPublic']);

Route::middleware('auth:sanctum')->prefix('tpi')->group(function () {
    Route::get('/transactions', [TpiTransactionController::class, 'index']);
    Route::post('/transactions', [TpiTransactionController::class, 'store']);
    Route::get('/transactions/{id}', [TpiTransactionController::class, 'show']);
    Route::post('/transactions/{id}/settle', [TpiTransactionController::class, 'settlePayment']);
    Route::get('/daily-summary', [TpiTransactionController::class, 'dailySummary']);
    Route::get('/cross-pbjt-stats', [TpiTransactionController::class, 'crossPbjtStats']);
});

// ------------------------------------------------------------------------
// DEPLOY HOOK (Staging only - protected by X-Deploy-Secret header)
// ------------------------------------------------------------------------
Route::post('/admin/deploy-hook', [DeployHookController::class, 'handle']);

// ------------------------------------------------------------------------
// NOPD / NORD 14 DIGIT — KODIFIKASI, KAMUS REFERENSI LEGACY & KLAIM OBJEK
// Lingkup: pajak daerah non-PBB/BPHTB + retribusi daerah
// (blok aditif — tidak mengubah route yang sudah ada)
// ------------------------------------------------------------------------
Route::prefix('object-numbers')->group(function () {
    Route::get('/sektor', [\App\Http\Controllers\ObjectNumberController::class, 'sektor']);

    Route::post('/legacy-lookup', [\App\Http\Controllers\ObjectNumberController::class, 'legacyLookup'])
        ->middleware('throttle:30,1');

    Route::get('/kandidat-klaim', [\App\Http\Controllers\ObjectNumberController::class, 'kandidatKlaim'])
        ->middleware('throttle:30,1');

    Route::get('/lookup', [\App\Http\Controllers\ObjectNumberController::class, 'lookup'])
        ->middleware('throttle:60,1');

    Route::post('/claim', [\App\Http\Controllers\ObjectNumberController::class, 'claim'])
        ->middleware('auth:sanctum');

    Route::get('/{nomor}', [\App\Http\Controllers\ObjectNumberController::class, 'show'])
        ->where('nomor', '[0-9.]+');
});
// ------------------------------------------------------------------------
// AKHIR BLOK NOPD / NORD 14 DIGIT
// ------------------------------------------------------------------------
