<?php

namespace App\Http\Controllers;

use App\Models\Notificacao;
use Illuminate\Http\Request;

class NotificacaoController extends Controller
{
    // Lista todas as notificações do usuário logado
    public function index(Request $request)
    {
        return $request->user()->notificacoes()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Marca uma única notificação como lida
    public function marcarComoLida($id)
    {
        $notificacao = Notificacao::where('id', $id)->firstOrFail();
        $notificacao->update(['lida' => true]);

        return response()->json(['message' => 'Notificação marcada como lida']);
    }

    // Marca todas de uma vez (útil para o botão "Limpar tudo")
    public function lerTudo(Request $request)
    {
        $request->user()->notificacoes()
            ->where('lida', false)
            ->update(['lida' => true]);

        return response()->json(['message' => 'Todas as notificações foram lidas']);
    }
}