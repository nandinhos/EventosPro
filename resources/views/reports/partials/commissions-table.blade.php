<x-tab-nav :active-tab="'commissions'">
    @section('exportButtons')
        <x-slot-button :type="'commissions'" :filters="$filters" />
    @endsection

    <div class="space-y-6 mt-6">
        <!-- Seção de Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-indigo-100 dark:bg-indigo-900/20 p-4 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Total de Comissões</h3>
                <p class="text-lg font-semibold text-indigo-800 dark:text-indigo-300">R$ {{ number_format($commissionsSummary['total_commissions'] ?? 0, 2, ',', '.') }}</p>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Eventos com Comissão</h3>
                <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">{{ $commissionsSummary['events_with_commissions'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Tabela de Comissões -->
        @if (isset($commissionsTable) && $commissionsTable->isNotEmpty())
            <div class="overflow-x-auto mt-6">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contrato</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Booker</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Comissão (BRL)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($commissionsTable as $row)
                            <tr>
                                <td class="px-3 py-1.5 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">{{ $row['contract_number'] ?? 'N/A' }}</td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $row['gig_date'] ?? 'N/A' }}</td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $row['booker'] ?? 'N/A' }}</td>
                                <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">R$ {{ number_format($row['commission'] ?? 0, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-center text-gray-500 dark:text-gray-400 mt-6">
                Nenhum dado de comissões disponível para o período selecionado.
            </p>
        @endif
    </div>
</x-tab-nav>