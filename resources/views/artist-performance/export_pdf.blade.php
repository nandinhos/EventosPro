<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Desempenho de Artistas</title>
    <style>
        /* CONFIGURAÇÕES GERAIS */
        @page { margin: 20px; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #374151;
            font-size: 7px;
            line-height: 1.3;
        }

        /* CABEÇALHO PADRONIZADO */
        .header {
            text-align: center;
            position: relative;
            padding-bottom: 8px;
            margin-bottom: 20px;
            border-bottom: 2px solid #6366f1;
        }
        .header .logo { position: absolute; top: -25px; left: 0; max-height: 100px; }
        .header h1 { font-size: 18px; margin: 0; color: #1f2937; }
        .header p { font-size: 9px; margin: 4px 0 0 0; color: #6b7280; }

        /* CARDS DE RESUMO */
        .summary-cards {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-card {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
        }
        .summary-card h3 {
            font-size: 8px;
            color: #6b7280;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .summary-card p {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            margin: 0;
        }

        /* ESTILO DA TABELA PRINCIPAL */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .main-table th {
            background-color: #f3f4f6;
            color: #4b5563;
            padding: 5px 3px;
            text-align: left;
            font-size: 7px;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
        }
        .main-table td {
            padding: 5px 3px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .main-table .text-right { text-align: right; }
        .main-table .font-bold { font-weight: bold; }

        /* ESTILOS DAS LINHAS ESPECIAIS */
        .group-header td {
            background-color: #eef2ff;
            color: #4338ca;
            font-size: 10px;
            font-weight: bold;
            padding: 6px 8px;
            border-bottom: 1px solid #c7d2fe;
            border-top: 2px solid #a5b4fc;
        }
        .subtotal-row td {
            background-color: #f9fafb;
            font-weight: bold;
            padding-top: 6px;
            padding-bottom: 6px;
        }
        .grand-total-row td {
            background-color: #eef2ff;
            color: #1e3a8a;
            font-weight: bold;
            font-size: 9px;
            border-top: 2px solid #6366f1;
        }
        .detail-row {
            font-size: 7px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('img/coral_360_logo.png') }}" alt="Logo" class="logo">
        <h1>Relatório de Desempenho de Artistas</h1>
        <p>Período: {{ isset($filters['start_date']) ? \Carbon\Carbon::parse($filters['start_date'])->isoFormat('L') : 'N/A' }} a {{ isset($filters['end_date']) ? \Carbon\Carbon::parse($filters['end_date'])->isoFormat('L') : 'N/A' }}</p>
        @if(isset($filters['artist_id']) && $filters['artist_id'])
            <p>Artista Selecionado: {{ \App\Models\Artist::find($filters['artist_id'])->name ?? 'N/A' }}</p>
        @endif
        <p style="font-size: 8px; color: #9ca3af;">
            Gerado em: {{ now()->isoFormat('L LT') }}
            @if(auth()->check())
                por: {{ auth()->user()->name }}
            @endif
        </p>
    </div>

    @php
        $summaryCards = $performanceData['summaryCards'] ?? [];
        $tableData = $performanceData['tableData'] ?? collect([]);
    @endphp

    <!-- Cards de Resumo -->
    <div class="summary-cards">
        <div class="summary-card">
            <h3>Total de Gigs</h3>
            <p>{{ $summaryCards['total_gigs'] ?? 0 }}</p>
        </div>
        <div class="summary-card">
            <h3>Valor Contrato</h3>
            <p>R$ {{ number_format($summaryCards['total_value'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="summary-card">
            <h3>Cachê Bruto Total</h3>
            <p>R$ {{ number_format($summaryCards['total_gross_cash'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="summary-card">
            <h3>Repasse Líquido Total</h3>
            <p>R$ {{ number_format($summaryCards['total_net_payout'] ?? 0, 2, ',', '.') }}</p>
        </div>
    </div>

    <!-- Tabela de Detalhes por Artista -->
    <table class="main-table">
        <thead>
            <tr>
                <th>Data Venda</th>
                <th>Data Gig</th>
                <th>Booker</th>
                <th style="width: 18%;">Local/Evento</th>
                <th class="text-right">Valor Contrato</th>
                <th class="text-right">Cachê Bruto</th>
                <th class="text-right">Repasse Líquido</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tableData as $artistData)
            <!-- Cabeçalho do Artista -->
            <tr class="group-header">
            <td colspan="7">
            {{ $artistData['artist_name'] }} - {{ $artistData['gigs_count'] }} gigs
            </td>
            </tr>

            <!-- Agrupamento por mês -->
            @foreach ($artistData['gigs_by_month'] as $monthData)
            <!-- Cabeçalho do Mês -->
            <tr class="month-header">
            <td colspan="7" style="background-color: #e0e7ff; border-top: 2px solid #6366f1; padding: 8px; font-weight: bold; color: #3730a3;">
                {{ $monthData['month_name'] }} - {{ $monthData['month_gigs_count'] }} evento{{ $monthData['month_gigs_count'] > 1 ? 's' : '' }}
                <span style="float: right; font-size: 9px;">Subtotal: R$ {{ number_format($monthData['month_total_net_payout'], 2, ',', '.') }}</span>
            </td>
            </tr>

            <!-- Detalhes das Gigs do Mês -->
                @foreach ($monthData['gigs'] as $gig)
                        <tr class="detail-row">
                        <td>{{ $gig['sale_date'] }}</td>
                        <td>{{ $gig['gig_date'] }}</td>
                    <td>{{ $gig['booker_name'] }}</td>
                    <td>{{ $gig['location_event_details'] }}</td>
                    <td class="text-right">R$ {{ number_format($gig['contract_value'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($gig['gross_cash_brl'], 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($gig['net_payout_brl'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach

                    <!-- Subtotal do Mês -->
                    <tr class="month-subtotal">
                        <td colspan="4" class="text-right" style="border-top: 1px solid #d1d5db; font-weight: bold; background-color: #f9fafb;">Subtotal {{ $monthData['month_name'] }}:</td>
                        <td class="text-right" style="border-top: 1px solid #d1d5db; font-weight: bold; background-color: #f9fafb;">R$ {{ number_format($monthData['month_total_contract'], 2, ',', '.') }}</td>
                        <td class="text-right" style="border-top: 1px solid #d1d5db; font-weight: bold; background-color: #f9fafb;">R$ {{ number_format($monthData['month_total_gross_cash'], 2, ',', '.') }}</td>
                        <td class="text-right" style="border-top: 1px solid #d1d5db; font-weight: bold; background-color: #f9fafb;">R$ {{ number_format($monthData['month_total_net_payout'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach

                <!-- Subtotal do Artista -->
                <tr class="subtotal-row">
                    <td colspan="4" class="text-right">SUBTOTAL ({{ $artistData['gigs_count'] }} Gigs):</td>
                    <td class="text-right">R$ {{ number_format($artistData['total_contract'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($artistData['total_gross_cash'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($artistData['total_net_payout'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">Nenhum dado encontrado para os filtros selecionados.</td>
                </tr>
            @endforelse

            <!-- Total Geral -->
            @if ($summaryCards['total_gigs'] > 0)
                <tr class="grand-total-row">
                    <td colspan="4" class="text-right">TOTAL GERAL ({{ $summaryCards['total_gigs'] }} Gigs):</td>
                    <td class="text-right">R$ {{ number_format($summaryCards['total_value'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($summaryCards['total_gross_cash'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($summaryCards['total_net_payout'], 2, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
