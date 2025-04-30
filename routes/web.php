<?php

use Illuminate\Support\Facades\Route;
use Illuminate\View\View; // Importe View

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

    Route::get('/contratos', function (): View {
        return view('contratos'); // Retorna a view contratos.blade.php
    })->name('contratos');

    Route::get('/artistas', function (): View {
         return view('artistas'); // Retorna a view artistas.blade.php
    })->name('artistas');

     Route::get('/bookers', function (): View {
         return view('bookers');
     })->name('bookers');

      Route::get('/locais', function (): View {
          return view('locais');
      })->name('locais');

      Route::get('/eventos', function (): View {
          return view('eventos');
      })->name('eventos');

      Route::get('/pagamentos', function (): View {
          return view('pagamentos');
      })->name('pagamentos');

      Route::get('/relatorios', function (): View {
          return view('relatorios');
      })->name('relatorios');

      Route::get('/projecoes', function (): View {
          return view('projecoes');
      })->name('projecoes');

      Route::get('/usuarios', function (): View {
          return view('usuarios');
      })->name('usuarios');

    // Adicione rotas para o CRUD das entidades (Artistas, Bookers, etc.)
    // Exemplo: Route::resource('artistas', App\Http\Controllers\ArtistController::class);
});