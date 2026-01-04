<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadRecyclingPool;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display the specified customer profile
     * GET /customers/{customerId}
     */
    public function show(string $customerId)
    {
        $customer = Customer::with([
            'orderHistory' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(50);
            },
            'leads' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
            'recyclingPool' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }
        ])->findOrFail($customerId);

        // Get agents for quick lead creation
        $agents = User::where('role', 'agent')
            ->orWhere('role', 'team_leader')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Calculate additional metrics
        $metrics = [
            'avg_order_value' => $customer->total_orders > 0
                ? round($customer->total_delivered_value / $customer->total_delivered, 2)
                : 0,
            'days_since_first_order' => $customer->first_seen_at
                ? $customer->first_seen_at->diffInDays(now())
                : 0,
            'days_since_last_order' => $customer->last_order_at
                ? $customer->last_order_at->diffInDays(now())
                : null,
        ];

        return view('customers.show', compact('customer', 'agents', 'metrics'));
    }

    /**
     * Blacklist a customer
     * POST /customers/{customerId}/blacklist
     */
    public function blacklist(Request $request, string $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $customer->blacklist();

        // Log the action
        activity()
            ->performedOn($customer)
            ->withProperties(['reason' => $request->input('reason')])
            ->log('Customer blacklisted');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer blacklisted successfully'
            ]);
        }

        return redirect()->route('customers.show', $customerId)
            ->with('success', 'Customer has been blacklisted.');
    }

    /**
     * Remove customer from blacklist
     * POST /customers/{customerId}/unblacklist
     */
    public function unblacklist(string $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $customer->update([
            'risk_level' => Customer::RISK_UNKNOWN,
            'cooldown_until' => null,
        ]);

        activity()
            ->performedOn($customer)
            ->log('Customer removed from blacklist');

        return redirect()->route('customers.show', $customerId)
            ->with('success', 'Customer has been removed from blacklist.');
    }

    /**
     * Create a new lead from customer
     * POST /customers/{customerId}/create-lead
     */
    public function createLead(Request $request, string $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $request->validate([
            'agent_id' => 'required|exists:users,id',
            'product_name' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $agent = User::findOrFail($request->input('agent_id'));

        // Create new lead
        $lead = Lead::create([
            'customer_id' => $customer->id,
            'name' => $customer->name_display,
            'phone' => $customer->phone_primary,
            'address' => $customer->primary_address,
            'city' => $customer->city,
            'state' => $customer->province,
            'barangay' => $customer->barangay,
            'street' => $customer->street,
            'status' => Lead::STATUS_NEW,
            'source' => 'manual',
            'assigned_to' => $agent->id,
            'assigned_at' => now(),
            'notes' => $request->input('notes'),
            'product_name' => $request->input('product_name'),
        ]);

        activity()
            ->performedOn($lead)
            ->causedBy($request->user())
            ->log('Lead created from customer profile');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'lead_id' => $lead->id,
                'message' => 'Lead created successfully'
            ]);
        }

        return redirect()->route('leads.index')
            ->with('success', "Lead created and assigned to {$agent->name}.");
    }

    /**
     * Update customer information
     * PUT /customers/{customerId}
     */
    public function update(Request $request, string $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $request->validate([
            'phone_primary' => 'required|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'primary_address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:255',
        ]);

        $customer->update($request->only([
            'phone_primary',
            'phone_secondary',
            'primary_address',
            'city',
            'province',
            'barangay',
            'street',
        ]));

        activity()
            ->performedOn($customer)
            ->causedBy($request->user())
            ->log('Customer information updated');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully'
            ]);
        }

        return redirect()->route('customers.show', $customerId)
            ->with('success', 'Customer information updated successfully.');
    }

    /**
     * Search customers
     * GET /customers/search
     */
    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json(['customers' => []]);
        }

        $customers = Customer::where('name_display', 'LIKE', "%{$query}%")
            ->orWhere('phone_primary', 'LIKE', "%{$query}%")
            ->orWhere('phone_secondary', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name_display', 'phone_primary', 'customer_score', 'risk_level']);

        return response()->json(['customers' => $customers]);
    }
}
