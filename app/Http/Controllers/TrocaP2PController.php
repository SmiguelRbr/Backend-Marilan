<?php

namespace App\Http\Controllers;

use App\Models\TrocaP2P;
use App\Models\User;
use App\Models\Ferramenta;
use App\Models\Movimentacao;
use Illuminate\Http\Request;

class TrocaP2PController extends Controller
{
    // 1. Cria o pedido de troca (status: pendente)
    public function solicitar(Request $request)
    {
        $deUsuario = User::where('cracha', $request->de_cracha)->first();
        $paraUsuario = User::where('cracha', $request->para_cracha)->first();

        if (!$deUsuario || !$paraUsuario) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        if ($deUsuario->id === $paraUsuario->id) {
            return response()->json(['error' => 'Você não pode transferir para si mesmo'], 400);
        }

        $troca = TrocaP2P::create([
            'de_user_id' => $deUsuario->id,
            'para_user_id' => $paraUsuario->id,
            'ferramentas' => $request->ferramentas,
            'status' => 'pendente'
        ]);

        return response()->json(['message' => 'Solicitação de troca enviada ao colega', 'id' => $troca->id]);
    }

    // 2. Lista as trocas pendentes para quem vai receber (Polling do App)
    public function pendentes(Request $request)
    {
        $paraUsuario = User::where('cracha', $request->query('para_cracha'))->first();

        if (!$paraUsuario) {
            return response()->json([]);
        }

        // Puxa as trocas pendentes e envia formatado para o React Native
        $pendentes = TrocaP2P::with(['deUsuario'])
            ->where('para_user_id', $paraUsuario->id)
            ->where('status', 'pendente')
            ->get()
            ->map(function($troca) {
                return [
                    'id' => $troca->id,
                    'de_cracha' => $troca->deUsuario->cracha,
                    'de_nome' => $troca->deUsuario->nome, 
                    'ferramentas' => $troca->ferramentas,
                ];
            });

        return response()->json($pendentes);
    }

    // 3. O colega aceitou: fecha a posse antiga e cria a nova posse
    public function aceitar(Request $request, $id)
    {
        $troca = TrocaP2P::find($id);

        if (!$troca || $troca->status !== 'pendente') {
            return response()->json(['error' => 'Troca inválida ou já processada'], 404);
        }

        $novoColaborador = User::find($troca->para_user_id);
        $anteriorColaborador = User::find($troca->de_user_id);
        $trocadas = [];

        foreach ($troca->ferramentas as $item) {
            $ferramenta = Ferramenta::where('codigo_patrimonio', $item['codigo'])->first();

            if ($ferramenta) {
                $movAntiga = Movimentacao::where('ferramenta_id', $ferramenta->id)
                    ->where('usuario_id', $anteriorColaborador->id)
                    ->where('status', 'aberto')
                    ->first();

                if ($movAntiga) {
                    // 1. Encerra posse anterior
                    $movAntiga->update([
                        'data_devolucao' => now(),
                        'status'         => 'concluido',
                    ]);

                    // 2. Cria nova posse
                    Movimentacao::create([
                        'ferramenta_id' => $ferramenta->id,
                        'usuario_id'    => $novoColaborador->id,
                        'almoxarife_id' => null, // P2P não tem almoxarife envolvido
                        'qtd'           => $item['qtd'] ?? $movAntiga->qtd,
                        'checklist'     => 'REALIZADO',
                        'observacao'    => 'Transferência direta (P2P) entre colaboradores',
                        'status'        => 'aberto',
                    ]);

                    // Dispara notificações (como já existia antes)
                    NotificacaoController::notificarTroca($novoColaborador, $ferramenta, $anteriorColaborador);
                    $trocadas[] = $ferramenta->codigo_patrimonio;
                }
            }
        }

        $troca->update(['status' => 'aceita']);

        return response()->json(['message' => 'Troca aceita e concluída com sucesso!']);
    }

    // 4. O colega recusou: a ferramenta continua com o antigo dono
    public function recusar($id)
    {
        $troca = TrocaP2P::find($id);

        if (!$troca || $troca->status !== 'pendente') {
            return response()->json(['error' => 'Troca inválida ou já processada'], 404);
        }

        $troca->update(['status' => 'recusada']);
        return response()->json(['message' => 'Troca recusada com sucesso.']);
    }
}