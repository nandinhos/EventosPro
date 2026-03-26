<?php

use App\Http\Controllers\Api\LegacyImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/import/legacy')->group(function () {
    Route::post('contracts', [LegacyImportController::class, 'importContracts']);
    Route::post('receivables', [LegacyImportController::class, 'importReceivables']);
    Route::post('payables', [LegacyImportController::class, 'importPayables']);
});
