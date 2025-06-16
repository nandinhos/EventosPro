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
    $type = $request->input('type', 'overview'); // 'overview' é o nosso detalhado
    $format = $request->input('format');
    $filters = $request->only(['start_date', 'end_date', 'booker_id', 'artist_id']);

    $this->reportService->setFilters($filters);
    
    // Gera um nome de arquivo padronizado
    $fileName = "relatorio_{$type}_" . now()->format('Ymd_His');

    if ($type === 'overview') {
        $reportData = $this->reportService->getDetailedPerformanceData();

        if ($format === 'xlsx') {
            return Excel::download(new DetailedPerformanceReportExport($reportData['tableData']), "{$fileName}.xlsx");
        }
        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.exports.detailed_performance', ['reportData' => $reportData, 'filters' => $filters])
                      ->setPaper('a4', 'landscape'); // Paisagem para caber as colunas
            return $pdf->download("{$fileName}.pdf");
        }
    }
    
    // TODO: Adicionar 'else if' para outros tipos de relatórios ('profitability', 'cashflow', etc.)

    return redirect()->back()->with('error', 'Tipo de relatório ou formato inválido para exportação.');
}
}