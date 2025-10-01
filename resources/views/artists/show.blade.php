<x-app-layout>
    <div class="max-w-9xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg">
            <div class="p-6 sm:px-20 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $artist->name }}</h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Visão geral da atividade do artista.</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('artists.index') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Voltar
                        </a>
                        <a href="{{ route('artists.edit', $artist) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L15.232 5.232z"></path></svg>
                            Editar Artista
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-6 sm:px-20 bg-white dark:bg-gray-800">
                <form method="GET" action="{{ route('artists.show', $artist) }}">
                    <div class="flex items-center space-x-4">
                        <div class="flex-grow">
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Período de Análise</label>
                            <div class="flex items-center mt-1 space-x-2">
                                <input type="date" name="start_date" id="start_date" value="{{ $startDate->format('Y-m-d') }}" class="block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                                <span class="text-gray-500">até</span>
                                <input type="date" name="end_date" id="end_date" value="{{ $endDate->format('Y-m-d') }}" class="block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                            </div>
                        </div>
                        <div class="pt-6">
                            <x-secondary-button type="submit">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                Aplicar Filtro
                            </x-secondary-button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="rounded-xl">
                <div x-data="{ tab: 'overview' }" class="p-4 sm:p-6 lg:p-8">
                    <div class="sm:hidden">
                        <label for="tabs" class="sr-only">Select a tab</label>
                        <select id="tabs" name="tabs" @change="tab = $event.target.value" class="block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="overview" :selected="tab === 'overview'">Visão Geral</option>
                            <option value="events" :selected="tab === 'events'">Eventos</option>
                            <option value="financials" :selected="tab === 'financials'">Fechamento Financeiro</option>
                        </select>
                    </div>
                    <div class="hidden sm:block">
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                <a href="#" @click.prevent="tab = 'overview'" :class="{'border-indigo-500 text-indigo-600': tab === 'overview', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'overview'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Visão Geral</a>
                                <a href="#" @click.prevent="tab = 'events'" :class="{'border-indigo-500 text-indigo-600': tab === 'events', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'events'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Eventos</a>
                                <a href="#" @click.prevent="tab = 'financials'" :class="{'border-indigo-500 text-indigo-600': tab === 'financials', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'financials'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Fechamento Financeiro</a>
                            </nav>
                        </div>
                    </div>

                    <div x-show="tab === 'overview'" class="mt-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            <!-- Total de Gigs -->
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-blue-500 p-5">
                                <div class="flex items-center">
                                    <div class="text-blue-500 text-3xl">
                                        <i class="fas fa-list-alt"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Gigs no Período</h3>
                                        <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">{{ $realizedGigs->count() + $futureGigs->count() }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Cachê Bruto Total -->
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-purple-500 p-5">
                                <div class="flex items-center">
                                    <div class="text-purple-500 text-3xl">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Cachê Bruto Total</h3>
                                        <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">R$ {{ number_format($metrics['totalGrossFee'] ?? 0, 2, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Líquido Recebido -->
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-green-500 p-5">
                                <div class="flex items-center">
                                    <div class="text-green-500 text-3xl">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Líquido Recebido (Realizado)</h3>
                                        <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">R$ {{ number_format($metrics['cache_received_brl'] ?? 0, 2, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Líquido Pendente -->
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-yellow-500 p-5">
                                <div class="flex items-center">
                                    <div class="text-yellow-500 text-3xl">
                                        <i class="fas fa-hourglass-half"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Líquido a Receber (Futuro)</h3>
                                        <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">R$ {{ number_format($metrics['cache_pending_brl'] ?? 0, 2, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="tab === 'events'">
                        <div class="mt-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Eventos Realizados</h3>
                            @include('artists.components.gigs-table', ['gigs' => $realizedGigs, 'tableId' => 'past-gigs-table'])
                        </div>
                        <div class="mt-8">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Próximos Eventos</h3>
                            @include('artists.components.gigs-table', ['gigs' => $futureGigs, 'tableId' => 'future-gigs-table'])
                        </div>
                    </div>

                    <div x-show="tab === 'financials'" class="mt-6">
                        @include('artists.components.financial-closing', ['metrics' => $metrics])
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>