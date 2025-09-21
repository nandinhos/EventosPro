<?php

use App\Http\Controllers\ArtistController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BookerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DelinquencyReportController;
use App\Http\Controllers\FinancialProjectionController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\GigController;
use App\Http\Controllers\GigCostController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PerformanceReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\TestReportController;
use App\Http\Controllers\UserController;
use App\Models\Gig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('auth.login'); // ou 'welcome' se preferir
})->name('home');

// Grupo de rotas protegidas por autenticação
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', UserController::class);

    // Nova Rota para o Portal do Booker
    Route::get('/meu-desempenho', [BookerController::class, 'portal'])->name('booker.portal');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rotas de Relatórios
    Route::get('/reports', [FinancialReportController::class, 'index'])->name('reports.index');
    // Relatório de Visão Geral
    Route::get('/reports/overview/export/{format}', [FinancialReportController::class, 'exportOverview'])->name('reports.overview.export');
    // Lista de inadimplentes
    Route::get('/reports/delinquency', [DelinquencyReportController::class, 'index'])->name('reports.delinquency');
    // Rota para exportação inadimplentes
    Route::get('/reports/delinquency/export/pdf', [DelinquencyReportController::class, 'exportPdf'])->name('reports.delinquency.exportPdf');
    // pagamentos em massa
    Route::post('/reports/commissions/settle-batch', [App\Http\Controllers\FinancialReportController::class, 'settleBatchBookerCommissions'])->name('reports.commissions.settleBatch');
    // desfazer pagaementos em massa
    Route::patch('/reports/commissions/unsettle-batch', [App\Http\Controllers\FinancialReportController::class, 'unsettleBatchBookerCommissions'])->name('reports.commissions.unsettleBatch');
    // para exportar em excel/pdf
    Route::get('/reports/export/{type}/{format}', [FinancialReportController::class, 'export'])->name('reports.export');

    // Artists
    Route::resource('artists', ArtistController::class);

    // Bookers
    Route::resource('bookers', BookerController::class);

    // Financial Projections
    Route::get('/projections', [FinancialProjectionController::class, 'index'])->name('projections.index');

    // **NOVA ROTA PARA A DEPURAÇÃO DAS PROJEÇÕES**
    Route::get('/projections/debug', [FinancialProjectionController::class, 'debug'])->name('projections.debug');

    // Performance Reports
    Route::get('/reports/performance', [PerformanceReportController::class, 'index'])->name('reports.performance.index');
    Route::get('/reports/performance/export', [PerformanceReportController::class, 'exportPdf'])->name('reports.performance.export');

    // Audit routes
    Route::get('/auditoria', [AuditController::class, 'index'])->name('audit.index');
    Route::get('/auditoria/{gig}', [AuditController::class, 'show'])->name('audit.show');
    Route::get('/auditoria/export/csv', [AuditController::class, 'export'])->name('audit.export');

    // Due Dates Reports
    Route::get('/reports/due-dates', [FinancialReportController::class, 'dueDatesReport'])->name('reports.due-dates');
    Route::get('/reports/due-dates/export/pdf', [FinancialReportController::class, 'exportDueDatesPdf'])->name('reports.due-dates.exportPdf');

    // Monthly Closing Reports
    // Rotas de Fechamento Mensal (movidas para o grupo financeiro)
    Route::get('/financeiro/fechamento-mensal', [App\Http\Controllers\MonthlyClosingController::class, 'index'])->name('finance.monthly-closing');
    Route::get('/financeiro/fechamento-mensal/exportar/pdf', [App\Http\Controllers\MonthlyClosingController::class, 'exportPdf'])->name('finance.monthly-closing.exportPdf');
    Route::get('/financeiro/fechamento-mensal/exportar', [App\Http\Controllers\MonthlyClosingController::class, 'export'])->name('finance.monthly-closing.export');

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

    // Test Report Routes
    Route::get('/test-report', [TestReportController::class, 'index'])->name('test-report.index');
    Route::post('/test-report/run', [TestReportController::class, 'runTests'])->name('test-report.run');
    Route::get('/test-report/export', [TestReportController::class, 'export'])->name('test-report.export');

});

// Rotas de autenticação (geradas pelo Breeze)
require __DIR__.'/auth.php';
