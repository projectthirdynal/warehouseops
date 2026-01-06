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
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CustomerController;

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

    // Lead Management
    Route::resource('leads', LeadController::class);
    Route::post('leads/check-duplicates', [LeadController::class, 'checkDuplicates'])->name('leads.check-duplicates');
    
    // QC / Checker Workflow
    Route::prefix('qc')->name('qc.')->group(function () {
        Route::get('/dashboard', [QcController::class, 'index'])->name('index');
        Route::post('/{lead}/approve', [QcController::class, 'approve'])->name('approve');
        Route::post('/{lead}/reject', [QcController::class, 'reject'])->name('reject');
        Route::post('/{lead}/recycle', [QcController::class, 'recycle'])->name('recycle');
    });

    // Leads Management (Original block, modified to fit new structure)
    Route::middleware(['role:leads_view'])->group(function () {
        Route::post('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.updateStatus');

        // Agent's recycling pool leads
        Route::get('/recycling/mine', [App\Http\Controllers\RecyclingPoolController::class, 'mine'])->name('recycling.mine');

        // Customer Profile & Search
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('/customers-search', [CustomerController::class, 'search'])->name('customers.search');

        // Creation / Import (Agents & Admins)
        Route::middleware(['role:leads_create'])->group(function () {
            Route::get('/leads-import', [LeadController::class, 'importForm'])->name('leads.importForm');
            Route::post('/leads-import', [LeadController::class, 'import'])->name('leads.import');
            Route::post('/leads-mine', [LeadController::class, 'mine'])->name('leads.mine');

            // Customer Actions
            Route::post('/customers/{customer}/create-lead', [CustomerController::class, 'createLead'])->name('customers.createLead');
            Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        });

        // Admin / Team Leader Actions
        Route::middleware(['role:leads_manage'])->group(function () {
            Route::get('/leads-monitoring', [LeadController::class, 'monitoring'])->name('leads.monitoring');
            Route::post('/leads-assign', [LeadController::class, 'assign'])->name('leads.assign');
            Route::post('/leads-distribute', [LeadController::class, 'distribute'])->name('leads.distribute');
            Route::get('/leads-export', [LeadController::class, 'export'])->name('leads.export');
            Route::get('/leads-export-jnt', [LeadController::class, 'exportJNT'])->name('leads.exportJNT');
            Route::post('/leads-clear', [LeadController::class, 'clear'])->name('leads.clear');
            
            // Smart Distribution
            Route::post('/leads-smart-distribute', [LeadController::class, 'smartDistribute'])->name('leads.smartDistribute');
            Route::get('/leads-distribution-stats', [LeadController::class, 'distributionStats'])->name('leads.distributionStats');
            
            // Lead Cycle Reports
            Route::get('/leads-reports/agent-performance', [LeadReportController::class, 'agentPerformance'])->name('leads.reports.agentPerformance');
            Route::get('/leads-reports/recycling-patterns', [LeadReportController::class, 'recyclingPatterns'])->name('leads.reports.recyclingPatterns');
            Route::get('/leads/{lead}/history', [LeadReportController::class, 'leadHistory'])->name('leads.history');
            
            // Agent Governance
            Route::get('/agents/governance', [App\Http\Controllers\AgentGovernanceController::class, 'index'])->name('agents.governance');
            Route::post('/agents/flags/{flag}/resolve', [App\Http\Controllers\AgentGovernanceController::class, 'resolve'])->name('agents.flags.resolve');
            
            // Operations Monitoring
            Route::get('/monitoring/dashboard', [App\Http\Controllers\MonitoringController::class, 'dashboard'])->name('monitoring.dashboard');
            Route::get('/monitoring/live-stats', [App\Http\Controllers\MonitoringController::class, 'liveStats'])->name('monitoring.live-stats');
            Route::get('/monitoring/stuck-cycles', [App\Http\Controllers\MonitoringController::class, 'stuckCycles'])->name('monitoring.stuck-cycles');
            Route::get('/monitoring/blocked-leads', [App\Http\Controllers\MonitoringController::class, 'blockedLeads'])->name('monitoring.blocked-leads');
            Route::get('/monitoring/recycle-heatmap', [App\Http\Controllers\MonitoringController::class, 'recycleHeatmap'])->name('monitoring.recycle-heatmap');

            // Lead Recycling Pool
            Route::get('/recycling/pool', [App\Http\Controllers\RecyclingPoolController::class, 'index'])->name('recycling.pool');
            Route::get('/recycling/pool/stats', [App\Http\Controllers\RecyclingPoolController::class, 'stats'])->name('recycling.stats');
            Route::post('/recycling/assign', [App\Http\Controllers\RecyclingPoolController::class, 'assign'])->name('recycling.assign');
            Route::post('/recycling/{poolId}/outcome', [App\Http\Controllers\RecyclingPoolController::class, 'processOutcome'])->name('recycling.outcome');
            Route::get('/recycling/{poolId}', [App\Http\Controllers\RecyclingPoolController::class, 'show'])->name('recycling.show');
            Route::post('/recycling/cleanup', [App\Http\Controllers\RecyclingPoolController::class, 'cleanup'])->name('recycling.cleanup');

            // Analytics & Reporting
            Route::get('/reports/customer-lifetime-value', [ReportController::class, 'customerLifetimeValue'])->name('reports.customer-ltv');
            Route::get('/reports/recycling-funnel', [ReportController::class, 'recyclingFunnel'])->name('reports.recycling-funnel');
            Route::get('/reports/agent-performance', [ReportController::class, 'agentPerformance'])->name('reports.agent-performance');
            Route::get('/reports/customer-cohorts', [ReportController::class, 'customerCohorts'])->name('reports.customer-cohorts');
            Route::get('/reports/risk-trends', [ReportController::class, 'riskTrends'])->name('reports.risk-trends');
            Route::get('/reports/order-status', [ReportController::class, 'orderStatus'])->name('reports.order-status');
            Route::get('/reports/priority-distribution', [ReportController::class, 'priorityDistribution'])->name('reports.priority-distribution');
            Route::get('/reports/dashboard', [ReportController::class, 'dashboard'])->name('reports.dashboard');

            // Customer Management (Admin Only)
            Route::post('/customers/{customer}/blacklist', [CustomerController::class, 'blacklist'])->name('customers.blacklist');
            Route::post('/customers/{customer}/unblacklist', [CustomerController::class, 'unblacklist'])->name('customers.unblacklist');
        });
    });

    // Monitoring & QC
    Route::middleware(['auth'])->group(function () {
        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('/monitoring/stats', [MonitoringController::class, 'getStats'])->name('monitoring.stats');
        Route::post('/monitoring/heartbeat', [MonitoringController::class, 'heartbeat'])->name('monitoring.heartbeat');
        
        // QC Actions (Checkers)
        Route::get('/monitoring/sales-queue', [MonitoringController::class, 'salesQueue'])->name('monitoring.salesQueue');
        Route::post('/monitoring/{lead}/approve', [MonitoringController::class, 'approveQc'])->name('monitoring.approve');
        Route::post('/monitoring/{lead}/reject', [MonitoringController::class, 'rejectQc'])->name('monitoring.reject');
    });
});
