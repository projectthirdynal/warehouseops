<?php

namespace App\Console\Commands;

use App\Services\OrderHistoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportJNTTrackingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jnt:import
                            {file : Path to CSV file with tracking data}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import J&T tracking status updates from CSV file';

    /**
     * Expected CSV columns (in order):
     * waybill_number, status, location, timestamp, return_reason, notes
     */
    protected array $csvColumns = [
        'waybill_number',
        'status',
        'location',
        'timestamp',
        'return_reason',
        'notes',
    ];

    /**
     * Execute the console command.
     */
    public function handle(OrderHistoryService $orderHistoryService): int
    {
        $filePath = $this->argument('file');
        $dryRun = $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info("Importing J&T tracking data from: {$filePath}");
        $this->newLine();

        // Read CSV file
        $trackingData = $this->readCsvFile($filePath);

        if (empty($trackingData)) {
            $this->error('No valid tracking data found in file');
            return Command::FAILURE;
        }

        $this->info("Found " . count($trackingData) . " tracking records to process.");
        $bar = $this->output->createProgressBar(count($trackingData));
        $bar->start();

        if ($dryRun) {
            // Just show what would be processed
            foreach ($trackingData as $record) {
                $bar->advance();
            }
            $bar->finish();
            $this->newLine(2);

            $this->table(
                ['Waybill', 'Status', 'Location'],
                array_map(fn($r) => [
                    $r['waybill_number'] ?? 'N/A',
                    $r['status'] ?? 'N/A',
                    $r['location'] ?? '-',
                ], array_slice($trackingData, 0, 10))
            );

            if (count($trackingData) > 10) {
                $this->info("... and " . (count($trackingData) - 10) . " more records");
            }

            return Command::SUCCESS;
        }

        // Process tracking data
        $results = $orderHistoryService->importJNTTracking($trackingData);

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('=== IMPORT COMPLETE ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Records Updated', $results['updated']],
                ['New Records Created', $results['created']],
                ['Not Found (waybill not in system)', $results['not_found']],
                ['Errors', $results['errors']],
            ]
        );

        Log::info('J&T tracking import completed', array_merge(['file' => $filePath], $results));

        return Command::SUCCESS;
    }

    /**
     * Read and parse CSV file.
     */
    protected function readCsvFile(string $filePath): array
    {
        $records = [];
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            return [];
        }

        // Read header row
        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);
            return [];
        }

        // Normalize header names
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        // Map header to expected columns
        $columnMap = [];
        foreach ($this->csvColumns as $expectedColumn) {
            $index = array_search($expectedColumn, $header);
            if ($index !== false) {
                $columnMap[$expectedColumn] = $index;
            }
        }

        // Must have at least waybill_number and status
        if (!isset($columnMap['waybill_number']) || !isset($columnMap['status'])) {
            $this->error('CSV must have waybill_number and status columns');
            fclose($handle);
            return [];
        }

        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            $record = [];

            foreach ($columnMap as $column => $index) {
                $record[$column] = $row[$index] ?? null;
            }

            // Skip empty waybill numbers
            if (empty($record['waybill_number'])) {
                continue;
            }

            $records[] = $record;
        }

        fclose($handle);

        return $records;
    }
}
