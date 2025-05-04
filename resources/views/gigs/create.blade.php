<x-app-layout>
    {{-- Cabeçalho da Página --}}
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Criar Nova Gig (Data)</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Insira as informações da nova data/evento.</p>
        </div>
         <a href="{{ route('gigs.index') }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">
             <i class="fas fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>

    {{-- Formulário --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        {{-- Usar um componente de formulário parcial para reutilizar em create e edit --}}
        <form action="{{ route('gigs.store') }}" method="POST">
            @csrf
            {{-- Passar variáveis necessárias para o formulário parcial --}}
            @include('gigs._form', [
                'gig' => new \App\Models\Gig(), {{-- Passa um modelo vazio para create --}}
                'artists' => $artists,
                'bookers' => $bookers,
                'tags' => $tags,
                'selectedTags' => old('tags', []) {{-- Para repopular tags em caso de erro --}}
            ])

            {{-- Botões de Ação --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end items-center space-x-3">
                <a href="{{ route('gigs.index') }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">
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
{{-- @push('scripts')
<script>
    // Inicializar TomSelect para o campo de tags
    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('tags')) {
            new TomSelect('#tags',{
                plugins: ['remove_button'],
                create: true, // Permitir criar novas tags (requer lógica no backend)
                // ... outras opções
            });
        }
    });
</script>
@endpush --}}