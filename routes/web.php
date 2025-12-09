<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\WaybillController;
use App\Http\Controllers\PendingController;
use App\Http\Controllers\NotificationController;


Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner');
Route::get('/upload', [UploadController::class, 'index'])->name('upload');
Route::get('/upload-batch', [UploadController::class, 'batchIndex'])->name('upload.batch');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');
Route::post('/upload-batch', [UploadController::class, 'storeBatch'])->name('upload.batch.store');
Route::post('/upload-batch/cancel', [UploadController::class, 'cancelBatch'])->name('upload.batch.cancel');
Route::get('/waybills', [WaybillController::class, 'index'])->name('waybills');
Route::get('/pending', [PendingController::class, 'index'])->name('pending');
Route::get('/pending/list', [PendingController::class, 'list'])->name('pending.list');
Route::post('/pending/dispatch', [PendingController::class, 'dispatch'])->name('pending.dispatch');

Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

