<?php

namespace App\Http\Controllers;

use App\Models\Ferramenta;
use App\Models\Movimentacao;
use App\Models\User;
use Illuminate\Http\Request;

class MovimentacaoController extends Controller
{
    // 1. RETIRADA (Almoxarife liberando ferramentas para o Colaborador)
    public function retirar(Request $request)
    {
        if ($request->user()->role !== 'almoxarife') {
            return response()->json(['error' => 'Ação permitida apenas para almoxarifes'], 403);
        }

        // Busca o colaborador pelo crachá que foi lido no NFC
        $colaborador = User::where('cracha', $request->cracha_colaborador)->firstOrFail();
        $ferramentas_req = $request->ferramentas; // Recebe o array de ferramentas
        $registros = [];

        foreach ($ferramentas_req as $item) {
            $ferramenta = Ferramenta::where('codigo_patrimonio', $item['codigo'])->first();
            
            if ($ferramenta && $ferramenta->status === 'disponivel') {
                $mov = Movimentacao::create([
                    'ferramenta_id' => $ferramenta->id,
                    'usuario_id'    => $colaborador->id,
                    'almoxarife_id' => $request->user()->id,
                    'qtd'           => $item['qtd'] ?? 1,
                    'checklist'     => $item['checklist'] ?? 'REALIZADO',
                    'observacao'    => $item['observacao'] ?? null,
                    'status'        => 'aberto'
                ]);

                $ferramenta->update(['status' => 'em_uso']);
                $registros[] = $mov;
            }
        }

        return response()->json(['message' => count($registros) . ' ferramenta(s) retirada(s) com sucesso!', 'data' => $registros]);
    }

    // 2. DEVOLUÇÃO (Colaborador devolvendo para o Almoxarife)
    public function devolver(Request $request)
    {
        // 1. Verifica se quem está recebendo é realmente um almoxarife
        $almoxarife = User::where('cracha', $request->cracha_almoxarife)->first();
        if (!$almoxarife || $almoxarife->role !== 'almoxarife') {
            return response()->json(['error' => 'A devolução deve ser feita para um Almoxarife'], 403);
        }

        // 2. Busca o colaborador que está tentando devolver
        $colaborador = User::where('cracha', $request->cracha_colaborador)->first();
        if (!$colaborador) {
            return response()->json(['error' => 'Colaborador não encontrado'], 404);
        }

        $ferramentas_req = $request->ferramentas;
        $devolvidas = [];
        $erros = []; // Guarda as ferramentas que ele tentou devolver mas não estavam com ele

        foreach ($ferramentas_req as $item) {
            $ferramenta = Ferramenta::where('codigo_patrimonio', $item['codigo'])->first();
            
            if ($ferramenta) {
                // A TRAVA MÁGICA: Acha a movimentação aberta PARA ESTE COLABORADOR
                $mov = Movimentacao::where('ferramenta_id', $ferramenta->id)
                                   ->where('usuario_id', $colaborador->id) // <- Só aceita se estiver no nome dele!
                                   ->where('status', 'aberto')
                                   ->first();

                if ($mov) {
                    $mov->update([
                        'data_devolucao' => now(),
                        'status'         => 'concluido',
                        'observacao'     => $item['observacao'] ?? $mov->observacao 
                    ]);

                    $ferramenta->update(['status' => 'disponivel']);
                    $devolvidas[] = $ferramenta->codigo_patrimonio;
                } else {
                    // Se não achou movimentação aberta no nome dele para essa ferramenta
                    $erros[] = "A ferramenta " . $ferramenta->codigo_patrimonio . " não está sob responsabilidade deste colaborador.";
                }
            }
        }

        return response()->json([
            'message' => 'Processamento concluído.',
            'qtd_devolvidas' => count($devolvidas),
            'erros' => $erros
        ]);
    }


    public function trocar(Request $request)
    {
        // Busca o novo peão que vai assumir as ferramentas pelo crachá
        $novoColaborador = User::where('cracha', $request->cracha_novo_colaborador)->first();
        
        if (!$novoColaborador) {
            return response()->json(['error' => 'Novo colaborador não encontrado.'], 404);
        }

        $ferramentas_req = $request->ferramentas;
        $trocadas = [];

        foreach ($ferramentas_req as $item) {
            $ferramenta = Ferramenta::where('codigo_patrimonio', $item['codigo'])->first();
            
            if ($ferramenta) {
                // Acha a movimentação que estava aberta (com o manutentor anterior)
                $movAntiga = Movimentacao::where('ferramenta_id', $ferramenta->id)
                                         ->where('status', 'aberto')
                                         ->first();

                if ($movAntiga) {
                    // 1. Encerra a posse do manutentor anterior
                    $movAntiga->update([
                        'data_devolucao' => now(),
                        'status'         => 'concluido'
                    ]);

                    // 2. Inicia a posse do NOVO manutentor
                    Movimentacao::create([
                        'ferramenta_id' => $ferramenta->id,
                        'usuario_id'    => $novoColaborador->id,
                        'almoxarife_id' => null, // Como é troca direta na fábrica, não tem almoxarife intermediando
                        'qtd'           => $item['qtd'] ?? $movAntiga->qtd,
                        'checklist'     => $item['checklist'] ?? 'REALIZADO',
                        'observacao'    => $item['observacao'] ?? $movAntiga->observacao,
                        'status'        => 'aberto'
                    ]);

                    // Nota: Não precisamos dar update na ferramenta pois ela continua 'em_uso'
                    
                    $trocadas[] = $ferramenta->codigo_patrimonio;
                }
            }
        }

        return response()->json(['message' => 'Transferência de ' . count($trocadas) . ' ferramenta(s) concluída com sucesso!']);
    }
}