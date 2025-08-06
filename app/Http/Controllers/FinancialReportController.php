<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Models\Gig;
use App\Models\Settlement;
use App\Services\FinancialReportService;
use App\Services\GigFinancialCalculatorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OverviewReportExport;
use App\Models\Payment;
use Illuminate\Support\Str;

class FinancialReportController extends Controller
{
    protected $reportService;
    protected $gigCalculatorService;

    public function __construct(FinancialReportService $reportService, GigFinancialCalculatorService $gigCalculatorService)
    {
        $this->reportService = $reportService;
        $this->gigCalculatorService = $gigCalculatorService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date', 'booker_id', 'artist_id', 'contract_status']);
        $activeTab = $request->input('tab', 'overview');

        $this->reportService->setFilters($filters);

        $overviewData = $this->reportService->getOverviewData();

        $commissionsReport = $this->reportService->getGroupedCommissionsData();
        $detailedPerformanceReport = $this->reportService->getDetailedPerformanceData();
        $profitabilityReport = $this->reportService->getProfitabilityAnalysisData();

        // Adicione aqui a chamada para outros métodos de relatório que você usa na view
        $salesProfitabilityData = $this->reportService->getSalesProfitabilityData();

        $cashflowSummary = $this->reportService->getCashflowSummary();
        $cashflowTable = collect($this->reportService->getCashflowTableData());
        $groupedExpensesReport = $this->reportService->getGroupedExpensesData();

        $bookers = \App\Models\Booker::orderBy('name')->get();
        $artists = \App\Models\Artist::withoutTrashed()->orderBy('name')->get();

        return view('reports.dashboard', [
            'filters' => $filters,
            'activeTab' => $activeTab,
            'salesProfitabilityData' => $salesProfitabilityData,
            'detailedPerformanceReport' => $detailedPerformanceReport,
            'profitabilityReport' => $profitabilityReport,
            'groupedExpensesReport' => $groupedExpensesReport,
            'commissionsReport' => $commissionsReport,
            'cashflowSummary' => $cashflowSummary,
            'cashflowTable' => $cashflowTable,
            'bookers' => $bookers,
            'artists' => $artists,
            'overviewData' => $overviewData,
        ]);
    }

    public function export(Request $request)
    {
        $type = $request->input('type', 'overview');
        $format = $request->input('format');
        $filters = $request->only(['start_date', 'end_date', 'booker_id', 'artist_id']);

        $this->reportService->setFilters($filters);
        
        $fileName = "relatorio_{$type}_" . now()->format('Ymd_His');

        if ($type === 'overview') {
            $reportData = $this->reportService->getDetailedPerformanceData();
            if ($format === 'xlsx') {
                return Excel::download(new \App\Exports\DetailedPerformanceReportExport($reportData['tableData']), "{$fileName}.xlsx");
            }
            if ($format === 'pdf') {
                $pdf = Pdf::loadView('reports.exports.detailed_performance', ['reportData' => $reportData, 'filters' => $filters])
                          ->setPaper('a4', 'landscape');
                return $pdf->download("{$fileName}.pdf");
            }
        }
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

                $settlement = Settlement::firstOrNew(['gig_id' => $gig->id]);
                $settlement->settlement_date = $settlement->settlement_date ?? $paymentDate;
                $settlement->booker_commission_value_paid = $bookerCommissionValue;
                $settlement->booker_commission_paid_at = $paymentDate;
                $settlement->notes = trim(($settlement->notes ?? '') . "\n[Booker Batch " . now()->format('d/m/y H:i') . "]: Pago via lote.");
                $settlement->save();

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
                $gig = Gig::with('settlement')->find($gigId);

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
        
        $redirectParams = $request->only(['start_date', 'end_date', 'booker_id', 'artist_id']);
        $redirectParams['tab'] = 'commissions';

        $message = "{$unsettledCount} comissões foram revertidas para 'Pendente'.";
        if (!empty($errors)) {
            $message .= " Avisos: " . implode(', ', $errors);
            return Redirect::route('reports.index', $redirectParams)->with('warning', $message);
        }
        return Redirect::route('reports.index', $redirectParams)->with('success', $message);
    }

    /**
     * Lida com a exportação da Visão Geral para PDF ou Excel.
     */
    public function exportOverview(Request $request, $format)
    {
        $this->reportService->setFilters($request->all());
        $overviewData = $this->reportService->getOverviewData();
        $filters = $request->only(['start_date', 'end_date', 'booker_id', 'artist_id']);
        
        $fileName = 'relatorio_visao_geral_' . now()->format('Y-m-d');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.exports.overview_pdf', [
                'overviewData' => $overviewData,
                'filters' => $filters
            ])
            // ***** ALTERAÇÃO AQUI *****
            ->setPaper('a4', 'landscape'); // Define a orientação para paisagem

            return $pdf->download("{$fileName}.pdf");
        }
        
        if ($format === 'xlsx') {
            return Excel::download(new \App\Exports\OverviewReportExport($overviewData), "{$fileName}.xlsx");
        }

        return redirect()->back()->with('error', 'Formato de exportação inválido.');
    }

    /**
     * Exibe o relatório de vencimentos com foco em parcelas PENDENTES.
     */
    public function dueDatesReport(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:a_vencer,vencido', // Apenas estes status são filtráveis
            'currency' => 'nullable|string',
        ]);

        // 1. Query base JÁ FILTRADA para parcelas NÃO CONFIRMADAS
        $query = Payment::query()
            ->whereNull('confirmed_at') // Foco apenas no que está em aberto
            ->with(['gig.artist', 'gig.booker']);

        // 2. Aplicar filtros de data e moeda
        if ($startDate = $filters['start_date'] ?? null) $query->where('due_date', '>=', $startDate);
        if ($endDate = $filters['end_date'] ?? null) $query->where('due_date', '<=', $endDate);
        if ($currency = $filters['currency'] ?? null) $query->where('currency', $currency);
        
        $pendingPayments = $query->orderBy('due_date')->get();

        // 3. Calcular totais APENAS para vencidos e a vencer
        $totals = [
            'vencido' => ['count' => 0, 'amount_brl' => 0],
            'a_vencer' => ['count' => 0, 'amount_brl' => 0],
        ];

        foreach ($pendingPayments as $payment) {
            $status = $payment->inferred_status;
            if (isset($totals[$status])) {
                $totals[$status]['count']++;
                $totals[$status]['amount_brl'] += $payment->due_value_brl;
            }
        }
        
        // 4. Filtrar para a tabela
        $statusFilter = $filters['status'] ?? null;
        $paymentsForTable = $statusFilter ? $pendingPayments->filter(fn($p) => $p->inferred_status === $statusFilter) : $pendingPayments;

        // 5. Retorna a view com os dados focados
        return view('reports.due_dates.index', [
            'payments' => $paymentsForTable,
            'totals' => $totals,
            'currencies' => Payment::select('currency')->distinct()->orderBy('currency')->pluck('currency'),
        ]);
    }

    /**
     * Exporta o relatório de vencimentos para PDF.
     */
    /**
     * Exporta o relatório de vencimentos para PDF.
     */
    public function exportDueDatesPdf(Request $request)
    {
        // 1. Aumenta os limites de execução ANTES de qualquer processamento pesado
        try {
            ini_set('memory_limit', '512M'); // Aumenta para 512MB
            set_time_limit(300);             // Aumenta para 5 minutos
        } catch (\Exception $e) {
            Log::warning('Não foi possível aumentar os limites de execução para PDF: ' . $e->getMessage());
        }

        $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:a_vencer,vencido',
            'currency' => 'nullable|string',
        ]);

        // 2. Query otimizada com 'select' explícito para reduzir uso de memória
        $query = Payment::query()
            ->with([
                // Carrega apenas as colunas que a view do PDF REALMENTE precisa
                'gig:id,artist_id,booker_id,location_event_details,gig_date',
                'gig.artist:id,name',
                'gig.booker:id,name'
            ])
            ->whereNull('confirmed_at');

        // Aplicar filtros
        if ($startDate = $filters['start_date'] ?? null) $query->where('due_date', '>=', $startDate);
        if ($endDate = $filters['end_date'] ?? null) $query->where('due_date', '<=', $endDate);
        if ($currency = $filters['currency'] ?? null) $query->where('currency', $currency);

        $pendingPayments = $query->orderBy('due_date')->get();

        // Calcular totais
        $totals = [
            'vencido' => ['count' => 0, 'amount_brl' => 0],
            'a_vencer' => ['count' => 0, 'amount_brl' => 0],
        ];
        foreach ($pendingPayments as $payment) {
            $status = $payment->inferred_status;
            if (isset($totals[$status])) {
                $totals[$status]['count']++;
                $totals[$status]['amount_brl'] += $payment->due_value_brl;
            }
        }

        $statusFilter = $filters['status'] ?? null;
        $paymentsForReport = $statusFilter ? $pendingPayments->filter(fn($p) => $p->inferred_status === $statusFilter) : $pendingPayments;

        $groupedPayments = $paymentsForReport->groupBy('inferred_status');

        // 3. Gera o PDF
        $pdf = Pdf::loadView('reports.exports.due_dates_pdf', [
            'groupedPayments' => $groupedPayments,
            'totals' => $totals,
            'filters' => array_filter($filters), // Remove filtros vazios
            'generated_at' => now()->format('d/m/Y H:i'),
        ]);

        $fileName = 'relatorio_vencimentos_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($fileName);
    }
}