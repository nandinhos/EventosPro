<?php
namespace App\Http\Controllers;

use App\Services\FinancialProjectionService;
use Illuminate\Http\Request;

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
}