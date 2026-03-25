<?php

use App\Http\Controllers\Admin\Configuracoes\BackupController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\ArtistPerformanceController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BookerController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DelinquencyReportController;
use App\Http\Controllers\FinancialProjectionController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\GigController;
use App\Http\Controllers\GigCostController;
use App\Http\Controllers\GigImportController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PerformanceReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettlementController;
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
    // pagamentos em massa - bookers
    Route::post('/reports/commissions/settle-batch', [App\Http\Controllers\FinancialReportController::class, 'settleBatchBookerCommissions'])->name('reports.commissions.settleBatch');
    // desfazer pagaementos em massa - bookers
    Route::patch('/reports/commissions/unsettle-batch', [App\Http\Controllers\FinancialReportController::class, 'unsettleBatchBookerCommissions'])->name('reports.commissions.unsettleBatch');
    // pagamentos em massa - artistas
    Route::post('/reports/artist-payments/settle-batch', [App\Http\Controllers\FinancialReportController::class, 'settleBatchArtistPayments'])->name('reports.artist-payments.settleBatch');
    // desfazer pagamentos em massa - artistas
    Route::patch('/reports/artist-payments/unsettle-batch', [App\Http\Controllers\FinancialReportController::class, 'unsettleBatchArtistPayments'])->name('reports.artist-payments.unsettleBatch');
    // pagamentos em massa - despesas
    Route::post('/reports/expenses/settle-batch', [App\Http\Controllers\FinancialReportController::class, 'settleBatchExpenses'])->name('reports.expenses.settleBatch');
    // desfazer pagamentos em massa - despesas
    Route::patch('/reports/expenses/unsettle-batch', [App\Http\Controllers\FinancialReportController::class, 'unsettleBatchExpenses'])->name('reports.expenses.unsettleBatch');
    // para exportar em excel/pdf
    Route::get('/reports/export/{type}/{format}', [FinancialReportController::class, 'export'])->name('reports.export');

    // Artists
    Route::resource('artists', ArtistController::class);
    // Batch payment routes for artists
    Route::post('/artists/payments/settle-batch', [ArtistController::class, 'settleBatchArtistPayments'])->name('artists.payments.settleBatch');
    Route::patch('/artists/payments/unsettle-batch', [ArtistController::class, 'unsettleBatchArtistPayments'])->name('artists.payments.unsettleBatch');

    // Artist Settlements (Fechamentos)
    Route::get('/artists-settlements', [App\Http\Controllers\ArtistSettlementsController::class, 'index'])->name('artists.settlements.index');
    Route::post('/artists-settlements/settle-batch', [App\Http\Controllers\ArtistSettlementsController::class, 'settleBatch'])->name('artists.settlements.settleBatch');
    Route::patch('/artists-settlements/unsettle-batch', [App\Http\Controllers\ArtistSettlementsController::class, 'unsettleBatch'])->name('artists.settlements.unsettleBatch');
    Route::post('/artists-settlements/send-batch', [App\Http\Controllers\ArtistSettlementsController::class, 'sendBatch'])->name('artists.settlements.sendBatch');
    Route::match(['patch', 'post'], '/artists-settlements/{gig}/send', [App\Http\Controllers\ArtistSettlementsController::class, 'sendSettlement'])->name('artists.settlements.send');
    Route::patch('/artists-settlements/{gig}/receive-document', [App\Http\Controllers\ArtistSettlementsController::class, 'markDocumentationReceived'])->name('artists.settlements.receiveDocument');
    Route::match(['patch', 'post'], '/artists-settlements/{gig}/pay', [App\Http\Controllers\ArtistSettlementsController::class, 'settleArtist'])->name('artists.settlements.pay');
    Route::post('/artists-settlements/{gig}/settle', [App\Http\Controllers\ArtistSettlementsController::class, 'settleArtist'])->name('artists.settlements.settle');
    Route::match(['patch', 'post'], '/artists-settlements/{gig}/revert', [App\Http\Controllers\ArtistSettlementsController::class, 'revertStage'])->name('artists.settlements.revert');
    Route::patch('/artists-settlements/revert-batch', [App\Http\Controllers\ArtistSettlementsController::class, 'revertBatch'])->name('artists.settlements.revertBatch');

    // Expense Reimbursements (Despesas Reembolsáveis)
    Route::get('/expense-reimbursements', [App\Http\Controllers\ExpenseReimbursementController::class, 'index'])->name('expenses.reimbursements.index');
    Route::post('/expense-reimbursements/{cost}/receive-proof', [App\Http\Controllers\ExpenseReimbursementController::class, 'receiveProof'])->name('expenses.reimbursements.receiveProof');
    Route::post('/expense-reimbursements/{cost}/confirm', [App\Http\Controllers\ExpenseReimbursementController::class, 'confirmReimbursement'])->name('expenses.reimbursements.confirm');
    Route::post('/expense-reimbursements/{cost}/reimburse', [App\Http\Controllers\ExpenseReimbursementController::class, 'markReimbursed'])->name('expenses.reimbursements.reimburse');
    Route::patch('/expense-reimbursements/{cost}/revert', [App\Http\Controllers\ExpenseReimbursementController::class, 'revertStage'])->name('expenses.reimbursements.revert');

    // API para atualização de estágio de comprovante (usada por componentes)
    Route::patch('/api/costs/{cost}/reimbursement-stage', [App\Http\Controllers\GigCostController::class, 'updateReimbursementStageApi'])->name('api.costs.reimbursement-stage');
    Route::delete('/api/costs/{cost}/remove-proof-file', [App\Http\Controllers\GigCostController::class, 'removeProofFile'])->name('api.costs.remove-proof-file');

    // Service Takers (Tomadores de Serviço)
    Route::resource('service-takers', App\Http\Controllers\ServiceTakerController::class);
    Route::get('/service-takers-list', [App\Http\Controllers\ServiceTakerController::class, 'list'])->name('service-takers.list');
    Route::get('/service-takers-import', [App\Http\Controllers\ServiceTakerController::class, 'showImportForm'])->name('service-takers.import');
    Route::post('/service-takers-import', [App\Http\Controllers\ServiceTakerController::class, 'importCsv'])->name('service-takers.import.process');
    Route::patch('/service-takers/{serviceTaker}/toggle-international', [App\Http\Controllers\ServiceTakerController::class, 'toggleInternational'])->name('service-takers.toggle-international');

    // Debit Notes (Notas de Débito)
    Route::get('/debit-notes/{gig}', [App\Http\Controllers\DebitNoteController::class, 'show'])->name('debit-notes.show');
    Route::get('/debit-notes/{gig}/preview', [App\Http\Controllers\DebitNoteController::class, 'preview'])->name('debit-notes.preview');
    Route::post('/debit-notes/{gig}/generate', [App\Http\Controllers\DebitNoteController::class, 'generate'])->name('debit-notes.generate');
    Route::post('/debit-notes/{gig}/cancel', [App\Http\Controllers\DebitNoteController::class, 'cancel'])->name('debit-notes.cancel');
    Route::post('/debit-notes/{debitNote}/activate', [App\Http\Controllers\DebitNoteController::class, 'activate'])->name('debit-notes.activate');
    Route::get('/debit-notes/{gig}/history', [App\Http\Controllers\DebitNoteController::class, 'history'])->name('debit-notes.history');

    // Settlement - Toggle ND requirement
    Route::patch('/gigs/{gig}/toggle-requires-nd', [App\Http\Controllers\SettlementController::class, 'toggleRequiresDebitNote'])->name('settlements.toggle-requires-nd');
    Route::post('/gigs/{gig}/link-service-taker', [App\Http\Controllers\SettlementController::class, 'linkServiceTaker'])->name('settlements.link-service-taker');

    // Bookers
    Route::resource('bookers', BookerController::class);

    // Agency Costs
    Route::resource('agency-costs', \App\Http\Controllers\AgencyCostController::class);

    // Cost Centers
    Route::resource('cost-centers', CostCenterController::class)->except(['show']);

    // (Optional API route) Restore via POST when using JS modal
    Route::post('/cost-centers/restore', [CostCenterController::class, 'restoreGhost'])
        ->name('cost-centers.restore');

    // Rota para atualizar comissão de eventos
    Route::post('/bookers/events/{eventId}/commission', [BookerController::class, 'updateEventCommission'])->name('bookers.events.commission.update');

    // Booker Performance Export Routes
    Route::get('/bookers/{booker}/export/pdf', [BookerController::class, 'exportPdf'])->name('bookers.export.pdf');
    Route::get('/bookers/{booker}/export/excel', [BookerController::class, 'exportExcel'])->name('bookers.export.excel');

    // Financial Projections
    Route::get('/projections', [FinancialProjectionController::class, 'index'])->name('projections.index');

    // **NOVA ROTA PARA A DEPURAÇÃO DAS PROJEÇÕES**
    Route::get('/projections/debug', [FinancialProjectionController::class, 'debug'])->name('projections.debug');

    // Performance Reports
    Route::get('/reports/performance', [PerformanceReportController::class, 'index'])->name('reports.performance.index');
    Route::get('/reports/performance/export', [PerformanceReportController::class, 'exportPdf'])->name('reports.performance.export');

    // Artist Performance Reports
    Route::get('/reports/artist-performance', [ArtistPerformanceController::class, 'index'])->name('reports.artist-performance.index');
    Route::get('/reports/artist-performance/export/pdf', [ArtistPerformanceController::class, 'exportPdf'])->name('reports.artist-performance.export.pdf');
    Route::get('/reports/artist-performance/export/excel', [ArtistPerformanceController::class, 'exportExcel'])->name('reports.artist-performance.export.excel');

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

    // Gigs Import (deve vir antes do resource para não conflitar)
    Route::get('gigs/import', [GigImportController::class, 'showForm'])->name('gigs.import.form');
    Route::get('gigs/import/template', [GigImportController::class, 'downloadTemplate'])->name('gigs.import.template');
    Route::post('gigs/import/preview', [GigImportController::class, 'preview'])->name('gigs.import.preview');
    Route::post('gigs/import', [GigImportController::class, 'import'])->name('gigs.import');

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
        Route::patch('costs/{cost}/reimbursement-stage', [GigCostController::class, 'updateReimbursementStage'])->name('costs.updateReimbursementStage');
        Route::get('costs-json', [GigCostController::class, 'listJson'])->name('costs.listJson');

        // Settlements
        // DEPRECATED: Rotas de artista substituídas pelo novo workflow em ArtistSettlementsController
        // Use artists.settlements.settle e artists.settlements.revert em vez destas
        // Route::post('settle-artist', [SettlementController::class, 'settleArtistPayment'])->name('settlements.artist');
        // Route::patch('unsettle-artist', [SettlementController::class, 'unsettleArtistPayment'])->name('settlements.artist.unsettle');

        // Rotas de booker continuam ativas
        Route::post('settle-booker', [SettlementController::class, 'settleBookerCommission'])->name('settlements.booker');
        Route::patch('unsettle-booker', [SettlementController::class, 'unsettleBookerCommission'])->name('settlements.booker.unsettle');

    });

    // ROTA DE DEPURAÇÃO FINANCEIRA PARA UMA GIG ESPECÍFICA
    // Coloque esta rota dentro do grupo de autenticação para que só usuários logados possam acessá-la.
    // Ela deve vir antes ou depois do Route::resource, a ordem aqui não é crítica.
    Route::get('gigs/{gig}/debug-financials', [GigController::class, 'debugFinancials'])->name('gigs.debugFinancials');

    // Data Audit Routes
    Route::get('/audit/data-audit', [AuditController::class, 'dataAudit'])->name('audit.data-audit');
    Route::post('/audit/run-data-audit', [AuditController::class, 'runDataAudit'])->name('audit.run-data-audit');
    Route::post('/audit/get-issues', [AuditController::class, 'getAuditIssues'])->name('audit.get-issues');
    Route::post('/audit/apply-fix', [AuditController::class, 'applyFix'])->name('audit.apply-fix');
    Route::post('/audit/apply-bulk-fix', [AuditController::class, 'applyBulkFix'])->name('audit.apply-bulk-fix');

    // New Audit System Routes (Phase 3)
    Route::get('/audit/available-audits', [AuditController::class, 'getAvailableAudits'])->name('audit.available-audits');
    Route::get('/audit/dashboard', [AuditController::class, 'getDashboard'])->name('audit.dashboard');
    Route::post('/audit/run-specific-audit', [AuditController::class, 'runSpecificAudit'])->name('audit.run-specific-audit');
    Route::post('/audit/run-all-audits', [AuditController::class, 'runAllAudits'])->name('audit.run-all-audits');

    // Admin Configuracoes - Gerenciador de Backups
    Route::middleware('can:manage backups')
        ->prefix('admin/configuracoes/backup')
        ->name('admin.backup.')
        ->group(function () {
            Route::get('/', [BackupController::class, 'index'])->name('index');
            Route::post('/', [BackupController::class, 'store'])->name('store');
            Route::post('/upload', [BackupController::class, 'upload'])->name('upload');
            Route::get('/{filename}/download', [BackupController::class, 'download'])->name('download');
            Route::post('/{filename}/restore', [BackupController::class, 'restore'])->name('restore');
            Route::delete('/{filename}', [BackupController::class, 'destroy'])->name('destroy');
        });

});

// Rotas de autenticação (geradas pelo Breeze)
require __DIR__.'/auth.php';

// Rota de diagnóstico temporária - REMOVER APÓS DEBUG
Route::get('/debug-db', function () {
    try {
        $users = \App\Models\User::all();
        $dbConnection = config('database.default');
        $dbConfig = config("database.connections.$dbConnection");
        $pdo = \DB::connection()->getPdo();

        return response()->json([
            'database' => $dbConfig['database'] ?? 'unknown',
            'connection' => $dbConnection,
            'users_count' => $users->count(),
            'users' => $users->map(fn ($u) => ['id' => $u->id, 'email' => $u->email, 'name' => $u->name]),
            'tables' => \DB::select('SHOW TABLES'),
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});
