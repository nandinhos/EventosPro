<!DOCTYPE html>
<html>
<head>
    <title>Relatório Financeiro - {{ $filters['start_date'] }} a {{ $filters['end_date'] }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1, h2, h3 { margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { font-weight: bold; }
        .summary { margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Relatório Financeiro</h1>
    <p>Período: {{ $filters['start_date'] }} a {{ $filters['end_date'] }}</p>

    <div class="summary">
        <h2>Faturamento {{ now()->isoFormat('MMM-YYYY') }}</h2>
        <p>Faturamento: R$ {{ number_format($data['total_revenue'], 2, ',', '.') }} ({{ $data['total_events'] }} datas)</p>
        <p>Coral 360: R$ {{ number_format($data['net_revenue'], 2, ',', '.') }}</p>
        <p>Bookers: R$ {{ number_format($data['total_booker_commissions'], 2, ',', '.') }}</p>
    </div>

    <h2>Faturamento por Booker</h2>
    @foreach ($data['revenue_by_booker'] as $booker => $revenue)
        <h3>{{ $booker }}</h3>
        <p>R$ {{ number_format($revenue, 2, ',', '.') }}</p>
    @endforeach

    <h2>Eventos por Artista</h2>
    @foreach ($data['events_by_artist'] as $artist => $events)
        <h3>{{ $artist }}</h3>
        @foreach ($events as $event)
            <p>{{ $event['date'] }} - {{ $event['location'] }}</p>
            <table>
                <tr>
                    <td>Cachê Bruto</td>
                    <td>R$ {{ number_format($event['contract_value'], 2, ',', '.') }}</td>
                    <td>Comissão Agência</td>
                    <td>R$ {{ number_format($event['agency_commission'], 2, ',', '.') }}</td>
                    <td>Comissão Booker</td>
                    <td>R$ {{ number_format($event['booker_commission'], 2, ',', '.') }}</td>
                    <td>Cachê Líquido</td>
                    <td>R$ {{ number_format($event['net_cache'], 2, ',', '.') }}</td>
                    <td>Eventos</td>
                    <td>{{ $event['event_count'] }}</td>
                </tr>
            </table>
        @endforeach
    @endforeach

    <h2>Resumo Financeiro</h2>
    <p>Total Geral:</p>
    <p>Faturamento: R$ {{ number_format($data['total_revenue'], 2, ',', '.') }}</p>
    <p>Comissões Agência: R$ {{ number_format($data['total_agency_commissions'], 2, ',', '.') }}</p>
    <p>Comissões Bookers: R$ {{ number_format($data['total_booker_commissions'], 2, ',', '.') }}</p>
    <p>Lucro Líquido: R$ {{ number_format($data['net_revenue'], 2, ',', '.') }}</p>
    <p>Total de Eventos: {{ $data['total_events'] }}</p>

    <h3>Despesas Operacionais</h3>
    <table>
        <thead>
            <tr>
                <th>Descrição</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['operational_expenses'] as $expense)
                @foreach ($expense['details'] as $detail)
                    <tr>
                        <td>{{ $detail['description'] }}</td>
                        <td>-R$ {{ number_format($detail['value'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr class="total">
                <td>Total Geral</td>
                <td>-R$ {{ number_format($data['total_operational_expenses'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <p>Faturamento Líquido: R$ {{ number_format($data['net_revenue'], 2, ',', '.') }}</p>
    <p>Resultado Operacional: R$ {{ number_format($data['operational_result'], 2, ',', '.') }}</p>
</body>
</html>