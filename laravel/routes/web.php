<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeadController;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\WaybillController;
use App\Http\Controllers\BatchScanController;
use App\Http\Controllers\PendingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\LeadReportController;

// Authentication routes (public)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/debug-config', function () {
    return response()->json([
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'php_ini_loaded_file' => php_ini_loaded_file(),
        'additional_ini_scanned_files' => php_ini_scanned_files(),
    ]);
});

// Protected routes
Route::middleware(['auth'])->group(function () {
    // Dashboard - requires dashboard permission
    Route::get('/', [DashboardController::class, 'index'])->middleware('role:dashboard')->name('dashboard');
    
    // Scanner - requires scanner permission
    Route::middleware(['role:scanner'])->group(function () {
        Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner');
        Route::get('/batch-scan/issues', [App\Http\Controllers\BatchScanController::class, 'getPendingIssues'])->name('batch.issues');
    Route::get('/batch-scan/history', [App\Http\Controllers\BatchScanController::class, 'getBatchHistory'])->name('batch.history');
    });
    
    // Pending Section - requires pending permission
    Route::middleware(['role:pending'])->group(function () {
        Route::get('/pending', [PendingController::class, 'index'])->name('pending');
        Route::get('/pending/list', [PendingController::class, 'list'])->name('pending.list');
        Route::post('/pending/dispatch', [PendingController::class, 'dispatch'])->name('pending.dispatch');
    });
    
    // Upload - requires upload permission
    Route::middleware(['role:upload'])->group(function () {
        Route::get('/upload', [UploadController::class, 'index'])->name('upload');
        Route::get('/upload-batch', [UploadController::class, 'batchIndex'])->name('upload.batch');
        Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');


        Route::post('/upload-batch', [UploadController::class, 'storeBatch'])->name('upload.batch.store');
        Route::get('/upload/{id}/status', [UploadController::class, 'status'])->name('upload.status');
        Route::post('/upload-batch/cancel', [UploadController::class, 'cancelBatch'])->name('upload.batch.cancel');
    });
    
    // Accounts/Waybills - requires accounts permission
    Route::middleware(['role:accounts'])->group(function () {
        Route::get('/waybills', [WaybillController::class, 'index'])->name('waybills');
    });
    
    // Settings - requires settings permission
    Route::middleware(['role:settings'])->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/update', [SettingsController::class, 'update'])->name('settings.update');
    });
    
    // User Management - requires users permission (Admin & Superadmin)
    Route::middleware(['role:users'])->group(function () {
        Route::get('/settings/users', [SettingsController::class, 'users'])->name('settings.users');
        Route::post('/settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
        Route::put('/settings/users/{id}', [SettingsController::class, 'updateUser'])->name('settings.users.update');
        Route::delete('/settings/users/{id}', [SettingsController::class, 'deleteUser'])->name('settings.users.delete');
    });
    
    // Notifications - accessible by all
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    // Leads Management
    Route::middleware(['role:leads_view'])->group(function () {
        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
        Route::post('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.updateStatus');
        
        // Creation / Import (Agents & Admins)
        Route::middleware(['role:leads_create'])->group(function () {
            Route::get('/leads-import', [LeadController::class, 'importForm'])->name('leads.importForm');
            Route::post('/leads-import', [LeadController::class, 'import'])->name('leads.import');
            Route::post('/leads-mine', [LeadController::class, 'mine'])->name('leads.mine');
        });

        // Admin / Team Leader Actions
        Route::middleware(['role:leads_manage'])->group(function () {
            Route::get('/leads-monitoring', [LeadController::class, 'monitoring'])->name('leads.monitoring');
            Route::post('/leads-assign', [LeadController::class, 'assign'])->name('leads.assign');
            Route::post('/leads-distribute', [LeadController::class, 'distribute'])->name('leads.distribute');
            Route::get('/leads-export', [LeadController::class, 'export'])->name('leads.export');
            Route::get('/leads-export-jnt', [LeadController::class, 'exportJNT'])->name('leads.exportJNT');
            Route::post('/leads-clear', [LeadController::class, 'clear'])->name('leads.clear');
            
            // Lead Cycle Reports
            Route::get('/leads-reports/agent-performance', [LeadReportController::class, 'agentPerformance'])->name('leads.reports.agentPerformance');
            Route::get('/leads-reports/recycling-patterns', [LeadReportController::class, 'recyclingPatterns'])->name('leads.reports.recyclingPatterns');
            Route::get('/leads/{lead}/history', [LeadReportController::class, 'leadHistory'])->name('leads.history');
        });
    });
});
