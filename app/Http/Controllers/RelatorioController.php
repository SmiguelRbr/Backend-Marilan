<?php

namespace App\Http\Controllers;

use App\Models\Movimentacao;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MovimentacaoExport;
use Barryvdh\DomPDF\Facade\Pdf;

class RelatorioController extends Controller
{
    public function gerar(Request $request)
    {
        $query = Movimentacao::with(['ferramenta', 'usuario', 'almoxarife']);

        if ($request->filled('data')) {
            $query->whereDate('created_at', $request->data);
        }

        $movimentacoes = $query->orderBy('created_at', 'desc')->get();

        // DADOS PARA O RESUMO DO PDF
        $disponiveis = \App\Models\Ferramenta::where('status', 'disponivel')->count();
        $em_uso = \App\Models\Ferramenta::where('status', 'em_uso')->count();
        $manutencao = \App\Models\Ferramenta::where('status', 'manutencao')->count();

        if ($request->formato === 'excel') {
            return Excel::download(new MovimentacaoExport($movimentacoes), 'relatorio_marilan.xlsx');
        }

        if ($request->formato === 'pdf') {
        // Gera um código único (ex: DOC-8F4A2B9C) para o rodapé do documento
        $codigoDocumento = 'DOC-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        
        $pdf = Pdf::loadView('relatorios.pdf', compact('movimentacoes', 'disponiveis', 'em_uso', 'manutencao', 'codigoDocumento'));
        return $pdf->download('Relatorio_Marilan_'.$codigoDocumento.'.pdf');
    }

        return response()->json($movimentacoes);
    }
}
