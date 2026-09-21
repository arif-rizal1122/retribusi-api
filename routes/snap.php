<?php

use App\Http\Controllers\Api\V1\Payment\SnapBIController;
use App\Http\Controllers\Api\V1\Payment\BtnSnapController;
use App\Http\Controllers\Api\V1\Payment\BniSnapController;

Route::post('/v1.1/access-token/b2b', [SnapBIController::class, 'getAccessToken']);
Route::post('/v1.1/qr/qr-mpm-notify', [SnapBIController::class, 'qrisNotify']);

Route::post('/v1.0/access-token/b2b', [SnapBIController::class, 'getAccessToken']);
Route::post('/v1.0/transfer-va/inquiry', [SnapBIController::class, 'brivaInquiry']);
Route::post('/v1.0/transfer-va/payment', [SnapBIController::class, 'brivaPayment']);

Route::post('/v1/transfer-va/inquiry', [BtnSnapController::class, 'inquiry']);
Route::post('/v1/transfer-va/payment', [BtnSnapController::class, 'payment']);

// BNI VA Billing — callback (path v1.1 sesuai instruksi BNI email 30 Agu 2026)
Route::post('/v1.1/transfer-va/inquiry', [BniSnapController::class, 'inquiry']);
Route::post('/v1.1/transfer-va/payment', [BniSnapController::class, 'payment']);
// BNI VA Billing — inquiry status (SC26, path v1.0 sesuai instruksi BNI 30 Agu 2026)
Route::post('/v1.0/transfer-va/status', [BniSnapController::class, 'inquiryStatus']);
