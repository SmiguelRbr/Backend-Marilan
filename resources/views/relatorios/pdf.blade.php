<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Gerencial - Marilan</title>
    <style>
        /* Margens do PDF */
        @page { margin: 30px 40px; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; line-height: 1.4; }
        
        /* Cabeçalho seguro em Tabela */
        .header-table { width: 100%; border-bottom: 2px solid #F26419; padding-bottom: 10px; margin-bottom: 20px; }
        .header-table td { vertical-align: middle; }
        .company-info { text-align: right; font-size: 9px; color: #555; }
        .company-name { font-size: 12px; font-weight: bold; color: #1a1c23; margin-bottom: 3px; }

        /* Título */
        .report-title { text-align: center; color: #F26419; font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 20px; letter-spacing: 1px; }

        /* KPIs usando Tabela (Para não quebrar no DomPDF) */
        .kpi-table { width: 100%; margin-bottom: 25px; border-collapse: separate; border-spacing: 10px 0; margin-left: -10px; }
        .kpi-cell { background-color: #FEF0E8; border: 1px solid #F26419; border-radius: 4px; padding: 12px; text-align: center; width: 33.3%; }
        .kpi-label { font-size: 8px; font-weight: bold; color: #D95311; text-transform: uppercase; margin-bottom: 4px; display: block; }
        .kpi-value { font-size: 22px; font-weight: bold; color: #1a1c23; }

        /* Tabela Principal */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th { background-color: #F26419; color: #ffffff; font-size: 9px; text-transform: uppercase; padding: 8px; text-align: left; border: 1px solid #D95311; }
        .data-table td { padding: 8px; border: 1px solid #ddd; font-size: 9px; vertical-align: middle; }
        .data-table tbody tr:nth-child(even) { background-color: #f9f9f9; }

        /* Estilização das Tags/Badges */
        .badge { font-weight: bold; font-size: 8px; text-transform: uppercase; padding: 4px 6px; border-radius: 2px; }
        .saida { color: #b91c1c; background-color: #fee2e2; border: 1px solid #fca5a5; }
        .entrada { color: #15803d; background-color: #dcfce7; border: 1px solid #86efac; }
        .troca { color: #1d4ed8; background-color: #dbeafe; border: 1px solid #93c5fd; }
        
        .code-font { font-family: 'Courier New', Courier, monospace; font-size: 10px; font-weight: bold; color: #555; }

        /* Rodapé de Auditoria */
        .footer { position: fixed; bottom: -10px; left: 0; width: 100%; text-align: center; font-size: 8px; color: #888; border-top: 1px solid #ddd; padding-top: 10px; }
        .auth-hash { font-family: 'Courier New', Courier, monospace; color: #D95311; font-weight: bold; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <h1 style="color: #F26419; margin: 0; font-size: 24px; letter-spacing: -1px;">Marilan<span style="color: #333;">ERP</span></h1>
            </td>
            <td class="company-info" style="width: 50%;">
                <div class="company-name">MARILAN ALIMENTOS S.A.</div>
                Setor: Almoxarifado Central e Manutenção<br>
                Emissão: {{ now()->format('d/m/Y \à\s H:i') }}<br>
                Operador: {{ Auth::user()->nome ?? 'Sistema' }}
            </td>
        </tr>
    </table>

    <div class="report-title">
        Auditoria e Fluxo de Ferramental
    </div>

    <table class="kpi-table">
        <tr>
            <td class="kpi-cell">
                <span class="kpi-label">Ativos em Estoque</span>
                <span class="kpi-value">{{ $disponiveis }}</span>
            </td>
            <td class="kpi-cell">
                <span class="kpi-label">Ativos em Operação (Campo)</span>
                <span class="kpi-value">{{ $em_uso }}</span>
            </td>
            <td class="kpi-cell">
                <span class="kpi-label">Aguardando Manutenção</span>
                <span class="kpi-value">{{ $manutencao }}</span>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%">Data / Hora</th>
                <th style="width: 25%">Colaborador Responsável</th>
                <th style="width: 30%">Equipamento / Patrimônio</th>
                <th style="width: 15%">Operação</th>
                <th style="width: 15%">Liberado Por</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimentacoes as $mov)
            <tr>
                <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    <b>{{ $mov->usuario->nome }}</b><br>
                    <span style="color:#777; font-size: 8px;">Crachá: {{ $mov->usuario->cracha }}</span>
                </td>
                <td>
                    {{ $mov->ferramenta->descricao }}<br>
                    <span class="code-font">#{{ $mov->ferramenta->codigo_patrimonio }}</span>
                </td>
                <td>
                    @if($mov->status === 'aberto' && $mov->almoxarife_id)
                        <span class="badge saida">RETIRADA</span>
                    @elseif($mov->status === 'aberto' && !$mov->almoxarife_id)
                        <span class="badge troca">TRANSFERÊNCIA</span>
                    @else
                        <span class="badge entrada">DEVOLUÇÃO</span>
                    @endif
                </td>
                <td>{{ $mov->almoxarife->nome ?? 'TROCA DIRETA' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Documento gerado automaticamente via Sistema de Gestão de Ferramental.<br>
        Chave de Autenticidade do Documento: <span class="auth-hash">{{ $codigoDocumento }}</span>
    </div>

</body>
</html>