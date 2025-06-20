<?php

namespace App\Http\Controllers;

use App\Services\FinancialReportService;
use Maatwebsite\Excel\Facades\Excel; // Importar Excel
use Barryvdh\DomPDF\Facade\Pdf; // Importar PDF
use Illuminate\Http\Request;
use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Services\GigFinancialCalculatorService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use App\Models\Gig;
use App\Models\Settlement;


class FinancialReportController extends Controller
{
    protected $reportService;
    protected $gigCalculatorService; // Adicionar propriedade

    // Modificar construtor para injetar GigFinancialCalculatorService
    public function __construct(FinancialReportService $reportService, GigFinancialCalculatorService $gigCalculatorService)
    {
        $this->reportService = $reportService;
        $this->gigCalculatorService = $gigCalculatorService; // Atribuir
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

/**
     * Processa o pagamento em massa de comissões de bookers.
     */
    public function settleBatchBookerCommissions(Request $request)
    {
        $validated = $request->validate([
            'gig_ids' => 'required|array',
            'gig_ids.*' => 'integer|exists:gigs,id',
            'payment_date' => 'required|date|before_or_equal:today',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'booker_id' => 'nullable',
            'artist_id' => 'nullable|integer',
        ]);

        $gigIds = $validated['gig_ids'];
        $paymentDate = Carbon::parse($validated['payment_date']);
        $settledCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($gigIds as $gigId) {
                $gig = Gig::find($gigId);

                if (!$gig || !$gig->booker_id || $gig->booker_payment_status === 'pago') {
                    $errors[] = "Comissão da Gig #{$gigId} não pôde ser paga (não encontrada, sem booker ou já paga).";
                    continue;
                }

                $bookerCommissionValue = $this->gigCalculatorService->calculateBookerCommissionBrl($gig);

                // Cria ou atualiza o registro de Settlement
                $settlement = Settlement::firstOrNew(['gig_id' => $gig->id]);
                $settlement->settlement_date = $settlement->settlement_date ?? $paymentDate;
                $settlement->booker_commission_value_paid = $bookerCommissionValue;
                $settlement->booker_commission_paid_at = $paymentDate;
                $settlement->notes = trim(($settlement->notes ?? '') . "\n[Booker Batch " . now()->format('d/m/y H:i') . "]: Pago via lote.");
                $settlement->save();

                // Atualiza o status da Gig
                $gig->booker_payment_status = 'pago';
                $gig->save();
                $settledCount++;
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao processar pagamento em massa de comissões: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Ocorreu um erro inesperado ao processar os pagamentos.');
        }

        // ***** REDIRECT CORRIGIDO - FORA DO CATCH E APÓS O COMMIT *****
        $redirectParams = $request->only(['start_date', 'end_date', 'booker_id', 'artist_id']);
        $redirectParams['tab'] = 'commissions';

        $message = "{$settledCount} comissões foram marcadas como pagas.";
        if (!empty($errors)) {
            $message .= " Avisos: " . implode(', ', $errors);
            return Redirect::route('reports.index', $redirectParams)->with('warning', $message);
        }
        return Redirect::route('reports.index', $redirectParams)->with('success', $message);
    }

    /**
     * Reverte o pagamento em massa de comissões de bookers.
     */
    public function unsettleBatchBookerCommissions(Request $request)
    {
        $validated = $request->validate([
            'gig_ids' => 'required|array',
            'gig_ids.*' => 'integer|exists:gigs,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'booker_id' => 'nullable',
            'artist_id' => 'nullable|integer',
        ]);

        $gigIds = $validated['gig_ids'];
        $unsettledCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($gigIds as $gigId) {
                $gig = Gig::with('settlement')->find($gigId); // Eager load settlement

                if (!$gig || $gig->booker_payment_status !== 'pago') {
                    $errors[] = "Comissão da Gig #{$gigId} não pôde ser revertida (não encontrada ou não estava paga).";
                    continue;
                }

                if ($gig->settlement) {
                    $gig->settlement->update([
                        'booker_commission_value_paid' => null,
                        'booker_commission_paid_at' => null,
                    ]);
                }

                $gig->update(['booker_payment_status' => 'pendente']);
                $unsettledCount++;
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao reverter pagamento em massa de comissões: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Ocorreu um erro inesperado ao reverter os pagamentos.');
        }
        
        // ***** REDIRECT CORRIGIDO - FORA DO CATCH E APÓS O COMMIT *****
        $redirectParams = $request->only(['start_date', 'end_date', 'booker_id', 'artist_id']);
        $redirectParams['tab'] = 'commissions';

        $message = "{$unsettledCount} comissões foram revertidas para 'Pendente'.";
        if (!empty($errors)) {
            $message .= " Avisos: " . implode(', ', $errors);
            return Redirect::route('reports.index', $redirectParams)->with('warning', $message);
        }
        return Redirect::route('reports.index', $redirectParams)->with('success', $message);
    }
}