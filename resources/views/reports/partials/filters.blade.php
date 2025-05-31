<div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md mb-6">
    <form method="GET" action="{{ route('reports.index') }}" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Filtro de Datas -->
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Inicial</label>
                <input type="date" name="start_date" id="start_date" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Final</label>
                <input type="date" name="end_date" id="end_date" value="{{ request('end_date', now()->endOfMonth()->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
            </div>

            <!-- Filtro de Booker -->
            <div>
                <label for="booker_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Booker</label>
                <select name="booker_id" id="booker_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Todos</option>
                    @if (isset($bookers))
                        @foreach ($bookers as $booker)
                            <option value="{{ $booker->id }}" {{ request('booker_id') == $booker->id ? 'selected' : '' }}>
                                {{ $booker->name ?? 'Sem Nome' }}
                            </option>
                        @endforeach
                    @else
                        <option value="" disabled>Carregando bookers...</option>
                    @endif
                </select>
            </div>

            <!-- Filtro de Artista -->
            <div>
                <label for="artist_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Artista</label>
                <select name="artist_id" id="artist_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Todos</option>
                    @if (isset($artists) && $artists->isNotEmpty())
                        @foreach ($artists as $artist)
                            <option value="{{ $artist->id }}" {{ request('artist_id') == $artist->id ? 'selected' : '' }}>
                                {{ $artist->name ?? 'Sem Nome' }}
                            </option>
                        @endforeach
                    @else
                        <option value="" disabled>Nenhum artista disponível</option>
                    @endif
                </select>
            </div>
        </div>

        <div class="text-right">
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Filtrar
            </button>
        </div>
    </form>
</div>