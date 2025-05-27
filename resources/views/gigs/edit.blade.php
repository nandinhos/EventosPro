{{-- resources/views/gigs/edit.blade.php --}}
<x-app-layout>
    {{-- Cabeçalho da Página --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Editar Gig #{{ $gig->id }}: {{ $gig->artist->name ?? 'N/A' }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Atualize as informações da data/evento.</p>
        </div>
        <a href="{{ route('gigs.index', $backUrlParams) }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Voltar para Lista
        </a>
    </div>

    {{-- Card Principal com Abas --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <form action="{{ route('gigs.update', $gig) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Campos hidden para guardar os parâmetros da URL anterior --}}
            @foreach($backUrlParams as $key => $value)
                @if(!is_array($value))
                    <input type="hidden" name="backParams[{{ $key }}]" value="{{ $value }}">
                @endif
            @endforeach

            {{-- Inclui o formulário parcial com abas --}}
            @include('gigs._form', [
                'gig' => $gig,
                'artists' => $artists,
                'bookers' => $bookers,
                'tags' => $tags,
                'selectedTags' => $selectedTags, // Vem do controller
                'costCenters' => $costCenters ?? \App\Models\CostCenter::orderBy('name')->pluck('name', 'id'), // Garante que costCenters exista
                'expensesDataForView' => $expensesDataForView
            ])

            {{-- Botões de Ação --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex justify-end items-center space-x-3 rounded-b-xl">
                 <a href="{{ route('gigs.show', ['gig' => $gig] + $backUrlParams) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                    Ver Detalhes
                </a>
                 <span class="text-gray-300 dark:text-gray-600">|</span>
                 <a href="{{ route('gigs.index', $backUrlParams) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                    Cancelar Edição
                </a>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm flex items-center ml-auto">
                    <i class="fas fa-save mr-1"></i> Atualizar Gig
                </button>
            </div>
        </form>
    </div>
</x-app-layout>