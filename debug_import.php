<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Starting import...\n";
    $upload = \App\Models\Upload::create([
        'filename' => 'debug_sample.xlsx',
        'uploaded_by' => 'Debug',
        'status' => 'processing',
        'notes' => 'Debug upload'
    ]);
    echo "Created Upload ID: " . $upload->id . "\n";
    
    $import = new \App\Imports\WaybillsImport($upload->id, false);
    \Maatwebsite\Excel\Facades\Excel::import($import, '/home/it-admin/Documents/v4/v4/sample.xlsx');
    echo "Import successful\n";
} catch (\Throwable $e) {
    echo "Import failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
