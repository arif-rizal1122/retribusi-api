<?php

use App\Http\Controllers\DocumentationController;

Route::get('/', function () {
    $commitHash = trim(exec('git rev-parse --short HEAD') ?: 'unknown');
    return view('welcome', compact('commitHash'));
})->middleware('throttle:60,1');

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

Route::prefix('docs')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [DocumentationController::class, 'index']);
    Route::get('/assets/{filename}', [DocumentationController::class, 'asset']);
    Route::get('/{page}', [DocumentationController::class, 'show']);
});
