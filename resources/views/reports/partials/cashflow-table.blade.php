<div class="overflow-x-auto mt-6">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contrato</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Receita (BRL)</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Custos (BRL)</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fluxo de Caixa Líquido (BRL)</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($cashflowTable as $row)
                <tr>
                    <td class="px-3 py-1.5 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">{{ $row['contract_number'] }}</td>
                    <td class="px-3 py-1.5 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $row['gig_date'] }}</td>
                    <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">R$ {{ number_format($row['revenue'], 2, ',', '.') }}</td>
                    <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">R$ {{ number_format($row['costs'], 2, ',', '.') }}</td>
                    <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">R$ {{ number_format($row['net_cashflow'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                        Nenhum dado disponível para o período selecionado.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($cashflowTable->isNotEmpty())
            <tfoot class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <td class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" colspan="2">Totais</td>
                    <td class="px-3 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-300">
                        R$ {{ number_format($cashflowTable->sum('revenue'), 2, ',', '.') }}
                    </td>
                    <td class="px-3 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-300">
                        R$ {{ number_format($cashflowTable->sum('costs'), 2, ',', '.') }}
                    </td>
                    <td class="px-3 py-2 text-right text-xs font-medium text-gray-700 dark:text-gray-300">
                        R$ {{ number_format($cashflowTable->sum('net_cashflow'), 2, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>