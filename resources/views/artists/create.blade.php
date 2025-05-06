<x-app-layout>
     <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Novo Artista</h2>
        </div>
         <a href="{{ route('artists.index') }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">
             <i class="fas fa-arrow-left mr-1"></i> Voltar
        </a>
    </div>
     <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <form action="{{ route('artists.store') }}" method="POST">
            @csrf
            @include('artists._form', ['artist' => new \App\Models\Artist(), 'tags' => $tags, 'selectedTags' => old('tags', [])])
            {{-- Botões --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end items-center space-x-3">
                <a href="{{ route('artists.index') }}" class="bg-gray-200 ...">Cancelar</a>
                <button type="submit" class="bg-primary-600 ...">Salvar Artista</button>
            </div>
        </form>
    </div>
</x-app-layout>