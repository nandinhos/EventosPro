<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                {{ __('Custos Operacionais da Agência') }}
            </h2>
            <a href="{{ route('agency-costs.create') }}" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                {{ __('Adicionar Custo') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @forelse ($groupedCosts as $month => $costs)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white uppercase">
                            {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->isoFormat('MMMM [de] YYYY') }}
                        </h3>
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Descrição
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Centro de Custo
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Tipo
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Vencimento
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Valor Mensal
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Ações</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($costs as $cost)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $cost->description }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $cost->costCenter->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($cost->cost_type === 'GIG')
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                    Operacional
                                                </span>
                                            @else
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                    Administrativo
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $cost->due_date ? $cost->due_date->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            R$ {{ number_format($cost->monthly_value, 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center space-x-2 justify-end">
                                                <a href="{{ route('agency-costs.show', $cost) }}" title="Ver Detalhes" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                                    <i class="fas fa-eye fa-fw"></i>
                                                </a>
                                                <a href="{{ route('agency-costs.edit', $cost) }}" title="Editar" class="text-primary-500 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                                    <i class="fas fa-edit fa-fw"></i>
                                                </a>
                                                <form action="{{ route('agency-costs.destroy', $cost) }}" method="POST" onsubmit="return confirm('Tem certeza?');" class="inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" title="Excluir" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                                        <i class="fas fa-trash-alt fa-fw"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <td colspan="4" class="px-6 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-200">
                                        Subtotal do Mês:
                                    </td>
                                    <td class="px-6 py-3 text-left text-sm font-bold text-gray-800 dark:text-white">
                                        R$ {{ number_format($costs->sum('monthly_value'), 2, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                        Nenhum custo operacional encontrado.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
