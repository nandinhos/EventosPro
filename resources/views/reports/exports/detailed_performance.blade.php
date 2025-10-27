<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Relatório de Performance de Gigs</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Relatório de Performance de Gigs</h1>
    <p>Período: {{ \Carbon\Carbon::parse($filters['start_date'])->isoFormat('L') }} a {{ \Carbon\Carbon::parse($filters['end_date'])->isoFormat('L') }}</p>
    <table>
        <thead>
            <tr>
                <th>Data Gig</th>
                <th>Artista</th>
                <th>Booker</th>
                <th>Cachê (BRL)</th>
                <th>Despesas</th>
                <th>Cachê Líq. Base</th>
                <th>Com. Ag. Líquida</th>
                <th>Repasse Artista</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reportData['tableData'] as $row)
                <tr>
                    <td>{{ $row['gig_date'] }}</td>
                    <td>{{ $row['artist_name'] }}</td>
                    <td>{{ $row['booker_name'] }}</td>
                    <td class="text-right">{{ number_format($row['cache_bruto_brl'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['total_despesas_confirmadas_brl'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['cache_liquido_base_brl'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['comissao_agencia_liquida_brl'], 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['repasse_estimado_artista_brl'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold; background-color: #f2f2f2;">
                <td colspan="3" class="text-right">TOTAIS</td>
                <td class="text-right">R$ {{ number_format($reportData['totals']['cache_bruto_brl'], 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($reportData['totals']['total_despesas_confirmadas_brl'], 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($reportData['totals']['cache_liquido_base_brl'], 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($reportData['totals']['comissao_agencia_liquida_brl'], 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($reportData['totals']['repasse_estimado_artista_brl'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>