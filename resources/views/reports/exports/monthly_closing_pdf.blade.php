<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Fechamento Mensal - {{ $month }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #666;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            color: #111;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
        }
        .summary {
            margin-bottom: 20px;
            overflow: hidden;
        }
        .summary-item {
            float: left;
            width: 24%;
            text-align: center;
            padding: 10px;
            border: 1px solid #eee;
            margin-right: 1%;
            margin-bottom: 10px;
            box-sizing: border-box;
        }
        .summary-item:last-child {
            margin-right: 0;
        }
        .summary-item h3 {
            font-size: 10px;
            margin: 0 0 5px 0;
            color: #666;
        }
        .summary-item .value {
            font-size: 14px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: left;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .mt-4 {
            margin-top: 16px;
        }
        .mb-4 {
            margin-bottom: 16px;
        }
        .page-break {
            page-break-after: always;
        }
        .chart-container {
            width: 100%;
            height: 300px;
            margin: 20px 0;
        }
        .signature {
            margin-top: 40px;
            border-top: 1px solid #333;
            padding-top: 10px;
            width: 60%;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Fechamento Mensal - {{ $month }}</h1>
        @if($booker)
            <p>Booker: {{ $booker->name }}</p>
        @endif
        <p>Gerado em: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <!-- Resumo -->
    <div class="summary">
        <div class="summary-item">
            <h3>Total de Gigs</h3>
            <div class="value">{{ $reportData['total_gigs'] }}</div>
        </div>
        <div class="summary-item">
            <h3>Total Cachê Bruto</h3>
            <div class="value">R$ {{ number_format($reportData['total_cache_brl'], 2, ',', '.') }}</div>
        </div>
        <div class="summary-item">
            <h3>Total Comissões Booker</h3>
            <div class="value">R$ {{ number_format($reportData['total_booker_commission'], 2, ',', '.') }}</div>
        </div>
        <div class="summary-item">
            <h3>Total Comissões Agência</h3>
            <div class="value">R$ {{ number_format($reportData['total_agency_commission'], 2, ',', '.') }}</div>
        </div>
    </div>



    <!-- Detalhes das Gigs -->
    <div class="page-break"></div>
    <h3>Detalhes das Gigs</h3>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Artista</th>
                <th>Local</th>
                <th class="text-right">Cachê Bruto</th>
                <th class="text-right">Comissão Booker</th>
                <th class="text-right">Comissão Agência</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reportData['gigs'] as $gig)
            <tr>
                <td>{{ $gig->gig_date->format('d/m/Y') }}</td>
                <td>{{ $gig->artist->name }}</td>
                <td>{{ $gig->location_event_details }} - {{ $gig->location_city }}/{{ $gig->location_state }}</td>
                <td class="text-right">R$ {{ number_format($gig->cache_value_brl, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($gig->booker_commission_value, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($gig->agency_commission_value, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Nenhuma gig encontrada para o período selecionado</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">TOTAIS:</th>
                <th class="text-right">R$ {{ number_format($reportData['total_cache_brl'], 2, ',', '.') }}</th>
                <th class="text-right">R$ {{ number_format($reportData['total_booker_commission'], 2, ',', '.') }}</th>
                <th class="text-right">R$ {{ number_format($reportData['total_agency_commission'], 2, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <!-- Assinatura -->
    <div class="signature">
        <p>_________________________________________</p>
        <p>Assinatura do Responsável</p>
    </div>
</body>
</html>
