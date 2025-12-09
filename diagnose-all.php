<?php

use App\Models\BatchSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

require '/var/www/waybill/vendor/autoload.php';
$app = require_once '/var/www/waybill/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Starting Diagnostic ---\n";

// 1. Check Database Connection
try {
    DB::connection()->getPdo();
    echo "[PASS] Database connection successful.\n";
} catch (\Exception $e) {
    echo "[FAIL] Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Check batch_sessions table
if (Schema::hasTable('batch_sessions')) {
    echo "[PASS] Table 'batch_sessions' exists.\n";
    $columns = Schema::getColumnListing('batch_sessions');
    echo "Columns: " . implode(', ', $columns) . "\n";
    
    if (in_array('end_time', $columns)) {
        echo "[PASS] Column 'end_time' exists.\n";
    } else {
        echo "[FAIL] Column 'end_time' MISSING!\n";
    }
} else {
    echo "[FAIL] Table 'batch_sessions' does not exist.\n";
}

// 3. Test BatchSession Update (Reproduce Stack Trace)
echo "\nTesting BatchSession Update...\n";
try {
    // Create a dummy active session
    $session = BatchSession::create([
        'scanned_by' => 'test_diagnostic',
        'status' => 'active'
    ]);
    echo "Created test session ID: " . $session->id . "\n";
    
    // Try the update that failed
    BatchSession::where('scanned_by', 'test_diagnostic')
        ->where('status', 'active')
        ->update(['status' => 'cancelled', 'end_time' => now()]);
        
    echo "[PASS] BatchSession update successful.\n";
    
    // Cleanup
    $session->delete();
} catch (\Exception $e) {
    echo "[FAIL] BatchSession update failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// 4. Check uploads table
echo "\nChecking Uploads Table...\n";
if (Schema::hasTable('uploads')) {
    echo "[PASS] Table 'uploads' exists.\n";
    $columns = Schema::getColumnListing('uploads');
    echo "Columns: " . implode(', ', $columns) . "\n";
} else {
    echo "[FAIL] Table 'uploads' does not exist.\n";
}

// 5. Test Upload Creation
echo "\nTesting Upload Creation...\n";
try {
    $upload = \App\Models\Upload::create([
        'filename' => 'diagnostic_test.xlsx',
        'uploaded_by' => 'Diagnostic',
        'status' => 'processing',
        'notes' => 'Diagnostic test'
    ]);
    echo "[PASS] Upload record created successfully. ID: " . $upload->id . "\n";
    $upload->delete();
} catch (\Exception $e) {
    echo "[FAIL] Upload record creation failed: " . $e->getMessage() . "\n";
}

// 6. Test Excel Library (Upload Issue)
echo "\nTesting Excel Library...\n";
try {
    if (class_exists('Maatwebsite\Excel\Excel')) {
        echo "[PASS] Maatwebsite\Excel class exists.\n";
    } else {
        echo "[FAIL] Maatwebsite\Excel class NOT found.\n";
    }
    
    // Check for ZipArchive (critical for Excel)
    if (class_exists('ZipArchive')) {
        echo "[PASS] ZipArchive class exists.\n";
    } else {
        echo "[FAIL] ZipArchive class NOT found (php-zip missing?).\n";
    }
    
    // Check for XMLWriter
    if (class_exists('XMLWriter')) {
        echo "[PASS] XMLWriter class exists.\n";
    } else {
        echo "[FAIL] XMLWriter class NOT found (php-xml missing?).\n";
    }
    
} catch (\Exception $e) {
    echo "[FAIL] Excel test failed: " . $e->getMessage() . "\n";
}

echo "\n--- Diagnostic Complete ---\n";
