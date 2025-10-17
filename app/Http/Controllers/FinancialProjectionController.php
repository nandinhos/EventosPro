<?php

namespace App\Http\Controllers;

use App\Services\FinancialProjectionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialProjectionController extends Controller
{
    protected $projectionService;

    public function __construct(FinancialProjectionService $projectionService)
    {
        $this->projectionService = $projectionService;
    }

    public function index(Request $request)
    {
        // Valida os inputs
        $validated = $request->validate([
            'period' => 'nullable|string|in:30_days,60_days,90_days,next_semester,next_year,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Garante que $period nunca seja null
        $period = $request->input('period') ?? '30_days';
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Define o período no serviço
        $this->projectionService->setPeriod($period, $startDate, $endDate);

        return view('projections.dashboard', [
            'accounts_receivable' => $this->projectionService->getAccountsReceivable(),
            'accounts_payable_artists' => $this->projectionService->getAccountsPayableArtists(),
            'accounts_payable_bookers' => $this->projectionService->getAccountsPayableBookers(),
            'accounts_payable_expenses' => $this->projectionService->getAccountsPayableExpenses(),
            'projected_cash_flow' => $this->projectionService->getProjectedCashFlow(),
            'upcoming_client_payments' => $this->projectionService->getUpcomingClientPayments(),
            'upcoming_artist_payments' => $this->projectionService->getUpcomingInternalPayments('artists'),
            'upcoming_booker_payments' => $this->projectionService->getUpcomingInternalPayments('bookers'),
            'projected_expenses_by_cost_center' => $this->projectionService->getProjectedExpensesByCostCenter(),
            'period' => $period,
        ]);
    }

    /**
     * Exibe uma página de depuração com todos os cálculos de projeção.
     */
    public function debug(Request $request): View
    {
        // Valida os inputs
        $validated = $request->validate([
            'period' => 'nullable|string|in:30_days,60_days,90_days,next_semester,next_year,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Pega o período do request ou usa o default
        $period = $request->input('period', '30_days');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $this->projectionService->setPeriod($period, $startDate, $endDate);

        // Armazena todos os resultados dos cálculos em um array
        $debugData = [
            'Contas a Receber (Clientes)' => [
                'value' => $this->projectionService->getAccountsReceivable(),
                'items' => $this->projectionService->getUpcomingPayments('clients'),
            ],
            'Contas a Pagar (Artistas)' => [
                'value' => $this->projectionService->getAccountsPayableArtists(),
                'items' => $this->projectionService->getUpcomingPayments('artists'),
            ],
            'Contas a Pagar (Bookers)' => [
                'value' => $this->projectionService->getAccountsPayableBookers(),
                'items' => $this->projectionService->getUpcomingPayments('bookers'),
            ],
            'Contas a Pagar (Despesas Previstas)' => [
                'value' => $this->projectionService->getAccountsPayableExpenses(),
                'items' => $this->projectionService->getProjectedExpensesByCostCenter(),
            ],
            'Fluxo de Caixa Projetado' => [
                'value' => $this->projectionService->getProjectedCashFlow(),
                'items' => null, // Não há itens detalhados para o total
            ],
        ];

        return view('projections.debug', [
            'debugData' => $debugData,
            'period' => $period,
        ]);
    }
}
