<?php

use App\Http\Controllers\Api\AttemptController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\SilapSyncController;
use Illuminate\Support\Facades\Route;

Route::post('/silap/sync', [SilapSyncController::class, 'sync'])->middleware('throttle:silap-sync');

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login'])->middleware('throttle:mobile-login');

    Route::middleware(['auth:sanctum', 'throttle:mobile-exam'])->group(function () {
        Route::post('/exam-package/queue', [MobileAuthController::class, 'queue']);
        Route::get('/exam-package', [MobileAuthController::class, 'package']);
        Route::post('/exam-package/download-complete', [MobileAuthController::class, 'downloadComplete']);
        Route::post('/exam-package/unlock', [MobileAuthController::class, 'unlock']);
        Route::post('/attempt/start', [AttemptController::class, 'start']);
        Route::post('/attempt/sync', [AttemptController::class, 'sync']);
        Route::post('/attempt/integrity-event', [AttemptController::class, 'integrityEvent']);
        Route::post('/attempt/submit', [AttemptController::class, 'submit']);
    });
});
