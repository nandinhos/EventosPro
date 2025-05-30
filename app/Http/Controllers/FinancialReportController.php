<?php

namespace App\Http\Controllers;

use App\Services\FinancialReportService;
use Maatwebsite\Excel\Facades\Excel; // Importar Excel
use Barryvdh\DomPDF\Facade\Pdf; // Importar PDF
use Illuminate\Http\Request;
use App\Models\Artist;
use App\Models\Booker;


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
        $this->reportService->setFilters($filters);

        $overviewSummary = $this->reportService->getOverviewSummary();
        $overviewTable = $this->reportService->getOverviewTableData();
        $profitabilityTable = $this->reportService->getProfitabilityTableData();
        $cashflowTable = $this->reportService->getCashflowTableData();
        $commissionsTable = $this->reportService->getCommissionsTableData();
        $expensesTable = $this->reportService->getExpensesTableData();

        $artists = Artist::pluck('name', 'id')->prepend('Todos', '');
        $bookers = Booker::pluck('name', 'id')->prepend('Todos', '');

        return view('reports.dashboard', compact(
            'overviewSummary',
            'overviewTable',
            'profitabilityTable',
            'cashflowTable',
            'commissionsTable',
            'expensesTable',
            'filters',
            'artists',
            'bookers'
        ));
    }

    public function export(Request $request)
{
    $type = $request->input('type');
    $format = $request->input('format');
    $filters = $request->only(['start_date', 'end_date', 'booker_id', 'artist_id']);

    // Validar e corrigir intervalo de datas
    $startDate = $filters['start_date'] ?? null;
    $endDate = $filters['end_date'] ?? null;
    if ($startDate && $endDate && $startDate > $endDate) {
        $temp = $startDate;
        $startDate = $endDate;
        $endDate = $temp;
    }
    $filters['start_date'] = $startDate;
    $filters['end_date'] = $endDate;

    $this->reportService->setFilters($filters);

    switch ($type) {
        case 'overview':
            $data = collect([
                'summary' => $this->reportService->getOverviewSummary(),
                'table' => $this->reportService->getOverviewTableData(),
            ]);
            break;
        case 'expenses':
            $data = $this->reportService->getExpensesTableData();
            break;
        // Adicione outros tipos (profitability, cashflow, commissions) conforme necessário
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
                if (isset($this->data['table'])) {
                    return $this->data['table']->map(function ($row) {
                        return collect($row);
                    });
                }
                return $this->data->map(function ($group) {
                    return collect($group)->except('expenses')->merge(['details' => $group['expenses']]);
                });
            }
        }, ($type === 'overview' ? 'visao_geral' : $type) . '_' . now()->format('Ymd_His') . '.xlsx');
    }

    if ($format === 'pdf') {
        $view = 'reports.exports.' . $type;
        $pdf = Pdf::loadView($view, ['data' => $data, 'filters' => $filters]);
        return $pdf->download(($type === 'overview' ? 'visao_geral' : $type) . '_' . now()->format('Ymd_His') . '.pdf');
    }

    return redirect()->back()->with('error', 'Formato inválido');
}
}