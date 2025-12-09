<?php

use App\Models\BatchSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Starting Local Diagnostic ---\n";

// 1. Check Database Connection
try {
    $pdo = DB::connection()->getPdo();
    echo "[PASS] Database connection successful.\n";
    echo "Connected to: " . DB::connection()->getDatabaseName() . " on " . DB::getConfig('host') . "\n";
} catch (\Exception $e) {
    echo "[FAIL] Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Check uploads table
echo "\nChecking Uploads Table...\n";
if (Schema::hasTable('uploads')) {
    echo "[PASS] Table 'uploads' exists.\n";
} else {
    echo "[FAIL] Table 'uploads' does not exist.\n";
}

// 3. Test Upload Creation
echo "\nTesting Upload Creation...\n";
try {
    $upload = \App\Models\Upload::create([
        'filename' => 'local_test.xlsx',
        'uploaded_by' => 'LocalDev',
        'status' => 'processing',
        'notes' => 'Local diagnostic test'
    ]);
    echo "[PASS] Upload record created successfully. ID: " . $upload->id . "\n";
    $upload->delete();
} catch (\Exception $e) {
    echo "[FAIL] Upload record creation failed: " . $e->getMessage() . "\n";
}

echo "\n--- Diagnostic Complete ---\n";
