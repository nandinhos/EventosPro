<?php

use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GigController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\BookerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\GigCostController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\FinancialProjectionController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\DelinquencyReportController;


use App\Models\Gig;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Aqui estão todas as rotas da aplicação web.
| Carregadas pelo RouteServiceProvider dentro do grupo "web".
|
*/

// Rota inicial
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('auth.login'); // ou 'welcome' se preferir
})->name('home');



// Grupo de rotas protegidas por autenticação
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', function (): View {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $activeFutureGigsCount = Gig::where('gig_date', '>=', $today)->count();
        $overdueClientPaymentsCount = Gig::where('payment_status', 'vencido')->count();
        $pendingArtistPaymentsCount = Gig::where('artist_payment_status', 'pendente')->count();
        $pendingBookerPaymentsCount = Gig::where('booker_payment_status', 'pendente')->count();

        $gigsThisMonth = Gig::whereBetween('gig_date', [$startOfMonth, $endOfMonth])->get();
        $totalCacheThisMonth = $gigsThisMonth->sum(fn($gig) => $gig->cache_value_brl);
        $totalAgencyCommissionThisMonth = $gigsThisMonth->sum('agency_commission_value');
        $totalBookerCommissionThisMonth = $gigsThisMonth->sum('booker_commission_value');

        $nextGigs = Gig::with('artist')
            ->where('gig_date', '>=', $today)
            ->orderBy('gig_date', 'asc')
            ->limit(5)
            ->get();

        // Dados para gráfico de faturamento mensal
        $endDateForChart = Carbon::now()->endOfMonth();
        $startDateForChart = Carbon::now()->subMonths(11)->startOfMonth();

        $monthlyRevenueData = Gig::select(
                \DB::raw("YEAR(gig_date) as year"),
                \DB::raw("MONTH(gig_date) as month"),
                \DB::raw("SUM(cache_value) as total_revenue_brl")
            )
            ->where('gig_date', '>=', $startDateForChart)
            ->where('gig_date', '<=', $endDateForChart)
            ->whereIn('contract_status', ['assinado', 'concluido', 'para_assinatura', 'n/a'])
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        $chartLabels = [];
        $chartData = [];

        $currentMonthIterator = $startDateForChart->copy();
        while ($currentMonthIterator->lessThanOrEqualTo($endDateForChart)) {
            $year = $currentMonthIterator->year;
            $month = $currentMonthIterator->month;

            $chartLabels[] = $currentMonthIterator->translatedFormat('M/y');

            $revenueForMonth = $monthlyRevenueData->first(function ($item) use ($year, $month) {
                return $item->year == $year && $item->month == $month;
            });

            $chartData[] = $revenueForMonth ? (float) $revenueForMonth->total_revenue_brl : 0;

            $currentMonthIterator->addMonth();
        }

        return view('dashboard', compact(
            'activeFutureGigsCount',
            'overdueClientPaymentsCount',
            'pendingArtistPaymentsCount',
            'pendingBookerPaymentsCount',
            'totalCacheThisMonth',
            'totalAgencyCommissionThisMonth',
            'totalBookerCommissionThisMonth',
            'nextGigs',
            'chartLabels',
            'chartData'
        ));
    })->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rotas de Relatórios
    Route::get('/reports', [FinancialReportController::class, 'index'])->name('reports.index');
    //Lista de inadimplentes
    Route::get('/reports/delinquency', [DelinquencyReportController::class, 'index'])->name('reports.delinquency');
    //pagamentos em massa
    Route::post('/reports/commissions/settle-batch', [App\Http\Controllers\FinancialReportController::class, 'settleBatchBookerCommissions'])->name('reports.commissions.settleBatch');
    //desfazer pagaementos em massa
    Route::patch('/reports/commissions/unsettle-batch', [App\Http\Controllers\FinancialReportController::class, 'unsettleBatchBookerCommissions'])->name('reports.commissions.unsettleBatch');
    //para exportar em excel/pdf
    Route::get('/reports/export/{type}/{format}', [FinancialReportController::class, 'export'])->name('reports.export');

    // Artists
    Route::resource('artists', ArtistController::class);

    // Bookers
    Route::resource('bookers', BookerController::class);

    // Financial Projections
    Route::get('/projections', [FinancialProjectionController::class, 'index'])->name('projections.index');

    // **NOVA ROTA PARA A DEPURAÇÃO DAS PROJEÇÕES**
    Route::get('/projections/debug', [FinancialProjectionController::class, 'debug'])->name('projections.debug');

    // Gigs
    Route::resource('gigs', GigController::class);
    Route::get('gigs/{gig}/request-nf', [GigController::class, 'showRequestNfForm'])->name('gigs.request-nf');

    // Rotas aninhadas em Gigs
    Route::prefix('gigs/{gig}')->name('gigs.')->group(function () {

        // Payments
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
        Route::put('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
        Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
        Route::patch('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
        Route::patch('payments/{payment}/unconfirm', [PaymentController::class, 'unconfirm'])->name('payments.unconfirm');

        // Costs
        Route::resource('costs', GigCostController::class)->except(['index', 'show']);
        Route::patch('costs/{cost}/confirm', [GigCostController::class, 'confirm'])->name('costs.confirm');
        Route::patch('costs/{cost}/unconfirm', [GigCostController::class, 'unconfirm'])->name('costs.unconfirm');
        Route::patch('costs/{cost}/toggle-invoice', [GigCostController::class, 'toggleInvoice'])->name('costs.toggleInvoice');
        Route::get('costs-json', [GigCostController::class, 'listJson'])->name('costs.listJson');

        // Settlements
        Route::post('settle-artist', [SettlementController::class, 'settleArtistPayment'])->name('settlements.artist');
        Route::post('settle-booker', [SettlementController::class, 'settleBookerCommission'])->name('settlements.booker');
        Route::patch('unsettle-artist', [SettlementController::class, 'unsettleArtistPayment'])->name('settlements.artist.unsettle');
        Route::patch('unsettle-booker', [SettlementController::class, 'unsettleBookerCommission'])->name('settlements.booker.unsettle');
    });

    // ROTA DE DEPURAÇÃO FINANCEIRA PARA UMA GIG ESPECÍFICA
    // Coloque esta rota dentro do grupo de autenticação para que só usuários logados possam acessá-la.
    // Ela deve vir antes ou depois do Route::resource, a ordem aqui não é crítica.
    Route::get('gigs/{gig}/debug-financials', [GigController::class, 'debugFinancials'])->name('gigs.debugFinancials');

});

// Rotas de autenticação (geradas pelo Breeze)
require __DIR__.'/auth.php';