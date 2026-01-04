<?php

namespace App\Console\Commands;

use App\Models\Waybill;
use App\Services\OrderHistoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillOrderHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:backfill
                            {--chunk=100 : Number of records to process per batch}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill customer order history from existing waybills';

    /**
     * Execute the console command.
     */
    public function handle(OrderHistoryService $orderHistoryService): int
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting order history backfill from waybills...');
        $this->newLine();

        // Get waybills that have receiver_phone and are not already in order history
        $query = Waybill::whereNotNull('receiver_phone')
            ->where('receiver_phone', '!=', '')
            ->whereNotExists(function ($q) {
                $q->select(\DB::raw(1))
                  ->from('customer_order_history')
                  ->whereColumn('customer_order_history.waybill_number', 'waybills.waybill_number');
            });

        $total = $query->count();

        if ($total === 0) {
            $this->info('No waybills to process. All order history is up to date.');
            return Command::SUCCESS;
        }

        $this->info("Found {$total} waybills to process.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $results = [
            'processed' => 0,
            'created' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $query->chunk($chunkSize, function ($waybills) use (&$results, $bar, $orderHistoryService, $dryRun) {
            foreach ($waybills as $waybill) {
                $results['processed']++;

                try {
                    if ($dryRun) {
                        $results['created']++;
                    } else {
                        $created = $orderHistoryService->createFromWaybill($waybill);
                        if ($created) {
                            $results['created']++;
                        } else {
                            $results['skipped']++;
                        }
                    }
                } catch (\Exception $e) {
                    $results['errors']++;
                    Log::warning("Failed to backfill order history for waybill {$waybill->id}: " . $e->getMessage());
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('=== BACKFILL COMPLETE ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Waybills Processed', $results['processed']],
                ['Order History Created', $results['created']],
                ['Skipped (no phone/already exists)', $results['skipped']],
                ['Errors', $results['errors']],
            ]
        );

        Log::info('Order history backfill completed', $results);

        return Command::SUCCESS;
    }
}
