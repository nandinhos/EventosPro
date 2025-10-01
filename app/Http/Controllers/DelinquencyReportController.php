<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DelinquencyReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['include_paid' => 'nullable|boolean']);
        $includePaidGigs = $request->boolean('include_paid');

        $gigQuery = Gig::query()
            ->whereNull('deleted_at')
            ->where(function ($query) use ($includePaidGigs) {
                $query->whereHas('payments', fn ($q) => $q->whereNull('confirmed_at'))
                    ->when($includePaidGigs, function ($q) {
                        $q->orWhere(fn ($sub) => $sub->doesntHave('payments', 'and', fn ($p) => $p->whereNull('confirmed_at')));
                    });
            });

        // Aplicar filtros
        if ($request->filled('event_start_date')) {
            $gigQuery->where('gig_date', '>=', $request->input('event_start_date'));
        }
        if ($request->filled('event_end_date')) {
            $gigQuery->where('gig_date', '<=', $request->input('event_end_date'));
        }
        if ($request->filled('artist_id')) {
            $gigQuery->where('artist_id', $request->input('artist_id'));
        }
        if ($request->filled('booker_id')) {
            if ($request->input('booker_id') === 'sem_booker') {
                $gigQuery->whereNull('booker_id');
            } else {
                $gigQuery->where('booker_id', $request->input('booker_id'));
            }
        }
        if ($request->filled('currency') && $request->input('currency') !== 'all') {
            $gigQuery->where('currency', $request->input('currency'));
        }
        if ($request->filled('due_start_date') || $request->filled('due_end_date')) {
            $gigQuery->whereHas('payments', function ($q) use ($request) {
                if ($request->filled('due_start_date')) {
                    $q->where('due_date', '>=', $request->input('due_start_date'));
                }
                if ($request->filled('due_end_date')) {
                    $q->where('due_date', '<=', $request->input('due_end_date'));
                }
            });
        }

        $relevantGigs = $gigQuery
            ->with(['artist', 'booker', 'payments' => fn ($q) => $q->orderBy('due_date', 'asc')])
            ->get()
            ->sortBy('booker.name');

        $gigsGroupedByBooker = $relevantGigs->groupBy(fn ($gig) => $gig->booker->name ?? 'Agência Direta');

        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->pluck('name', 'id');
        $currencies = Gig::select('currency')->distinct()->orderBy('currency')->pluck('currency');

        // ***** INÍCIO DA CORREÇÃO NO CÁLCULO *****
        $totalContractValueConsolidatedBRL = 0;
        $totalReceivedValueBRL = 0;
        $totalPendingByOtherCurrency = [];

        // ***** Variável correta usada aqui: $relevantGigs *****
        foreach ($relevantGigs as $gig) {
            $cacheBrlDetails = $gig->cacheValueBrlDetails;

            if (strtoupper($gig->currency) === 'BRL') {
                $totalContractValueConsolidatedBRL += $gig->cache_value;
            } elseif ($cacheBrlDetails['type'] === 'confirmed' && $cacheBrlDetails['value'] !== null) {
                $totalContractValueConsolidatedBRL += $cacheBrlDetails['value'];
            }

            $gigTotalReceivedBRL = $gig->payments
                ->whereNotNull('confirmed_at')
                ->sum(function ($p) {
                    if (strtoupper($p->currency) === 'BRL') {
                        return $p->received_value_actual;
                    }
                    $rate = $p->exchange_rate_received_actual ?: ($p->exchange_rate ?: 1);

                    return $p->received_value_actual * $rate;
                });
            $totalReceivedValueBRL += $gigTotalReceivedBRL;

            if (strtoupper($gig->currency) !== 'BRL') {
                $pendingOriginalForGig = $gig->cache_value - $gig->payments->whereNotNull('confirmed_at')->where('currency', $gig->currency)->sum('received_value_actual');
                if ($pendingOriginalForGig > 0.009) {
                    $totalPendingByOtherCurrency[$gig->currency] = ($totalPendingByOtherCurrency[$gig->currency] ?? 0) + $pendingOriginalForGig;
                }
            }
        }
        $totalPendingValueConsolidatedBRL = $totalContractValueConsolidatedBRL - $totalReceivedValueBRL;
        // ***** FIM DA CORREÇÃO NO CÁLCULO *****

        return view('reports.delinquency', compact(
            'gigsGroupedByBooker',
            'artists',
            'bookers',
            'currencies',
            'totalContractValueConsolidatedBRL',
            'totalReceivedValueBRL',
            'totalPendingValueConsolidatedBRL',
            'totalPendingByOtherCurrency'
        ));
    }

    public function exportPdf(Request $request)
    {
        $request->validate(['include_paid' => 'nullable|boolean']);
        $includePaidGigs = $request->boolean('include_paid');

        $gigQuery = Gig::query()
            ->whereNull('deleted_at')
            ->where(function ($query) use ($includePaidGigs) {
                $query->whereHas('payments', fn ($q) => $q->whereNull('confirmed_at'))
                    ->when($includePaidGigs, function ($q) {
                        $q->orWhere(fn ($sub) => $sub->doesntHave('payments', 'and', fn ($p) => $p->whereNull('confirmed_at')));
                    });
            });

        if ($request->filled('event_start_date')) {
            $gigQuery->where('gig_date', '>=', $request->input('event_start_date'));
        }
        if ($request->filled('event_end_date')) {
            $gigQuery->where('gig_date', '<=', $request->input('event_end_date'));
        }
        if ($request->filled('artist_id')) {
            $gigQuery->where('artist_id', $request->input('artist_id'));
        }
        if ($request->filled('booker_id')) {
            if ($request->input('booker_id') === 'sem_booker') {
                $gigQuery->whereNull('booker_id');
            } else {
                $gigQuery->where('booker_id', $request->input('booker_id'));
            }
        }

        $relevantGigs = $gigQuery
            ->with(['artist', 'booker', 'payments' => fn ($q) => $q->orderBy('due_date', 'asc')])
            ->get()
            ->sortBy('booker.name');

        $gigsGroupedByBooker = $relevantGigs->groupBy(fn ($gig) => $gig->booker->name ?? 'Agência Direta');

        $filters = $request->only(['event_start_date', 'event_end_date']);

        $pdf = Pdf::loadView('reports.exports.delinquency_pdf', [
            'gigsGroupedByBooker' => $gigsGroupedByBooker,
            'filters' => $filters,
        ]);

        $fileName = 'relatorio_pendencias_'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Exibe o relatório de vencimentos (pagamentos a vencer e vencidos)
     */
    public function dueDates(Request $request)
    {
        // Validação dos parâmetros de data
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:paid,pending,overdue',
            'currency' => 'nullable|string|size:3',
        ]);

        // Instanciar o serviço de cálculo financeiro
        $financialService = app(\App\Services\GigFinancialCalculatorService::class);

        // Consulta para obter pagamentos com data de vencimento e carregar relacionamentos necessários
        $payments = Payment::query()
            ->with([
                'gig.artist',
                'gig.booker',
                'gig.costs',
                'gig.payments',
            ])
            ->whereNotNull('due_date')
            ->when($request->filled('start_date'), function ($query) use ($request) {
                return $query->where('due_date', '>=', $request->input('start_date'));
            })
            ->when($request->filled('end_date'), function ($query) use ($request) {
                return $query->where('due_date', '<=', $request->input('end_date'));
            })
            ->when($request->filled('currency'), function ($query) use ($request) {
                return $query->where('currency', $request->input('currency'));
            })
            ->orderBy('due_date', 'asc')
            ->get();

        // Processar cada pagamento para adicionar os valores calculados
        $payments->each(function ($payment) {
            if ($payment->gig) {
                // Calcular o valor em BRL usando o serviço financeiro
                $gig = $payment->gig;

                // Se o pagamento já estiver pago, usar o valor confirmado se disponível
                if ($payment->is_paid && $payment->confirmed_value) {
                    $payment->calculated_value = $payment->confirmed_value;
                    $payment->calculated_brl = $payment->currency === 'BRL'
                        ? $payment->confirmed_value
                        : $payment->confirmed_value * ($payment->exchange_rate ?: 1);
                } else {
                    // Para pagamentos não confirmados, calcular com base no valor original
                    $payment->calculated_value = $payment->value;

                    // Se não for BRL, converter para BRL usando a taxa de câmbio
                    if ($payment->currency !== 'BRL' && $payment->exchange_rate) {
                        $payment->calculated_brl = $payment->value * $payment->exchange_rate;
                    } else {
                        $payment->calculated_brl = $payment->value;
                    }
                }

                // Adicionar informações adicionais para exibição
                // Garantir que os valores sejam numéricos antes de formatar
                $payment->formatted_value = number_format(floatval($payment->calculated_value ?? 0), 2, ',', '.');
                $payment->formatted_brl = number_format(floatval($payment->calculated_brl ?? 0), 2, ',', '.');
            }
        });

        // Classificar pagamentos por status
        $groupedPayments = [
            'overdue' => $payments->filter(fn ($payment) => $payment->due_date < now() && ! $payment->is_paid),
            'pending' => $payments->filter(fn ($payment) => $payment->due_date >= now() &&
                $payment->due_date <= now()->addDays(30) &&
                ! $payment->is_paid
            ),
            'paid' => $payments->filter(fn ($payment) => $payment->is_paid),
        ];

        // Aplicar filtro de status se especificado
        if ($request->filled('status')) {
            $groupedPayments = [$request->input('status') => $groupedPayments[$request->input('status')] ?? collect()];
        }

        // Calcular totais usando os valores calculados
        $totals = [];
        foreach ($groupedPayments as $status => $payments) {
            $totals[$status] = [
                'count' => $payments->count(),
                'amount' => $payments->sum('calculated_value'),
                'amount_brl' => $payments->sum('calculated_brl'),
            ];
        }

        // Obter moedas disponíveis para filtro
        $currencies = Payment::select('currency')
            ->distinct()
            ->whereNotNull('currency')
            ->orderBy('currency')
            ->pluck('currency');

        return view('reports.due_dates.index', [
            'groupedPayments' => $groupedPayments,
            'totals' => $totals,
            'filters' => $request->only(['start_date', 'end_date', 'status', 'currency']),
            'currencies' => $currencies,
        ]);
    }

    /**
     * Exporta o relatório de vencimentos para PDF
     */
    public function exportDueDates(Request $request)
    {
        // Validação dos parâmetros
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:paid,pending,overdue',
            'currency' => 'nullable|string|size:3',
        ]);

        // Instanciar o serviço de cálculo financeiro
        $financialService = app(\App\Services\GigFinancialCalculatorService::class);

        // Consulta para obter pagamentos com data de vencimento e carregar relacionamentos necessários
        $payments = Payment::query()
            ->with([
                'gig.artist',
                'gig.booker',
                'gig.costs',
                'gig.payments',
            ])
            ->whereNotNull('due_date')
            ->when($request->filled('start_date'), function ($query) use ($request) {
                return $query->where('due_date', '>=', $request->input('start_date'));
            })
            ->when($request->filled('end_date'), function ($query) use ($request) {
                return $query->where('due_date', '<=', $request->input('end_date'));
            })
            ->when($request->filled('currency'), function ($query) use ($request) {
                return $query->where('currency', $request->input('currency'));
            })
            ->orderBy('due_date', 'asc')
            ->get();

        // Processar cada pagamento para adicionar os valores calculados
        $payments->each(function ($payment) {
            if ($payment->gig) {
                // Calcular o valor em BRL usando o serviço financeiro
                $gig = $payment->gig;

                // Se o pagamento já estiver pago, usar o valor confirmado se disponível
                if ($payment->is_paid && $payment->confirmed_value) {
                    $payment->calculated_value = $payment->confirmed_value;
                    $payment->calculated_brl = $payment->currency === 'BRL'
                        ? $payment->confirmed_value
                        : $payment->confirmed_value * ($payment->exchange_rate ?: 1);
                } else {
                    // Para pagamentos não confirmados, calcular com base no valor original
                    $payment->calculated_value = $payment->value;

                    // Se não for BRL, converter para BRL usando a taxa de câmbio
                    if ($payment->currency !== 'BRL' && $payment->exchange_rate) {
                        $payment->calculated_brl = $payment->value * $payment->exchange_rate;
                    } else {
                        $payment->calculated_brl = $payment->value;
                    }
                }

                // Adicionar informações adicionais para exibição
                // Garantir que os valores sejam numéricos antes de formatar
                $payment->formatted_value = number_format(floatval($payment->calculated_value ?? 0), 2, ',', '.');
                $payment->formatted_brl = number_format(floatval($payment->calculated_brl ?? 0), 2, ',', '.');
            }
        });

        // Classificar pagamentos por status
        $groupedPayments = [
            'overdue' => $payments->filter(fn ($payment) => $payment->due_date < now() && ! $payment->is_paid),
            'pending' => $payments->filter(fn ($payment) => $payment->due_date >= now() &&
                $payment->due_date <= now()->addDays(30) &&
                ! $payment->is_paid
            ),
            'paid' => $payments->filter(fn ($payment) => $payment->is_paid),
        ];

        // Aplicar filtro de status se especificado
        if ($request->filled('status')) {
            $groupedPayments = [$request->input('status') => $groupedPayments[$request->input('status')] ?? collect()];
        }

        // Calcular totais usando os valores calculados
        $totals = [];
        foreach ($groupedPayments as $status => $payments) {
            $totals[$status] = [
                'count' => $payments->count(),
                'amount' => $payments->sum('calculated_value'),
                'amount_brl' => $payments->sum('calculated_brl'),
            ];
        }

        // Gerar PDF
        $pdf = Pdf::loadView('reports.exports.due_dates_pdf', [
            'groupedPayments' => $groupedPayments,
            'totals' => $totals,
            'filters' => $request->only(['start_date', 'end_date', 'status', 'currency']),
            'generated_at' => now()->format('d/m/Y H:i'),
        ]);

        $fileName = 'relatorio_vencimentos_'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($fileName);
    }
}
