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
        // Busca com os relacionamentos para não pesar o banco
        $query = Movimentacao::with(['ferramenta', 'usuario', 'almoxarife']);

        // Filtro opcional: pegar só de uma data específica
        if ($request->filled('data')) {
            $query->whereDate('created_at', $request->data);
        }

        $movimentacoes = $query->orderBy('created_at', 'desc')->get();

        // 1. Gera Excel se o Frontend pedir
        if ($request->formato === 'excel') {
            return Excel::download(new MovimentacaoExport($movimentacoes), 'relatorio_marilan.xlsx');
        }

        // 2. Gera PDF se o Frontend pedir
        if ($request->formato === 'pdf') {
            $pdf = Pdf::loadView('relatorios.pdf', compact('movimentacoes'));
            return $pdf->download('relatorio_marilan.pdf');
        }

        // 3. Padrão: devolve JSON normal para montar telas ou gráficos no App
        return response()->json($movimentacoes);
    }
}