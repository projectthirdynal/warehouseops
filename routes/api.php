<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BatchScanController;
use App\Http\Controllers\PendingController;
use App\Models\Waybill;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('batch-scan')->group(function () {
    Route::post('/start', [BatchScanController::class, 'startSession']);
    Route::post('/scan', [BatchScanController::class, 'scan']);
    Route::get('/status', [BatchScanController::class, 'status']);
    Route::get('/pending', [BatchScanController::class, 'pending']);
    Route::post('/dispatch', [BatchScanController::class, 'dispatch']);
    Route::post('/mark-pending', [BatchScanController::class, 'markAsPending']);
    Route::post('/resume-pending', [BatchScanController::class, 'resumePending']);
    Route::get('/issues', [BatchScanController::class, 'getPendingIssues']);
});

Route::post('/pending/dispatch', [PendingController::class, 'dispatch']);

// Waybill lookup for Scanner page
Route::get('/waybill/{waybillNumber}', function ($waybillNumber) {
    $waybill = Waybill::where('waybill_number', $waybillNumber)->first();

    if ($waybill) {
        return response()->json([
            'success' => true,
            'waybill' => $waybill
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Waybill not found'
    ], 404);
});
