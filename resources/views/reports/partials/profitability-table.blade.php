@php
    $tableData = $profitabilityReport['tableData'] ?? collect([]);
    // Os totais também podem vir prontos do service se preferir
    $totalProfitabilitySummary = [
        'total_comissao_agencia_liquida' => $tableData->sum('total_comissao_agencia_liquida'),
        'total_gigs' => $tableData->sum('num_gigs'),
        'overall_margin' => $tableData->sum('total_cache_liquido_base') > 0
            ? ($tableData->sum('total_comissao_agencia_liquida') / $tableData->sum('total_cache_liquido_base')) * 100
            : 0,
    ];
@endphp

<div class="space-y-6 mt-4">
    {{-- Cards de Resumo --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Comissão Ag. Líquida Total</h3>
            <p class="text-lg font-semibold text-green-800 dark:text-green-300">R$ {{ number_format($totalProfitabilitySummary['total_comissao_agencia_liquida'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-yellow-100 dark:bg-yellow-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Margem Média da Agência</h3>
            <p class="text-lg font-semibold text-yellow-800 dark:text-yellow-300">{{ number_format($totalProfitabilitySummary['overall_margin'], 2, ',', '.') }}%</p>
        </div>
        <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total de Gigs</h3>
            <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">{{ $totalProfitabilitySummary['total_gigs'] }}</p>
        </div>
    </div>

    {{-- Tabela de Rentabilidade Agrupada por Mês --}}
    @if ($tableData->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
            <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mês/Ano</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nº Gigs</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cachê Líq. Base (BRL)</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Com. Agência (BRL)</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Com. Booker (BRL)</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Com. Ag. Líquida (BRL)</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Margem Ag. (%)</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($tableData as $row)
                        <tr>
                            <td class="px-3 py-1.5 whitespace-nowrap font-semibold">{{ $row['month_label'] }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-center">{{ $row['num_gigs'] }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right">{{ number_format($row['total_cache_liquido_base'], 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right">{{ number_format($row['total_comissao_agencia'], 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right">{{ number_format($row['total_comissao_booker'], 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right font-bold">{{ number_format($row['total_comissao_agencia_liquida'], 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right font-bold">{{ number_format($row['margem_bruta_agencia'], 2, ',', '.') }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- GRÁFICOS (APENAS OS CANVAS) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
            <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg">
                <h4 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-3">Evolução da Comissão Líquida da Agência</h4>
                <div class="h-64"><canvas id="netAgencyCommissionChart"></canvas></div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg">
                <h4 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-3">Evolução da Margem Bruta da Agência (%)</h4>
                <div class="h-64"><canvas id="grossMarginChart"></canvas></div>
            </div>
            <div class="lg:col-span-2 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg">
                 <h4 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-3">Comparativo de Comissão Líquida por Booker</h4>
                <div class="h-64"><canvas id="commissionByBookerChart"></canvas></div>
            </div>
        </div>

    @else
        <p class="text-center text-gray-500 dark:text-gray-400 mt-6">Nenhum dado de rentabilidade disponível para o período selecionado.</p>
    @endif
</div>
