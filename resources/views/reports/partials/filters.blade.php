<div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-md">
    <form action="{{ route('reports.index') }}" method="GET">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <x-form.input type="date" id="start_date" name="start_date" label="Data Inicial" :value="old('start_date', $filters['start_date'] ?? '')" />
            <x-form.input type="date" id="end_date" name="end_date" label="Data Final" :value="old('end_date', $filters['end_date'] ?? '')" />
            <x-form.select id="booker_id" name="booker_id" label="Booker" :options="$bookers" :selected="old('booker_id', $filters['booker_id'] ?? '')" placeholder="Todos" />
            <x-form.select id="artist_id" name="artist_id" label="Artista" :options="$artists" :selected="old('artist_id', $filters['artist_id'] ?? '')" placeholder="Todos" />
        </div>
        <div class="flex justify-end space-x-2">
            <a href="{{ route('reports.index') }}" class="bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 px-3 py-2 rounded-md text-sm">
                Limpar
            </a>
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-2 rounded-md text-sm">
                <i class="fas fa-filter mr-1"></i> Filtrar
            </button>
        </div>
    </form>
</div>