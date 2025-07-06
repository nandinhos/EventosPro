<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Visão Geral</title>
    <style>
        /* CONFIGURAÇÕES GERAIS */
        @page { margin: 20px; }
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            color: #374151; 
            font-size: 7px; /* Fonte menor para caber mais colunas */
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
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('img/coral_360_logo.png') }}" alt="Logo" class="logo">
        <h1>Relatório de Visão Geral - Por Artista</h1>
        <p>Período: {{ isset($filters['start_date']) ? \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') : 'N/A' }} a {{ isset($filters['end_date']) ? \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') : 'N/A' }}</p>
        <p style="font-size: 8px; color: #9ca3af;">
            Gerado em: {{ now()->format('d/m/Y H:i') }}
            @if(auth()->check())
                por: {{ auth()->user()->name }}
            @endif
        </p>
    </div>

    @php
        // Extrai as variáveis para facilitar o acesso no Blade
        $dataByArtist = $overviewData['dataByArtist'] ?? collect([]);
        $grandTotals = $overviewData['grandTotals'] ?? [];
    @endphp

    <table class="main-table">
        <thead>
            <tr>
                <th>Data Gig</th>
                <th>Booker</th>
                <th style="width: 15%;">Local/Evento</th>
                <th class="text-right">Cachê (Orig)</th>
                <th class="text-right">Cachê (BRL)</th>
                <th class="text-right">Despesas</th>
                <th class="text-right">Cachê Líq. Base</th>
                <th class="text-right">Repasse Artista</th>
                <th class="text-right">Com. Agência</th>
                <th class="text-right">Com. Booker</th>
                <th class="text-right">Com. Ag. Líq.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dataByArtist as $artistData)
                <tr class="group-header">
                    <td colspan="11">{{ $artistData['artist_name'] }}</td>
                </tr>
                @foreach ($artistData['gigs'] as $row)
                    <tr>
                        <td>{{ $row['gig_date'] }}</td>
                        <td>{{ $row['booker_name'] }}</td>
                        <td>{{ $row['location_event_details'] }}</td>
                        <td class="text-right">{{ $row['cache_bruto_original'] }}</td>
                        <td class="text-right">{{ number_format($row['cache_bruto_brl'], 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['total_despesas_confirmadas_brl'], 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['cache_liquido_base_brl'], 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['repasse_estimado_artista_brl'], 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['comissao_agencia_brl'], 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['comissao_booker_brl'], 2, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format($row['comissao_agencia_liquida_brl'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="3" class="text-right">SUBTOTAL ({{ $artistData['gig_count'] }} Gigs):</td>
                    <td></td>
                    <td class="text-right">R$ {{ number_format($artistData['subtotals']['cache_bruto_brl'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($artistData['subtotals']['total_despesas_confirmadas_brl'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($artistData['subtotals']['cache_liquido_base_brl'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($artistData['subtotals']['repasse_estimado_artista_brl'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($artistData['subtotals']['comissao_agencia_brl'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($artistData['subtotals']['comissao_booker_brl'], 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($artistData['subtotals']['comissao_agencia_liquida_brl'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center; padding: 20px;">Nenhum dado encontrado para os filtros selecionados.</td>
                </tr>
            @endforelse

            {{-- ***** INÍCIO DA CORREÇÃO: TOTAIS GERAIS MOVEM-SE PARA DENTRO DO TBODY ***** --}}
            @if (!empty($grandTotals) && $grandTotals['gig_count'] > 0)
                <tr class="grand-total-row">
                    <td colspan="3" class="text-right">TOTAL GERAL ({{ $grandTotals['gig_count'] ?? '0' }} Gigs):</td>
                    <td></td>
                    <td class="text-right">R$ {{ number_format($grandTotals['cache_bruto_brl'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($grandTotals['total_despesas_confirmadas_brl'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($grandTotals['cache_liquido_base_brl'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($grandTotals['repasse_estimado_artista_brl'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($grandTotals['comissao_agencia_brl'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($grandTotals['comissao_booker_brl'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($grandTotals['comissao_agencia_liquida_brl'] ?? 0, 2, ',', '.') }}</td>
                </tr>
            @endif
            {{-- ***** FIM DA CORREÇÃO ***** --}}
        </tbody>
        {{-- Removido <tfoot> para maior compatibilidade com DOMPDF --}}
    </table>
</body>
</html>