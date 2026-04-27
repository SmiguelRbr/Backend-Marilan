<?php

namespace App\Http\Controllers;

use App\Models\Ferramenta;
use Illuminate\Http\Request;

class FerramentaController extends Controller
{
    public function index(Request $request)
    {
        // Puxa as ferramentas e já traz junto a movimentação que estiver 'aberto'
        $ferramentas = Ferramenta::with(['movimentacoes' => function($query) {
            $query->where('status', 'aberto')->with('usuario');
        }])->get();

        // Moldamos os dados EXATAMENTE como o React Native do seu amigo espera
        $dados = $ferramentas->map(function ($f) {
            $movAberta = $f->movimentacoes->first();

            // Traduz o status do Banco (minúsculo) para o App (Maiúsculo e com acento)
            $statusFront = 'Disponível';
            if ($f->status === 'em_uso') $statusFront = 'Em uso';
            if ($f->status === 'manutencao') $statusFront = 'Em manutenção';

            return [
                'codigo'      => $f->codigo_patrimonio,
                'nome'        => $f->descricao,
                'categoria'   => 'Geral', // Como não temos isso na planilha, deixamos um padrão
                'status'      => $statusFront,
                'alocadoPara' => $movAberta ? $movAberta->usuario->nome : null,
            ];
        });

        return response()->json($dados);
    }
}