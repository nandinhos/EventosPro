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

        $commissionsReport = $this->reportService->getGroupedCommissionsData();
        $detailedPerformanceReport = $this->reportService->getDetailedPerformanceData();
        $profitabilityReport = $this->reportService->getProfitabilityAnalysisData();
        // Adicione aqui a chamada para outros métodos de relatório que você usa na view
        $cashflowSummary = $this->reportService->getCashflowSummary();
        $cashflowTable = collect($this->reportService->getCashflowTableData());
        $groupedExpensesReport = $this->reportService->getGroupedExpensesData();

        $bookers = \App\Models\Booker::orderBy('name')->get();
        $artists = \App\Models\Artist::withoutTrashed()->orderBy('name')->get();

        return view('reports.dashboard', [
            'filters' => $filters,
            'activeTab' => $activeTab,
            'detailedPerformanceReport' => $detailedPerformanceReport,
            'profitabilityReport' => $profitabilityReport,
            'groupedExpensesReport' => $groupedExpensesReport,
            'commissionsReport' => $commissionsReport,
            'cashflowSummary' => $cashflowSummary,
            'cashflowTable' => $cashflowTable,
            'bookers' => $bookers,
            'artists' => $artists,
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
}