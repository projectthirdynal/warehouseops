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
            $query->where(function($q) use ($search) {
                $q->where('waybill_number', 'ilike', "%$search%")
                  ->orWhere('sender_name', 'ilike', "%$search%")
                  ->orWhere('receiver_name', 'ilike', "%$search%")
                  ->orWhere('destination', 'ilike', "%$search%")
                  ->orWhere('sender_phone', 'ilike', "%$search%")
                  ->orWhere('receiver_phone', 'ilike', "%$search%");
            });

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

        // Calculate stats for the 6-card grid
        $stats = [
            'total' => Waybill::count(),
            'dispatched' => 0, // Status not used in current workflow
            'in_transit' => Waybill::where('status', 'in transit')->count(),
            'delivered' => Waybill::where('status', 'delivered')->count(),
            'delivering' => Waybill::where('status', 'delivering')->count(),
            'returned' => Waybill::whereIn('status', ['returned', 'for return'])->count(),
            'hq_scheduling' => Waybill::where('status', 'headquarters scheduling to outlets')->count(),
            'pending' => Waybill::where('status', 'pending')->count(),
        ];

        return view('waybills', compact('waybills', 'stats'));
    }
}
