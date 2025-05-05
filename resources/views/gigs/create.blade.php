{{-- resources/views/gigs/create.blade.php --}}
<x-app-layout>
    {{-- Cabeçalho da Página --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Criar Nova Gig (Data)</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Insira as informações da nova data/evento.</p>
        </div>
        <a href="{{ route('gigs.index', $backUrlParams) }}" class="bg-gray-200 ...">
    <i class="fas fa-arrow-left mr-1"></i> Voltar para Lista
</a>
    </div>

    {{-- Formulário --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <form action="{{ route('gigs.store') }}" method="POST">
            @csrf
            @if(isset($gig->id)) @method('PUT') @endif {{-- Adiciona PUT para edit --}}

    {{-- Campos hidden para guardar os parâmetros da URL anterior --}}
    @foreach($backUrlParams as $key => $value)
        @if(!is_array($value))
            <input type="hidden" name="backParams[{{ $key }}]" value="{{ $value }}">
        @endif
    @endforeach
            {{-- Inclui o formulário parcial --}}
            {{-- Passa um modelo Gig vazio e arrays vazios/coletados para selects/tags --}}
            @include('gigs._form', [
                'gig' => new \App\Models\Gig(),
                'artists' => $artists,
                'bookers' => $bookers,
                'tags' => $tags,
                'selectedTags' => old('tags', [])
            ])

            {{-- Botões de Ação --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end items-center space-x-3">
                {{-- AJUSTE AQUI: Adiciona $backUrlParams à rota do botão Cancelar --}}
                <a href="{{ route('gigs.index', $backUrlParams) }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">
                    Cancelar
                </a>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm">
                    <i class="fas fa-save mr-1"></i> Salvar Gig
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
                // create: true, // Habilitar se quiser permitir criar tags on-the-fly
                // onItemAdd: function() { this.setTextboxValue(''); this.refreshOptions(); }, // Limpa input após add
                // // Opção para buscar tags via API se forem muitas (load)
            });
        }
    });
</script> --}}
@endpush