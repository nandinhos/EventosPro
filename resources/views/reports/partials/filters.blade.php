<div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md ">
    <form method="GET" action="{{ route('reports.index', ['tab' => request()->input('tab', 'overview')]) }}">
        {{-- 
            Ajuste principal: 
            - Aumentamos o número de colunas no grid principal para acomodar os botões.
            - Os botões agora são itens diretos desse grid.
            - Usamos 'items-end' para alinhar todos os itens do grid pela base (útil se os labels estiverem acima dos inputs).
        --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4 items-end">
            
            {{-- Data Inicial --}}
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Inicial</label>
                <input type="date" name="start_date" id="start_date" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
            </div>

            {{-- Data Final --}}
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Final</label>
                <input type="date" name="end_date" id="end_date" value="{{ request('end_date', now()->endOfMonth()->format('Y-m-d')) }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
            </div>

            {{-- Filtro de Booker --}}
            <div>
                <label for="booker_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Booker</label>
                <select name="booker_id" id="booker_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">Todos</option>
                    @if (isset($bookers) && $bookers->isNotEmpty())
                        @foreach ($bookers as $booker)
                            <option value="{{ $booker->id }}" {{ request('booker_id') == $booker->id ? 'selected' : '' }}>
                                {{ $booker->name ?? 'Sem Nome' }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Filtro de Artista --}}
            <div>
                <label for="artist_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Artista</label>
                <select name="artist_id" id="artist_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">Todos</option>
                    @if (isset($artists) && $artists->isNotEmpty())
                        @foreach ($artists as $artist)
                            <option value="{{ $artist->id }}" {{ request('artist_id') == $artist->id ? 'selected' : '' }}>
                                {{ $artist->name ?? 'Sem Nome' }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
            
           
            {{-- Botão Filtrar - agora como um item do grid --}}
            {{-- Para telas menores, pode ser necessário ajustar o col-span ou a ordem --}}
            <div class="xl:col-start-5"> {{-- Tenta posicionar a partir da 5a coluna em telas xl --}}  
            <button type="submit" class="flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 w-full h-full">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
            </div>

            {{-- Botão Limpar - agora como um item do grid --}}
            <div>                
            <a href="{{ route('reports.index', ['tab' => request()->input('tab', 'overview')]) }}" 
                   class="flex items-center justify-center px-3 py-2 rounded-md text-sm border border-gray-300 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 w-full h-full">
                   {{-- h-full para tentar igualar altura com inputs, pode precisar de ajuste fino --}}
                    <i class="fas fa-broom mr-2"></i>Limpar
                </a>
            </div>
        </div>
    </form>
</div>