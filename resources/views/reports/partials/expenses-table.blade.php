@php
    // Variáveis esperadas do controller: $groupedExpensesReport
    $expenseGroups = $groupedExpensesReport['groups'] ?? collect([]);
    $totalGeral = $groupedExpensesReport['total_geral'] ?? 0;
    $totalConfirmado = $groupedExpensesReport['total_confirmado'] ?? 0;
    $totalPendente = $groupedExpensesReport['total_pendente'] ?? 0;
@endphp

<div class="space-y-6 mt-4">
    {{-- Cards de Resumo para Despesas --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-red-100 dark:bg-red-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Geral de Despesas</h3>
            <p class="text-lg font-semibold text-red-800 dark:text-red-300">R$ {{ number_format($totalGeral, 2, ',', '.') }}</p>
        </div>
        <div class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Confirmado</h3>
            <p class="text-lg font-semibold text-green-800 dark:text-green-300">R$ {{ number_format($totalConfirmado, 2, ',', '.') }}</p>
        </div>
        <div class="bg-yellow-100 dark:bg-yellow-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Pendente</h3>
            <p class="text-lg font-semibold text-yellow-800 dark:text-yellow-300">R$ {{ number_format($totalPendente, 2, ',', '.') }}</p>
        </div>
    </div>

    {{-- Tabela Agrupada por Centro de Custo --}}
    @if ($expenseGroups->isNotEmpty())
        <div class="space-y-6">
            {{-- Loop através dos grupos de Centro de Custo --}}
            @foreach ($expenseGroups as $group)
                <div class="bg-white dark:bg-gray-800/50 rounded-lg shadow-sm border dark:border-gray-700">
                    {{-- Cabeçalho do Grupo com Subtotal --}}
                    <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-t-lg flex justify-between items-center">
                        <h4 class="text-md font-semibold text-gray-800 dark:text-white">{{ $group['cost_center_name'] }}</h4>
                        <span class="text-sm font-bold text-gray-600 dark:text-gray-300">
                            Subtotal: R$ {{ number_format($group['subtotal'], 2, ',', '.') }}
                        </span>
                    </div>

                    {{-- Tabela de Despesas do Grupo --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Data Despesa</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Gig / Artista</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Descrição</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase">Valor</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 uppercase">NF do Artista?</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($group['costs'] as $cost)
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $cost->expense_date ? $cost->expense_date->isoFormat('L') : 'N/A' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <a href="{{ route('gigs.show', $cost->gig) }}" class="font-semibold text-primary-600 hover:underline" title="{{ $cost->gig->location_event_details ?? 'Ver Gig' }}">
                                                Gig #{{ $cost->gig_id }}
                                            </a>
                                            <span class="block text-gray-500">{{ $cost->gig->artist->name ?? 'N/A' }}</span>
                                        </td>
                                        <td class="px-3 py-2 whitespace-normal max-w-xs truncate" title="{{ $cost->description }}">{{ $cost->description }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-right font-semibold">{{ $cost->currency }} {{ number_format($cost->value, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center">
                                            @if($cost->is_confirmed)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">Confirmada</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">Pendente</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center">
                                            @if($cost->is_invoice)
                                                <i class="fas fa-check-circle text-green-500" title="Sim, reembolsável"></i>
                                            @else
                                                <i class="fas fa-times-circle text-gray-400" title="Não"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-center text-gray-500 dark:text-gray-400 mt-6">
            Nenhuma despesa encontrada para os filtros selecionados.
        </p>
    @endif
</div>