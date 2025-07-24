<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Desempenho por Booker</title>
    <style>
        @page { margin: 30px; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #333;
            font-size: 9px;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 10px;
            margin-bottom: 25px;
            position: relative;
        }

        .header .logo {
            position: absolute;
            top: -15px;
            left: 0;
            max-height: 105px;
        }

        .header h1 {
            font-size: 22px;
            margin: 0;
            color: #1f2937;
        }

        .header p {
            font-size: 10px;
            margin: 5px 0 0 0;
            color: #6b7280;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .summary-table th {
            background-color: #f3f4f6;
            color: #4b5563;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
        }

        .summary-table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .text-right {
            text-align: right;
        }

        .summary-table tfoot td {
            font-weight: bold;
            font-size: 11px;
            background-color: #f3f4f6;
            color: #111827;
        }

        .summary-table tfoot .total-value {
            color: #4f46e5;
        }

        .summary-table tfoot .total-gross-cash {
            color: #059669;
        }

        .page-break {
            page-break-after: always;
        }

        .analytical-title {
            font-size: 18px;
            color: #1f2937;
            margin-top: 0;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 5px;
        }

        .booker-group {
            page-break-inside: avoid;
            margin-bottom: 25px;
        }

        .booker-header {
            font-size: 15px;
            font-weight: bold;
            color: #4f46e5;
            margin: 0;
            padding: 5px 0;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table th {
            color: #6b7280;
            padding: 6px 4px;
            text-align: left;
            font-size: 9px;
            border-bottom: 1px solid #d1d5db;
        }

        .details-table td {
            padding: 6px 4px;
            border-bottom: 1px solid #f3f4f6;
        }

        .details-table .text-right {
            text-align: right;
        }

        .details-table .subtotal-row td {
            font-weight: bold;
            background-color: #f9fafb;
            border-top: 1px solid #d1d5db;
        }

        .subtotal-contract {
            color: #1e40af;
        }

        .subtotal-gross-cash {
            color: #047857;
        }
    </style>
</head>
<body>
    <!-- CABEÇALHO -->
    <div class="header">
        <img src="{{ public_path('img/coral_360_logo.png') }}" alt="Logo" class="logo">
        <h1>Relatório de Desempenho de Vendas</h1>
        <p>Período: {{ \Carbon\Carbon::parse($filters['start_date'] ?? now()->startOfMonth())->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($filters['end_date'] ?? now()->endOfMonth())->format('d/m/Y') }}</p>
        <p style="font-size: 8px; color: #9ca3af;">
            Gerado em: {{ now()->format('d/m/Y H:i') }}
            @if(auth()->check())
                por: {{ auth()->user()->name }}
            @endif
        </p>
    </div>

    <!-- RESUMO POR BOOKER -->
    <h2 class="analytical-title">Resumo por Booker</h2>
    <table class="summary-table">
        <thead>
            <tr>
                <th>Booker</th>
                <th class="text-right">Qtd. Gigs</th>
                <th class="text-right">Total Contrato (BRL)</th>
                <th class="text-right">Total Cachê Bruto (BRL)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($performanceData['tableData'] as $bookerData)
                <tr>
                    <td>{{ $bookerData['booker_name'] }}</td>
                    <td class="text-right">{{ $bookerData['gigs_count'] }}</td>
                    <td class="text-right">R$ {{ number_format($bookerData['total_contract'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($bookerData['total_gross_cash'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAL GERAL</td>
                <td class="text-right">{{ $performanceData['summaryCards']['total_gigs'] }}</td>
                <td class="text-right total-value">R$ {{ number_format($performanceData['summaryCards']['total_value'], 2, ',', '.') }}</td>
                <td class="text-right total-gross-cash">R$ {{ number_format($performanceData['summaryCards']['total_gross_cash'], 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="page-break"></div>

    <!-- DETALHES POR BOOKER -->
    <h2 class="analytical-title">Detalhes por Booker</h2>
    @foreach($performanceData['tableData'] as $bookerData)
        <div class="booker-group">
            <h3 class="booker-header">{{ $bookerData['booker_name'] }}</h3>
            <table class="details-table">
                <thead>
                    <tr>
                        <th style="width: 12%;">Data Venda</th>
                        <th style="width: 12%;">Data Evento</th>
                        <th style="width: 46%;">Artista - Local do Evento</th>
                        <th class="text-right" style="width: 15%;">Valor Contrato</th>
                        <th class="text-right" style="width: 15%;">Cachê Bruto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookerData['gigs'] as $gig)
                        <tr>
                            <td>{{ $gig['sale_date'] }}</td>
                            <td>{{ $gig['gig_date'] }}</td>
                            <td>{{ $gig['artist_local'] }}</td>
                            <td class="text-right">R$ {{ number_format($gig['contract_value'], 2, ',', '.') }}</td>
                            <td class="text-right">R$ {{ number_format($gig['gross_cash_brl'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="3" class="text-right">SUBTOTAL</td>
                        <td class="text-right subtotal-contract">R$ {{ number_format($bookerData['total_contract'], 2, ',', '.') }}</td>
                        <td class="text-right subtotal-gross-cash">R$ {{ number_format($bookerData['total_gross_cash'], 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

</body>
</html>
