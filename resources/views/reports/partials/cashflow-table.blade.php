<div class="space-y-6 mt-4">
    {{-- Seção de Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        {{-- Card de Entradas --}}
        <div class="lg:col-span-3 bg-green-100 dark:bg-green-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Entradas (Clientes)</h3>
            <p class="text-lg font-semibold text-green-800 dark:text-green-300">R$ {{ number_format($cashflowSummary['total_inflow'] ?? 0, 2, ',', '.') }}</p>
        </div>

        {{-- Cards de Saídas Detalhados --}}
        <div class="bg-red-100 dark:bg-red-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">(-) Saídas (Despesas)</h3>
            <p class="text-lg font-semibold text-red-700 dark:text-red-300">R$ {{ number_format($cashflowSummary['total_outflow_expenses'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="bg-red-100 dark:bg-red-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">(-) Saídas (Artistas)</h3>
            <p class="text-lg font-semibold text-red-700 dark:text-red-300">R$ {{ number_format($cashflowSummary['total_outflow_artists'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="bg-red-100 dark:bg-red-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">(-) Saídas (Bookers)</h3>
            <p class="text-lg font-semibold text-red-700 dark:text-red-300">R$ {{ number_format($cashflowSummary['total_outflow_bookers'] ?? 0, 2, ',', '.') }}</p>
        </div>
        
        {{-- Card Total Saídas e Saldo Operacional --}}
        <div class="bg-gray-100 dark:bg-gray-700/50 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Saídas</h3>
            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">R$ {{ number_format($cashflowSummary['total_outflow'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="col-span-1 lg:col-span-2 bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">(=) Saldo Operacional do Período</h3>
            <p class="text-xl font-bold {{ ($cashflowSummary['net_cashflow'] ?? 0) >= 0 ? 'text-blue-800 dark:text-blue-300' : 'text-red-600 dark:text-red-400' }}">
                R$ {{ number_format($cashflowSummary['net_cashflow'] ?? 0, 2, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Tabela de Fluxo de Caixa (a tabela atual pode ser mantida ou evoluída para uma lista de transações) --}}
    @if (isset($cashflowTable) && $cashflowTable->isNotEmpty())
        <div class="overflow-x-auto mt-6">
            <h4 class="text-md font-semibold text-gray-800 dark:text-white mb-2">Visão por Gig (Resumo)</h4>
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
                                <td class="px-3 py-1.5 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $row['gig_date'] ?? 'N/A' }}</td>
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