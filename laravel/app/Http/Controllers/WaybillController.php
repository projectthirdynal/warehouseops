<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Waybill;

class WaybillController extends Controller
{
    public function index(Request $request)
    {
        $query = Waybill::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('waybill_number', 'ilike', "%$search%")
                    ->orWhere('sender_name', 'ilike', "%$search%")
                    ->orWhere('receiver_name', 'ilike', "%$search%")
                    ->orWhere('destination', 'ilike', "%$search%")
                    ->orWhere('sender_phone', 'ilike', "%$search%")
                    ->orWhere('receiver_phone', 'ilike', "%$search%")
                    ->orWhere('item_name', 'ilike', "%$search%");
            });
        }

        if ($request->filled('item_name')) {
            $query->where('item_name', $request->item_name);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('signing_time', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('signing_time', '<=', $request->date_to);
        }

        $waybills = $query->orderBy('signing_time', 'desc')
            ->paginate($request->input('limit', 25))
            ->withQueryString();

        // Calculate stats for the 6-card grid (single optimized query)
        $statusCounts = Waybill::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN LOWER(status) = 'in transit' THEN 1 ELSE 0 END) as in_transit,
            SUM(CASE WHEN LOWER(status) = 'delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN LOWER(status) = 'delivering' THEN 1 ELSE 0 END) as delivering,
            SUM(CASE WHEN LOWER(status) IN ('returned', 'for return') THEN 1 ELSE 0 END) as returned,
            SUM(CASE WHEN LOWER(status) = 'headquarters scheduling to outlets' THEN 1 ELSE 0 END) as hq_scheduling,
            SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) as pending
        ")->first();

        $stats = [
            'total' => $statusCounts->total ?? 0,
            'dispatched' => 0, // Status not used in current workflow
            'in_transit' => $statusCounts->in_transit ?? 0,
            'delivered' => $statusCounts->delivered ?? 0,
            'delivering' => $statusCounts->delivering ?? 0,
            'returned' => $statusCounts->returned ?? 0,
            'hq_scheduling' => $statusCounts->hq_scheduling ?? 0,
            'pending' => $statusCounts->pending ?? 0,
        ];

        // Cache product options (rarely changes)
        $productOptions = \Illuminate\Support\Facades\Cache::remember('waybill_product_options', 300, function () {
            return Waybill::whereNotNull('item_name')->distinct()->orderBy('item_name')->pluck('item_name');
        });

        // Batch query: Get all unique phone numbers from current page
        $phones = $waybills->pluck('receiver_phone')->filter()->unique()->values();

        // Batch count orders per phone (single query)
        $phoneOrderCounts = [];
        if ($phones->isNotEmpty()) {
            $phoneOrderCounts = Waybill::selectRaw('receiver_phone, COUNT(*) as order_count')
                ->whereIn('receiver_phone', $phones)
                ->groupBy('receiver_phone')
                ->pluck('order_count', 'receiver_phone')
                ->toArray();
        }

        // Batch fetch customers by phone (single query)
        $customers = [];
        if ($phones->isNotEmpty()) {
            $customers = \App\Models\Customer::where(function ($q) use ($phones) {
                $q->whereIn('phone_primary', $phones)
                  ->orWhereIn('phone_secondary', $phones);
            })->get()->keyBy(function ($customer) {
                return $customer->phone_primary;
            });

            // Also index by secondary phone
            foreach ($customers as $customer) {
                if ($customer->phone_secondary) {
                    $customers[$customer->phone_secondary] = $customer;
                }
            }
        }

        // Attach data to waybills (no additional queries)
        foreach ($waybills as $waybill) {
            $phone = $waybill->receiver_phone;
            $waybill->total_customer_orders = $phoneOrderCounts[$phone] ?? 0;
            $waybill->is_repeat_customer = ($phoneOrderCounts[$phone] ?? 0) > 1;
            $waybill->customer = $customers[$phone] ?? null;
        }

        return view('waybills', compact('waybills', 'stats', 'productOptions'));
    }

    /**
     * Display print-ready shipping label for a waybill.
     */
    public function printLabel(Waybill $waybill)
    {
        return view('waybills.print-label', compact('waybill'));
    }
}
