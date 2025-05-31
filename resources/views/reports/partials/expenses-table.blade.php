<x-tab-nav :active-tab="'despesas'">
    @section('exportButtons')
        <x-slot-button :type="'expenses'" :filters="$filters" />
    @endsection

    <div class="space-y-6 mt-6">
        <!-- Seção de Cards -->
        @if ($expensesTable->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <!-- Card Alimentação -->
                @php
                    $alimentacaoTotal = $expensesTable->firstWhere('cost_center_name', 'Alimentação')['total_brl'] ?? 0;
                @endphp
                <div class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg">
                    <h3 class="text-sm text-gray-500 dark:text-gray-400">Alimentação</h3>
                    <p class="text-lg font-semibold text-green-800 dark:text-green-300">R$ {{ number_format($alimentacaoTotal, 2, ',', '.') }}</p>
                </div>

                <!-- Card Hospedagem -->
                @php
                    $hospedagemTotal = $expensesTable->firstWhere('cost_center_name', 'Hospedagem')['total_brl'] ?? 0;
                @endphp
                <div class="bg-yellow-100 dark:bg-yellow-900/20 p-4 rounded-lg">
                    <h3 class="text-sm text-gray-500 dark:text-gray-400">Hospedagem</h3>
                    <p class="text-lg font-semibold text-yellow-800 dark:text-yellow-300">R$ {{ number_format($hospedagemTotal, 2, ',', '.') }}</p>
                </div>

                <!-- Card Logística -->
                @php
                    $logisticaTotal = $expensesTable->firstWhere('cost_center_name', 'Logística')['total_brl'] ?? 0;
                @endphp
                <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
                    <h3 class="text-sm text-gray-500 dark:text-gray-400">Logística</h3>
                    <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">R$ {{ number_format($logisticaTotal, 2, ',', '.') }}</p>
                </div>
            </div>
        @endif

        <!-- Seção de Grupos de Despesas -->
        @forelse ($expensesTable as $group)
            <div class="mt-6">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $group['cost_center_name'] }}</h3>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Total: R$ {{ number_format($group['total_brl'], 2, ',', '.') }}
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contrato</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Descrição</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valor (BRL)</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Moeda</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($group['expenses'] as $expense)
                                <tr>
                                    <td class="px-3 py-1.5 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">{{ $expense['gig_contract_number'] }}</td>
                                    <td class="px-3 py-1.5 text-gray-600 dark:text-gray-400">{{ $expense['description'] }}</td>
                                    <td class="px-3 py-1.5 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $expense['expense_date'] }}</td>
                                    <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">R$ {{ number_format($expense['value_brl'], 2, ',', '.') }}</td>
                                    <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-600 dark:text-gray-400">{{ $expense['currency'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="text-center text-gray-500 dark:text-gray-400 mt-6">
                Nenhuma despesa disponível para o período selecionado.
            </p>
        @endforelse

        @if ($expensesTable->isNotEmpty())
            <div class="flex justify-end mt-4">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Total Geral: R$ {{ number_format($expensesTable->sum('total_brl'), 2, ',', '.') }}
                </span>
            </div>
        @endif
    </div>
</x-tab-nav>