<?php

namespace App\Http\Controllers;

use App\Exports\DetailedPerformanceReportExport;
use App\Exports\OverviewReportExport;
use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Payment;
use App\Models\Settlement;
use App\Services\CommissionPaymentValidationService;
use App\Services\FinancialReportService;
use App\Services\GigFinancialCalculatorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

        $overviewData = $this->reportService->getOverviewData();

        $commissionsReport = $this->reportService->getGroupedCommissionsData();
        $detailedPerformanceReport = $this->reportService->getDetailedPerformanceData();
        $profitabilityReport = $this->reportService->getProfitabilityAnalysisData();

        // Adicione aqui a chamada para outros métodos de relatório que você usa na view
        $salesProfitabilityData = $this->reportService->getSalesProfitabilityData();

        $cashflowSummary = $this->reportService->getCashflowSummary();
        $cashflowTable = collect($this->reportService->getCashflowTableData());
        $groupedExpensesReport = $this->reportService->getGroupedExpensesData();

        $bookers = Booker::orderBy('name')->get();
        $artists = Artist::withoutTrashed()->orderBy('name')->get();

        // Dados para os relatórios
        $monthlyClosingReport = [
            'total_gigs' => 0,
            'total_cache_brl' => 0,
            'total_booker_commission' => 0,
            'total_agency_commission' => 0,
            'booker_data' => collect([]),
            'artist_data' => collect([]),
            'gigs' => collect([]),
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ];

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
            'monthlyClosingReport' => $monthlyClosingReport,
        ]);
    }

    public function export(Request $request)
    {
        $type = $request->input('type', 'overview');
        $format = $request->input('format');
        $filters = $request->only(['start_date', 'end_date', 'booker_id', 'artist_id']);

        $this->reportService->setFilters($filters);

        $fileName = "relatorio_{$type}_".now()->format('Ymd_His');

        if ($type === 'overview') {
            $reportData = $this->reportService->getDetailedPerformanceData();
            if ($format === 'xlsx') {
                return Excel::download(new DetailedPerformanceReportExport($reportData['tableData']), "{$fileName}.xlsx");
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

        // Validar regras de negócio antes de processar
        $validationService = app(CommissionPaymentValidationService::class);
        $gigs = Gig::whereIn('id', $gigIds)->get();
        $batchValidation = $validationService->validateBatchPayment($gigs, false);

        if ($batchValidation['invalid_gigs']->isNotEmpty()) {
            return back()->with('error', 'Alguns eventos não podem ser pagos: '.implode('; ', $batchValidation['errors']));
        }

        DB::beginTransaction();
        try {
            foreach ($gigIds as $gigId) {
                $gig = Gig::find($gigId);

                if (! $gig || ! $gig->booker_id || $gig->booker_payment_status === 'pago') {
                    $errors[] = "Comissão da Gig #{$gigId} não pôde ser paga (não encontrada, sem booker ou já paga).";

                    continue;
                }

                $bookerCommissionValue = $this->gigCalculatorService->calculateBookerCommissionBrl($gig);

                $settlement = Settlement::firstOrNew(['gig_id' => $gig->id]);
                $settlement->settlement_date = $settlement->settlement_date ?? $paymentDate;
                $settlement->booker_commission_value_paid = $bookerCommissionValue;
                $settlement->booker_commission_paid_at = $paymentDate;
                $settlement->notes = trim(($settlement->notes ?? '')."\n[Booker Batch ".now()->format('d/m/y H:i').']: Pago via lote.');
                $settlement->save();

                $gig->booker_payment_status = 'pago';
                $gig->save();
                $settledCount++;
            }

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao processar pagamento em massa de comissões: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Ocorreu um erro inesperado ao processar os pagamentos.');
        }

        $redirectParams = $request->only(['start_date', 'end_date', 'booker_id', 'artist_id']);
        $redirectParams['tab'] = 'commissions';

        $message = "{$settledCount} comissões foram marcadas como pagas.";
        if (! empty($errors)) {
            $message .= ' Avisos: '.implode(', ', $errors);

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

                if (! $gig || $gig->booker_payment_status !== 'pago') {
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

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao reverter pagamento em massa de comissões: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Ocorreu um erro inesperado ao reverter os pagamentos.');
        }

        $redirectParams = $request->only(['start_date', 'end_date', 'booker_id', 'artist_id']);
        $redirectParams['tab'] = 'commissions';

        $message = "{$unsettledCount} comissões foram revertidas para 'Pendente'.";
        if (! empty($errors)) {
            $message .= ' Avisos: '.implode(', ', $errors);

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

        $fileName = 'relatorio_visao_geral_'.now()->format('Y-m-d');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.exports.overview_pdf', [
                'overviewData' => $overviewData,
                'filters' => $filters,
            ])
            // ***** ALTERAÇÃO AQUI *****
                ->setPaper('a4', 'landscape'); // Define a orientação para paisagem

            return $pdf->download("{$fileName}.pdf");
        }

        if ($format === 'xlsx') {
            return Excel::download(new OverviewReportExport($overviewData), "{$fileName}.xlsx");
        }

        return redirect()->back()->with('error', 'Formato de exportação inválido.');
    }

    /**
     * Exibe o relatório de vencimentos com foco em parcelas PENDENTES.
     */
    public function dueDatesReport(Request $request)
    {
        // Log dos parâmetros recebidos
        Log::info('Parâmetros da requisição:', $request->all());

        $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'contract_status' => 'nullable|in:assinado,cancelado,concluido,expirado,n/a,para_assinatura',
            'status' => 'nullable|in:a_vencer,vencido',
            'currency' => 'nullable|string',
        ]);

        // 1. Query base para parcelas NÃO CONFIRMADAS e que NÃO estão em gigs excluídas
        $query = Payment::query()
            ->whereNull('confirmed_at') // Foco apenas no que está em aberto
            ->whereHas('gig', function ($q) {
                $q->whereNull('deleted_at'); // Exclui gigs com soft delete
            })
            ->with(['gig.artist', 'gig.booker']);

        // 2. Aplicar filtros de data e moeda
        if ($startDate = $filters['start_date'] ?? null) {
            $query->where('due_date', '>=', $startDate);
            Log::info('Filtrando por data inicial: '.$startDate);
        }

        if ($endDate = $filters['end_date'] ?? null) {
            $query->where('due_date', '<=', $endDate);
            Log::info('Filtrando por data final: '.$endDate);
        }

        if ($currency = $filters['currency'] ?? null) {
            $query->where('currency', $currency);
            Log::info('Filtrando por moeda: '.$currency);
        }

        // 3. Aplicar filtro de status do contrato se fornecido
        if ($contractStatus = $filters['contract_status'] ?? null) {
            $query->whereHas('gig', function ($q) use ($contractStatus) {
                $q->where('contract_status', $contractStatus);
            });
            Log::info('Filtrando por status do contrato: '.$contractStatus);
        }

        // 4. Obter pagamentos pendentes
        $pendingPayments = $query->orderBy('due_date')->get();
        Log::info('Total de pagamentos encontrados: '.$pendingPayments->count());

        // 5. Calcular totais APENAS para vencidos e a vencer
        $totals = [
            'vencido' => ['count' => 0, 'amount_brl' => 0],
            'a_vencer' => ['count' => 0, 'amount_brl' => 0],
        ];

        foreach ($pendingPayments as $payment) {
            $status = $payment->inferred_status;
            if (isset($totals[$status])) {
                $totals[$status]['count']++;
                $totals[$status]['amount_brl'] += $payment->due_value_brl ?? 0;
            }
        }

        // 6. Filtrar para a tabela (status de vencimento)
        $statusFilter = $filters['status'] ?? null;
        $paymentsForTable = $pendingPayments;

        if ($statusFilter) {
            $paymentsForTable = $pendingPayments->filter(function ($payment) use ($statusFilter) {
                return $payment->inferred_status === $statusFilter;
            });
            Log::info('Filtrando por status de vencimento: '.$statusFilter.'. Total: '.$paymentsForTable->count());
        }

        // 7. Aplicar agrupamento personalizado por prioridades
        $groupedPayments = $this->applyCustomGrouping($paymentsForTable);

        // Para manter compatibilidade, criar uma lista linear dos pagamentos ordenados por prioridade
        $prioritizedPayments = collect();
        foreach ($groupedPayments as $groupKey => $groupPayments) {
            if ($groupKey === 'evento_futuro_multiplas_vencidas') {
                // Para sub-agrupamentos, extrair todos os pagamentos
                foreach ($groupPayments as $subGroup) {
                    $prioritizedPayments = $prioritizedPayments->merge($subGroup['payments']);
                }
            } else {
                // Para grupos simples, adicionar diretamente
                $prioritizedPayments = $prioritizedPayments->merge($groupPayments);
            }
        }

        // Paginar os resultados priorizados
        $currentPage = request()->get('page', 1);
        $perPage = 50;
        $payments = new LengthAwarePaginator(
            $prioritizedPayments->forPage($currentPage, $perPage),
            $prioritizedPayments->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'pageName' => 'page']
        );
        $payments->withQueryString();

        // 8. Retorna a view com os dados focados
        return view('reports.due_dates.index', [
            'payments' => $payments,
            'groupedPayments' => $groupedPayments,
            'totals' => $totals,
            'currencies' => Payment::select('currency')->distinct()->orderBy('currency')->pluck('currency'),
        ]);
    }

    /**
     * Aplica agrupamento personalizado por prioridades nos pagamentos.
     * Prioridades:
     * 1. Evento realizado com vencimento pendente
     * 2. Evento futuro com mais de 1 parcela vencida (sub-agrupado por Gig)
     * 3. Evento futuro com parcela vencida
     * 4. Evento futuro com parcela a vencer
     */
    private function applyCustomGrouping($payments)
    {
        $grouped = [
            'evento_realizado_vencimento_pendente' => collect(),
            'evento_futuro_multiplas_vencidas' => [],
            'evento_futuro_parcela_vencida' => collect(),
            'evento_futuro_parcela_a_vencer' => collect(),
        ];

        $today = now()->startOfDay();

        // Agrupar pagamentos por gig para análise
        $paymentsByGig = $payments->groupBy('gig_id');

        foreach ($paymentsByGig as $gigId => $gigPayments) {
            $gig = $gigPayments->first()->gig;
            $gigDate = Carbon::parse($gig->gig_date)->startOfDay();
            $isEventRealized = $gigDate->lt($today);

            // Contar parcelas vencidas para esta gig
            $parcelasVencidas = $gigPayments->filter(function ($payment) use ($today) {
                return Carbon::parse($payment->due_date)->startOfDay()->lt($today);
            });

            $parcelasAVencer = $gigPayments->filter(function ($payment) use ($today) {
                return Carbon::parse($payment->due_date)->startOfDay()->gte($today);
            });

            // Verificar se é evento futuro com múltiplas parcelas vencidas
            if (! $isEventRealized && $parcelasVencidas->count() > 1) {
                // Criar sub-agrupamento por Gig com subtotal
                $subtotal = $gigPayments->sum('due_value_brl');
                $grouped['evento_futuro_multiplas_vencidas'][] = [
                    'gig' => $gig,
                    'payments' => $gigPayments->sortBy('due_date'),
                    'subtotal' => $subtotal,
                    'parcelas_vencidas_count' => $parcelasVencidas->count(),
                    'parcelas_a_vencer_count' => $parcelasAVencer->count(),
                ];
            } else {
                // Processar pagamentos individualmente para outros grupos
                foreach ($gigPayments as $payment) {
                    $dueDate = Carbon::parse($payment->due_date)->startOfDay();
                    $isVencido = $dueDate->lt($today);

                    if ($isEventRealized && $isVencido) {
                        // Prioridade 1: Evento realizado com vencimento pendente
                        $grouped['evento_realizado_vencimento_pendente']->push($payment);
                    } elseif (! $isEventRealized && $isVencido) {
                        // Prioridade 3: Evento futuro com parcela vencida
                        $grouped['evento_futuro_parcela_vencida']->push($payment);
                    } elseif (! $isEventRealized && ! $isVencido) {
                        // Prioridade 4: Evento futuro com parcela a vencer
                        $grouped['evento_futuro_parcela_a_vencer']->push($payment);
                    }
                }
            }
        }

        // Ordenar grupos simples por data de vencimento e depois por valor
        foreach ($grouped as $key => $group) {
            if ($key !== 'evento_futuro_multiplas_vencidas' && is_object($group)) {
                $grouped[$key] = $group->sortBy([
                    ['due_date', 'asc'],
                    ['due_value_brl', 'desc'],
                ]);
            }
        }

        // Ordenar sub-agrupamentos de múltiplas parcelas por subtotal (maior primeiro)
        if (! empty($grouped['evento_futuro_multiplas_vencidas'])) {
            usort($grouped['evento_futuro_multiplas_vencidas'], function ($a, $b) {
                return $b['subtotal'] <=> $a['subtotal'];
            });
        }

        return $grouped;
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
        } catch (Exception $e) {
            Log::warning('Não foi possível aumentar os limites de execução para PDF: '.$e->getMessage());
        }

        // Log dos parâmetros recebidos
        Log::info('Parâmetros da requisição (PDF):', $request->all());

        $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'contract_status' => 'nullable|in:assinado,cancelado,concluido,expirado,n/a,para_assinatura',
            'status' => 'nullable|in:a_vencer,vencido',
            'currency' => 'nullable|string',
        ]);

        // 2. Query otimizada com 'select' explícito para reduzir uso de memória
        $query = Payment::query()
            ->with([
                // Carrega apenas as colunas que a view do PDF REALMENTE precisa
                'gig:id,artist_id,booker_id,location_event_details,gig_date,contract_status',
                'gig.artist:id,name',
                'gig.booker:id,name',
            ])
            ->whereNull('confirmed_at')
            ->whereHas('gig', function ($q) {
                $q->whereNull('deleted_at'); // Exclui gigs com soft delete
            });

        // Aplicar filtros
        if ($startDate = $filters['start_date'] ?? null) {
            $query->where('due_date', '>=', $startDate);
            Log::info('PDF - Filtrando por data inicial: '.$startDate);
        }

        if ($endDate = $filters['end_date'] ?? null) {
            $query->where('due_date', '<=', $endDate);
            Log::info('PDF - Filtrando por data final: '.$endDate);
        }

        if ($currency = $filters['currency'] ?? null) {
            $query->where('currency', $currency);
            Log::info('PDF - Filtrando por moeda: '.$currency);
        }

        // Aplicar filtro de status do contrato se fornecido
        if ($contractStatus = $filters['contract_status'] ?? null) {
            $query->whereHas('gig', function ($q) use ($contractStatus) {
                $q->where('contract_status', $contractStatus);
            });
            Log::info('PDF - Filtrando por status do contrato: '.$contractStatus);
        }

        $pendingPayments = $query->orderBy('due_date')->get();
        Log::info('PDF - Total de pagamentos encontrados: '.$pendingPayments->count());

        // Calcular totais
        $totals = [
            'vencido' => ['count' => 0, 'amount_brl' => 0],
            'a_vencer' => ['count' => 0, 'amount_brl' => 0],
        ];

        foreach ($pendingPayments as $payment) {
            $status = $payment->inferred_status;
            if (isset($totals[$status])) {
                $totals[$status]['count']++;
                $totals[$status]['amount_brl'] += $payment->due_value_brl ?? 0;
            }
        }

        // Aplicar filtro de status de vencimento
        $statusFilter = $filters['status'] ?? null;
        $paymentsForReport = $pendingPayments;

        if ($statusFilter) {
            $paymentsForReport = $pendingPayments->filter(function ($payment) use ($statusFilter) {
                return $payment->inferred_status === $statusFilter;
            });
            Log::info('PDF - Filtrando por status de vencimento: '.$statusFilter.'. Total: '.$paymentsForReport->count());
        }

        // Aplicar agrupamento personalizado por prioridades para o PDF
        $customGroupedPayments = $this->applyCustomGrouping($paymentsForReport);

        // Manter agrupamento original para compatibilidade
        $groupedPayments = $paymentsForReport->groupBy('inferred_status');

        // 3. Gera o PDF
        $pdf = Pdf::loadView('reports.exports.due_dates_pdf', [
            'groupedPayments' => $groupedPayments,
            'customGroupedPayments' => $customGroupedPayments,
            'totals' => $totals,
            'filters' => array_filter($filters), // Remove filtros vazios
            'generated_at' => now()->format('d/m/Y H:i'),
        ]);

        $fileName = 'relatorio_vencimentos_'.now()->format('Ymd_His').'.pdf';

        return $pdf->download($fileName);
    }
}
