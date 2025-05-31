<x-tab-nav :active-tab="'profitability'">
    @section('exportButtons')
        <x-slot-button :type="'profitability'" :filters="$filters" />
    @endsection

    <div class="space-y-6 mt-6">
        <!-- Seção de Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Lucro Total</h3>
                <p class="text-lg font-semibold text-green-800 dark:text-green-300">R$ {{ number_format($profitabilitySummary['total_profit'] ?? 0, 2, ',', '.') }}</p>
            </div>
            <div class="bg-yellow-100 dark:bg-yellow-900/20 p-4 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Margem Média</h3>
                <p class="text-lg font-semibold text-yellow-800 dark:text-yellow-300">{{ number_format($profitabilitySummary['average_margin'] ?? 0, 2, ',', '.') }}%</p>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Eventos Rentáveis</h3>
                <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">{{ $profitabilitySummary['profitable_events'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Tabela de Rentabilidade -->
        @if (isset($profitabilityTable) && $profitabilityTable->isNotEmpty())
            <div class="overflow-x-auto mt-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contrato</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Artista</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Receita (BRL)</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Custos (BRL)</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lucro (BRL)</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Margem (%)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($profitabilityTable as $row)
                            <tr>
                                <td class="px-3 py-1.5 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">{{ $row['contract_number'] ?? 'N/A' }}</td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $row['artist'] ?? 'N/A' }}</td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $row['gig_date'] ?? 'N/A' }}</td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">R$ {{ number_format($row['revenue'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">R$ {{ number_format($row['costs'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">R$ {{ number_format($row['profit'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">{{ number_format($row['margin'] ?? 0, 2, ',', '.') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-center text-gray-500 dark:text-gray-400 mt-6">
                Nenhum dado de rentabilidade disponível para o período selecionado.
            </p>
        @endif
    </div>
</x-tab-nav>