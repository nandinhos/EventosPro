<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfBooker
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Se o usuário estiver logado, for um booker, e estiver tentando acessar o dashboard principal
        if ($user && $user->hasRole('BOOKER') && $request->route()->getName() === 'filament.admin.pages.dashboard') {

            // Verifica se o usuário tem um booker_id associado
            if ($user->booker_id) {
                // ***** CORREÇÃO AQUI: Passa o parâmetro 'record' para a rota *****
                return redirect()->route('filament.admin.resources.bookers.view', [
                    'record' => $user->booker_id,
                ]);
            }
        }

        return $next($request);
    }
}
