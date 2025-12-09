<?php

use App\Imports\WaybillsImport;
use App\Models\Waybill;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Testing WaybillsImport Logic ---\n";

$import = new WaybillsImport(1, true);

$row = [
    'waybill_number' => 'TEST123456',
    'sender_name' => 'John Doe',
    'sender_address' => '123 Main St',
    'sender_cellphone' => '09123456789',
    'receiver' => 'Jane Doe',
    'barangay' => 'Brgy 1',
    'city' => 'Manila',
    'province' => 'Metro Manila',
    'receiver_cellphone' => '09987654321',
    'item_weight' => 1.5,
    'number_of_items' => 2,
    'express_type' => 'Standard',
    'cod' => 100.00,
    'remarks' => 'Handle with care'
];

try {
    $waybill = $import->model($row);
    
    if ($waybill instanceof Waybill) {
        echo "[PASS] Waybill model created successfully.\n";
        echo "Waybill Number: " . $waybill->waybill_number . "\n";
        echo "Receiver Address: " . $waybill->receiver_address . "\n";
        
        // Verify address concatenation
        if ($waybill->receiver_address === 'Brgy 1, Manila, Metro Manila') {
             echo "[PASS] Address concatenation correct.\n";
        } else {
             echo "[FAIL] Address concatenation incorrect: " . $waybill->receiver_address . "\n";
        }
    } else {
        echo "[FAIL] model() returned null or invalid type.\n";
    }
} catch (\Exception $e) {
    echo "[FAIL] Exception during import: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
