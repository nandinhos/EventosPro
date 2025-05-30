<!DOCTYPE html>
<html>
<head>
    <title>Relatório de Visão Geral</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <h1>Relatório de Visão Geral</h1>
    <p>Período: {{ $filters['start_date'] ?? 'N/A' }} a {{ $filters['end_date'] ?? 'N/A' }}</p>
    <h2>Resumo</h2>
    <p>Faturamento Total: R$ {{ number_format($data['summary']['total_revenue'], 2, ',', '.') }}</p>
    <p>Comissões: R$ {{ number_format($data['summary']['total_commissions'], 2, ',', '.') }}</p>
    <p>Despesas: R$ {{ number_format($data['summary']['total_expenses'], 2, ',', '.') }}</p>
    <p>Lucro Líquido: R$ {{ number_format($data['summary']['net_profit'], 2, ',', '.') }}</p>

    <h2>Tabela de Detalhes</h2>
    <table>
        <thead>
            <tr>
                <th>Contrato</th>
                <th>Data</th>
                <th>Artista</th>
                <th>Booker</th>
                <th>Receita (BRL)</th>
                <th>Custos (BRL)</th>
                <th>Comissão (BRL)</th>
                <th>Lucro Líquido (BRL)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['table'] as $row)
                <tr>
                    <td>{{ $row['contract_number'] ?? 'N/A' }}</td>
                    <td>{{ $row['gig_date'] }}</td>
                    <td>{{ $row['artist'] }}</td>
                    <td>{{ $row['booker'] }}</td>
                    <td>R$ {{ number_format($row['revenue'], 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($row['costs'], 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($row['commission'], 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($row['net_profit'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>