<?php

namespace App\Http\Controllers;

use App\Models\Ferramenta;
use Illuminate\Http\Request;

class FerramentaController extends Controller
{
   public function index(Request $request)
    {
        // Puxa as ferramentas e já traz junto a movimentação que estiver 'aberto' OU 'aguardando_devolucao'
        $ferramentas = Ferramenta::with(['movimentacoes' => function($query) {
            $query->whereIn('status', ['aberto', 'aguardando_devolucao'])->with('usuario');
        }])->get();

        // Moldamos os dados EXATAMENTE como o React Native espera (seu código original)
        $dados = $ferramentas->map(function ($f) {
            $movAberta = $f->movimentacoes->first();

            // Traduz o status do Banco (minúsculo) para o App (Maiúsculo e com acento)
            $statusFront = 'Disponível';
            if ($f->status === 'em_uso') $statusFront = 'Em uso';
            if ($f->status === 'manutencao') $statusFront = 'Em manutenção';

            return [
                'codigo'              => $f->codigo_patrimonio,
                'nome'                => $f->descricao,
                'categoria'           => 'Geral', // Como não temos isso na planilha, deixamos um padrão
                'status'              => $statusFront,
                'alocadoPara'         => $movAberta ? $movAberta->usuario->nome : null,
                
                // --- AS DUAS FLAGS NOVAS PARA A DEVOLUÇÃO FUNCIONAR ---
                'cracha_alocado'      => $movAberta ? $movAberta->usuario->cracha : null,
                'aguardandoDevolucao' => $movAberta && $movAberta->status === 'aguardando_devolucao',
            ];
        });

        // Retorna TUDO sem filtrar, para não quebrar a sua aba de Ferramentas!
        return response()->json($dados);
    }
}