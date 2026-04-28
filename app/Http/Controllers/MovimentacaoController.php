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
    public function devolver(Request $request)
    {
        $almoxarife = User::where('cracha', $request->cracha_almoxarife)->first();
        if (!$almoxarife || $almoxarife->role !== 'almoxarife') {
            return response()->json(['error' => 'A devolução deve ser feita para um Almoxarife'], 403);
        }

        $colaborador = User::where('cracha', $request->cracha_colaborador)->first();
        if (!$colaborador) {
            return response()->json(['error' => 'Colaborador não encontrado'], 404);
        }

        $ferramentasReq = $request->ferramentas;
        $devolvidas     = [];
        $erros          = [];

        foreach ($ferramentasReq as $item) {
            $ferramenta = Ferramenta::where('codigo_patrimonio', $item['codigo'])->first();

            if ($ferramenta) {
                $mov = Movimentacao::where('ferramenta_id', $ferramenta->id)
                    ->where('usuario_id', $colaborador->id)
                    ->where('status', 'aberto')
                    ->first();

                if ($mov) {
                    $mov->update([
                        'data_devolucao' => now(),
                        'status'         => 'concluido',
                        'observacao'     => $item['observacao'] ?? $mov->observacao,
                    ]);

                    $ferramenta->update(['status' => 'disponivel']);

                    // Dispara notificação de devolução
                    NotificacaoController::notificarDevolucao($mov);

                    $devolvidas[] = $ferramenta->codigo_patrimonio;
                } else {
                    $erros[] = "A ferramenta {$ferramenta->codigo_patrimonio} não está sob responsabilidade deste colaborador.";
                }
            }
        }

        return response()->json([
            'message'        => 'Processamento concluído.',
            'qtd_devolvidas' => count($devolvidas),
            'erros'          => $erros,
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
}