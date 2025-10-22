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
        <p style="font-size: 8px; margin-top: 5px;">Gerado em: {{ now()->format('d/m/Y H:i') }} por {{ $generatedBy }}</p>
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

    {{-- TABELA COMPARATIVA DE ARTISTAS (VERTICAL) --}}
    @if(count($reportData['artist_comparison'] ?? []) > 0)
    <div class="section-title" style="margin-left: 10px; margin-right: 10px;">COMPARATIVO DE PERFORMANCE - ARTISTAS</div>
    <table style="margin-bottom: 20px; margin-left: 10px; margin-right: 10px; width: calc(100% - 20px);">
        <thead>
            <tr>
                <th style="width: 30%; text-align: left; padding-left: 12px;">ARTISTA</th>
                <th style="width: 15%; text-align: center;">VENDAS</th>
                <th style="width: 20%; text-align: right; padding-right: 12px;">CACHÊ LÍQUIDO</th>
                <th style="width: 20%; text-align: right; padding-right: 12px;">COMISSÃO AGÊNCIA</th>
                <th style="width: 15%; text-align: right; padding-right: 12px;">COMISSÃO LÍQUIDA</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalVendas = 0;
                $totalCacheLiquido = 0;
                $totalComissaoAgencia = 0;
                $totalComissaoLiquida = 0;
            @endphp

            @foreach($reportData['artist_comparison'] as $artist)
                @php
                    $totalVendas += $artist['vendas'];
                    $totalCacheLiquido += $artist['cache_liquido'];
                    $totalComissaoAgencia += $artist['comissao_agencia'];
                    $totalComissaoLiquida += $artist['comissao_liquida'];
                @endphp
                <tr>
                    <td style="padding: 6px 6px 6px 12px;">
                        <span class="avatar">{{ strtoupper(substr($artist['name'], 0, 2)) }}</span>
                        <strong>{{ $artist['name'] }}</strong>
                    </td>
                    <td class="text-center" style="padding: 6px;">
                        <span class="badge text-blue" style="background: #dbeafe; color: #1e40af; padding: 3px 8px;">
                            {{ $artist['vendas'] }}
                        </span>
                    </td>
                    <td class="text-right text-indigo font-bold" style="padding: 6px 12px 6px 6px;">
                        R$ {{ number_format($artist['cache_liquido'], 2, ',', '.') }}
                    </td>
                    <td class="text-right text-purple font-bold" style="padding: 6px 12px 6px 6px;">
                        R$ {{ number_format($artist['comissao_agencia'], 2, ',', '.') }}
                    </td>
                    <td class="text-right text-green font-bold" style="padding: 6px 12px 6px 6px;">
                        R$ {{ number_format($artist['comissao_liquida'], 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach

            {{-- Linha de Total --}}
            <tr style="background: #3b82f6; color: white; font-weight: bold;">
                <td style="padding: 8px 6px 8px 12px;">TOTAL GERAL</td>
                <td class="text-center" style="padding: 8px 6px;">{{ $totalVendas }}</td>
                <td class="text-right" style="padding: 8px 12px 8px 6px;">R$ {{ number_format($totalCacheLiquido, 2, ',', '.') }}</td>
                <td class="text-right" style="padding: 8px 12px 8px 6px;">R$ {{ number_format($totalComissaoAgencia, 2, ',', '.') }}</td>
                <td class="text-right" style="padding: 8px 12px 8px 6px;">R$ {{ number_format($totalComissaoLiquida, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- TABELA COMPARATIVA DE BOOKERS (HORIZONTAL) --}}
    @if(count($reportData['booker_comparison'] ?? []) > 0)
    <div class="section-title">VENDAS NO PERÍODO - BOOKERS</div>
    <table style="margin-bottom: 20px;">
        <thead>
            <tr>
                <th style="width: 20%; background: #d1d5db;">INDICADOR</th>
                @foreach($reportData['booker_comparison'] as $booker)
                    <th class="text-right" style="width: {{ 80 / count($reportData['booker_comparison']) }}%;">
                        {{ strtoupper($booker['name']) }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            {{-- Linha: Vendas --}}
            <tr>
                <td class="font-bold" style="background: #f3f4f6;">Vendas</td>
                @foreach($reportData['booker_comparison'] as $booker)
                    <td class="text-right text-blue font-bold">{{ $booker['vendas'] }}</td>
                @endforeach
            </tr>

            {{-- Linha: Cachê Bruto --}}
            <tr>
                <td class="font-bold" style="background: #f3f4f6;">Cachê Bruto</td>
                @foreach($reportData['booker_comparison'] as $booker)
                    <td class="text-right text-indigo font-bold">R$ {{ number_format($booker['cache_bruto'], 2, ',', '.') }}</td>
                @endforeach
            </tr>

            {{-- Linha: Comissão Booker --}}
            <tr>
                <td class="font-bold" style="background: #f3f4f6;">Comissão Booker</td>
                @foreach($reportData['booker_comparison'] as $booker)
                    <td class="text-right text-purple font-bold">R$ {{ number_format($booker['cache_booker'], 2, ',', '.') }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
    @endif

    {{-- TABELA COMPARATIVA DE DESEMPENHO - BOOKERS (HORIZONTAL) --}}
    @if(count($reportData['booker_comparison'] ?? []) > 0)
    <div class=\"section-title\">COMPARATIVO DE DESEMPENHO - BOOKERS</div>
    <table style=\"margin-bottom: 20px;\">
        <thead>
            <tr>
                <th style=\"width: 20%; background: #d1d5db;\">INDICADOR</th>
                @foreach($reportData['booker_comparison'] as $booker)
                    <th class=\"text-right\" style=\"width: {{ 80 / count($reportData['booker_comparison']) }}%;\">
                        {{ strtoupper($booker['name']) }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            {{-- Linha: Contratos Celebrados --}}
            <tr>
                <td class=\"font-bold\" style=\"background: #f3f4f6;\">Contratos Celebrados</td>
                @foreach($reportData['booker_comparison'] as $booker)
                    <td class=\"text-right text-blue font-bold\">{{ $booker['vendas'] }}</td>
                @endforeach
            </tr>

            {{-- Linha: Cachê Bruto --}}
            <tr>
                <td class=\"font-bold\" style=\"background: #f3f4f6;\">Cachê Bruto</td>
                @foreach($reportData['booker_comparison'] as $booker)
                    <td class=\"text-right text-indigo font-bold\">R$ {{ number_format($booker['cache_bruto'], 2, ',', '.') }}</td>
                @endforeach
            </tr>

            {{-- Linha: Comissão Booker --}}
            <tr>
                <td class=\"font-bold\" style=\"background: #f3f4f6;\">Comissão Booker</td>
                @foreach($reportData['booker_comparison'] as $booker)
                    <td class=\"text-right text-purple font-bold\">R$ {{ number_format($booker['cache_booker'], 2, ',', '.') }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
    @endif

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
                            <div style="font-size: 8px; font-weight: bold; margin-bottom: 2px;">
                                {{ $gigDetail['date']->format('d/m/Y') }} | {{ $gigDetail['location'] }}
                            </div>
                          
                        </td>
                        <td class="text-right text-blue" style="white-space: nowrap;">R$ {{ number_format($gigDetail['cache_liquido'], 2, ',', '.') }}</td>
                        <td class="text-right text-indigo" style="white-space: nowrap;">R$ {{ number_format($gigDetail['comissao_agencia'], 2, ',', '.') }}</td>
                        <td class="text-right text-purple" style="white-space: nowrap;">R$ {{ number_format($gigDetail['comissao_booker'], 2, ',', '.') }}</td>
                        <td class="text-right text-green" style="white-space: nowrap;">R$ {{ number_format($gigDetail['comissao_liquida'], 2, ',', '.') }}</td>
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
                <tr style="background: #eef2ff; border-top: 2px solid #6366f1;">
                    <td colspan="3" style="padding: 10px 6px;">
                        <span class="avatar" style="background: #4f46e5; color: white;">
                            {{ strtoupper(substr($bookerGroup['booker']->name, 0, 2)) }}
                        </span>
                        <strong style="font-size: 10px;">{{ $bookerGroup['booker']->name }}</strong>
                        <span style="font-size: 8px; color: #6b7280;"> ({{ $bookerGroup['vendas'] }} eventos)</span>
                    </td>
                </tr>

                {{-- Eventos do Booker --}}
                @foreach($bookerGroup['gigs_detailed'] as $gigDetail)
                    <tr>
                        <td style="padding: 5px 6px;">
                            <div style="font-size: 8px; font-weight: bold; margin-bottom: 2px;">
                                {{ $gigDetail['date']->format('d/m/Y') }} |
                                <span style="color: #4f46e5; font-weight: bold;">{{ $gigDetail['artist_name'] }}</span>
                                @ {{ $gigDetail['location'] }}
                            </div>
                            
                        </td>
                        <td class="text-right text-blue" style="white-space: nowrap;">R$ {{ number_format($gigDetail['cache_liquido'], 2, ',', '.') }}</td>
                        <td class="text-right text-purple" style="white-space: nowrap;">R$ {{ number_format($gigDetail['comissao_booker'], 2, ',', '.') }}</td>
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
