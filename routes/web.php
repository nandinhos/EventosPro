<?php

use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GigController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\BookerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\GigCostController; // Certifique-se que este controller existe,
use App\Http\Controllers\ReportController;
use App\Models\Gig; // Importar Gig
use Carbon\Carbon;  // Importar Carbon
use App\Http\Controllers\SettlementController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // Redirecionar para o login se não estiver logado, ou para o dashboard se estiver.
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('auth.login'); // Ou view('welcome') se preferir
})->name('home');


// Rotas de Relatórios
Route::get('/reports/financial', [ReportController::class, 'financial'])->name('reports.financial');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function (): View {
        // ... (KPIs que já tínhamos: activeFutureGigsCount, etc.) ...
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $activeFutureGigsCount = Gig::where('gig_date', '>=', $today)->count();
        $overdueClientPaymentsCount = Gig::where('payment_status', 'vencido')->count();
        $pendingArtistPaymentsCount = Gig::where('artist_payment_status', 'pendente')->count();
        $pendingBookerPaymentsCount = Gig::where('booker_payment_status', 'pendente')->count();

        $gigsThisMonth = Gig::whereBetween('gig_date', [$startOfMonth, $endOfMonth])->get();
        $totalCacheThisMonth = $gigsThisMonth->sum(fn($gig) => $gig->cache_value_brl); // Usa Accessor
        $totalAgencyCommissionThisMonth = $gigsThisMonth->sum('agency_commission_value');
        $totalBookerCommissionThisMonth = $gigsThisMonth->sum('booker_commission_value');
        $nextGigs = Gig::with('artist')->where('gig_date', '>=', $today)->orderBy('gig_date', 'asc')->limit(5)->get();


        // --- NOVOS DADOS PARA O GRÁFICO DE FATURAMENTO MENSAL ---
        // Pegar os últimos 12 meses, por exemplo
        $endDateForChart = Carbon::now()->endOfMonth();
        $startDateForChart = Carbon::now()->subMonths(11)->startOfMonth(); // 11 meses atrás + mês atual = 12 meses

        // Buscar Gigs realizadas ou com status que indicam faturamento (ajuste a condição de status se necessário)
        // Agrupar por ano e mês, e somar cache_value_brl
        $monthlyRevenueData = Gig::select(
            DB::raw("YEAR(gig_date) as year"),
            DB::raw("MONTH(gig_date) as month"),
            DB::raw("SUM(cache_value) as total_revenue_brl") // Usa a coluna já convertida
        )
        ->where('gig_date', '>=', $startDateForChart)
        ->where('gig_date', '<=', $endDateForChart)
        // EXEMPLO: Condições mais flexíveis para ver dados
        ->whereIn('contract_status', ['assinado', 'concluido', 'para_assinatura', 'n/a']) // Inclui mais status
        // ->where('payment_status', 'pago') // Talvez remover este filtro por enquanto se os dados não estiverem como 'pago'
        ->groupBy('year', 'month')
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get();

        // Preparar dados para Chart.js
        $chartLabels = [];
        $chartData = [];
        // Iterar sobre os 12 meses para garantir que todos apareçam, mesmo os com receita zero
        $currentMonthIterator = $startDateForChart->copy();
        while ($currentMonthIterator->lessThanOrEqualTo($endDateForChart)) {
            $year = $currentMonthIterator->year;
            $month = $currentMonthIterator->month;
            // Formato do label: "Jan/25"
            $chartLabels[] = $currentMonthIterator->translatedFormat('M/y');

            // Encontrar a receita para este mês/ano nos dados do banco
            $revenueForMonth = $monthlyRevenueData->first(function ($item) use ($year, $month) {
                return $item->year == $year && $item->month == $month;
            });

            $chartData[] = $revenueForMonth ? (float) $revenueForMonth->total_revenue_brl : 0;
            $currentMonthIterator->addMonth();
        }
        // --- FIM DOS DADOS PARA O GRÁFICO ---

        return view('dashboard', compact(
            'activeFutureGigsCount', 'overdueClientPaymentsCount',
            'pendingArtistPaymentsCount', 'pendingBookerPaymentsCount',
            'totalCacheThisMonth', 'totalAgencyCommissionThisMonth', 'totalBookerCommissionThisMonth',
            'nextGigs',
            'chartLabels', // <-- Passar para view
            'chartData'    // <-- Passar para view
        ));
    })->name('dashboard');

    // Rotas de Profile (geralmente já definidas pelo Breeze em auth.php, mas podem ficar aqui)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- NOVA ROTA PARA A PÁGINA DE SOLICITAÇÃO DE NF ---
    Route::get('gigs/{gig}/request-nf', [GigController::class, 'showRequestNfForm'])->name('gigs.request-nf');

    // --- ROTAS PARA GIGS (EVENTOS/DATAS) ---
    Route::resource('gigs', GigController::class);
    // --- NOVA ROTA PARA A PÁGINA DE SOLICITAÇÃO DE NF ---
    Route::get('gigs/{gig}/request-nf', [GigController::class, 'showRequestNfForm'])->name('gigs.request-nf');

    // --- ROTAS ANINHADAS DENTRO DE UMA GIG ESPECÍFICA ---
    Route::prefix('gigs/{gig}')->name('gigs.')->group(function () {

        // --- ROTAS PARA PAYMENTS (PAGAMENTOS RECEBIDOS DA GIG) ---
        // O resource aninhado pode ser uma opção, mas como você já tem rotas específicas:
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
        // Rota para o formulário de edição de um pagamento (se você criar uma página dedicada)
        Route::get('payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
        Route::put('payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
        Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
        Route::patch('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
        Route::patch('payments/{payment}/unconfirm', [PaymentController::class, 'unconfirm'])->name('payments.unconfirm');

        // --- ROTAS PARA GIGCOSTS (DESPESAS DA GIG) ---
        // Usando resource para as ações padrão de CRUD (exceto index e show que não são típicos para sub-recursos em modal)
        Route::resource('costs', GigCostController::class)->except(['index', 'show']);
            // As rotas geradas pelo resource aninhado serão:
            // GET    gigs/{gig}/costs/create  (gigs.costs.create)
            // POST   gigs/{gig}/costs         (gigs.costs.store)
            // GET    gigs/{gig}/costs/{cost}/edit (gigs.costs.edit)
            // PUT    gigs/{gig}/costs/{cost}      (gigs.costs.update)
            // DELETE gigs/{gig}/costs/{cost}      (gigs.costs.destroy)

        // Rotas adicionais para confirmar/desconfirmar despesa.
        Route::patch('costs/{cost}/confirm', [GigCostController::class, 'confirm'])->name('costs.confirm');
        Route::patch('costs/{cost}/unconfirm', [GigCostController::class, 'unconfirm'])->name('costs.unconfirm');
        Route::patch('costs/{cost}/toggle-invoice', [GigCostController::class, 'toggleInvoice'])->name('costs.toggleInvoice');

        // Rota para buscar custos via JSON para Alpine (para a view _show_costs)
        Route::get('costs-json', [GigCostController::class, 'listJson'])->name('costs.listJson');

        // --- ROTAS PARA ACERTOS (SETTLEMENTS) DA GIG ---
        // Marcar cachê do artista como pago e registrar detalhes do acerto
        Route::post('settle-artist', [SettlementController::class, 'settleArtistPayment'])->name('settlements.artist');

        // Rota para a view de solicitação de nota fiscal
        //Route::get('request-nf', function (Gig $gig) {return view('request-nf', compact('gig'));})->name('request-nf');

        

        // Marcar comissão do booker como paga e registrar detalhes do acerto
        Route::post('settle-booker', [SettlementController::class, 'settleBookerCommission'])->name('settlements.booker');

        // (Opcional) Rotas para reverter pagamentos de artista/booker, se necessário
        Route::patch('unsettle-artist', [SettlementController::class, 'unsettleArtistPayment'])->name('settlements.artist.unsettle');
        Route::patch('unsettle-booker', [SettlementController::class, 'unsettleBookerCommission'])->name('settlements.booker.unsettle');
   
    });


    // --- ROTAS PARA ARTISTAS ---
    Route::resource('artists', ArtistController::class);

    // --- ROTAS PARA BOOKERS ---
    Route::resource('bookers', BookerController::class);

    // Adicionar rotas para Tags, Relatórios, etc. aqui no futuro
    // Route::resource('tags', TagController::class);
});

require __DIR__.'/auth.php'; // Rotas de autenticação do Breeze