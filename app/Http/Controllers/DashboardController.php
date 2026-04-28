<?php

namespace App\Http\Controllers;

use App\Models\Ferramenta;
use App\Models\Movimentacao;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. KPIs (Indicadores Principais)
        $kpis = [
            'total' => Ferramenta::count(),
            'disponiveis' => Ferramenta::where('status', 'disponivel')->count(),
            'em_uso' => Ferramenta::where('status', 'em_uso')->count(),
            'manutencao' => Ferramenta::where('status', 'manutencao')->count(),
        ];

        // 2. Últimas Movimentações para a Tabela
        $ultimasMovimentacoes = Movimentacao::with(['ferramenta', 'usuario', 'almoxarife'])
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        // 3. Dados para o Gráfico de Linha (Movimentações dos últimos 7 dias)
        $dias = [];
        $saidas = [];
        $entradas = [];

        for ($i = 6; $i >= 0; $i--) {
            $data = Carbon::today()->subDays($i);
            $dias[] = $data->format('d/m');
            
            $saidas[] = Movimentacao::whereDate('created_at', $data)->count();
            $entradas[] = Movimentacao::whereDate('data_devolucao', $data)->count();
        }

        $graficoLinha = [
            'labels' => $dias,
            'saidas' => $saidas,
            'entradas' => $entradas
        ];

        return view('dashboard', compact('kpis', 'ultimasMovimentacoes', 'graficoLinha'));
    }
}