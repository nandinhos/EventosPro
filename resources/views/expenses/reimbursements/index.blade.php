<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            <i class="fas fa-receipt mr-2 text-primary-500"></i>
            {{ __('Despesas Reembolsáveis') }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Rastreie comprovantes e status de reembolso das despesas de artistas</p>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        
        {{-- Cards de Métricas por Estágio --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            {{-- Aguardando Comprovante --}}
            <a href="{{ route('expenses.reimbursements.index', ['stage' => 'aguardando_comprovante']) }}"
               class="block p-4 rounded-lg shadow transition-all hover:shadow-lg hover:scale-105 {{ request('stage') === 'aguardando_comprovante' ? 'ring-2 ring-gray-500 bg-gray-200 dark:bg-gray-700' : 'bg-gray-100 dark:bg-gray-800/50' }}">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-clock text-gray-500 dark:text-gray-400"></i>
                    <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400">Aguardando</h3>
                </div>
                <p class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ $metrics['aguardando_comprovante']['count'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">R$ {{ number_format($metrics['aguardando_comprovante']['value'], 2, ',', '.') }}</p>
            </a>

            {{-- Comprovante Recebido --}}
            <a href="{{ route('expenses.reimbursements.index', ['stage' => 'comprovante_recebido']) }}"
               class="block p-4 rounded-lg shadow transition-all hover:shadow-lg hover:scale-105 {{ request('stage') === 'comprovante_recebido' ? 'ring-2 ring-yellow-500 bg-yellow-200 dark:bg-yellow-700' : 'bg-yellow-100 dark:bg-yellow-900/30' }}">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-file-alt text-yellow-500 dark:text-yellow-400"></i>
                    <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400">Recebido</h3>
                </div>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $metrics['comprovante_recebido']['count'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">R$ {{ number_format($metrics['comprovante_recebido']['value'], 2, ',', '.') }}</p>
            </a>

            {{-- Conferido --}}
            <a href="{{ route('expenses.reimbursements.index', ['stage' => 'conferido']) }}"
               class="block p-4 rounded-lg shadow transition-all hover:shadow-lg hover:scale-105 {{ request('stage') === 'conferido' ? 'ring-2 ring-blue-500 bg-blue-200 dark:bg-blue-700' : 'bg-blue-100 dark:bg-blue-900/40' }}">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-check-double text-blue-500 dark:text-blue-400"></i>
                    <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400">Conferido</h3>
                </div>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $metrics['conferido']['count'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">R$ {{ number_format($metrics['conferido']['value'], 2, ',', '.') }}</p>
            </a>

            {{-- Reembolsado --}}
            <a href="{{ route('expenses.reimbursements.index', ['stage' => 'reembolsado']) }}"
               class="block p-4 rounded-lg shadow transition-all hover:shadow-lg hover:scale-105 {{ request('stage') === 'reembolsado' ? 'ring-2 ring-green-500 bg-green-200 dark:bg-green-700' : 'bg-green-100 dark:bg-green-900/30' }}">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-check-circle text-green-500 dark:text-green-400"></i>
                    <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400">Reembolsado</h3>
                </div>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $metrics['reembolsado']['count'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">R$ {{ number_format($metrics['reembolsado']['value'], 2, ',', '.') }}</p>
            </a>
        </div>

        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-4">
            <form method="GET" action="{{ route('expenses.reimbursements.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Busca --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Buscar</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Descrição, evento, centro..."
                           class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                </div>

                {{-- Estágio --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Estágio</label>
                    <select name="stage" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                        <option value="all">Todos</option>
                        @foreach(\App\Models\GigCost::REIMBURSEMENT_STAGES as $key => $label)
                            <option value="{{ $key }}" @selected(request('stage') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Artista --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Artista</label>
                    <select name="artist_id" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Todos</option>
                        @foreach($artists as $id => $name)
                            <option value="{{ $id }}" @selected(request('artist_id') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Botões --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 text-sm font-medium transition-colors">
                        <i class="fas fa-filter mr-1"></i> Filtrar
                    </button>
                    <a href="{{ route('expenses.reimbursements.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 text-sm transition-colors">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabela de Despesas --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estágio</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Artista</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Evento</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Centro de Custo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descrição</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($expenses as $expense)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <x-reimbursement-badge :cost="$expense" size="sm" />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $expense->expense_date?->format('d/m/Y') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $expense->gig?->artist?->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-[200px] truncate" title="{{ $expense->gig?->location_event_details }}">
                                    <a href="{{ route('gigs.show', $expense->gig_id) }}" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                        {{ Str::limit($expense->gig?->location_event_details, 30) }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $expense->costCenter?->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-[150px] truncate" title="{{ $expense->description }}">
                                    {{ $expense->description ?? '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">
                                    R$ {{ number_format($expense->value, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    @include('expenses.reimbursements._actions', ['expense' => $expense])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-inbox text-4xl mb-2 text-gray-400"></i>
                                        <p>Nenhuma despesa reembolsável encontrada.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginação --}}
            @if($expenses->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    {{ $expenses->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
