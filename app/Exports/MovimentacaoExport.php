<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MovimentacaoExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $movimentacoes;

    public function __construct($movimentacoes)
    {
        $this->movimentacoes = $movimentacoes;
    }

    public function collection()
    {
        return $this->movimentacoes;
    }

    public function headings(): array
    {
        return [
            'CRACHÁ', 'NOME', 'OFICINA', 'TURNO', 'CÓD. FERRAMENTA', 
            'DESC. FERRAMENTA', 'QTD', 'CHECKLIST', 'ALMOXARIFE RESP.', 
            'DATA', 'MOVIMENTAÇÃO', 'OBSERVAÇÃO'
        ];
    }

    public function map($mov): array
    {
        return [
            $mov->usuario->cracha ?? '-',
            $mov->usuario->nome ?? '-',
            $mov->usuario->oficina ?? '-',
            $mov->usuario->turno ?? '-',
            $mov->ferramenta->codigo_patrimonio ?? '-',
            $mov->ferramenta->descricao ?? '-',
            $mov->qtd,
            $mov->checklist,
            $mov->almoxarife->nome ?? 'TROCA DIRETA',
            $mov->created_at->format('d/m/Y'),
            $mov->status === 'aberto' ? 'Saída' : 'Entrada',
            $mov->observacao ?? ''
        ];
    }
}