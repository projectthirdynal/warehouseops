<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkLeadsToCustomersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:link-customers
                            {--chunk=500 : Number of records to process per batch}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link existing leads to customer profiles based on phone number';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting to link leads to customers...');
        $this->newLine();

        // Get leads without customer_id
        $query = Lead::whereNull('customer_id')
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        $total = $query->count();

        if ($total === 0) {
            $this->info('No leads to process. All leads are already linked.');
            return Command::SUCCESS;
        }

        $this->info("Found {$total} leads to link.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $results = [
            'processed' => 0,
            'linked' => 0,
            'not_found' => 0,
            'errors' => 0,
        ];

        $query->chunk($chunkSize, function ($leads) use (&$results, $bar, $dryRun) {
            foreach ($leads as $lead) {
                $results['processed']++;

                try {
                    // Normalize phone: remove leading 0 if present
                    $normalizedPhone = ltrim($lead->phone, '0');

                    // Try to find customer by normalized phone
                    $customer = Customer::where('phone_primary', $normalizedPhone)
                        ->orWhere('phone_primary', $lead->phone) // Also try original format
                        ->first();

                    if ($customer) {
                        if (!$dryRun) {
                            $lead->customer_id = $customer->id;
                            $lead->save();
                        }
                        $results['linked']++;
                    } else {
                        $results['not_found']++;
                    }
                } catch (\Exception $e) {
                    $results['errors']++;
                    $this->warn("Error processing lead {$lead->id}: " . $e->getMessage());
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('=== LINKING COMPLETE ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Leads Processed', $results['processed']],
                ['Successfully Linked', $results['linked']],
                ['No Customer Found', $results['not_found']],
                ['Errors', $results['errors']],
            ]
        );

        if (!$dryRun) {
            $this->newLine();
            $this->info('✓ Leads have been linked to customers.');
            $this->info('You can now view customer profiles from the leads page.');
        }

        return Command::SUCCESS;
    }

    /**
     * Normalize phone number by removing leading zero and non-numeric characters
     */
    private function normalizePhone(string $phone): string
    {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zero
        return ltrim($phone, '0');
    }
}
