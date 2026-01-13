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

// ============================
// VoIP Softphone API Routes
// ============================
Route::middleware('auth:sanctum')->group(function () {
    // Call Management
    Route::prefix('calls')->group(function () {
        Route::post('/initiate', [\App\Http\Controllers\CallController::class, 'initiate']);
        Route::patch('/{callId}/status', [\App\Http\Controllers\CallController::class, 'updateStatus']);
        Route::patch('/{callId}/notes', [\App\Http\Controllers\CallController::class, 'addNotes']);
        Route::get('/', [\App\Http\Controllers\CallController::class, 'index']);
        Route::get('/monitoring', [\App\Http\Controllers\CallController::class, 'monitoring']);
        Route::get('/history', [\App\Http\Controllers\CallController::class, 'history']);
        Route::get('/{callId}', [\App\Http\Controllers\CallController::class, 'show']);
    });

    // SIP Configuration
    Route::prefix('sip')->group(function () {
        Route::get('/config', [\App\Http\Controllers\SipSettingsController::class, 'getConfig']);
        Route::get('/accounts', [\App\Http\Controllers\SipSettingsController::class, 'index']);
        Route::post('/accounts', [\App\Http\Controllers\SipSettingsController::class, 'store']);
        Route::delete('/accounts/{id}', [\App\Http\Controllers\SipSettingsController::class, 'destroy']);
        Route::post('/test', [\App\Http\Controllers\SipSettingsController::class, 'test']);
    });
});

// ============================
// Courier Webhook Routes (Public - authenticated via API key)
// ============================
Route::prefix('courier')->group(function () {
    // J&T Express webhook
    Route::post('/jnt/webhook', [\App\Http\Controllers\Api\CourierWebhookController::class, 'handleJnt']);

    // Generic courier webhook handler
    Route::post('/{courierCode}/webhook', [\App\Http\Controllers\Api\CourierWebhookController::class, 'handleGeneric']);
});

// Manual status update (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/waybill/{waybill}/update-status', function (\Illuminate\Http\Request $request, \App\Models\Waybill $waybill) {
        $request->validate([
            'status' => 'required|string|in:pickup_failed,picked_up,in_transit,arrived_hub,out_for_delivery,delivery_failed,delivered,returning,returned',
            'reason' => 'nullable|string|max:500',
        ]);

        $courierService = \App\Services\Courier\CourierFactory::default();
        $courierService->updateStatus($waybill, $request->status, $request->reason);

        return response()->json(['success' => true, 'message' => 'Status updated']);
    });
});
