{{-- Este parcial espera a variável $detailedPerformanceReport do controller --}}
@php
    $tableData = $detailedPerformanceReport['tableData'] ?? collect([]);
    $totals = $detailedPerformanceReport['totals'] ?? [];
@endphp

<div class="space-y-6 mt-4">
    {{-- Botões de Exportação --}}
    <div class="flex justify-end">
        {{-- Aqui chamamos o nosso componente de botão --}}
        <x-reports.slot-button type="overview" />
    </div>


    {{-- Cards de Resumo (Opcional, pode usar os totais aqui) --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Cachê Bruto (BRL)</h3>
            <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">R$ {{ number_format($totals['cache_bruto_brl'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="bg-red-100 dark:bg-red-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Despesas</h3>
            <p class="text-lg font-semibold text-red-800 dark:text-red-300">R$ {{ number_format($totals['total_despesas_confirmadas_brl'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Comissão Ag. Líquida</h3>
            <p class="text-lg font-semibold text-green-800 dark:text-green-300">R$ {{ number_format($totals['comissao_agencia_liquida_brl'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="bg-indigo-100 dark:bg-indigo-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Repasse Artistas</h3>
            <p class="text-lg font-semibold text-indigo-800 dark:text-indigo-300">R$ {{ number_format($totals['repasse_estimado_artista_brl'] ?? 0, 2, ',', '.') }}</p>
        </div>
    </div>


    {{-- Tabela de Visão Geral Detalhada --}}
    @if ($tableData->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data Gig</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Artista</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Booker</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Local/Evento</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cachê (Orig)</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cachê (BRL)</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Despesas</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cachê Líq. Base</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Repasse Artista</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Com. Agência</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Com. Booker</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Com. Ag. Líq.</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status Contrato</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status Pgto.</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($tableData as $row)
                        <tr>
                            <td class="px-3 py-1.5 whitespace-nowrap">{{ $row['gig_date'] }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap font-semibold">{{ $row['artist_name'] }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap">{{ $row['booker_name'] }}</td>
                            <td class="px-3 py-1.5 whitespace-normal max-w-xs truncate" title="{{ $row['location_event_details'] }}">{{ $row['location_event_details'] }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right">{{ $row['cache_bruto_original'] }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right">{{ number_format($row['cache_bruto_brl'], 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right text-red-600">{{ number_format($row['total_despesas_confirmadas_brl'], 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right font-bold">{{ number_format($row['cache_liquido_base_brl'], 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right">{{ number_format($row['repasse_estimado_artista_brl'], 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right">{{ number_format($row['comissao_agencia_brl'], 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right">{{ number_format($row['comissao_booker_brl'], 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right">{{ number_format($row['comissao_agencia_liquida_brl'], 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-center"><x-status-badge :status="$row['contract_status']" type="contract" /></td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-center"><x-status-badge :status="$row['payment_status']" type="payment" /></td>
                        </tr>
                    @endforeach
                </tbody>
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cachê (Orig)</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cachê (BRL)</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Despesas</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cachê Líq. Base</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Repasse Artista</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Com. Agência</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Com. Booker</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Com. Ag. Líq.</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                        <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tfoot class="bg-gray-50 dark:bg-gray-900 font-bold">
                    <tr>
                        <td class="px-3 py-2 text-right" colspan="5">TOTAIS</td>
                        <td class="px-3 py-2 text-right">{{ number_format($totals['cache_bruto_brl'] ?? 0, 2, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($totals['total_despesas_confirmadas_brl'] ?? 0, 2, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($totals['cache_liquido_base_brl'] ?? 0, 2, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($totals['repasse_estimado_artista_brl'] ?? 0, 2, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($totals['comissao_agencia_brl'] ?? 0, 2, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($totals['comissao_booker_brl'] ?? 0, 2, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($totals['comissao_agencia_liquida_brl'] ?? 0, 2, ',', '.') }}</td>
                        <td class="px-3 py-2" colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <p class="text-center text-gray-500 dark:text-gray-400 mt-6">Nenhum dado de performance disponível para o período selecionado.</p>
    @endif
</div>