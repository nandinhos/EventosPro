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

    {{-- Formulário --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <form action="{{ route('gigs.update', $gig) }}" method="POST">
            @csrf
            @if(isset($gig->id)) @method('PUT') @endif {{-- Adiciona PUT para edit --}}

    {{-- Campos hidden para guardar os parâmetros da URL anterior --}}
    @foreach($backUrlParams as $key => $value)
        @if(!is_array($value))
            <input type="hidden" name="backParams[{{ $key }}]" value="{{ $value }}">
        @endif
    @endforeach

            {{-- Inclui o formulário parcial, passando a gig existente e as tags selecionadas --}}
            @include('gigs._form', [
                'gig' => $gig,
                'artists' => $artists,
                'bookers' => $bookers,
                'tags' => $tags,
                'selectedTags' => $selectedTags
            ])

            {{-- Botões de Ação --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end items-center space-x-3">
                 {{-- Link Ver Detalhes (mantém como está ou adiciona params se quiser voltar para index filtrada DEPOIS de ver detalhes) --}}
                 <a href="{{ route('gigs.show', ['gig' => $gig] + $backUrlParams) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                    Ver Detalhes
                </a>
                 <span class="text-gray-300 dark:text-gray-600">|</span>
                 {{-- AJUSTE AQUI: Adiciona $backUrlParams à rota do botão Cancelar Edição --}}
                 <a href="{{ route('gigs.index', $backUrlParams) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                    Cancelar Edição
                </a>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm ml-auto">
                    <i class="fas fa-save mr-1"></i> Atualizar Gig
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

{{-- Adicionar JS para Select Múltiplo (Ex: TomSelect) se desejar --}}
@push('scripts')
{{-- <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('tags')) {
            new TomSelect('#tags',{
                plugins: ['remove_button'],
            });
        }
    });
</script> --}}
@endpush