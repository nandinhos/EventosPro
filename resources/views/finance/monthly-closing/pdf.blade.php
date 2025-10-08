<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fechamento Mensal - {{ $period }}</title>
    <style>
        @page {
            margin: 15mm 10mm;
            size: A4 landscape;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            color: #1f2937;
            line-height: 1.4;
            padding: 5mm;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 3px solid #3b82f6;
        }

        .header h1 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 4px;
        }

        .header p {
            font-size: 10px;
            color: #6b7280;
        }

        .cards {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .card {
            display: table-cell;
            width: 33.33%;
            padding: 12px;
            text-align: center;
            border: 2px solid #e5e7eb;
            background: #f9fafb;
        }

        .card:nth-child(1) { border-color: #3b82f6; background: #eff6ff; }
        .card:nth-child(2) { border-color: #6366f1; background: #eef2ff; }
        .card:nth-child(3) { border-color: #8b5cf6; background: #f5f3ff; }

        .card-label {
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .card-value {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .card:nth-child(1) .card-value { color: #1e40af; }
        .card:nth-child(2) .card-value { color: #4338ca; }
        .card:nth-child(3) .card-value { color: #6d28d9; }

        .card-meta {
            font-size: 8px;
            color: #9ca3af;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
            margin-top: 15px;
            padding-bottom: 4px;
            border-bottom: 2px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7px;
            padding: 8px 6px;
            text-align: left;
            border-bottom: 2px solid #d1d5db;
            color: #374151;
        }

        td {
            padding: 6px 6px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 8px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .font-bold { font-weight: bold; }

        .text-blue { color: #2563eb; }
        .text-indigo { color: #4f46e5; }
        .text-purple { color: #7c3aed; }
        .text-green { color: #059669; }

        .bg-gray {
            background: #f9fafb;
        }

        .bg-total {
            background: #e5e7eb;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            background: #f3f4f6;
            border-radius: 10px;
            font-size: 7px;
            font-weight: bold;
        }

        .avatar {
            display: inline-block;
            width: 24px;
            height: 24px;
            background: #e0e7ff;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            font-size: 8px;
            font-weight: bold;
            color: #4338ca;
            margin-right: 8px;
            vertical-align: middle;
        }

        .footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 7px;
            text-align: center;
            color: #9ca3af;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        .page-break {
            page-break-after: always;
        }

        .content {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    {{-- CABEÇALHO --}}
    <div class="header">
        <h1>FECHAMENTO MENSAL</h1>
        <p>Período: {{ $startDate->format('d/m/Y') }} a {{ $endDate->format('d/m/Y') }}</p>
        @if($booker)
            <p><strong>Booker:</strong> {{ $booker->name }}</p>
        @endif
        <p style="font-size: 8px; margin-top: 5px;">Gerado em: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    {{-- CARDS PRINCIPAIS --}}
    <div class="cards">
        <div class="card">
            <div class="card-label">Faturamento</div>
            <div class="card-value">R$ {{ number_format($reportData['total_faturamento'] ?? 0, 2, ',', '.') }}</div>
            <div class="card-meta">{{ $reportData['total_gigs'] ?? 0 }} eventos</div>
        </div>
        <div class="card">
            <div class="card-label">Comissão Agência</div>
            <div class="card-value">R$ {{ number_format($reportData['total_comissao_agencia'] ?? 0, 2, ',', '.') }}</div>
            @php
                $percentAgencia = $reportData['total_faturamento'] > 0
                    ? ($reportData['total_comissao_agencia'] / $reportData['total_faturamento']) * 100
                    : 0;
            @endphp
            <div class="card-meta">{{ number_format($percentAgencia, 1) }}% do faturamento</div>
        </div>
        <div class="card">
            <div class="card-label">Comissão Booker</div>
            <div class="card-value">R$ {{ number_format($reportData['total_comissao_booker'] ?? 0, 2, ',', '.') }}</div>
            @php
                $percentBooker = $reportData['total_faturamento'] > 0
                    ? ($reportData['total_comissao_booker'] / $reportData['total_faturamento']) * 100
                    : 0;
            @endphp
            <div class="card-meta">{{ number_format($percentBooker, 1) }}% do faturamento</div>
        </div>
    </div>

    {{-- TABELA 1: ANALÍTICA POR ARTISTA --}}
    <div class="section-title">ANALÍTICA POR ARTISTA</div>
    <table>
        <thead>
            <tr>
                <th style="width: 35%;">DATA | LOCAL DO EVENTO</th>
                <th class="text-right" style="width: 13%;">CACHÊ LÍQUIDO</th>
                <th class="text-right" style="width: 13%;">COMISSÃO AGÊNCIA</th>
                <th class="text-right" style="width: 13%;">COMISSÃO BOOKER</th>
                <th class="text-right" style="width: 13%;">COMISSÃO LÍQUIDA</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalCacheLiquido = 0;
                $totalComAgencia = 0;
                $totalComBooker = 0;
                $totalComLiquida = 0;
            @endphp

            @forelse($reportData['artist_data'] ?? [] as $artistGroup)
                @php
                    $totalCacheLiquido += $artistGroup['cache_liquido'];
                    $totalComAgencia += $artistGroup['comissao_agencia'];
                    $totalComBooker += $artistGroup['comissao_booker'];
                    $totalComLiquida += $artistGroup['comissao_liquida'];
                    $percentual = $reportData['total_faturamento'] > 0
                        ? ($artistGroup['cache_liquido'] / $reportData['total_faturamento']) * 100
                        : 0;
                @endphp

                {{-- Cabeçalho do Artista --}}
                <tr style="background: #e0e7ff; border-top: 2px solid #6366f1;">
                    <td colspan="5" style="padding: 8px 6px;">
                        <span class="avatar">{{ strtoupper(substr($artistGroup['artist']->name, 0, 2)) }}</span>
                        <strong>{{ $artistGroup['artist']->name }}</strong>
                        <span style="font-size: 7px; color: #6b7280;"> ({{ $artistGroup['vendas'] }} eventos)</span>
                    </td>
                </tr>

                {{-- Eventos do Artista --}}
                @foreach($artistGroup['gigs_detailed'] as $gigDetail)
                    <tr>
                        <td style="padding: 5px 6px;">
                            <strong style="font-size: 8px;">{{ $gigDetail['date']->format('d/m/Y') }} | {{ $gigDetail['location'] }}</strong>
                            <div style="font-size: 7px; color: #6b7280;">{{ $gigDetail['city_state'] }}</div>
                        </td>
                        <td class="text-right text-blue">R$ {{ number_format($gigDetail['cache_liquido'], 2, ',', '.') }}</td>
                        <td class="text-right text-indigo">R$ {{ number_format($gigDetail['comissao_agencia'], 2, ',', '.') }}</td>
                        <td class="text-right text-purple">R$ {{ number_format($gigDetail['comissao_booker'], 2, ',', '.') }}</td>
                        <td class="text-right text-green">R$ {{ number_format($gigDetail['comissao_liquida'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach

                {{-- Subtotal do Artista --}}
                <tr class="bg-gray" style="font-weight: bold;">
                    <td>
                        SUBTOTAL {{ strtoupper($artistGroup['artist']->name) }}
                        <span style="font-size: 7px; font-weight: normal;"> ({{ number_format($percentual, 1) }}% do total)</span>
                    </td>
                    <td class="text-right text-blue">R$ {{ number_format($artistGroup['cache_liquido'], 2, ',', '.') }}</td>
                    <td class="text-right text-indigo">R$ {{ number_format($artistGroup['comissao_agencia'], 2, ',', '.') }}</td>
                    <td class="text-right text-purple">R$ {{ number_format($artistGroup['comissao_booker'], 2, ',', '.') }}</td>
                    <td class="text-right text-green">R$ {{ number_format($artistGroup['comissao_liquida'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px; color: #9ca3af;">
                        Nenhum artista encontrado no período selecionado
                    </td>
                </tr>
            @endforelse

            @if(count($reportData['artist_data'] ?? []) > 0)
                <tr class="bg-total">
                    <td><strong>TOTAL GERAL</strong></td>
                    <td class="text-right text-blue">R$ {{ number_format($totalCacheLiquido, 2, ',', '.') }}</td>
                    <td class="text-right text-indigo">R$ {{ number_format($totalComAgencia, 2, ',', '.') }}</td>
                    <td class="text-right text-purple">R$ {{ number_format($totalComBooker, 2, ',', '.') }}</td>
                    <td class="text-right text-green">R$ {{ number_format($totalComLiquida, 2, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- TABELA 2: ANALÍTICA POR BOOKER --}}
    <div class="section-title">ANALÍTICA POR BOOKER</div>
    <table>
        <thead>
            <tr>
                <th style="width: 50%;">DATA | ARTISTA @ LOCAL DO EVENTO</th>
                <th class="text-right" style="width: 25%;">CACHÊ LÍQUIDO</th>
                <th class="text-right" style="width: 25%;">COMISSÃO BOOKER</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalBookerCache = 0;
                $totalBookerCom = 0;
            @endphp

            @forelse($reportData['booker_data'] ?? [] as $bookerGroup)
                @php
                    $totalBookerCache += $bookerGroup['cache_liquido'];
                    $totalBookerCom += $bookerGroup['comissao_booker'];
                    $percentual = $reportData['total_faturamento'] > 0
                        ? ($bookerGroup['cache_liquido'] / $reportData['total_faturamento']) * 100
                        : 0;
                @endphp

                {{-- Cabeçalho do Booker --}}
                <tr style="background: #f3e8ff; border-top: 2px solid #7c3aed;">
                    <td colspan="3" style="padding: 8px 6px;">
                        <span class="avatar" style="background: #7c3aed; color: white;">
                            {{ strtoupper(substr($bookerGroup['booker']->name, 0, 2)) }}
                        </span>
                        <strong>{{ $bookerGroup['booker']->name }}</strong>
                        <span style="font-size: 7px; color: #6b7280;"> ({{ $bookerGroup['vendas'] }} eventos)</span>
                    </td>
                </tr>

                {{-- Eventos do Booker --}}
                @foreach($bookerGroup['gigs_detailed'] as $gigDetail)
                    <tr>
                        <td style="padding: 5px 6px;">
                            <strong style="font-size: 8px;">{{ $gigDetail['date']->format('d/m/Y') }} | {{ $gigDetail['artist_name'] }} @ {{ $gigDetail['location'] }}</strong>
                            <div style="font-size: 7px; color: #6b7280;">{{ $gigDetail['city_state'] }}</div>
                        </td>
                        <td class="text-right text-blue">R$ {{ number_format($gigDetail['cache_liquido'], 2, ',', '.') }}</td>
                        <td class="text-right text-purple">R$ {{ number_format($gigDetail['comissao_booker'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach

                {{-- Subtotal do Booker --}}
                <tr class="bg-gray" style="font-weight: bold;">
                    <td>
                        SUBTOTAL {{ strtoupper($bookerGroup['booker']->name) }}
                        <span style="font-size: 7px; font-weight: normal;"> ({{ number_format($percentual, 1) }}% do total)</span>
                    </td>
                    <td class="text-right text-blue">R$ {{ number_format($bookerGroup['cache_liquido'], 2, ',', '.') }}</td>
                    <td class="text-right text-purple">R$ {{ number_format($bookerGroup['comissao_booker'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="padding: 20px; color: #9ca3af;">
                        Nenhum booker encontrado no período selecionado
                    </td>
                </tr>
            @endforelse

            @if(count($reportData['booker_data'] ?? []) > 0)
                <tr class="bg-total">
                    <td><strong>TOTAL GERAL</strong></td>
                    <td class="text-right text-blue">R$ {{ number_format($totalBookerCache, 2, ',', '.') }}</td>
                    <td class="text-right text-purple">R$ {{ number_format($totalBookerCom, 2, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- RODAPÉ --}}
    <div class="footer">
        <p>EventosPro - Sistema de Gestão de Eventos | Relatório gerado automaticamente</p>
        <p>Este é um documento confidencial. Todos os valores são baseados nos cálculos do GigFinancialCalculatorService</p>
    </div>
</body>
</html>
