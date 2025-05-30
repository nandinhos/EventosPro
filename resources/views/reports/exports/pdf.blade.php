<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relatório Financeiro</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h1 { text-align: center; }
    </style>
</head>
<body>
    <h1>Relatório Financeiro - {{ ucfirst($type) }}</h1>
    <table>
        <thead>
            @if ($type == 'overview' || $type == 'profitability')
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
            @elseif ($type == 'cashflow')
                <tr>
                    <th>Tipo</th>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Valor (BRL)</th>
                </tr>
            @elseif ($type == 'commissions')
                <tr>
                    <th>Contrato</th>
                    <th>Data</th>
                    <th>Comissão (BRL)</th>
                </tr>
            @elseif ($type == 'expenses')
                <tr>
                    <th>Contrato</th>
                    <th>Descrição</th>
                    <th>Data</th>
                    <th>Valor (BRL)</th>
                    <th>Moeda</th>
                </tr>
            @endif
        </thead>
        <tbody>
            @foreach ($data as $row)
                <tr>
                    @if ($type == 'overview' || $type == 'profitability')
                        <td>{{ $row['contract_number'] }}</td>
                        <td>{{ $row['gig_date'] }}</td>
                        <td>{{ $row['artist'] }}</td>
                        <td>{{ $row['booker'] }}</td>
                        <td>R$ {{ number_format($row['revenue'], 2, ',', '.') }}</td>
                        <td>R$ {{ number_format($row['costs'], 2, ',', '.') }}</td>
                        <td>R$ {{ number_format($row['commission'], 2, ',', '.') }}</td>
                        <td>R$ {{ number_format($row['net_profit'], 2, ',', '.') }}</td>
                    @elseif ($type == 'cashflow')
                        <td>{{ $row['type'] }}</td>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['description'] }}</td>
                        <td>R$ {{ number_format(abs($row['value_brl']), 2, ',', '.') }}</td>
                    @elseif ($type == 'commissions')
                        <td>{{ $row['contract_number'] }}</td>
                        <td>{{ $row['gig_date'] }}</td>
                        <td>R$ {{ number_format($row['commission'], 2, ',', '.') }}</td>
                    @elseif ($type == 'expenses')
                        <td>{{ $row['gig_contract_number'] }}</td>
                        <td>{{ $row['description'] }}</td>
                        <td>{{ $row['expense_date'] ? \Carbon\Carbon::parse($row['expense_date'])->format('d/m/Y') : 'N/A' }}</td>
                        <td>R$ {{ number_format($row['value_brl'], 2, ',', '.') }}</td>
                        <td>{{ $row['currency'] }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>