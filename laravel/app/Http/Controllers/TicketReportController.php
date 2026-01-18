<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketWorklog;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TicketReportController extends Controller
{
    public function index()
    {
        $startOfDay = Carbon::now()->startOfDay();
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. KPI Cards
        $stats = [
            'open_tickets' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'resolved_today' => Ticket::where('status', 'resolved')
                                    ->where('updated_at', '>=', $startOfDay)->count(),
            'created_today' => Ticket::where('created_at', '>=', $startOfDay)->count(),
        ];

        // 2. Ticket Volume (Last 7 Days) for Chart
        $volumeData = Ticket::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')->toArray();
            
        // Fill missing dates
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartData['labels'][] = Carbon::parse($date)->format('M d');
            $chartData['values'][] = $volumeData[$date] ?? 0;
        }

        // 3. Worklog Activity Today
        $worklogsToday = TicketWorklog::with(['user', 'ticket'])
            ->where('created_at', '>=', $startOfDay)
            ->latest()
            ->get();

        // 4. Agent Performance (Resolved Tickets this Month & Logged Minutes)
        $agents = User::whereIn('role', ['superadmin', 'admin', 'it_staff'])
            ->withCount(['assignedTickets as resolved_count' => function($q) use ($startOfMonth) {
                $q->where('status', 'resolved')->where('updated_at', '>=', $startOfMonth);
            }])
            ->get()->map(function($agent) use ($startOfMonth) {
                $minutes = TicketWorklog::where('user_id', $agent->id)
                    ->where('created_at', '>=', $startOfMonth)
                    ->sum('time_spent');
                $agent->logged_minutes = $minutes;
                return $agent;
            })->sortByDesc('resolved_count');

        return view('reports.tickets.index', compact('stats', 'chartData', 'worklogsToday', 'agents'));
    }

    public function export()
    {
        $startOfDay = Carbon::now()->startOfDay();
        // Group logs by ticket to summarize
        $worklogs = TicketWorklog::with(['user', 'ticket'])
            ->where('created_at', '>=', $startOfDay)
            ->get()
            ->groupBy('ticket_id');

        $date = Carbon::now()->format('Y-m-d');
        $md = "# Work Summary - " . Carbon::now()->format('M d, Y') . "\n\n";

        foreach ($worklogs as $ticketId => $logs) {
            $ticket = $logs->first()->ticket;
            $totalTime = $logs->sum('time_spent');
            $actions = $logs->pluck('action_type')->unique()->map(fn($a) => ucfirst($a))->implode(', ');
            $descriptions = $logs->pluck('description')->implode('; ');

            $md .= "### [" . $ticket->ref_no . "] " . $ticket->subject . "\n";
            $md .= "- **Status**: " . ucfirst($ticket->status) . "\n";
            $md .= "- **Total Time Today**: " . $totalTime . " mins\n";
            $md .= "- **Actions**: " . $actions . "\n";
            $md .= "- **Summary**: " . $descriptions . "\n\n";
            $md .= "---\n\n";
        }

        if ($worklogs->isEmpty()) {
            $md .= "No work recorded today.\n";
        }

        $md .= "Generated at " . Carbon::now()->format('H:i:s');

        return response()->streamDownload(function () use ($md) {
            echo $md;
        }, 'work_summary_' . $date . '.md', [
            'Content-Type' => 'text/markdown',
        ]);
    }
}
