<?php

use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GigController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\BookerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\GigCostController; // Certifique-se que este controller existe

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


Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function (): View {
        return view('dashboard');
    })->name('dashboard');

    // Rotas de Profile (geralmente já definidas pelo Breeze em auth.php, mas podem ficar aqui)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- ROTAS PARA GIGS (EVENTOS/DATAS) ---
    Route::resource('gigs', GigController::class);

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

        // Rota para buscar custos via JSON para Alpine (para a view _show_costs)
        Route::get('costs-json', [GigCostController::class, 'listJson'])->name('costs.listJson');
    });


    // --- ROTAS PARA ARTISTAS ---
    Route::resource('artists', ArtistController::class);

    // --- ROTAS PARA BOOKERS ---
    Route::resource('bookers', BookerController::class);

    // Adicionar rotas para Tags, Relatórios, etc. aqui no futuro
    // Route::resource('tags', TagController::class);
});

require __DIR__.'/auth.php'; // Rotas de autenticação do Breeze