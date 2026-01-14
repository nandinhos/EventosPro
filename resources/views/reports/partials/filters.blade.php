{{-- Dados para o Alpine.js --}}
<script>
    window.artistsData = @json($artists->map(fn($a) => ['id' => $a->id, 'name' => $a->name]));
    window.selectedArtistIds = @json(array_map('intval', request('artist_ids', [])));
    window.bookersData = @json($bookers->map(fn($b) => ['id' => $b->id, 'name' => $b->name]));
    window.selectedBookerIds = @json(array_map('intval', request('booker_ids', [])));
</script>

<div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md"
     x-data="reportFilter()"
     x-init="init()">
    <form method="GET" action="{{ route('reports.index') }}">
        {{-- Campo hidden para preservar a aba atual --}}
        <input type="hidden" name="tab" :value="currentTab">

        {{-- Hidden inputs para os artistas selecionados --}}
        <template x-for="artistId in selectedArtists" :key="artistId">
            <input type="hidden" name="artist_ids[]" :value="artistId">
        </template>

        {{-- Hidden inputs para os bookers selecionados --}}
        <template x-for="bookerId in selectedBookers" :key="bookerId">
            <input type="hidden" name="booker_ids[]" :value="bookerId">
        </template>

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

            {{-- Filtro de Booker (Multi-Select com Busca) --}}
            <div class="relative" @click.away="bookerDropdownOpen = false">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Booker</label>
                <button type="button"
                        @click="bookerDropdownOpen = !bookerDropdownOpen"
                        class="mt-1 relative w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-pointer focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 text-sm text-gray-900 dark:text-white">
                    <span class="block truncate" x-text="selectedBookers.length ? selectedBookers.length + ' selecionado(s)' : 'Todos os bookers'"></span>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </span>
                </button>

                {{-- Dropdown --}}
                <div x-show="bookerDropdownOpen"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg max-h-72 rounded-md ring-1 ring-black ring-opacity-5 overflow-hidden"
                     style="display: none;">

                    {{-- Campo de busca + Ações rápidas --}}
                    <div class="p-2 border-b border-gray-200 dark:border-gray-600 space-y-2">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text"
                                   x-model="bookerSearch"
                                   @click.stop
                                   placeholder="Buscar booker..."
                                   class="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-white">
                        </div>
                        {{-- Ações rápidas: Selecionar Todos / Limpar --}}
                        <div class="flex justify-between text-xs">
                            <button type="button"
                                    @click="selectAllBookers()"
                                    class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">
                                <i class="fas fa-check-double mr-1"></i>Selecionar Todos
                            </button>
                            <button type="button"
                                    @click="clearAllBookers()"
                                    class="text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400">
                                <i class="fas fa-times mr-1"></i>Limpar seleção
                            </button>
                        </div>
                    </div>

                    {{-- Lista de bookers com checkbox --}}
                    <ul class="max-h-48 overflow-y-auto py-1">
                        <template x-for="booker in filteredBookers" :key="booker.id">
                            <li @click="toggleBooker(booker.id)"
                                class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm text-gray-900 dark:text-gray-100">
                                <div class="flex items-center">
                                    <input type="checkbox"
                                           :checked="isBookerSelected(booker.id)"
                                           @click.stop="toggleBooker(booker.id)"
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <span class="ml-3 block truncate" x-text="booker.name"></span>
                                </div>
                                <span x-show="isBookerSelected(booker.id)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-indigo-600">
                                    <i class="fas fa-check text-xs"></i>
                                </span>
                            </li>
                        </template>
                        <li x-show="filteredBookers.length === 0" class="py-2 px-3 text-sm text-gray-500 dark:text-gray-400">
                            Nenhum booker encontrado
                        </li>
                    </ul>

                    {{-- Rodapé do dropdown --}}
                    <div class="border-t border-gray-200 dark:border-gray-600 p-2 flex justify-end">
                        <button type="button"
                                @click="bookerDropdownOpen = false"
                                class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">
                            <i class="fas fa-check mr-1"></i>Concluído
                        </button>
                    </div>
                </div>
            </div>

            {{-- Filtro de Artistas (Multi-Select com Busca) --}}
            <div class="relative" @click.away="artistDropdownOpen = false">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Artistas</label>
                <button type="button"
                        @click="artistDropdownOpen = !artistDropdownOpen"
                        class="mt-1 relative w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-pointer focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 text-sm text-gray-900 dark:text-white">
                    <span class="block truncate" x-text="selectedArtists.length ? selectedArtists.length + ' selecionado(s)' : 'Todos os artistas'"></span>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </span>
                </button>

                {{-- Dropdown --}}
                <div x-show="artistDropdownOpen"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg max-h-72 rounded-md ring-1 ring-black ring-opacity-5 overflow-hidden"
                     style="display: none;">

                    {{-- Campo de busca + Ações rápidas --}}
                    <div class="p-2 border-b border-gray-200 dark:border-gray-600 space-y-2">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text"
                                   x-model="artistSearch"
                                   @click.stop
                                   placeholder="Buscar artista..."
                                   class="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-white">
                        </div>
                        {{-- Ações rápidas: Selecionar Todos / Limpar --}}
                        <div class="flex justify-between text-xs">
                            <button type="button"
                                    @click="selectAllArtists()"
                                    class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">
                                <i class="fas fa-check-double mr-1"></i>Selecionar Todos
                            </button>
                            <button type="button"
                                    @click="clearAllArtists()"
                                    class="text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400">
                                <i class="fas fa-times mr-1"></i>Limpar seleção
                            </button>
                        </div>
                    </div>

                    {{-- Lista de artistas com checkbox --}}
                    <ul class="max-h-48 overflow-y-auto py-1">
                        <template x-for="artist in filteredArtists" :key="artist.id">
                            <li @click="toggleArtist(artist.id)"
                                class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm text-gray-900 dark:text-gray-100">
                                <div class="flex items-center">
                                    <input type="checkbox"
                                           :checked="isArtistSelected(artist.id)"
                                           @click.stop="toggleArtist(artist.id)"
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <span class="ml-3 block truncate" x-text="artist.name"></span>
                                </div>
                                <span x-show="isArtistSelected(artist.id)" class="absolute inset-y-0 right-0 flex items-center pr-3 text-indigo-600">
                                    <i class="fas fa-check text-xs"></i>
                                </span>
                            </li>
                        </template>
                        <li x-show="filteredArtists.length === 0" class="py-2 px-3 text-sm text-gray-500 dark:text-gray-400">
                            Nenhum artista encontrado
                        </li>
                    </ul>

                    {{-- Rodapé do dropdown --}}
                    <div class="border-t border-gray-200 dark:border-gray-600 p-2 flex justify-end">
                        <button type="button"
                                @click="artistDropdownOpen = false"
                                class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">
                            <i class="fas fa-check mr-1"></i>Concluído
                        </button>
                    </div>
                </div>
            </div>

            {{-- Botão Filtrar --}}
            <div class="xl:col-start-5">
                <button type="submit" class="flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 w-full h-full">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
            </div>

            {{-- Botão Limpar --}}
            <div>
                <button type="button"
                       @click="window.location.href = '{{ route('reports.index') }}?tab=' + currentTab"
                       class="flex items-center justify-center px-3 py-2 rounded-md text-sm border border-gray-300 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 w-full h-full">
                    <i class="fas fa-broom mr-2"></i>Limpar
                </button>
            </div>
        </div>

        {{-- Badges dos bookers selecionados --}}
        <div x-show="selectedBookers.length > 0"
             x-transition
             class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center flex-wrap gap-1.5">
                <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">Bookers:</span>
                <template x-for="bookerId in selectedBookers" :key="bookerId">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                        <span x-text="getBookerName(bookerId)"></span>
                        <button type="button"
                                @click="removeBooker(bookerId)"
                                class="flex-shrink-0 ml-0.5 h-4 w-4 rounded-full inline-flex items-center justify-center text-emerald-600 hover:bg-emerald-200 hover:text-emerald-800 dark:text-emerald-300 dark:hover:bg-emerald-800 dark:hover:text-emerald-100 focus:outline-none">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                    </span>
                </template>
                <button type="button"
                        @click="clearAllBookers()"
                        x-show="selectedBookers.length > 1"
                        class="text-xs text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 ml-2">
                    (limpar todos)
                </button>
            </div>
        </div>

        {{-- Badges dos artistas selecionados --}}
        <div x-show="selectedArtists.length > 0"
             x-transition
             :class="{ 'mt-3 pt-3 border-t border-gray-200 dark:border-gray-700': selectedBookers.length === 0, 'mt-2': selectedBookers.length > 0 }">
            <div class="flex items-center flex-wrap gap-1.5">
                <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">Artistas:</span>
                <template x-for="artistId in selectedArtists" :key="artistId">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                        <span x-text="getArtistName(artistId)"></span>
                        <button type="button"
                                @click="removeArtist(artistId)"
                                class="flex-shrink-0 ml-0.5 h-4 w-4 rounded-full inline-flex items-center justify-center text-indigo-600 hover:bg-indigo-200 hover:text-indigo-800 dark:text-indigo-300 dark:hover:bg-indigo-800 dark:hover:text-indigo-100 focus:outline-none">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                    </span>
                </template>
                <button type="button"
                        @click="clearAllArtists()"
                        x-show="selectedArtists.length > 1"
                        class="text-xs text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 ml-2">
                    (limpar todos)
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reportFilter', () => ({
            currentTab: new URLSearchParams(window.location.search).get('tab') || 'overview',

            // Estado para Artistas
            artistSearch: '',
            artistDropdownOpen: false,
            selectedArtists: [],
            artists: [],

            // Estado para Bookers
            bookerSearch: '',
            bookerDropdownOpen: false,
            selectedBookers: [],
            bookers: [],

            init() {
                this.artists = window.artistsData || [];
                this.selectedArtists = window.selectedArtistIds || [];
                this.bookers = window.bookersData || [];
                this.selectedBookers = window.selectedBookerIds || [];
            },

            // Métodos para Artistas
            get filteredArtists() {
                if (!this.artistSearch) return this.artists;
                const s = this.artistSearch.toLowerCase();
                return this.artists.filter(a => a.name.toLowerCase().includes(s));
            },

            toggleArtist(id) {
                const idx = this.selectedArtists.indexOf(id);
                if (idx > -1) {
                    this.selectedArtists.splice(idx, 1);
                } else {
                    this.selectedArtists.push(id);
                }
            },

            isArtistSelected(id) {
                return this.selectedArtists.includes(id);
            },

            getArtistName(id) {
                const artist = this.artists.find(a => a.id === id);
                return artist ? artist.name : '';
            },

            removeArtist(id) {
                const idx = this.selectedArtists.indexOf(id);
                if (idx > -1) this.selectedArtists.splice(idx, 1);
            },

            clearAllArtists() {
                this.selectedArtists = [];
            },

            selectAllArtists() {
                this.selectedArtists = this.artists.map(a => a.id);
            },

            // Métodos para Bookers
            get filteredBookers() {
                if (!this.bookerSearch) return this.bookers;
                const s = this.bookerSearch.toLowerCase();
                return this.bookers.filter(b => b.name.toLowerCase().includes(s));
            },

            toggleBooker(id) {
                const idx = this.selectedBookers.indexOf(id);
                if (idx > -1) {
                    this.selectedBookers.splice(idx, 1);
                } else {
                    this.selectedBookers.push(id);
                }
            },

            isBookerSelected(id) {
                return this.selectedBookers.includes(id);
            },

            getBookerName(id) {
                const booker = this.bookers.find(b => b.id === id);
                return booker ? booker.name : '';
            },

            removeBooker(id) {
                const idx = this.selectedBookers.indexOf(id);
                if (idx > -1) this.selectedBookers.splice(idx, 1);
            },

            clearAllBookers() {
                this.selectedBookers = [];
            },

            selectAllBookers() {
                this.selectedBookers = this.bookers.map(b => b.id);
            }
        }));
    });
</script>
