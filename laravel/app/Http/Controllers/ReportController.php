<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReportController extends Controller
{
    public function __construct(
        private ReportingService $reportingService
    ) {}

    /**
     * Customer Lifetime Value Report
     */
    public function customerLifetimeValue(Request $request)
    {
        $limit = $request->input('limit', 50);
        $format = $request->input('format', 'json');

        $data = $this->reportingService->getCustomerLifetimeValue($limit);

        if ($format === 'excel') {
            return $this->exportCustomerLTV($data);
        }

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.customer-lifetime-value', compact('data'));
    }

    /**
     * Recycling Funnel Analysis
     */
    public function recyclingFunnel(Request $request)
    {
        $startDate = $request->input('start_date') ? new \DateTime($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? new \DateTime($request->input('end_date')) : null;
        $format = $request->input('format', 'json');

        $data = $this->reportingService->getRecyclingFunnel($startDate, $endDate);

        if ($format === 'excel') {
            return $this->exportRecyclingFunnel($data);
        }

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.recycling-funnel', compact('data'));
    }

    /**
     * Agent Performance on Recycled Leads
     */
    public function agentPerformance(Request $request)
    {
        $startDate = $request->input('start_date') ? new \DateTime($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? new \DateTime($request->input('end_date')) : null;
        $format = $request->input('format', 'json');

        $data = $this->reportingService->getAgentRecyclingPerformance($startDate, $endDate);

        if ($format === 'excel') {
            return $this->exportAgentPerformance($data);
        }

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.agent-performance', compact('data'));
    }

    /**
     * Customer Cohorts Analysis
     */
    public function customerCohorts(Request $request)
    {
        $months = $request->input('months', 6);
        $format = $request->input('format', 'json');

        $data = $this->reportingService->getCustomerCohorts($months);

        if ($format === 'excel') {
            return $this->exportCustomerCohorts($data);
        }

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.customer-cohorts', compact('data'));
    }

    /**
     * Risk Trends Over Time
     */
    public function riskTrends(Request $request)
    {
        $months = $request->input('months', 3);
        $format = $request->input('format', 'json');

        $data = $this->reportingService->getRiskTrends($months);

        if ($format === 'excel') {
            return $this->exportRiskTrends($data);
        }

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.risk-trends', compact('data'));
    }

    /**
     * Order Status Distribution
     */
    public function orderStatus(Request $request)
    {
        $data = $this->reportingService->getOrderStatusDistribution();

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.order-status', compact('data'));
    }

    /**
     * Priority Distribution in Recycling Pool
     */
    public function priorityDistribution(Request $request)
    {
        $data = $this->reportingService->getPriorityDistribution();

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.priority-distribution', compact('data'));
    }

    /**
     * Comprehensive Dashboard Statistics
     */
    public function dashboard(Request $request)
    {
        $data = $this->reportingService->getDashboardStats();

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.dashboard', compact('data'));
    }

    /**
     * Export Customer LTV to Excel
     */
    private function exportCustomerLTV(array $data)
    {
        $export = new class($data) implements FromCollection, WithHeadings, ShouldAutoSize {
            public function __construct(private array $data) {}

            public function collection()
            {
                return collect($this->data['top_customers'])->map(function ($customer) {
                    return [
                        'ID' => $customer['id'],
                        'Name' => $customer['name_display'],
                        'Phone' => $customer['phone_primary'],
                        'Total Orders' => $customer['total_orders'],
                        'Delivered Orders' => $customer['total_delivered'],
                        'Total Value' => number_format($customer['total_delivered_value'], 2),
                        'Success Rate' => $customer['delivery_success_rate'] . '%',
                        'Customer Score' => $customer['customer_score'],
                        'Risk Level' => $customer['risk_level'],
                    ];
                });
            }

            public function headings(): array
            {
                return ['ID', 'Name', 'Phone', 'Total Orders', 'Delivered', 'Total Value', 'Success Rate', 'Score', 'Risk'];
            }
        };

        return Excel::download($export, 'customer-lifetime-value-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export Recycling Funnel to Excel
     */
    private function exportRecyclingFunnel(array $data)
    {
        $export = new class($data) implements FromCollection, WithHeadings, ShouldAutoSize {
            public function __construct(private array $data) {}

            public function collection()
            {
                $rows = collect();

                // Funnel stages
                $rows->push(['FUNNEL STAGES', '']);
                foreach ($this->data['funnel'] as $stage => $count) {
                    $rows->push([ucfirst(str_replace('_', ' ', $stage)), $count]);
                }

                // Metrics
                $rows->push(['', '']);
                $rows->push(['METRICS', '']);
                foreach ($this->data['metrics'] as $metric => $value) {
                    $rows->push([ucfirst(str_replace('_', ' ', $metric)), $value . '%']);
                }

                // By reason
                $rows->push(['', '']);
                $rows->push(['BY REASON', 'Total', 'Converted', 'Exhausted', 'Conversion Rate']);
                foreach ($this->data['by_reason'] as $reasonData) {
                    $rows->push([
                        $reasonData['reason'],
                        $reasonData['total'],
                        $reasonData['converted'],
                        $reasonData['exhausted'],
                        $reasonData['conversion_rate'] . '%',
                    ]);
                }

                return $rows;
            }

            public function headings(): array
            {
                return ['Metric', 'Value'];
            }
        };

        return Excel::download($export, 'recycling-funnel-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export Agent Performance to Excel
     */
    private function exportAgentPerformance(array $data)
    {
        $export = new class($data) implements FromCollection, WithHeadings, ShouldAutoSize {
            public function __construct(private array $data) {}

            public function collection()
            {
                return collect($this->data['agents'])->map(function ($agent) {
                    return [
                        'Agent ID' => $agent['agent_id'],
                        'Agent Name' => $agent['agent_name'],
                        'Total Assigned' => $agent['total_assigned'],
                        'Converted' => $agent['converted'],
                        'Exhausted' => $agent['exhausted'],
                        'Expired' => $agent['expired'],
                        'Conversion Rate' => $agent['conversion_rate'] . '%',
                        'Avg Hours to Process' => $agent['avg_hours_to_process'],
                    ];
                });
            }

            public function headings(): array
            {
                return ['Agent ID', 'Name', 'Assigned', 'Converted', 'Exhausted', 'Expired', 'Conversion %', 'Avg Hours'];
            }
        };

        return Excel::download($export, 'agent-performance-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export Customer Cohorts to Excel
     */
    private function exportCustomerCohorts(array $data)
    {
        $export = new class($data) implements FromCollection, WithHeadings, ShouldAutoSize {
            public function __construct(private array $data) {}

            public function collection()
            {
                return collect($this->data['cohorts'])->map(function ($cohort) {
                    return [
                        'Month' => $cohort['month'],
                        'Customers' => $cohort['customers'],
                        'Total Orders' => $cohort['total_orders'],
                        'Delivered' => $cohort['total_delivered'],
                        'Revenue' => number_format($cohort['revenue'], 2),
                        'Avg Success Rate' => $cohort['avg_success_rate'] . '%',
                        'Avg Score' => $cohort['avg_score'],
                        'Revenue per Customer' => number_format($cohort['avg_revenue_per_customer'], 2),
                    ];
                });
            }

            public function headings(): array
            {
                return ['Month', 'Customers', 'Orders', 'Delivered', 'Revenue', 'Success %', 'Avg Score', 'Rev/Customer'];
            }
        };

        return Excel::download($export, 'customer-cohorts-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export Risk Trends to Excel
     */
    private function exportRiskTrends(array $data)
    {
        $export = new class($data) implements FromCollection, WithHeadings, ShouldAutoSize {
            public function __construct(private array $data) {}

            public function collection()
            {
                return collect($this->data['trends'])->map(function ($trend) {
                    return [
                        'Month' => $trend['month'],
                        'Total' => $trend['total'],
                        'Unknown' => $trend['UNKNOWN'],
                        'Low Risk' => $trend['LOW'],
                        'Medium Risk' => $trend['MEDIUM'],
                        'High Risk' => $trend['HIGH'],
                        'Blacklisted' => $trend['BLACKLIST'],
                    ];
                });
            }

            public function headings(): array
            {
                return ['Month', 'Total', 'Unknown', 'Low', 'Medium', 'High', 'Blacklist'];
            }
        };

        return Excel::download($export, 'risk-trends-' . date('Y-m-d') . '.xlsx');
    }
}
