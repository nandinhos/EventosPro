<x-app-layout>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Editar Artista: {{ $artist->name }}</h2>
        </div>
         <x-back-button :fallback="route('artists.index')" class="bg-gray-200 hover:bg-gray-300 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 px-4 py-2 rounded-md text-sm" />
    </div>
     <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <form action="{{ route('artists.update', $artist) }}" method="POST">
            @csrf
            @method('PUT')
            @include('artists._form', ['artist' => $artist, 'tags' => $tags, 'selectedTags' => old('tags', $selectedTags)])
             {{-- Botões --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end items-center space-x-3">
                <a href="{{ route('artists.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 px-4 py-2 rounded-md text-sm">Cancelar</a>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md flex items-center text-sm shrink-0">Atualizar Artista</button>
            </div>
        </form>
    </div>
</x-app-layout>