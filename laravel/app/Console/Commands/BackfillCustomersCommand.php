<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Waybill;
use App\Services\CustomerIdentificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillCustomersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:backfill
                            {--leads-only : Only process leads, skip waybills}
                            {--waybills-only : Only process waybills, skip leads}
                            {--chunk=100 : Number of records to process per batch}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill customer records from existing leads and waybills data';

    protected CustomerIdentificationService $customerService;

    /**
     * Execute the console command.
     */
    public function handle(CustomerIdentificationService $customerService): int
    {
        $this->customerService = $customerService;
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting customer backfill process...');
        $this->newLine();

        $totalResults = [
            'leads_processed' => 0,
            'leads_linked' => 0,
            'waybills_processed' => 0,
            'customers_created' => 0,
            'errors' => 0,
        ];

        // Process Leads
        if (!$this->option('waybills-only')) {
            $leadResults = $this->processLeads($chunkSize, $dryRun);
            $totalResults['leads_processed'] = $leadResults['processed'];
            $totalResults['leads_linked'] = $leadResults['linked'];
            $totalResults['customers_created'] += $leadResults['created'];
            $totalResults['errors'] += $leadResults['errors'];
        }

        // Process Waybills (to ensure all receiver phones have customer records)
        if (!$this->option('leads-only')) {
            $waybillResults = $this->processWaybills($chunkSize, $dryRun);
            $totalResults['waybills_processed'] = $waybillResults['processed'];
            $totalResults['customers_created'] += $waybillResults['created'];
            $totalResults['errors'] += $waybillResults['errors'];
        }

        // Summary
        $this->newLine();
        $this->info('=== BACKFILL COMPLETE ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Leads Processed', $totalResults['leads_processed']],
                ['Leads Linked to Customers', $totalResults['leads_linked']],
                ['Waybills Processed', $totalResults['waybills_processed']],
                ['Customers Created', $totalResults['customers_created']],
                ['Errors', $totalResults['errors']],
            ]
        );

        Log::info('Customer backfill completed', $totalResults);

        return Command::SUCCESS;
    }

    /**
     * Process leads and link them to customers.
     */
    protected function processLeads(int $chunkSize, bool $dryRun): array
    {
        $this->info('Processing leads without customer_id...');

        $query = Lead::whereNull('customer_id')
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        $total = $query->count();

        if ($total === 0) {
            $this->info('No leads to process.');
            return ['processed' => 0, 'linked' => 0, 'created' => 0, 'errors' => 0];
        }

        $this->info("Found {$total} leads to process.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $results = [
            'processed' => 0,
            'linked' => 0,
            'created' => 0,
            'errors' => 0,
        ];

        $query->chunk($chunkSize, function ($leads) use (&$results, $bar, $dryRun) {
            foreach ($leads as $lead) {
                $results['processed']++;

                try {
                    if ($dryRun) {
                        // Just check if customer would be created
                        $phoneNormalized = $this->customerService->normalizePhone($lead->phone);
                        $exists = DB::table('customers')
                            ->where('phone_primary', $phoneNormalized)
                            ->exists();

                        if (!$exists) {
                            $results['created']++;
                        }
                        $results['linked']++;
                    } else {
                        // Actually link the lead
                        $phoneNormalized = $this->customerService->normalizePhone($lead->phone);
                        $existsBefore = DB::table('customers')
                            ->where('phone_primary', $phoneNormalized)
                            ->exists();

                        $customer = $this->customerService->linkLeadToCustomer($lead);

                        if ($customer) {
                            $results['linked']++;
                            if (!$existsBefore) {
                                $results['created']++;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $results['errors']++;
                    Log::warning("Failed to process lead {$lead->id}: " . $e->getMessage());
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Leads: {$results['linked']} linked, {$results['created']} new customers, {$results['errors']} errors");

        return $results;
    }

    /**
     * Process waybills to ensure all receiver phones have customer records.
     * This doesn't link waybills to customers directly, but ensures customers exist
     * for the order history system.
     */
    protected function processWaybills(int $chunkSize, bool $dryRun): array
    {
        $this->info('Processing waybills to create missing customer records...');

        // Get unique phone numbers from waybills that don't have customer records
        $query = Waybill::select('receiver_phone', 'receiver_name', 'receiver_address', 'city', 'province', 'barangay', 'street')
            ->whereNotNull('receiver_phone')
            ->where('receiver_phone', '!=', '')
            ->distinct();

        // We'll process in chunks by getting distinct phones
        $processed = 0;
        $created = 0;
        $errors = 0;

        // Get total count for progress bar
        $total = DB::table('waybills')
            ->whereNotNull('receiver_phone')
            ->where('receiver_phone', '!=', '')
            ->distinct()
            ->count('receiver_phone');

        if ($total === 0) {
            $this->info('No waybills to process.');
            return ['processed' => 0, 'created' => 0, 'errors' => 0];
        }

        $this->info("Found {$total} unique phone numbers in waybills.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // Get unique phones with their most recent data
        DB::table('waybills')
            ->select(DB::raw('DISTINCT ON (receiver_phone) receiver_phone, receiver_name, receiver_address, city, province, barangay, street'))
            ->whereNotNull('receiver_phone')
            ->where('receiver_phone', '!=', '')
            ->orderBy('receiver_phone')
            ->orderBy('created_at', 'desc')
            ->chunk($chunkSize, function ($waybills) use (&$processed, &$created, &$errors, $bar, $dryRun) {
                foreach ($waybills as $waybill) {
                    $processed++;

                    try {
                        $phoneNormalized = $this->customerService->normalizePhone($waybill->receiver_phone);

                        // Check if customer already exists
                        $exists = DB::table('customers')
                            ->where('phone_primary', $phoneNormalized)
                            ->exists();

                        if (!$exists) {
                            if (!$dryRun) {
                                $this->customerService->findOrCreateCustomer([
                                    'phone' => $waybill->receiver_phone,
                                    'name' => $waybill->receiver_name,
                                    'address' => $waybill->receiver_address,
                                    'city' => $waybill->city,
                                    'province' => $waybill->province,
                                    'barangay' => $waybill->barangay,
                                    'street' => $waybill->street,
                                ]);
                            }
                            $created++;
                        }
                    } catch (\Exception $e) {
                        $errors++;
                        Log::warning("Failed to process waybill phone {$waybill->receiver_phone}: " . $e->getMessage());
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->info("Waybills: {$processed} phones processed, {$created} new customers, {$errors} errors");

        return ['processed' => $processed, 'created' => $created, 'errors' => $errors];
    }
}
