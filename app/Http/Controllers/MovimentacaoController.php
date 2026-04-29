<?php

namespace App\Http\Controllers;

use App\Models\Ferramenta;
use App\Models\Movimentacao;
use App\Models\User;
use Illuminate\Http\Request;

class MovimentacaoController extends Controller
{
    /**
     * 1. RETIRADA — Almoxarife libera ferramentas para o Colaborador.
     *    Dispara notificação para o colaborador informando os itens retirados.
     */
    public function retirar(Request $request)
    {
        if ($request->user()->role !== 'almoxarife') {
            return response()->json(['error' => 'Ação permitida apenas para almoxarifes'], 403);
        }

        $colaborador    = User::where('cracha', $request->cracha_colaborador)->firstOrFail();
        $ferramentasReq = $request->ferramentas;
        $registros      = [];
        $ferramentasRetiradas = [];

        foreach ($ferramentasReq as $item) {
            $ferramenta = Ferramenta::where('codigo_patrimonio', $item['codigo'])->first();

            if ($ferramenta && $ferramenta->status === 'disponivel') {
                $mov = Movimentacao::create([
                    'ferramenta_id' => $ferramenta->id,
                    'usuario_id'    => $colaborador->id,
                    'almoxarife_id' => $request->user()->id,
                    'qtd'           => $item['qtd'] ?? 1,
                    'checklist'     => $item['checklist'] ?? 'REALIZADO',
                    'observacao'    => $item['observacao'] ?? null,
                    'status'        => 'aberto',
                ]);

                $ferramenta->update(['status' => 'em_uso']);
                $registros[] = $mov;
                $ferramentasRetiradas[] = [
                    'descricao'         => $ferramenta->descricao,
                    'codigo_patrimonio' => $ferramenta->codigo_patrimonio,
                ];
            }
        }

        // Dispara notificação de retirada para o colaborador
        if (!empty($ferramentasRetiradas)) {
            NotificacaoController::notificarRetirada($colaborador, $ferramentasRetiradas);
        }

        return response()->json([
            'message' => count($registros) . ' ferramenta(s) retirada(s) com sucesso!',
            'data'    => $registros,
        ]);
    }

    /**
     * 2. DEVOLUÇÃO — Colaborador devolve ferramentas para o Almoxarife.
     *    Dispara notificação de confirmação para o almoxarife.
     */
    public function solicitarDevolucao(Request $request)
    {
        $colaborador = User::where('cracha', $request->cracha_colaborador)->first();
        if (!$colaborador) {
            return response()->json(['error' => 'Colaborador não encontrado'], 404);
        }

        $ferramentasReq = $request->ferramentas;
        $solicitadas = [];

        foreach ($ferramentasReq as $item) {
            $ferramenta = Ferramenta::where('codigo_patrimonio', $item['codigo'])->first();

            if ($ferramenta) {
                // Busca a posse atual que está 'aberto'
                $mov = Movimentacao::where('ferramenta_id', $ferramenta->id)
                    ->where('usuario_id', $colaborador->id)
                    ->where('status', 'aberto')
                    ->first();

                if ($mov) {
                    $mov->update([
                        'status' => 'aguardando_devolucao',
                        'almoxarife_id' => $request->almoxarife_id ?? null // Opcional, se já souber pra quem vai
                    ]);
                    $solicitadas[] = $ferramenta->codigo_patrimonio;
                }
            }
        }

        return response()->json([
            'message' => 'Solicitação de devolução enviada ao Almoxarifado.',
            'qtd_solicitadas' => count($solicitadas),
        ]);
    }

    /**
     * PASSO 2 (SUCESSO): Almoxarife confirma que recebeu.
     * Aqui entra o CHECKLIST: Se a ferramenta voltar quebrada, ela vai para manutenção.
     */
    public function confirmarDevolucao(Request $request, $id)
    {
        $mov = Movimentacao::find($id);

        if (!$mov || $mov->status !== 'aguardando_devolucao') {
            return response()->json(['error' => 'Movimentação não encontrada ou não está pendente.'], 404);
        }

        // Pega o status do checklist (esperado: 'ok' ou 'com_defeito')
        $statusChecklist = $request->input('checklist_status', 'ok');
        $observacao = $request->input('observacao', $mov->observacao);

        // 1. Atualiza a Movimentação fechando ela
        $mov->update([
            'data_devolucao' => now(),
            'status'         => 'concluido',
            'almoxarife_id'  => $request->user()->id, // O almoxarife que logou e aceitou
            'checklist'      => $statusChecklist === 'ok' ? 'REALIZADO' : 'COM DEFEITO',
            'observacao'     => $observacao,
        ]);

        // 2. Atualiza o status da Ferramenta de acordo com o checklist
        $ferramenta = Ferramenta::find($mov->ferramenta_id);
        if ($ferramenta) {
            $novoStatus = ($statusChecklist === 'ok') ? 'disponivel' : 'manutencao';
            $ferramenta->update(['status' => $novoStatus]);
        }

        // Dispara notificação de devolução concluída para o colaborador
        NotificacaoController::notificarDevolucao($mov);

        return response()->json([
            'message' => 'Devolução confirmada com sucesso.',
            'ferramenta_status' => $ferramenta->status
        ]);
    }

    /**
     * PASSO 3 (ROLLBACK): Almoxarife diz "Não recebi essa ferramenta" ou cancela a operação.
     * O status volta para 'aberto' e o Colaborador continua responsável por ela.
     */
    public function recusarDevolucao(Request $request, $id)
    {
        $mov = Movimentacao::find($id);

        if (!$mov || $mov->status !== 'aguardando_devolucao') {
            return response()->json(['error' => 'Movimentação inválida para recusa.'], 404);
        }

        // ROLLBACK: Volta para aberto
        $mov->update([
            'status' => 'aberto',
            'observacao' => 'Devolução recusada pelo almoxarifado: ' . $request->input('motivo', 'Sem justificativa')
        ]);

        // Opcional: Notificar o colaborador de que a devolução foi negada
        // NotificacaoController::notificarRecusaDevolucao($mov);

        return response()->json([
            'message' => 'Devolução recusada. A ferramenta retornou para a custódia do colaborador.'
        ]);
    }

    /**
     * 3. TROCA — Transferência direta entre colaboradores (sem almoxarife).
     *    Notifica o novo responsável e o anterior.
     */
    public function trocar(Request $request)
    {
        $novoColaborador = User::where('cracha', $request->cracha_novo_colaborador)->first();

        if (!$novoColaborador) {
            return response()->json(['error' => 'Novo colaborador não encontrado.'], 404);
        }

        $ferramentasReq = $request->ferramentas;
        $trocadas       = [];

        foreach ($ferramentasReq as $item) {
            $ferramenta = Ferramenta::where('codigo_patrimonio', $item['codigo'])->first();

            if ($ferramenta) {
                $movAntiga = Movimentacao::where('ferramenta_id', $ferramenta->id)
                    ->where('status', 'aberto')
                    ->first();

                if ($movAntiga) {
                    $anteriorColaborador = User::find($movAntiga->usuario_id);

                    // 1. Encerra posse anterior
                    $movAntiga->update([
                        'data_devolucao' => now(),
                        'status'         => 'concluido',
                    ]);

                    // 2. Cria nova posse
                    Movimentacao::create([
                        'ferramenta_id' => $ferramenta->id,
                        'usuario_id'    => $novoColaborador->id,
                        'almoxarife_id' => null,
                        'qtd'           => $item['qtd'] ?? $movAntiga->qtd,
                        'checklist'     => $item['checklist'] ?? 'REALIZADO',
                        'observacao'    => $item['observacao'] ?? $movAntiga->observacao,
                        'status'        => 'aberto',
                    ]);

                    // Dispara notificações de troca
                    NotificacaoController::notificarTroca(
                        $novoColaborador,
                        $ferramenta,
                        $anteriorColaborador
                    );

                    $trocadas[] = $ferramenta->codigo_patrimonio;
                }
            }
        }

        return response()->json([
            'message' => 'Transferência de ' . count($trocadas) . ' ferramenta(s) concluída com sucesso!',
        ]);
    }

    /**
     * Lista todas as devoluções pendentes para o Almoxarife aprovar/recusar.
     */
    public function pendentes()
    {
        $pendentes = Movimentacao::with(['ferramenta', 'usuario'])
            ->where('status', 'aguardando_devolucao')
            ->get();

        return response()->json($pendentes);
    }
}
