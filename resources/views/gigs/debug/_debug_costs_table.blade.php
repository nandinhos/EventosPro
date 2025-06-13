@props(['gig'])

<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900 dark:text-gray-100">
        <h3 class="text-lg font-medium border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">Despesas Consideradas</h3>
        @if($gig->costs->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Descrição</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase">Valor (BRL)</th>
                            <th class="px-4 py-2 text-center font-medium text-gray-500 dark:text-gray-400 uppercase">Confirmada?</th>
                            <th class="px-4 py-2 text-center font-medium text-gray-500 dark:text-gray-400 uppercase">Reembolsável? (NF)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($gig->costs as $cost)
                            <tr>
                                <td class="px-4 py-2">{{ $cost->description ?: '-' }}</td>
                                <td class="px-4 py-2 text-right">R$ {{ number_format($cost->value, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-center">
                                    @if($cost->is_confirmed)
                                        <span class="text-green-500">Sim</span>
                                    @else
                                        <span class="text-red-500">Não</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center">
                                     @if($cost->is_invoice)
                                        <span class="text-green-500">Sim</span>
                                    @else
                                        <span class="text-red-500">Não</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p>Nenhuma despesa registrada para esta Gig.</p>
        @endif
    </div>
</div>