<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f4f4f4; }
        .saida { color: red; }
        .entrada { color: green; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Controle de Ferramentas - Marilan</h2>
        <p>Gerado em: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Crachá / Nome</th>
                <th>Ferramenta</th>
                <th>Qtd</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimentacoes as $mov)
            <tr>
                <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $mov->usuario->cracha }} - {{ $mov->usuario->nome }}</td>
                <td>{{ $mov->ferramenta->codigo_patrimonio }} ({{ $mov->ferramenta->descricao }})</td>
                <td>{{ $mov->qtd }}</td>
                <td class="{{ $mov->status == 'aberto' ? 'saida' : 'entrada' }}">
                    <b>{{ $mov->status == 'aberto' ? 'SAÍDA' : 'ENTRADA' }}</b>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>