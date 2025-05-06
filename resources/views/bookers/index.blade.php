<x-app-layout>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Gerenciamento de Bookers</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Adicione, edite ou remova bookers.</p>
        </div>
        <a href="{{ route('bookers.create') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md flex items-center text-sm shrink-0">
            <i class="fas fa-plus mr-2"></i> Novo Booker
        </a>
    </div>

    {{-- Tabela --}}
    <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nome</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Comissão Padrão (%)</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gigs</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($bookers as $booker)
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap font-semibold text-gray-900 dark:text-white">{{ $booker->name }}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-gray-600 dark:text-gray-400">{{ $booker->default_commission_rate ? number_format($booker->default_commission_rate, 2, ',', '.') . '%' : '-' }}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-gray-600 dark:text-gray-400">{{ $booker->gigs_count }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                {{-- NOVO: Botão Ver --}}
                    <a href="{{ route('bookers.show', $booker) }}" title="Ver Detalhes" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                        <i class="fas fa-eye fa-fw"></i>
                    </a>
                                    <a href="{{ route('bookers.edit', $booker) }}" title="Editar" class="text-primary-500 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                        <i class="fas fa-edit fa-fw"></i>
                                    </a>
                                    <form action="{{ route('bookers.destroy', $booker) }}" method="POST" onsubmit="return confirm('Tem certeza?');" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Excluir" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                            <i class="fas fa-trash-alt fa-fw"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Nenhum booker encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
         {{-- Paginação --}}
         <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6 mt-4">
             @if ($bookers->hasPages())
                {{ $bookers->appends(request()->query())->links() }}
            @endif
        </div>
    </div>
</x-app-layout>