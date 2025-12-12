<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Waybill;
use App\Models\ScannedWaybill;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Date Filter for Stats
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Statistics
        $deliveredCount = Waybill::where('status', 'delivered')
            ->whereBetween('signing_time', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->count();

        $returnedCount = Waybill::where('status', 'returned')
            ->whereBetween('signing_time', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->count();

        $forReturnCount = Waybill::where('status', 'for return')
            ->whereBetween('signing_time', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->count();

        $totalTerminated = $deliveredCount + $returnedCount + $forReturnCount;

        $deliveryRate = $totalTerminated > 0 ? ($deliveredCount / $totalTerminated) * 100 : 0;
        $returnRate = $totalTerminated > 0 ? (($returnedCount + $forReturnCount) / $totalTerminated) * 100 : 0;

        $stats = [
            'total_waybills' => Waybill::count(),
            'pending_waybills' => Waybill::where('status', 'pending')->count(),
            'dispatched_waybills' => Waybill::where('status', 'dispatched')->count(),
            'today_scans' => ScannedWaybill::whereDate('scan_date', Carbon::today())->count(),
            'delivered_period' => $deliveredCount,
            'returned_period' => $returnedCount + $forReturnCount,
            'delivery_rate' => $deliveryRate,
            'return_rate' => $returnRate,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        // Recent Scans
        $recentScans = ScannedWaybill::leftJoin('waybills', 'scanned_waybills.waybill_number', '=', 'waybills.waybill_number')
            ->select('scanned_waybills.*', 'waybills.sender_name', 'waybills.receiver_name', 'waybills.destination')
            ->orderBy('scan_date', 'desc')
            ->limit(10)
            ->get();

        // Waybills List with Filter
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

        $waybills = $query->orderBy('created_at', 'desc')
                          ->paginate($request->input('limit', 50))
                          ->withQueryString();

        return view('dashboard', compact('stats', 'recentScans', 'waybills'));
    }
}
