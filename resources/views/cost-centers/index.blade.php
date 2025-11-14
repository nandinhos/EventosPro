<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Centros de Custo') }}
            </h2>
            @can('manage cost-centers')
                <a href="{{ route('cost-centers.create') }}"
                   class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-5 py-2 rounded-md flex items-center text-sm shadow-md transition">
                    <i class="fas fa-plus mr-2"></i> Novo Centro de Custo
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('cost-centers.index') }}" class="mb-6 flex gap-4">
                        <input type="text" name="search" placeholder="Buscar por nome ou descrição..."
                               value="{{ request('search') }}"
                               class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">

                        <select name="status" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">Todos os Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Ativos</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inativos</option>
                        </select>

                        <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">Filtrar</button>
                        <a href="{{ route('cost-centers.index') }}" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded">Limpar</a>
                    </form>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nome</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Descrição</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Cor</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Custos</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($costCenters as $costCenter)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $costCenter->name }}</td>
                                        <td class="px-6 py-4 text-sm">{{ Str::limit($costCenter->description, 50) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if($costCenter->is_active)
                                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Ativo</span>
                                            @else
                                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inativo</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if($costCenter->color)
                                                <span class="inline-block w-6 h-6 rounded" style="background-color: {{ $costCenter->color }}"></span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $costCenter->gig_costs_count }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @can('manage cost-centers')
                                                <div class="flex items-center justify-center space-x-3">
                                                    <a href="{{ route('cost-centers.edit', $costCenter) }}" title="Editar"
                                                       class="text-primary-500 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                                        <i class="fas fa-edit fa-fw"></i>
                                                    </a>
                                                    <form action="{{ route('cost-centers.destroy', $costCenter) }}" method="POST" class="inline"
                                                          onsubmit="return confirm('Tem certeza que deseja excluir este centro de custo?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" title="Excluir"
                                                                class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                                            <i class="fas fa-trash-alt fa-fw"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">Nenhum centro de custo encontrado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $costCenters->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
