<?php

namespace App\Http\Controllers;

use App\Models\Ferramenta;
use App\Models\Movimentacao;
use App\Models\Solicitacao;
use App\Models\User;
use Illuminate\Http\Request;

class SolicitacaoController extends Controller
{
    /**
     * POST /solicitacoes
     *
     * Colaborador cria uma solicitação informando:
     *   - cracha_colaborador  (pode ser lido do token ou enviado no body — aqui lemos do token para segurança)
     *   - cracha_almoxarife   (quem vai receber a notificação)
     *   - ferramentas         (array com codigo, qtd, checklist, observacao)
     *
     * Qualquer usuário autenticado pode chamar esta rota.
     */
    public function criar(Request $request)
    {
        $request->validate([
            'cracha_almoxarife'    => 'required|string',
            'ferramentas'          => 'required|array|min:1',
            'ferramentas.*.codigo' => 'required|string',
            'ferramentas.*.qtd'    => 'required|integer|min:1',
        ]);

        // Quem está fazendo a solicitação = usuário do token
        $colaborador = $request->user();

        // Valida que não é almoxarife tentando usar este fluxo
        // (almoxarife usa diretamente POST /retirar)
        // Permitimos mesmo assim para não quebrar edge-cases.

        // Busca o almoxarife pelo crachá digitado
        $almoxarife = User::where('cracha', $request->cracha_almoxarife)
            ->where('role', 'almoxarife')
            ->first();

        if (!$almoxarife) {
            return response()->json([
                'error' => 'Almoxarife não encontrado. Verifique o crachá informado.',
            ], 404);
        }

        // Cria a solicitação com status 'pendente'
        $solicitacao = Solicitacao::create([
            'colaborador_id' => $colaborador->id,
            'almoxarife_id'  => $almoxarife->id,
            'ferramentas'    => $request->ferramentas,
            'status'         => 'pendente',
        ]);

        // Notifica o almoxarife sobre a nova solicitação
        $lista = collect($request->ferramentas)
            ->map(fn ($f) => "{$f['codigo']} ×{$f['qtd']}")
            ->implode(', ');

        NotificacaoController::criar(
            $almoxarife->id,
            'Nova Solicitação de Retirada',
            "{$colaborador->nome} (Crachá: {$colaborador->cracha}) solicitou: {$lista}. Acesse o app para aprovar ou recusar.",
            'info'
        );

        return response()->json([
            'message'      => 'Solicitação criada com sucesso! Aguarde a aprovação do almoxarife.',
            'solicitacao'  => $solicitacao->load('colaborador', 'almoxarife'),
        ], 201);
    }

    /**
     * GET /solicitacoes/pendentes
     *
     * Almoxarife busca as solicitações pendentes direcionadas a ele.
     * Rota protegida por middleware role:almoxarife.
     *
     * Retorna array de solicitações com dados do colaborador e ferramentas
     * enriquecidos com o nome da ferramenta (buscado do banco).
     */
    public function pendentes(Request $request)
    {
        $almoxarife = $request->user();

        $solicitacoes = Solicitacao::with('colaborador')
            ->where('almoxarife_id', $almoxarife->id)
            ->pendentes()
            ->orderBy('created_at', 'asc') // mais antigas primeiro
            ->get();

        // Enriquece cada solicitação com o nome das ferramentas
        $resultado = $solicitacoes->map(function (Solicitacao $sol) {
            $ferramentasEnriquecidas = collect($sol->ferramentas)->map(function ($item) {
                $ferramenta = Ferramenta::where('codigo_patrimonio', $item['codigo'])->first();
                return [
                    'codigo'    => $item['codigo'],
                    'nome'      => $ferramenta?->descricao ?? $item['codigo'],
                    'qtd'       => $item['qtd'],
                    'checklist' => $item['checklist'] ?? 'REALIZADO',
                    'observacao' => $item['observacao'] ?? '',
                ];
            })->values()->all();

            return [
                'id'                  => $sol->id,
                'colaborador_nome'    => $sol->colaborador->nome,
                'colaborador_cracha'  => $sol->colaborador->cracha,
                'ferramentas'         => $ferramentasEnriquecidas,
                'criado_em'           => $sol->created_at->toIso8601String(),
            ];
        });

        return response()->json($resultado);
    }

    /**
     * POST /solicitacoes/{id}/aprovar
     *
     * Almoxarife aprova → cria Movimentacao para cada ferramenta disponível.
     * Rota protegida por middleware role:almoxarife.
     */
    public function aprovar(Request $request, int $id)
    {
        $almoxarife = $request->user();

        $solicitacao = Solicitacao::with('colaborador')
            ->where('id', $id)
            ->where('almoxarife_id', $almoxarife->id)
            ->where('status', 'pendente')
            ->firstOrFail();

        $colaborador        = $solicitacao->colaborador;
        $ferramentasRetiradas = [];
        $erros              = [];

        foreach ($solicitacao->ferramentas as $item) {
            $ferramenta = Ferramenta::where('codigo_patrimonio', $item['codigo'])->first();

            if (!$ferramenta) {
                $erros[] = "Ferramenta {$item['codigo']} não encontrada.";
                continue;
            }

            if ($ferramenta->status !== 'disponivel') {
                $erros[] = "Ferramenta {$ferramenta->codigo_patrimonio} não está disponível (status: {$ferramenta->status}).";
                continue;
            }

            Movimentacao::create([
                'ferramenta_id' => $ferramenta->id,
                'usuario_id'    => $colaborador->id,
                'almoxarife_id' => $almoxarife->id,
                'qtd'           => $item['qtd'] ?? 1,
                'checklist'     => $item['checklist'] ?? 'REALIZADO',
                'observacao'    => $item['observacao'] ?? null,
                'status'        => 'aberto',
            ]);

            $ferramenta->update(['status' => 'em_uso']);

            $ferramentasRetiradas[] = [
                'descricao'         => $ferramenta->descricao,
                'codigo_patrimonio' => $ferramenta->codigo_patrimonio,
            ];
        }

        // Marca a solicitação como aprovada
        $solicitacao->update(['status' => 'aprovada']);

        // Notifica o colaborador sobre a aprovação
        if (!empty($ferramentasRetiradas)) {
            NotificacaoController::notificarRetirada($colaborador, $ferramentasRetiradas);
        }

        // Notificação extra se houve erros parciais
        if (!empty($erros)) {
            NotificacaoController::criar(
                $colaborador->id,
                'Solicitação Parcialmente Aprovada',
                'Algumas ferramentas não puderam ser liberadas: ' . implode(' | ', $erros),
                'alerta'
            );
        }

        return response()->json([
            'message'             => 'Solicitação aprovada.',
            'ferramentas_liberadas' => count($ferramentasRetiradas),
            'erros'               => $erros,
        ]);
    }

    /**
     * POST /solicitacoes/{id}/recusar
     *
     * Almoxarife recusa a solicitação.
     * Rota protegida por middleware role:almoxarife.
     */
    public function recusar(Request $request, int $id)
    {
        $almoxarife = $request->user();

        $solicitacao = Solicitacao::with('colaborador')
            ->where('id', $id)
            ->where('almoxarife_id', $almoxarife->id)
            ->where('status', 'pendente')
            ->firstOrFail();

        $motivo = $request->motivo ?? 'Não informado';

        $solicitacao->update([
            'status'        => 'recusada',
            'motivo_recusa' => $motivo,
        ]);

        // Notifica o colaborador sobre a recusa
        NotificacaoController::criar(
            $solicitacao->colaborador->id,
            'Solicitação Recusada',
            "O almoxarife {$almoxarife->nome} recusou sua solicitação de retirada. Motivo: {$motivo}.",
            'alerta'
        );

        return response()->json(['message' => 'Solicitação recusada.']);
    }

    /**
  * GET /solicitacoes/{id}/status
  * Retorna o status para a tela de aguardando do colaborador.
  */
 public function status($id)
 {
     $solicitacao = \App\Models\Solicitacao::findOrFail($id);
     return response()->json(['status' => $solicitacao->status]);
 }
}