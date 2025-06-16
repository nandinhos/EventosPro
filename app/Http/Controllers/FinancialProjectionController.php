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
        $period = $request->input('period', '30_days');
        $this->projectionService->setPeriod($period);

        return view('projections.dashboard', [
            'accounts_receivable' => $this->projectionService->getAccountsReceivable(),
            'accounts_payable_artists' => $this->projectionService->getAccountsPayableArtists(),
            'accounts_payable_bookers' => $this->projectionService->getAccountsPayableBookers(),
            'accounts_payable_expenses' => $this->projectionService->getAccountsPayableExpenses(),
            'projected_cash_flow' => $this->projectionService->getProjectedCashFlow(),
            'upcoming_client_payments' => $this->projectionService->getUpcomingPayments('clients'),
            'upcoming_artist_payments' => $this->projectionService->getUpcomingPayments('artists'),
            'upcoming_booker_payments' => $this->projectionService->getUpcomingPayments('bookers'),
            'projected_expenses_by_cost_center' => $this->projectionService->getProjectedExpensesByCostCenter(),
            'period' => $period,
        ]);
    }

    /**
     * Exibe uma página de depuração com todos os cálculos de projeção.
     *
     * @param Request $request
     * @return View
     */
    public function debug(Request $request): View
    {
        // Pega o período do request ou usa o default
        $period = $request->input('period', '30_days');
        $this->projectionService->setPeriod($period);

        // Armazena todos os resultados dos cálculos em um array
        $debugData = [
            'Contas a Receber (Clientes)' => [
                'value' => $this->projectionService->getAccountsReceivable(),
                'items' => $this->projectionService->getUpcomingPayments('clients')
            ],
            'Contas a Pagar (Artistas)' => [
                'value' => $this->projectionService->getAccountsPayableArtists(),
                'items' => $this->projectionService->getUpcomingPayments('artists')
            ],
            'Contas a Pagar (Bookers)' => [
                'value' => $this->projectionService->getAccountsPayableBookers(),
                'items' => $this->projectionService->getUpcomingPayments('bookers')
            ],
            'Contas a Pagar (Despesas Previstas)' => [
                'value' => $this->projectionService->getAccountsPayableExpenses(),
                'items' => $this->projectionService->getProjectedExpensesByCostCenter()
            ],
            'Fluxo de Caixa Projetado' => [
                'value' => $this->projectionService->getProjectedCashFlow(),
                'items' => null // Não há itens detalhados para o total
            ],
        ];

        return view('projections.debug', [
            'debugData' => $debugData,
            'period' => $period,
        ]);
}
}