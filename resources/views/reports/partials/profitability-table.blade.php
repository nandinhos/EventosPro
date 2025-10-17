@php
    // Variável esperada do controller: $salesProfitabilityData
    $tableData = $salesProfitabilityData ?? collect([]);
@endphp

<div class="space-y-6 mt-4">
    {{-- Cards de Resumo para a Aba de Rentabilidade --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Contrato Bruto</h3>
            <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">R$ {{ number_format($tableData->sum('revenue'), 2, ',', '.') }}</p>
        </div>
        <div class="bg-red-100 dark:bg-red-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Custos</h3>
            <p class="text-lg font-semibold text-red-800 dark:text-red-300">R$ {{ number_format($tableData->sum('costs'), 2, ',', '.') }}</p>
        </div>
        <div class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Contrato Líquido</h3>
            <p class="text-lg font-semibold text-green-800 dark:text-green-300">R$ {{ number_format($tableData->sum('profitability'), 2, ',', '.') }}</p>
        </div>
        <div class="bg-yellow-100 dark:bg-yellow-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Margem</h3>
            @php
                $totalRevenue = $tableData->sum('revenue');
                $totalProfit = $tableData->sum('profitability');
                $averageMargin = ($totalRevenue > 0) ? ($totalProfit / $totalRevenue) * 100 : 0;
            @endphp
            <p class="text-lg font-semibold text-yellow-800 dark:text-yellow-300">{{ number_format($averageMargin, 2, ',', '.') }}%</p>
        </div>
    </div>

    {{-- Tabela de Rentabilidade por Venda --}}
    @if ($tableData->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data da Venda</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gig</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Receita Bruta</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Custos Totais</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rentabilidade (R$)</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Margem (%)</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($tableData as $row)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $row['sale_date'] }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('gigs.show', $row['gig_id']) }}" class="font-semibold text-primary-600 hover:underline">
                                    {{ $row['gig_contract_number'] ?: 'Gig #'.$row['gig_id'] }}
                                </a>
                                <span class="block text-xs text-gray-500">{{ $row['gig_name'] }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-gray-700 dark:text-gray-200">
                                R$ {{ number_format($row['revenue'], 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-red-500">
                                R$ {{ number_format($row['costs'], 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right font-bold {{ $row['profitability'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                R$ {{ number_format($row['profitability'], 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right font-bold {{ $row['margin'] >= 0 ? 'text-gray-700 dark:text-gray-200' : 'text-red-600' }}">
                                {{ number_format($row['margin'], 2, ',', '.') }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-10">
            <i class="fas fa-search-dollar fa-3x text-gray-400 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">Nenhuma venda registrada no período selecionado.</p>
        </div>
    @endif
</div>