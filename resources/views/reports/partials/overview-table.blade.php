@php
    $dataByArtist = $overviewData['dataByArtist'] ?? collect([]);
    $grandTotals = $overviewData['grandTotals'] ?? [];
@endphp

<div class="space-y-6 mt-4">
    {{-- Botões de Exportação --}}
    <div class="flex justify-end space-x-2">
        <a href="{{ route('reports.overview.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" 
           class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md flex items-center text-sm transition-colors duration-200">
            <i class="fas fa-file-excel mr-2"></i> Exportar para Excel
        </a>
        <a href="{{ route('reports.overview.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" 
           target="_blank" 
           class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-md flex items-center text-sm transition-colors duration-200">
            <i class="fas fa-file-pdf mr-2"></i> Exportar para PDF
        </a>
    </div>

    {{-- Tabela Agrupada por Artista --}}
    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-2 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Data Gig</th>
                    <th class="px-2 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Booker</th>
                    <th class="px-2 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Local/Evento</th>
                    <th class="px-2 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Cachê (Orig)</th>
                    <th class="px-2 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Cachê (BRL)</th>
                    <th class="px-2 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Despesas</th>
                    <th class="px-2 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Cachê Líq. Base</th>
                    <th class="px-2 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Repasse Artista</th>
                    <th class="px-2 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Com. Agência</th>
                    <th class="px-2 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Com. Booker</th>
                    <th class="px-2 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Com. Ag. Líq.</th>
                    <th class="px-2 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Status Contrato</th>
                    <th class="px-2 py-2 text-center font-medium text-gray-500 dark:text-gray-400">Status Pgto.</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800">
                @forelse ($dataByArtist as $artistData)
                    {{-- Linha de Cabeçalho do Grupo de Artista --}}
                    <tr class="bg-gray-100 dark:bg-gray-700/50">
                        <td class="px-2 py-3 font-bold text-sm text-primary-700 dark:text-primary-400" colspan="13">
                            <i class="fas fa-music fa-fw mr-2"></i>{{ $artistData['artist_name'] }}
                        </td>
                    </tr>
                    
                    {{-- Linhas de Gigs do Artista --}}
                    @foreach ($artistData['gigs'] as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-2 py-2 whitespace-nowrap">{{ $row['gig_date'] }}</td>
                            <td class="px-2 py-2 whitespace-nowrap">{{ $row['booker_name'] }}</td>
                            <td class="px-2 py-2 whitespace-normal max-w-xs truncate" title="{{ $row['location_event_details'] }}">{{ $row['location_event_details'] }}</td>
                            <td class="px-2 py-2 text-right whitespace-nowrap">{{ $row['cache_bruto_original'] }}</td>
                            <td class="px-2 py-2 text-right whitespace-nowrap">{{ number_format($row['cache_bruto_brl'], 2, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right whitespace-nowrap text-red-500">{{ number_format($row['total_despesas_confirmadas_brl'], 2, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right whitespace-nowrap font-semibold">{{ number_format($row['cache_liquido_base_brl'], 2, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right whitespace-nowrap">{{ number_format($row['repasse_estimado_artista_brl'], 2, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right whitespace-nowrap">{{ number_format($row['comissao_agencia_brl'], 2, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right whitespace-nowrap">{{ number_format($row['comissao_booker_brl'], 2, ',', '.') }}</td>
                            <td class="px-2 py-2 text-right whitespace-nowrap font-semibold">{{ number_format($row['comissao_agencia_liquida_brl'], 2, ',', '.') }}</td>
                            <td class="px-2 py-2 text-center whitespace-nowrap"><x-status-badge :status="$row['contract_status']" type="contract" /></td>
                            <td class="px-2 py-2 text-center whitespace-nowrap"><x-status-badge :status="$row['payment_status']" type="payment" /></td>
                        </tr>
                    @endforeach

                    {{-- Linha de Subtotal do Artista --}}
                    <tr class="bg-gray-50 dark:bg-gray-800/80 font-bold border-b-2 border-gray-300 dark:border-gray-600">
                        <td class="px-2 py-2 text-right" colspan="3">SUBTOTAL ({{ $artistData['gig_count'] }} Gigs):</td>
                        <td></td> {{-- Célula vazia para alinhar Cachê (Orig) --}}
                        <td class="px-2 py-2 text-right">R$ {{ number_format($artistData['subtotals']['cache_bruto_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-2 text-right">R$ {{ number_format($artistData['subtotals']['total_despesas_confirmadas_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-2 text-right">R$ {{ number_format($artistData['subtotals']['cache_liquido_base_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-2 text-right">R$ {{ number_format($artistData['subtotals']['repasse_estimado_artista_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-2 text-right">R$ {{ number_format($artistData['subtotals']['comissao_agencia_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-2 text-right">R$ {{ number_format($artistData['subtotals']['comissao_booker_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-2 text-right">R$ {{ number_format($artistData['subtotals']['comissao_agencia_liquida_brl'], 2, ',', '.') }}</td>
                        <td colspan="2"></td> {{-- Células vazias para status --}}
                    </tr>
                @empty
                    <tr><td class="text-center py-10" colspan="13">Nenhum dado encontrado para os filtros selecionados.</td></tr>
                @endforelse
            </tbody>
            {{-- Rodapé com Total Geral --}}
            <tfoot class="bg-gray-200 dark:bg-gray-900 font-bold text-sm">
                @if (!empty($grandTotals) && $grandTotals['gig_count'] > 0)
                    <tr>
                        <td class="px-2 py-3 text-right" colspan="3">TOTAIS GERAIS:</td>
                        <td class="px-2 py-3 text-center">{{ $grandTotals['gig_count'] }} Gigs</td>
                        <td class="px-2 py-3 text-right">R$ {{ number_format($grandTotals['cache_bruto_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-3 text-right">R$ {{ number_format($grandTotals['total_despesas_confirmadas_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-3 text-right">R$ {{ number_format($grandTotals['cache_liquido_base_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-3 text-right">R$ {{ number_format($grandTotals['repasse_estimado_artista_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-3 text-right">R$ {{ number_format($grandTotals['comissao_agencia_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-3 text-right">R$ {{ number_format($grandTotals['comissao_booker_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-3 text-right">R$ {{ number_format($grandTotals['comissao_agencia_liquida_brl'], 2, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                @endif
            </tfoot>
        </table>
    </div>
</div>