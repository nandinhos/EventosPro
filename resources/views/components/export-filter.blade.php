@props([
    'route',
    'artists' => [],
    'bookers' => [],
    'selectedArtist' => null,
    'selectedBooker' => null,
    'selectedMonth' => null,
    'selectedYear' => null,
    'title' => 'Exportar Relatório',
    'formats' => ['pdf', 'csv', 'json']
])

<div x-data="{
    showExportModal: false,
    selectedFormat: 'pdf',
    selectedArtist: '{{ $selectedArtist }}',
    selectedBooker: '{{ $selectedBooker }}',
    selectedMonth: '{{ $selectedMonth }}',
    selectedYear: '{{ $selectedYear }}',
    isExporting: false,
    exportReport() {
        this.isExporting = true;
        const params = new URLSearchParams();
        if (this.selectedArtist) params.append('artist_id', this.selectedArtist);
        if (this.selectedBooker) params.append('booker_id', this.selectedBooker);
        if (this.selectedMonth) params.append('month', this.selectedMonth);
        if (this.selectedYear) params.append('year', this.selectedYear);
        params.append('format', this.selectedFormat);
        const url = \`{{ $route }}?\${params.toString()}\`;
        if (this.selectedFormat === 'json') {
            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                }
            }).then(response => response.json()).then(data => {
                const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = \`relatorio-\${this.selectedMonth}-\${this.selectedYear}.json\`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                this.isExporting = false;
                this.showExportModal = false;
            }).catch(error => {
                console.error('Erro na exportação:', error);
                this.isExporting = false;
            });
        } else {
            window.open(url, '_blank');
            this.isExporting = false;
            this.showExportModal = false;
        }
    }
}" class="inline-block">
    <!-- Botão de Exportação -->
    <button @click="showExportModal = true" 
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        {{ $title }}
    </button>

    <!-- Modal de Exportação -->
    <div x-show="showExportModal" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">{{ $title }}</h3>
                            <div class="mt-4 space-y-4">
                                <!-- Filtro por Artista -->
                                @if(count($artists) > 0)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Artista (opcional)</label>
                                    <select x-model="selectedArtist" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm rounded-md">
                                        <option value="">Todos os Artistas</option>
                                        @foreach($artists as $artist)
                                            <option value="{{ $artist->id }}">{{ $artist->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <!-- Filtro por Booker -->
                                @if(count($bookers) > 0)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Booker (opcional)</label>
                                    <select x-model="selectedBooker" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm rounded-md">
                                        <option value="">Todos os Bookers</option>
                                        @foreach($bookers as $booker)
                                            <option value="{{ $booker->id }}">{{ $booker->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <!-- Formato de Exportação -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Formato</label>
                                    <select x-model="selectedFormat" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm rounded-md">
                                        @foreach($formats as $format)
                                            <option value="{{ $format }}">{{ strtoupper($format) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button @click="exportReport()" 
                            :disabled="isExporting"
                            :class="isExporting ? 'opacity-50 cursor-not-allowed' : 'hover:bg-red-700'"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200">
                        <span x-show="!isExporting">Exportar</span>
                        <span x-show="isExporting" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Exportando...
                        </span>
                    </button>
                    <button @click="showExportModal = false" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>