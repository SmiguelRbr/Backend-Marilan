<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role): Response
    {
        // Verifica se o usuário está logado e tem o cargo exigido
        if ($request->user() && $request->user()->role === $role) {
            return $next($request);
        }

        return response()->json(['error' => 'Acesso negado. Apenas o Almoxarifado pode acessar.'], 403);
    }
}