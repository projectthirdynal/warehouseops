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

        // Real-time Status Counts
        $stats = [
            'total_waybills' => Waybill::count(),
            'dispatched' => 0, // Status not used in current workflow
            'in_transit' => Waybill::where('status', 'in transit')->count(),
            'delivered' => Waybill::where('status', 'delivered')->count(),
            'delivering' => Waybill::where('status', 'delivering')->count(),
            'returned' => Waybill::whereIn('status', ['returned', 'for return'])->count(),
            'hq_scheduling' => Waybill::where('status', 'headquarters scheduling to outlets')->count(),
            'pending' => Waybill::where('status', 'pending')->count(),
            
            // Period-based stats for delivery/return rates
            'delivered_period' => Waybill::where('status', 'delivered')
                ->whereBetween('signing_time', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
                ->count(),
            'returned_period' => Waybill::whereIn('status', ['returned', 'for return'])
                ->whereBetween('signing_time', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
                ->count(),
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $totalTerminated = $stats['delivered_period'] + $stats['returned_period'];
        $stats['delivery_rate'] = $totalTerminated > 0 ? ($stats['delivered_period'] / $totalTerminated) * 100 : 0;
        $stats['return_rate'] = $totalTerminated > 0 ? ($stats['returned_period'] / $totalTerminated) * 100 : 0;

        // Recent Scans
        $recentScans = ScannedWaybill::leftJoin('waybills', 'scanned_waybills.waybill_number', '=', 'waybills.waybill_number')
            ->select('scanned_waybills.*', 'waybills.sender_name', 'waybills.receiver_name', 'waybills.destination')
            ->orderBy('scan_date', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard', compact('stats', 'recentScans'));
    }
}
