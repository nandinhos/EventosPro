    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Visão Geral</title>
        <style>
            /* ANOTAÇÃO: Todo este bloco de CSS foi refatorado para seguir o padrão do relatório anterior. */

            /* --- CONFIGURAÇÕES GERAIS --- */
            @page { margin: 30px; }
            body { 
                font-family: 'DejaVu Sans', sans-serif; 
                color: #333; 
                font-size: 9px;
                line-height: 1.4;
            }

            /* --- CABEÇALHO PADRONIZADO --- */
            .header { 
                text-align: center; 
                border-bottom: 2px solid #6366f1; /* Cor primária */
                padding-bottom: 10px; 
                margin-bottom: 25px; 
                position: relative;
            }
            .header .logo { position: absolute; top: -5px; left: 0; max-height: 100px; }
            .header h1 { font-size: 22px; margin: 0; color: #1f2937; }
            .header p { font-size: 10px; margin: 5px 0 0 0; color: #6b7280; }

            /* --- ESTILO DA TABELA PRINCIPAL --- */
            .main-table {
    max-width: 85%; /* Define a nova largura máxima. Ajuste conforme necessário. */
    margin-left: auto; /* Centraliza a tabela */
    margin-right: auto; /* Centraliza a tabela */
    border-collapse: collapse;
    margin-top: 15px;
}
            .main-table th {
                background-color: #f3f4f6;
                color: #4b5563;
                padding: 8px 6px;
                text-align: left;
                font-size: 10px;
                text-transform: uppercase;
                border-bottom: 2px solid #e5e7eb;
            }
            .main-table td {
                padding: 7px 6px;
                border-bottom: 1px solid #e5e7eb;
                vertical-align: top;
            }
            .main-table .text-right { text-align: right; }
            .main-table .font-bold { font-weight: bold; }

            /* --- ESTILOS DAS LINHAS ESPECIAIS --- */
            .group-header td {
    background-color: #f7f7f7; /* Cinza super claro */
    color: indigo; /* Cor índigo */
    font-size: 12px;
    font-weight: bold;
    padding: 8px;
    border-bottom: 1px solid #e5e7eb; /* Adiciona uma linha inferior para separação */
    text-transform: uppercase; /* Texto em maiúsculo */
    }
            .subtotal-row td {
                background-color: #f9fafb;
                font-weight: bold;
                border-top: 2px solid #e5e7eb;
            }
            .grand-total-row td {
                background-color: #eef2ff; /* Tom claro da cor primária */
                color: #1e3a8a;
                font-weight: bold;
                font-size: 11px;
                border-top: 2px solid #6366f1;
            }
        </style>
    </head>
    <body>
        {{-- ANOTAÇÃO: O cabeçalho foi atualizado para o nosso novo padrão. --}}
        <div class="header">
            <img src="{{ public_path('img/coral_360_logo.png') }}" alt="Logo" class="logo">
            <h1>Relatório de Visão Geral - Por Artista</h1>
            <p>Período: {{ \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') }}</p>
            <p style="font-size: 8px; color: #9ca3af;">Gerado em: {{ now()->format('d/m/Y H:i') }}</p>
        </div>

        <table class="main-table">
            <thead>
                <tr>
                    <th>Data Gig</th>
                    <th>Booker</th>
                    <th style="width: 25%;">Local/Evento</th>
                    <th class="text-right">Cachê (Orig)</th>
                    <th class="text-right">Cachê (BRL)</th>
                    <th class="text-right">Despesas (BRL)</th>
                    <th class="text-right">Cachê Líq. Base</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($overviewData['dataByArtist'] as $artistData)
                    {{-- Linha de Cabeçalho do Grupo de Artista --}}
                    <tr class="group-header">
                        <td colspan="7">
                            Artista: {{ $artistData['artist_name'] }}
                        </td>
                    </tr>
                    {{-- Linhas de Gigs do Artista --}}
                    @foreach ($artistData['gigs'] as $row)
                        <tr>
                            <td>{{ $row['gig_date'] }}</td>
                            <td>{{ $row['booker_name'] }}</td>
                            <td>{{ $row['location_event_details'] }}</td>
                            <td class="text-right">{{ $row['cache_bruto_original'] }}</td>
                            <td class="text-right">{{ number_format($row['cache_bruto_brl'], 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($row['total_despesas_confirmadas_brl'], 2, ',', '.') }}</td>
                            <td class="text-right font-bold">{{ number_format($row['cache_liquido_base_brl'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    {{-- Linha de Subtotal do Artista --}}
                    <tr class="subtotal-row">
                        <td colspan="4" class="text-right">SUBTOTAL ({{ $artistData['gig_count'] }} Gigs):</td>
                        <td class="text-right">R$ {{ number_format($artistData['subtotals']['cache_bruto_brl'], 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($artistData['subtotals']['total_despesas_confirmadas_brl'], 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($artistData['subtotals']['cache_liquido_base_brl'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px;">Nenhum dado encontrado para os filtros selecionados.</td>
                    </tr>
                @endforelse
            </tbody>
            {{-- Rodapé com Total Geral --}}
            @if (!empty($grandTotals))
                <tfoot>
                    <tr class="grand-total-row">
                        <td colspan="4" class="text-right">TOTAL GERAL ({{ $grandTotals['gig_count'] ?? '0' }} Gigs):</td>
                        <td class="text-right">R$ {{ number_format($grandTotals['cache_bruto_brl'] ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($grandTotals['total_despesas_confirmadas_brl'] ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($grandTotals['cache_liquido_base_brl'] ?? 0, 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>

    </body>
    </html>