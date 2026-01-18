<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        // Cache key based on date range
        $cacheKey = "dashboard_stats_{$startDate}_{$endDate}";

        // Get status counts with a single optimized query (cached for 60 seconds)
        $statusCounts = Cache::remember('dashboard_status_counts', 60, function () {
            return Waybill::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN LOWER(status) = 'in transit' THEN 1 ELSE 0 END) as in_transit,
                SUM(CASE WHEN LOWER(status) = 'delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN LOWER(status) = 'delivering' THEN 1 ELSE 0 END) as delivering,
                SUM(CASE WHEN LOWER(status) IN ('returned', 'for return') THEN 1 ELSE 0 END) as returned,
                SUM(CASE WHEN LOWER(status) = 'headquarters scheduling to outlets' THEN 1 ELSE 0 END) as hq_scheduling,
                SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) as pending
            ")->first();
        });

        // Period-based stats (shorter cache since date-dependent)
        $periodStats = Cache::remember($cacheKey, 30, function () use ($startDate, $endDate) {
            return Waybill::selectRaw("
                SUM(CASE WHEN LOWER(status) = 'delivered' THEN 1 ELSE 0 END) as delivered_period,
                SUM(CASE WHEN LOWER(status) IN ('returned', 'for return') THEN 1 ELSE 0 END) as returned_period
            ")
            ->whereBetween('signing_time', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ])
            ->first();
        });

        $stats = [
            'total_waybills' => $statusCounts->total ?? 0,
            'dispatched' => 0, // Status not used in current workflow
            'in_transit' => $statusCounts->in_transit ?? 0,
            'delivered' => $statusCounts->delivered ?? 0,
            'delivering' => $statusCounts->delivering ?? 0,
            'returned' => $statusCounts->returned ?? 0,
            'hq_scheduling' => $statusCounts->hq_scheduling ?? 0,
            'pending' => $statusCounts->pending ?? 0,
            'delivered_period' => $periodStats->delivered_period ?? 0,
            'returned_period' => $periodStats->returned_period ?? 0,
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
