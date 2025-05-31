<x-tab-nav :active-tab="'cashflow'">
    @section('exportButtons')
        <x-slot-button :type="'cashflow'" :filters="$filters" />
    @endsection

    <div class="space-y-6 mt-6">
        <!-- Seção de Cards -->
        @if (isset($cashflowSummary) && $cashflowSummary['net_cashflow'] > 0)
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg">
                    <h3 class="text-sm text-gray-500 dark:text-gray-400">Entrada Total</h3>
                    <p class="text-lg font-semibold text-green-800 dark:text-green-300">R$ {{ number_format($cashflowSummary['total_inflow'] ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="bg-red-100 dark:bg-red-900/20 p-4 rounded-lg">
                    <h3 class="text-sm text-gray-500 dark:text-gray-400">Saída Total</h3>
                    <p class="text-lg font-semibold text-red-800 dark:text-red-300">R$ {{ number_format($cashflowSummary['total_outflow'] ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
                    <h3 class="text-sm text-gray-500 dark:text-gray-400">Fluxo Líquido</h3>
                    <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">R$ {{ number_format($cashflowSummary['net_cashflow'] ?? 0, 2, ',', '.') }}</p>
                </div>
            </div>
        @endif

        <!-- Tabela de Fluxo de Caixa -->
        @if (isset($cashflowTable) && $cashflowTable->isNotEmpty())
            <div class="overflow-x-auto mt-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contrato</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Entrada (BRL)</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Saída (BRL)</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fluxo Líquido (BRL)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($cashflowTable as $row)
                            <tr>
                                <td class="px-3 py-1.5 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">{{ $row['contract_number'] ?? 'N/A' }}</td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $row['gig_date'] ?? 'N/A' }}</td> <!-- Mudado de 'date' para 'gig_date' -->
                                <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">R$ {{ number_format($row['revenue'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">R$ {{ number_format($row['costs'] ?? 0, 2, ',', '.') }}</td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">R$ {{ number_format($row['net_cashflow'] ?? 0, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-center text-gray-500 dark:text-gray-400 mt-6">
                Nenhum dado de fluxo de caixa disponível para o período selecionado.
            </p>
        @endif
    </div>
</x-tab-nav>