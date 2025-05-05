<?php

use Illuminate\Support\Facades\Route;
use Illuminate\View\View; // Importe View
use App\Http\Controllers\GigController; // Importe o controlador GigController
use App\Http\Controllers\ProfileController; // Importe o controlador ProfileController
use App\Http\Controllers\ArtistController; // Importe o controlador ArtistController
use App\Http\Controllers\BookerController; // Importe o controlador BookerController
use App\Http\Controllers\PaymentController; // Importe o controlador PaymentController

Route::get('/', function () {
    return view('welcome'); // Ou redirecione para o login/dashboard
});

// Rotas de autenticação do Breeze (já devem estar aqui)
require __DIR__.'/auth.php';

// Rotas protegidas por autenticação
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function (): View {
        return view('dashboard'); // Retorna a view dashboard.blade.php
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
   
    // --- ROTAS PARA GIGS ---
    Route::resource('gigs', GigController::class); // Cria index, create, store, show, edit, update, destroy
    
    // Salvar um pagamento para uma gig
    Route::post('gigs/{gig}/payments', [PaymentController::class, 'store'])->name('gigs.payments.store');
    
    //Deleta um pagamento específico de uma gig
    Route::delete('gigs/{gig}/payments/{payment}', [PaymentController::class, 'destroy'])->name('gigs.payments.destroy');

    //Edita um pagamento específico de uma gig
    Route::put('gigs/{gig}/payments/{payment}', [PaymentController::class, 'update'])->name('gigs.payments.update');

    //Confirma um pagamento específico de uma gig
    Route::patch('gigs/{gig}/payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('gigs.payments.confirm');

    //desconfirma um pagamento específico de uma gig
    Route::patch('gigs/{gig}/payments/{payment}/unconfirm', [PaymentController::class, 'unconfirm'])->name('gigs.payments.unconfirm');
    
});