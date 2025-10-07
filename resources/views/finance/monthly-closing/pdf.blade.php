<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fechamento Mensal - {{ $period }}</title>
    <style>
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
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #3b82f6;
        }

        .header h1 {
            font-size: 20px;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 11px;
            color: #6b7280;
        }

        .cards {
            display: table;
            width: 100%;
            margin-bottom: 20px;
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
            font-size: 12px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
            margin-top: 20px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7px;
            padding: 6px 8px;
            text-align: left;
            border-bottom: 2px solid #d1d5db;
            color: #374151;
        }

        td {
            padding: 5px 8px;
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
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 7px;
            text-align: center;
            color: #9ca3af;
        }

        .page-break {
            page-break-after: always;
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

    {{-- TABELA 1: RESUMO POR ARTISTA --}}
    <div class="section-title">RESUMO POR ARTISTA</div>
    <table>
        <thead>
            <tr>
                <th>ARTISTA</th>
                <th class="text-right">CACHÊ LÍQUIDO</th>
                <th class="text-right">COMISSÃO AGÊNCIA</th>
                <th class="text-right">COMISSÃO BOOKER</th>
                <th class="text-right">COMISSÃO LÍQUIDA</th>
                <th class="text-center">VENDAS</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalCacheLiquido = 0;
                $totalComAgencia = 0;
                $totalComBooker = 0;
                $totalComLiquida = 0;
                $totalVendas = 0;
            @endphp

            @forelse($reportData['artist_data'] ?? [] as $artist)
                @php
                    $totalCacheLiquido += $artist['cache_liquido'];
                    $totalComAgencia += $artist['comissao_agencia'];
                    $totalComBooker += $artist['comissao_booker'];
                    $totalComLiquida += $artist['comissao_liquida'];
                    $totalVendas += $artist['vendas'];
                @endphp
                <tr>
                    <td>
                        <span class="avatar">{{ strtoupper(substr($artist['artist']->name, 0, 2)) }}</span>
                        <strong>{{ $artist['artist']->name }}</strong>
                    </td>
                    <td class="text-right text-blue font-bold">R$ {{ number_format($artist['cache_liquido'], 2, ',', '.') }}</td>
                    <td class="text-right text-indigo font-bold">R$ {{ number_format($artist['comissao_agencia'], 2, ',', '.') }}</td>
                    <td class="text-right text-purple font-bold">R$ {{ number_format($artist['comissao_booker'], 2, ',', '.') }}</td>
                    <td class="text-right text-green font-bold">R$ {{ number_format($artist['comissao_liquida'], 2, ',', '.') }}</td>
                    <td class="text-center">
                        <span class="badge">{{ $artist['vendas'] }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px; color: #9ca3af;">
                        Nenhum artista encontrado no período selecionado
                    </td>
                </tr>
            @endforelse

            @if(count($reportData['artist_data'] ?? []) > 0)
                <tr class="bg-total">
                    <td><strong>TOTAL</strong></td>
                    <td class="text-right text-blue">R$ {{ number_format($totalCacheLiquido, 2, ',', '.') }}</td>
                    <td class="text-right text-indigo">R$ {{ number_format($totalComAgencia, 2, ',', '.') }}</td>
                    <td class="text-right text-purple">R$ {{ number_format($totalComBooker, 2, ',', '.') }}</td>
                    <td class="text-right text-green">R$ {{ number_format($totalComLiquida, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $totalVendas }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- TABELA 2: DESEMPENHO POR BOOKER --}}
    <div class="section-title">DESEMPENHO POR BOOKER</div>
    <table>
        <thead>
            <tr>
                <th>BOOKER</th>
                <th class="text-right">CACHÊ LÍQUIDO</th>
                <th class="text-right">COMISSÃO BOOKER</th>
                <th class="text-center">VENDAS</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalBookerCache = 0;
                $totalBookerCom = 0;
                $totalBookerVendas = 0;
            @endphp

            @forelse($reportData['booker_data'] ?? [] as $booker)
                @php
                    $totalBookerCache += $booker['cache_liquido'];
                    $totalBookerCom += $booker['comissao_booker'];
                    $totalBookerVendas += $booker['vendas'];
                @endphp
                <tr>
                    <td>
                        <span class="avatar" style="background: #f3e8ff; color: #7c3aed;">
                            {{ strtoupper(substr($booker['booker']->name, 0, 2)) }}
                        </span>
                        <strong>{{ $booker['booker']->name }}</strong>
                    </td>
                    <td class="text-right text-blue font-bold">R$ {{ number_format($booker['cache_liquido'], 2, ',', '.') }}</td>
                    <td class="text-right text-purple font-bold">R$ {{ number_format($booker['comissao_booker'], 2, ',', '.') }}</td>
                    <td class="text-center">
                        <span class="badge">{{ $booker['vendas'] }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="padding: 20px; color: #9ca3af;">
                        Nenhum booker encontrado no período selecionado
                    </td>
                </tr>
            @endforelse

            @if(count($reportData['booker_data'] ?? []) > 0)
                <tr class="bg-total">
                    <td><strong>TOTAL</strong></td>
                    <td class="text-right text-blue">R$ {{ number_format($totalBookerCache, 2, ',', '.') }}</td>
                    <td class="text-right text-purple">R$ {{ number_format($totalBookerCom, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $totalBookerVendas }}</td>
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
