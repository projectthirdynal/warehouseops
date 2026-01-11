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

        // OPTIMIZED: Calculate all stats in a single query instead of 7 separate queries
        $stats = \Illuminate\Support\Facades\DB::table('waybills')
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'in transit' THEN 1 END) as in_transit,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered,
                COUNT(CASE WHEN status = 'delivering' THEN 1 END) as delivering,
                COUNT(CASE WHEN status IN ('returned', 'for return') THEN 1 END) as returned,
                COUNT(CASE WHEN status = 'headquarters scheduling to outlets' THEN 1 END) as hq_scheduling,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending
            ")
            ->first();

        $stats = [
            'total' => $stats->total ?? 0,
            'dispatched' => 0,
            'in_transit' => $stats->in_transit ?? 0,
            'delivered' => $stats->delivered ?? 0,
            'delivering' => $stats->delivering ?? 0,
            'returned' => $stats->returned ?? 0,
            'hq_scheduling' => $stats->hq_scheduling ?? 0,
            'pending' => $stats->pending ?? 0,
        ];

        // Cache product options (rarely changes)
        $productOptions = \Illuminate\Support\Facades\Cache::remember('waybill_product_options', 300, function () {
            return Waybill::whereNotNull('item_name')->distinct()->orderBy('item_name')->pluck('item_name');
        });

        // OPTIMIZED: Batch load phone order counts instead of N+1 queries
        $receiverPhones = $waybills->pluck('receiver_phone')->unique()->filter()->values()->toArray();

        $phoneOrderCounts = [];
        if (!empty($receiverPhones)) {
            $phoneOrderCounts = \Illuminate\Support\Facades\DB::table('waybills')
                ->select('receiver_phone', \Illuminate\Support\Facades\DB::raw('COUNT(*) as order_count'))
                ->whereIn('receiver_phone', $receiverPhones)
                ->groupBy('receiver_phone')
                ->pluck('order_count', 'receiver_phone')
                ->toArray();
        }

        // OPTIMIZED: Batch load customers instead of N+1 queries
        $customers = [];
        if (!empty($receiverPhones)) {
            $customers = \App\Models\Customer::where(function ($q) use ($receiverPhones) {
                $q->whereIn('phone_primary', $receiverPhones)
                    ->orWhereIn('phone_secondary', $receiverPhones);
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

        // Attach pre-loaded data to waybills (no additional queries)
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
