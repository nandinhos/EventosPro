{{-- resources/views/gigs/create.blade.php --}}
<x-app-layout>
    {{-- Cabeçalho da Página --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Criar Nova Gig (Data)</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Insira as informações da nova data/evento.</p>
        </div>
        <x-back-button :fallback="route('gigs.index', $backUrlParams)" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">Voltar para Lista</x-back-button>
    </div>

    {{-- Card Principal com Abas --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <form action="{{ route('gigs.store') }}" method="POST">
            @csrf
            {{-- Campos hidden para guardar os parâmetros da URL anterior (se houver) --}}
            @foreach($backUrlParams as $key => $value)
                @if(!is_array($value))
                    <input type="hidden" name="backParams[{{ $key }}]" value="{{ $value }}">
                @endif
            @endforeach

            {{-- Inclui o formulário parcial com abas --}}
            @include('gigs._form', [
                'gig' => new \App\Models\Gig(),
                'artists' => $artists,
                'bookers' => $bookers,
                'tags' => $tags,
                'selectedTags' => old('tags', []),
                'expensesDataForView' => $expensesDataForView,
                'costCenters' => $costCenters ?? \App\Models\CostCenter::orderBy('name')->pluck('name', 'id') // Garante que costCenters exista
            ])

            {{-- Botões de Ação --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex justify-end items-center space-x-3 rounded-b-xl">
                <a href="{{ route('gigs.index', $backUrlParams) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                    Cancelar
                </a>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm flex items-center">
                    <i class="fas fa-save mr-1"></i> Salvar Gig
                </button>
            </div>
        </form>
    </div>
</x-app-layout>