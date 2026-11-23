<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QaReportController;

// UBAH auth:sanctum MENJADI auth:web di baris bawah ini
Route::middleware('auth:web')->group(function () {
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/reports', [QaReportController::class, 'index']);
    Route::post('/qa-reports', [QaReportController::class, 'store']);
    Route::get('/reports/{id}', [QaReportController::class, 'show']);
    Route::get('/qa-reports/{id}/export', [QaReportController::class, 'export']);

});