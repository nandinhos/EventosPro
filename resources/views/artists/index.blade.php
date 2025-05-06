<x-app-layout>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Gerenciamento de Artistas</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Adicione, edite ou remova artistas.</p>
        </div>
        <a href="{{ route('artists.create') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md flex items-center text-sm shrink-0">
            <i class="fas fa-plus mr-2"></i> Novo Artista
        </a>
    </div>

    {{-- TODO: Adicionar Filtros/Busca aqui se necessário --}}

    {{-- Tabela --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        {{-- Coluna Combinada --}}
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Artista / Contato</th>
                        {{-- Coluna Contato Removida --}}
                        {{-- <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contato</th> --}}
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gigs</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($artists as $artist)
                        <tr>
                            {{-- Coluna Combinada --}}
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="font-semibold text-gray-900 dark:text-white block">{{ $artist->name }}</span>
                                @if($artist->contact_info)
                                <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ $artist->contact_info }}</span>
                                @endif
                            </td>
                            {{-- Coluna Contato Removida --}}
                            {{-- <td class="px-4 py-2 whitespace-normal text-gray-600 dark:text-gray-400">{{ $artist->contact_info ?: '-' }}</td> --}}
                            <td class="px-4 py-2 whitespace-nowrap text-center text-gray-600 dark:text-gray-400">{{ $artist->gigs_count }}</td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    {{-- Botões Ver, Editar, Excluir --}}
                                    <a href="{{ route('artists.show', $artist) }}" title="Ver Detalhes" class="text-blue-500 ..."><i class="fas fa-eye fa-fw"></i></a>
                                    <a href="{{ route('artists.edit', $artist) }}" title="Editar" class="text-primary-500 ..."><i class="fas fa-edit fa-fw"></i></a>
                                    <form action="{{ route('artists.destroy', $artist) }}" method="POST" onsubmit="return confirm('Tem certeza?');" class="inline"> @csrf @method('DELETE') <button type="submit" title="Excluir" class="text-red-500 ..."><i class="fas fa-trash-alt fa-fw"></i></button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            {{-- Ajustar colspan --}}
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Nenhum artista encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
         {{-- Paginação --}}
         <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6 mt-4">
             @if ($artists->hasPages())
                {{ $artists->appends(request()->query())->links() }}
            @endif
        </div>
    </div>
</x-app-layout>