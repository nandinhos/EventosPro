<x-app-layout>
    {{-- Cabeçalho da Página --}}
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Editar Gig #{{ $gig->id }}: {{ $gig->artist->name ?? 'N/A' }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Atualize as informações da data/evento.</p>
        </div>
         <a href="{{ route('gigs.index') }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">
             <i class="fas fa-arrow-left mr-1"></i> Voltar para Lista
        </a>
    </div>

    {{-- Formulário --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        {{-- Formulário aponta para a rota update, usando método PUT --}}
        <form action="{{ route('gigs.update', $gig) }}" method="POST">
            @csrf
            @method('PUT') {{-- Define o método HTTP como PUT para atualização --}}

            {{-- Inclui o mesmo formulário parcial, passando a $gig existente --}}
            @include('gigs._form', [
                'gig' => $gig, {{-- Passa a gig atual para preencher os campos --}}
                'artists' => $artists,
                'bookers' => $bookers,
                'tags' => $tags,
                'selectedTags' => old('tags', $selectedTags) {{-- Usa 'old' ou as tags selecionadas do controller --}}
            ])

            {{-- Botões de Ação --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end items-center space-x-3">
                <a href="{{ route('gigs.index') }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">
                    Cancelar
                </a>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm">
                    <i class="fas fa-save mr-1"></i> Atualizar Gig
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

{{-- Adicionar JS para Select Múltiplo (Ex: TomSelect) se desejar --}}
{{-- @push('scripts') ... @endpush --}}