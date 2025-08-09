@php
    $dataByArtist = $overviewData['dataByArtist'] ?? collect([]);
    $grandTotals = $overviewData['grandTotals'] ?? [];
@endphp

<div class="mt-4">
    
    @if (!empty($grandTotals) && $grandTotals['gig_count'] > 0)
    {{-- Layout Principal (Flexbox) com Cards e Botões --}}
    <div class="flex flex-col md:flex-row justify-between items-start gap-6 mb-6">
        
        {{-- Container para os Cards com o novo estilo --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 flex-grow w-full">
            
            {{-- Card Azul: Cachê Líquido Base --}}
            <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Cachê Líquido Base</h3>
                <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">
                    R$ {{ number_format($grandTotals['cache_liquido_base_brl'] ?? 0, 2, ',', '.') }}
                </p>
            </div>

            {{-- Card Verde: Comissão Líquida --}}
            <div class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Comissão Líquida Agência</h3>
                <p class="text-lg font-semibold text-green-800 dark:text-green-300">
                    R$ {{ number_format($grandTotals['comissao_agencia_liquida_brl'] ?? 0, 2, ',', '.') }}
                </p>
            </div>

            {{-- Card Vermelho: Total Despesas --}}
            <div class="bg-red-100 dark:bg-red-900/20 p-4 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Total de Despesas</h3>
                <p class="text-lg font-semibold text-red-800 dark:text-red-300">
                    R$ {{ number_format($grandTotals['total_despesas_confirmadas_brl'] ?? 0, 2, ',', '.') }}
                </p>
            </div>

            {{-- Card Amarelo: Total de Gigs --}}
            <div class="bg-yellow-100 dark:bg-yellow-900/20 p-4 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Total de Gigs</h3>
                <p class="text-lg font-semibold text-yellow-800 dark:text-yellow-300">
                    {{ $grandTotals['gig_count'] ?? 0 }}
                </p>
            </div>
        </div>

        {{-- Botões de Exportação (Empilhados Verticalmente) --}}
        <div class="flex flex-col space-y-2 w-full md:w-auto">
            <a href="{{ route('reports.overview.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center justify-center text-sm transition-colors duration-200">
                <i class="fas fa-file-excel mr-2"></i>Excel
            </a>
            <a href="{{ route('reports.overview.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" 
               target="_blank" 
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center justify-center text-sm transition-colors duration-200">
                <i class="fas fa-file-pdf mr-2"></i>PDF
            </a>
        </div>

    </div>
    @endif

    {{-- Tabela (sem alterações) --}}
    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
            {{-- ... Conteúdo completo da tabela como na versão anterior ... --}}
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
                    <tr class="bg-gray-100 dark:bg-gray-700/50">
                        <td class="px-2 py-3 font-bold text-sm text-primary-700 dark:text-primary-400" colspan="13">
                            <i class="fas fa-music fa-fw mr-2"></i>{{ $artistData['artist_name'] }}
                        </td>
                    </tr>
                    @foreach ($artistData['gigs'] as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-2 py-2 whitespace-nowrap">{{ $row['gig_date'] }}</td>
                            <td class="px-2 py-2 whitespace-nowrap">{{ $row['booker_name'] }}</td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <a href="{{ route('gigs.show', $row['gig_id']) }}" class="text-primary-600 hover:underline" title="Ver detalhes da Gig">
                                    Gig #{{ $row['gig_id'] }}
                                </a>
                                @if($row['location_event_details'])
                                    <span class="block text-gray-500 dark:text-gray-400 italic text-xxs truncate max-w-[150px]" title="{{ $row['location_event_details'] }}">
                                        {{ Str::limit($row['location_event_details'], 30) }}
                                    </span>
                                @endif
                            </td>
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
                    <tr class="bg-gray-50 dark:bg-gray-800/80 font-bold border-b-2 border-gray-300 dark:border-gray-600">
                        <td class="px-2 py-2 text-right" colspan="3">SUBTOTAL ({{ $artistData['gig_count'] }} Gigs):</td>
                        <td></td>
                        <td class="px-2 py-2 text-right">R$ {{ number_format($artistData['subtotals']['cache_bruto_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-2 text-right">R$ {{ number_format($artistData['subtotals']['total_despesas_confirmadas_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-2 text-right">R$ {{ number_format($artistData['subtotals']['cache_liquido_base_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-2 text-right">R$ {{ number_format($artistData['subtotals']['repasse_estimado_artista_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-2 text-right">R$ {{ number_format($artistData['subtotals']['comissao_agencia_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-2 text-right">R$ {{ number_format($artistData['subtotals']['comissao_booker_brl'], 2, ',', '.') }}</td>
                        <td class="px-2 py-2 text-right">R$ {{ number_format($artistData['subtotals']['comissao_agencia_liquida_brl'], 2, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                @empty
                    <tr><td class="text-center py-10" colspan="13">Nenhum dado encontrado para os filtros selecionados.</td></tr>
                @endforelse
            </tbody>
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