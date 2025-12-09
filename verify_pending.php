<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Waybill;
use App\Models\BatchSession;
use App\Models\BatchScanItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

echo "Starting Verification...\n";

// Cleanup
Waybill::whereIn('waybill_number', ['WB_TEST_001', 'WB_TEST_002'])->delete();
BatchSession::where('scanned_by', 'TestUser')->delete();

// 1. Setup Data
echo "\n[Test 1] Setup Data...\n";

// Create dummy upload
$upload = \App\Models\Upload::create([
    'filename' => 'test_upload.xlsx',
    'uploaded_by' => 'TestUser',
    'total_rows' => 2,
    'processed_rows' => 2,
    'status' => 'processed'
]);
echo "Created dummy upload ID: {$upload->id}\n";

$wb1 = Waybill::create([
    'waybill_number' => 'WB_TEST_001',
    'upload_id' => $upload->id,
    'status' => 'pending',
    'batch_ready' => true,
    'sender_name' => 'Test Sender',
    'receiver_name' => 'Test Receiver',
    'destination' => 'Test Dest'
]);
$wb2 = Waybill::create([
    'waybill_number' => 'WB_TEST_002',
    'upload_id' => $upload->id,
    'status' => 'pending',
    'batch_ready' => true,
    'sender_name' => 'Test Sender',
    'receiver_name' => 'Test Receiver',
    'destination' => 'Test Dest'
]);
echo "Created WB_TEST_001 and WB_TEST_002 (batch_ready=true)\n";

// 2. Start Session & Scan WB_TEST_001
echo "\n[Test 2] Start Session & Scan...\n";
$controller = new \App\Http\Controllers\BatchScanController();
$reqStart = new \Illuminate\Http\Request(['scanned_by' => 'TestUser']);
$resStart = $controller->startSession($reqStart);
$sessionId = $resStart->getData()->session_id;
echo "Session started: $sessionId\n";

$reqScan = new \Illuminate\Http\Request([
    'waybill_number' => 'WB_TEST_001',
    'session_id' => $sessionId
]);
$resScan = $controller->scan($reqScan);
echo "Scan WB_TEST_001: " . $resScan->getData()->message . "\n";

// 3. Dispatch Batch
echo "\n[Test 3] Dispatch Batch...\n";
$reqDispatch = new \Illuminate\Http\Request(['session_id' => $sessionId]);
$resDispatch = $controller->dispatch($reqDispatch);
echo "Dispatch Result: " . $resDispatch->getData()->message . "\n";

// Verify Statuses
$wb1->refresh();
$wb2->refresh();
echo "WB_TEST_001 Status: " . $wb1->status . " (Expected: dispatched)\n";
echo "WB_TEST_002 Status: " . $wb2->status . " (Expected: issue_pending)\n";

if ($wb1->status !== 'dispatched' || $wb2->status !== 'issue_pending') {
    echo "FAILED: Incorrect statuses after dispatch.\n";
    exit(1);
}

// 4. Scan Pending Item (WB_TEST_002)
echo "\n[Test 4] Scan Pending Item...\n";
$resStart2 = $controller->startSession($reqStart);
$sessionId2 = $resStart2->getData()->session_id;

$reqScan2 = new \Illuminate\Http\Request([
    'waybill_number' => 'WB_TEST_002',
    'session_id' => $sessionId2
]);
$resScan2 = $controller->scan($reqScan2);
echo "Scan WB_TEST_002 (Pending): " . $resScan2->getData()->message . "\n";

if ($resScan2->getData()->scan_type !== 'valid') {
    echo "FAILED: Could not scan pending item.\n";
    exit(1);
}

// 5. Auto-Cancellation
echo "\n[Test 5] Auto-Cancellation...\n";
// Reset WB_TEST_002 to issue_pending and old date
$wb2->update([
    'status' => 'issue_pending',
    'marked_pending_at' => now()->subDays(6)
]);
echo "Set WB_TEST_002 to issue_pending (6 days old)\n";

Artisan::call('waybills:auto-cancel-pending');
echo Artisan::output();

$wb2->refresh();
echo "WB_TEST_002 Status: " . $wb2->status . " (Expected: cancelled)\n";

if ($wb2->status !== 'cancelled') {
    echo "FAILED: Auto-cancellation did not work.\n";
    exit(1);
}

// 6. Verify Pending Route
echo "\n[Test 6] Verify Pending Route...\n";
$reqPending = \Illuminate\Http\Request::create('/pending', 'GET');
$response = app()->handle($reqPending);

if ($response->getStatusCode() === 200) {
    echo "Pending route accessible (200 OK).\n";
} else {
    echo "FAILED: Pending route returned " . $response->getStatusCode() . "\n";
    exit(1);
}

// 7. Verify Quick Dispatch (PendingController)
echo "\n[Test 7] Verify Quick Dispatch...\n";
// Reset WB_TEST_002 to issue_pending
$wb2->update(['status' => 'issue_pending']);

$reqDispatchPending = \Illuminate\Http\Request::create('/api/pending/dispatch', 'POST', [
    'waybill_number' => 'WB_TEST_002'
]);
$response = app()->handle($reqDispatchPending);

if ($response->getStatusCode() === 200) {
    echo "Quick dispatch successful (200 OK).\n";
    $wb2->refresh();
    if ($wb2->status === 'dispatched') {
        echo "WB_TEST_002 is dispatched.\n";
    } else {
        echo "FAILED: WB_TEST_002 status is " . $wb2->status . "\n";
        exit(1);
    }
} else {
    echo "FAILED: Quick dispatch returned " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
    exit(1);
}

// 8. Verify Pagination
echo "\n[Test 8] Verify Pagination...\n";
// Create more dummy waybills to test pagination
for ($i = 1; $i <= 15; $i++) {
    \App\Models\Waybill::firstOrCreate(
        ['waybill_number' => 'WB_PAGE_' . str_pad($i, 3, '0', STR_PAD_LEFT)],
        [
            'upload_id' => $upload->id,
            'status' => 'issue_pending',
            'marked_pending_at' => now(),
            'batch_ready' => false
        ]
    );
}

$reqPagination = \Illuminate\Http\Request::create('/api/batch-scan/issues', 'GET', ['limit' => 10, 'page' => 1]);
$response = app()->handle($reqPagination);
$data = json_decode($response->getContent(), true);

if ($response->getStatusCode() === 200 && $data['success']) {
    echo "Pagination request successful.\n";
    echo "Total: " . $data['total'] . " (Expected > 15)\n";
    echo "Per Page: " . count($data['issues']) . " (Expected 10)\n";
    echo "Current Page: " . $data['page'] . "\n";
    
    if (count($data['issues']) === 10 && $data['total'] >= 15) {
        echo "Pagination logic verified.\n";
    } else {
        echo "FAILED: Pagination logic incorrect.\n";
        exit(1);
    }
} else {
    echo "FAILED: Pagination request failed.\n";
    exit(1);
}

echo "\nALL TESTS PASSED!\n";
