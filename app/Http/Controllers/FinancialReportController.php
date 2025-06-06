<?php

namespace App\Http\Controllers;

use App\Services\FinancialReportService;
use Maatwebsite\Excel\Facades\Excel; // Importar Excel
use Barryvdh\DomPDF\Facade\Pdf; // Importar PDF
use Illuminate\Http\Request;
use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;


class FinancialReportController extends Controller
{
    protected $reportService;

    public function __construct(FinancialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
{
    $filters = $request->only(['start_date', 'end_date', 'booker_id', 'artist_id']);
    $activeTab = $request->input('tab', 'overview');

    $this->reportService->setFilters($filters);

    $overviewSummary = $this->reportService->getOverviewSummary();
    $overviewTable = collect($this->reportService->getOverviewTableData());
    $expensesTable = collect($this->reportService->getExpensesTableData());
    $profitabilitySummary = $this->reportService->getProfitabilitySummary();
    $profitabilityTable = collect($this->reportService->getProfitabilityTableData());
    $groupedExpensesReport = $this->reportService->getGroupedExpensesData();
    $commissionsReport = $this->reportService->getGroupedCommissionsData();
    $cashflowSummary = $this->reportService->getCashflowSummary();
    $cashflowTable = collect($this->reportService->getCashflowTableData());
    $commissionsSummary = $this->reportService->getCommissionsSummary();
    $commissionsTable = collect($this->reportService->getCommissionsTableData());
    $detailedPerformanceReport = $this->reportService->getDetailedPerformanceData();
    $profitabilityReport = $this->reportService->getProfitabilityAnalysisData();
    $detailedExpenses = $this->reportService->getDetailedExpenses();



    // Depuração temporária
    /*dd([
        'overviewSummary' => $overviewSummary,
        'profitabilitySummary' => $profitabilitySummary,
        'cashflowSummary' => $cashflowSummary,
        'filters' => $filters
    ]);*/

    $bookers = \App\Models\Booker::all();
    $artists = \App\Models\Artist::withoutTrashed()->orderBy('name')->get();
    $costCenters = CostCenter::orderBy('name')->get();

    return view('reports.dashboard', [
        'filters' => $filters,
        'activeTab' => $activeTab,
        'detailedExpenses' => $detailedExpenses,
        'detailedPerformanceReport' => $detailedPerformanceReport,
        'profitabilityReport' => $profitabilityReport,
        'groupedExpensesReport' => $groupedExpensesReport,
        'commissionsReport' => $commissionsReport,
        'overviewSummary' => $overviewSummary,
        'overviewTable' => $overviewTable,
        'expensesTable' => $expensesTable,
        'profitabilitySummary' => $profitabilitySummary,
        'profitabilityTable' => $profitabilityTable,
        'cashflowSummary' => $cashflowSummary,
        'cashflowTable' => $cashflowTable,
        'commissionsSummary' => $commissionsSummary,
        'commissionsTable' => $commissionsTable,
        'bookers' => $bookers,
        'artists' => $artists,
        'costCenters' => $costCenters,
    ]);
}

    public function export(Request $request)
{
    $type = $request->input('type');
    $format = $request->input('format');
    $filters = $request->only(['start_date', 'end_date', 'booker_id', 'artist_id']);

    // Validar e corrigir intervalo de datas
    $startDate = $filters['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
    $endDate = $filters['end_date'] ?? now()->endOfMonth()->format('Y-m-d');
    if ($startDate && $endDate && $startDate > $endDate) {
        $temp = $startDate;
        $startDate = $endDate;
        $endDate = $temp;
    }
    $filters['start_date'] = $startDate;
    $filters['end_date'] = $endDate;

    $this->reportService->setFilters($filters);

    switch ($type) {
        case 'financial_report':
            $data = $this->reportService->getFinancialReportData();
            break;
        case 'overview':
            $data = collect([
                'summary' => $this->reportService->getOverviewSummary(),
                'table' => $this->reportService->getOverviewTableData(),
            ]);
            break;
        case 'expenses':
            $data = $this->reportService->getExpensesTableData();
            break;
        default:
            return redirect()->back()->with('error', 'Tipo de relatório inválido');
    }

    if ($format === 'xlsx') {
        return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromCollection {
            protected $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function collection()
            {
                if (isset($this->data['events_by_artist'])) {
                    $rows = collect();
                    foreach ($this->data['events_by_artist'] as $artist => $events) {
                        foreach ($events as $event) {
                            $rows->push([
                                'Artista' => $artist,
                                'Data' => $event['date'],
                                'Local' => $event['location'],
                                'Cachê Bruto' => $event['contract_value'],
                                'Comissão Agência' => $event['agency_commission'],
                                'Comissão Booker' => $event['booker_commission'],
                                'Cachê Líquido' => $event['net_cache'],
                            ]);
                        }
                    }
                    return $rows;
                }
                if (isset($this->data['table'])) {
                    return $this->data['table']->map(function ($row) {
                        return collect($row);
                    });
                }
                return $this->data->map(function ($group) {
                    return collect($group)->except('expenses')->merge(['details' => $group['expenses']]);
                });
            }
        }, ($type === 'financial_report' ? 'relatorio_financeiro' : $type) . '_' . now()->format('Ymd_His') . '.xlsx');
    }

    if ($format === 'pdf') {
        $view = 'reports.exports.' . $type;
        $pdf = Pdf::loadView($view, ['data' => $data, 'filters' => $filters]);
        return $pdf->download(($type === 'financial_report' ? 'relatorio_financeiro' : $type) . '_' . now()->format('Ymd_His') . '.pdf');
    }

    return redirect()->back()->with('error', 'Formato inválido');
}
}