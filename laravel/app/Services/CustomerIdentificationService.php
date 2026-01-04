<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Waybill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerIdentificationService
{
    /**
     * Normalize a phone number for matching.
     * Removes country codes, spaces, dashes, and leading zeros.
     */
    public function normalizePhone(string $phone): string
    {
        // Remove all non-numeric characters
        $normalized = preg_replace('/[^0-9]/', '', $phone);

        // Remove Philippine country code (+63 or 63)
        if (str_starts_with($normalized, '63') && strlen($normalized) > 10) {
            $normalized = substr($normalized, 2);
        }

        // Remove leading zero if present (common in PH numbers)
        if (str_starts_with($normalized, '0') && strlen($normalized) === 11) {
            $normalized = substr($normalized, 1);
        }

        return $normalized;
    }

    /**
     * Normalize a name for matching.
     * Lowercase, trim, remove extra spaces.
     */
    public function normalizeName(string $name): string
    {
        // Lowercase and trim
        $normalized = strtolower(trim($name));

        // Remove extra whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        // Remove common prefixes/suffixes
        $normalized = preg_replace('/^(mr\.?|mrs\.?|ms\.?|dr\.?)\s+/i', '', $normalized);

        return $normalized;
    }

    /**
     * Find an existing customer or create a new one based on provided data.
     *
     * Matching priority:
     * 1. Exact match on phone_primary
     * 2. Exact match on phone_secondary
     * 3. Fuzzy match: name_normalized + last 7 digits of phone
     * 4. Create new customer if not found
     */
    public function findOrCreateCustomer(array $data): Customer
    {
        $phone = $data['phone'] ?? null;
        $name = $data['name'] ?? null;

        if (!$phone) {
            throw new \InvalidArgumentException('Phone number is required for customer identification.');
        }

        $phoneNormalized = $this->normalizePhone($phone);
        $nameNormalized = $name ? $this->normalizeName($name) : null;

        // Step 1: Try exact match on phone_primary
        $customer = Customer::where('phone_primary', $phoneNormalized)->first();

        if ($customer) {
            Log::debug("Customer found by phone_primary: {$phoneNormalized}", ['customer_id' => $customer->id]);
            return $this->updateCustomerIfNeeded($customer, $data);
        }

        // Step 2: Try match on phone_secondary
        $customer = Customer::where('phone_secondary', $phoneNormalized)->first();

        if ($customer) {
            Log::debug("Customer found by phone_secondary: {$phoneNormalized}", ['customer_id' => $customer->id]);
            return $this->updateCustomerIfNeeded($customer, $data);
        }

        // Step 3: Fuzzy match - name_normalized + last 7 digits of phone
        if ($nameNormalized && strlen($phoneNormalized) >= 7) {
            $phoneSuffix = substr($phoneNormalized, -7);

            $customer = Customer::where('name_normalized', $nameNormalized)
                ->where(function ($query) use ($phoneSuffix) {
                    $query->where('phone_primary', 'LIKE', '%' . $phoneSuffix)
                          ->orWhere('phone_secondary', 'LIKE', '%' . $phoneSuffix);
                })
                ->first();

            if ($customer) {
                Log::debug("Customer found by fuzzy match: name={$nameNormalized}, phone suffix={$phoneSuffix}", [
                    'customer_id' => $customer->id
                ]);

                // Update secondary phone if different from primary
                if ($customer->phone_primary !== $phoneNormalized && !$customer->phone_secondary) {
                    $customer->phone_secondary = $phoneNormalized;
                    $customer->save();
                }

                return $this->updateCustomerIfNeeded($customer, $data);
            }
        }

        // Step 4: Create new customer
        Log::info("Creating new customer: phone={$phoneNormalized}, name={$nameNormalized}");

        return $this->createCustomer($data, $phoneNormalized, $nameNormalized);
    }

    /**
     * Create a new customer record.
     */
    protected function createCustomer(array $data, string $phoneNormalized, ?string $nameNormalized): Customer
    {
        return Customer::create([
            'phone_primary' => $phoneNormalized,
            'name_normalized' => $nameNormalized,
            'name_display' => $data['name'] ?? null,
            'primary_address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'province' => $data['province'] ?? $data['state'] ?? null,
            'barangay' => $data['barangay'] ?? null,
            'street' => $data['street'] ?? null,
            'first_seen_at' => now(),
        ]);
    }

    /**
     * Update customer data if new information is more complete.
     */
    protected function updateCustomerIfNeeded(Customer $customer, array $data): Customer
    {
        $updated = false;

        // Update name if not set
        if (!$customer->name_display && !empty($data['name'])) {
            $customer->name_display = $data['name'];
            $customer->name_normalized = $this->normalizeName($data['name']);
            $updated = true;
        }

        // Update address if not set
        if (!$customer->primary_address && !empty($data['address'])) {
            $customer->primary_address = $data['address'];
            $updated = true;
        }

        // Update city if not set
        if (!$customer->city && !empty($data['city'])) {
            $customer->city = $data['city'];
            $updated = true;
        }

        // Update province if not set
        $province = $data['province'] ?? $data['state'] ?? null;
        if (!$customer->province && $province) {
            $customer->province = $province;
            $updated = true;
        }

        // Update barangay if not set
        if (!$customer->barangay && !empty($data['barangay'])) {
            $customer->barangay = $data['barangay'];
            $updated = true;
        }

        // Update street if not set
        if (!$customer->street && !empty($data['street'])) {
            $customer->street = $data['street'];
            $updated = true;
        }

        if ($updated) {
            $customer->save();
        }

        return $customer;
    }

    /**
     * Link a lead to a customer record.
     * Finds or creates the customer based on lead data.
     */
    public function linkLeadToCustomer(Lead $lead): ?Customer
    {
        if (!$lead->phone) {
            Log::warning("Cannot link lead to customer: no phone number", ['lead_id' => $lead->id]);
            return null;
        }

        try {
            $customer = $this->findOrCreateCustomer([
                'phone' => $lead->phone,
                'name' => $lead->name,
                'address' => $lead->address,
                'city' => $lead->city,
                'province' => $lead->state,
                'barangay' => $lead->barangay,
                'street' => $lead->street,
            ]);

            $lead->customer_id = $customer->id;
            $lead->save();

            Log::debug("Linked lead to customer", [
                'lead_id' => $lead->id,
                'customer_id' => $customer->id
            ]);

            return $customer;
        } catch (\Exception $e) {
            Log::error("Failed to link lead to customer", [
                'lead_id' => $lead->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Link a waybill to a customer record (for order history).
     */
    public function findCustomerFromWaybill(Waybill $waybill): ?Customer
    {
        if (!$waybill->receiver_phone) {
            return null;
        }

        try {
            return $this->findOrCreateCustomer([
                'phone' => $waybill->receiver_phone,
                'name' => $waybill->receiver_name,
                'address' => $waybill->receiver_address,
                'city' => $waybill->city,
                'province' => $waybill->province,
                'barangay' => $waybill->barangay,
                'street' => $waybill->street,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to find/create customer from waybill", [
                'waybill_id' => $waybill->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Bulk process leads to link them to customers.
     * Used for initial data migration.
     */
    public function bulkLinkLeads(iterable $leads, ?callable $progressCallback = null): array
    {
        $results = [
            'processed' => 0,
            'linked' => 0,
            'created' => 0,
            'errors' => 0,
        ];

        foreach ($leads as $lead) {
            $results['processed']++;

            if ($lead->customer_id) {
                // Already linked
                continue;
            }

            try {
                $phoneNormalized = $this->normalizePhone($lead->phone);

                // Check if customer already exists
                $existingCustomer = Customer::where('phone_primary', $phoneNormalized)->first();
                $wasNew = !$existingCustomer;

                $customer = $this->linkLeadToCustomer($lead);

                if ($customer) {
                    $results['linked']++;
                    if ($wasNew) {
                        $results['created']++;
                    }
                }
            } catch (\Exception $e) {
                $results['errors']++;
                Log::warning("Bulk link error for lead {$lead->id}: " . $e->getMessage());
            }

            if ($progressCallback) {
                $progressCallback($results['processed']);
            }
        }

        return $results;
    }
}
