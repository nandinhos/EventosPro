<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Auditoria de Gigs
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Identifique divergências financeiras entre contratos e pagamentos.</p>
    </x-slot>
    
    <div class="py-8 max-w-full mx-auto sm:px-6 lg:px-8">
        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow-md mb-6">
            <form method="GET" action="{{ route('audit.index') }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                    {{-- Busca --}}
                    <div class="lg:col-span-3">
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar</label>
                        <input type="text" name="search" id="search" value="{{ $filters['search'] ?? '' }}" 
                               placeholder="Contrato, local, artista..."
                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                    
                    {{-- Período --}}
                    <div class="lg:col-span-2">
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Início</label>
                        <input type="date" name="start_date" id="start_date" value="{{ $filters['start_date'] ?? '' }}" 
                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                    <div class="lg:col-span-2">
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Fim</label>
                        <input type="date" name="end_date" id="end_date" value="{{ $filters['end_date'] ?? '' }}" 
                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                    </div>

                    {{-- Filtros Adicionais --}}
                    <div class="lg:col-span-2">
                        <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Moeda</label>
                        <select name="currency" id="currency" 
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="all">Todas</option>
                            @foreach($currencies as $currency)
                                <option value="{{ $currency }}" {{ ($filters['currency'] ?? '') == $currency ? 'selected' : '' }}>{{ $currency }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="lg:col-span-2">
                        <label for="payment_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status Pagamento</label>
                        <select name="payment_status" id="payment_status" 
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">Todos</option>
                            <option value="pending" {{ ($filters['payment_status'] ?? '') == 'pending' ? 'selected' : '' }}>Pendente</option>
                            <option value="partial" {{ ($filters['payment_status'] ?? '') == 'partial' ? 'selected' : '' }}>Parcial</option>
                            <option value="paid" {{ ($filters['payment_status'] ?? '') == 'paid' ? 'selected' : '' }}>Pago</option>
                        </select>
                    </div>

                    {{-- Checkbox --}}
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filtros</label>
                        <div class="mt-1 flex items-center">
                            <input type="checkbox" id="has_divergence" name="has_divergence" value="1" 
                                   {{ ($filters['has_divergence'] ?? false) ? 'checked' : '' }}
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="has_divergence" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                Só divergências
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-between items-center mt-4">
                    <div class="flex gap-2">
                        <button type="submit" 
                                class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <i class="fas fa-search mr-2"></i> Filtrar
                        </button>
                        <a href="{{ route('audit.index') }}" 
                           class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                            <i class="fas fa-times mr-2"></i> Limpar
                        </a>
                    </div>
                    
                    <a href="{{ route('audit.export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" 
                       class="inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-medium text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                        <i class="fas fa-download mr-2"></i> Exportar CSV
                    </a>
                </div>
            </form>
        </div>

        {{-- Cards de Resumo --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-blue-500 p-5">
                <div class="flex items-center">
                    <div class="text-blue-500 text-3xl">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total de Gigs</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ ($fullyPaidGigs->count() + $gigsWithIssues->count()) }}</dd>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-green-500 p-5">
                <div class="flex items-center">
                    <div class="text-green-500 text-3xl">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Totalmente Pagos</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $fullyPaidGigs->count() }}</dd>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-yellow-500 p-5">
                <div class="flex items-center">
                    <div class="text-yellow-500 text-3xl">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Com Problemas</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $gigsWithIssues->count() }}</dd>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-green-500 p-5">
                <div class="flex items-center">
                    <div class="text-green-500 text-3xl">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Contratos</dt>
                        <dd class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ number_format(collect($auditData)->sum('valor_contrato'), 2, ',', '.') }}</dd>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-red-500 p-5">
                <div class="flex items-center">
                    <div class="text-red-500 text-3xl">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Divergência</dt>
                        <dd class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ number_format(collect($auditData)->sum('divergencia'), 2, ',', '.') }}</dd>
                    </div>
                </div>
            </div>
        </div>

        {{-- Listbox de Gigs Totalmente Pagos --}}
        @if($fullyPaidGigs->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md mb-6">
                <div class="bg-green-50 dark:bg-green-900/20 px-6 py-3 border-b border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-medium text-green-800 dark:text-green-200 flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        Gigs Totalmente Pagos ({{ $fullyPaidGigs->count() }})
                    </h3>
                </div>
                <div class="p-4">
                    <div class="max-h-40 overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($fullyPaidGigs as $gig)
                                @php $audit = $auditData[$gig->id] ?? []; @endphp
                                <div class="bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800 rounded-lg p-3">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $gig->gig_date ? $gig->gig_date->format('d/m/Y') : '-' }}
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ Str::limit($gig->location_event_details ?? 'N/A', 25) }}
                                            </div>
                                            @if($gig->artist)
                                                <div class="text-xs text-gray-500 dark:text-gray-500">
                                                    {{ $gig->artist->name }}
                                                </div>
                                            @endif
                                            <div class="text-xs font-medium text-green-600 dark:text-green-400 mt-1">
                                                {{ $gig->currency }} {{ number_format($audit['valor_contrato'] ?? 0, 2, ',', '.') }}
                                            </div>
                                        </div>
                                        <div class="flex space-x-1 ml-2">
                                            <a href="{{ route('audit.show', $gig) }}" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300" title="Ver Detalhes">
                                                <i class="fas fa-eye text-xs"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Listagem Principal: Apenas Gigs com Problemas --}}
        @if($gigsWithIssues->count() > 0)

            {{-- Tabela de Gigs com Problemas --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="bg-yellow-50 dark:bg-yellow-900/20 px-6 py-3 border-b border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-medium text-yellow-800 dark:text-yellow-200 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Gigs com Divergências ou Pendências ({{ $gigsWithIssues->count() }})
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'gig_date', 'sort_direction' => $sortBy == 'gig_date' && $sortDirection == 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100 no-underline flex items-center justify-between">
                                        Data Gig
                                        @if($sortBy == 'gig_date')
                                            <i class="fas fa-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }} ml-1"></i>
                                        @endif
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'location', 'sort_direction' => $sortBy == 'location' && $sortDirection == 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100 no-underline flex items-center justify-between">
                                        Local
                                        @if($sortBy == 'location')
                                            <i class="fas fa-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }} ml-1"></i>
                                        @endif
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'currency', 'sort_direction' => $sortBy == 'currency' && $sortDirection == 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100 no-underline flex items-center justify-between">
                                        Moeda
                                        @if($sortBy == 'currency')
                                            <i class="fas fa-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }} ml-1"></i>
                                        @endif
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'contract_number', 'sort_direction' => $sortBy == 'contract_number' && $sortDirection == 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100 no-underline flex items-center justify-between">
                                        Artista
                                        @if($sortBy == 'contract_number')
                                            <i class="fas fa-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }} ml-1"></i>
                                        @endif
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor Contrato</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Pago</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Pendente</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Divergência</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Observação</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($gigsWithIssues as $gig)
                                        @php
                                            $audit = $auditData[$gig->id] ?? [];
                                            $statusClass = match($audit['status_divergencia'] ?? 'ok') {
                                                'ok' => 'bg-green-50 dark:bg-green-900/20',
                                                'falta' => 'bg-yellow-50 dark:bg-yellow-900/20',
                                                'excesso' => 'bg-red-50 dark:bg-red-900/20',
                                                'erro' => 'bg-gray-50 dark:bg-gray-900/20',
                                                default => ''
                                            };
                                        @endphp
                                        <tr class="{{ $statusClass }} hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $gig->gig_date ? $gig->gig_date->format('d/m/Y') : '-' }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ Str::limit($gig->location_event_details ?? 'N/A', 30) }}</div>
                                                @if($gig->booker)
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $gig->booker->name }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">{{ $gig->currency ?? 'N/A' }}</span>
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($gig->artist)
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $gig->artist->name }}</div>
                                                @endif
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $gig->contract_number ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $gig->currency }} {{ number_format($audit['valor_contrato'] ?? 0, 2, ',', '.') }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-medium text-green-600 dark:text-green-400">
                                                    {{ $gig->currency }} {{ number_format($audit['total_pago'] ?? 0, 2, ',', '.') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">
                                                    {{ $gig->currency }} {{ number_format($audit['total_pendente'] ?? 0, 2, ',', '.') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                @php
                                                    $divergencia = $audit['divergencia'] ?? 0;
                                                    $divergenciaClass = abs($divergencia) <= 0.01 ? 'text-green-600 dark:text-green-400' : ($divergencia > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400');
                                                @endphp
                                                <div class="text-sm font-medium {{ $divergenciaClass }}">
                                                    {{ $gig->currency }} {{ number_format($divergencia, 2, ',', '.') }}
                                                </div>
                                                @if(abs($divergencia) > 0.01)
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ number_format($audit['divergencia_percentual'] ?? 0, 1) }}%
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $audit['observacao'] ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <div class="flex justify-center space-x-2">
                                                    <a href="{{ route('audit.show', $gig) }}" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300" title="Ver Detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('gigs.show', $gig) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300" title="Ver Gig">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Agrupamentos por Status de Pagamento (apenas para gigs com problemas) --}}
                @if(isset($groupedGigs) && count($groupedGigs) > 0)
                    <div class="mt-6 space-y-6">
                        @foreach($groupedGigs as $groupKey => $group)
                            @if($group['gigs']->count() > 0)
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                                    <div class="bg-red-50 dark:bg-red-900/20 px-6 py-3 border-b border-gray-200 dark:border-gray-600">
                                        <h3 class="text-lg font-medium text-red-800 dark:text-red-200 flex items-center">
                                            <i class="fas fa-clock mr-2"></i>
                                            {{ $group['title'] }} ({{ $group['gigs']->count() }} gigs)
                                        </h3>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-700">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data Gig</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Local</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Moeda</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Artista</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor Contrato</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Pago</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Pendente</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Divergência</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Observação</th>
                                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach($group['gigs'] as $gig)
                                                    @php
                                                        $audit = $auditData[$gig->id] ?? [];
                                                        $statusClass = match($audit['status_divergencia'] ?? 'ok') {
                                                            'ok' => 'bg-green-50 dark:bg-green-900/20',
                                                            'falta' => 'bg-yellow-50 dark:bg-yellow-900/20',
                                                            'excesso' => 'bg-red-50 dark:bg-red-900/20',
                                                            'erro' => 'bg-gray-50 dark:bg-gray-900/20',
                                                            default => ''
                                                        };
                                                    @endphp
                                                    <tr class="{{ $statusClass }} hover:bg-gray-50 dark:hover:bg-gray-700">
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $gig->gig_date ? $gig->gig_date->format('d/m/Y') : '-' }}</div>
                                                        </td>
                                                        <td class="px-6 py-4">
                                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ Str::limit($gig->location_event_details ?? 'N/A', 30) }}</div>
                                                            @if($gig->booker)
                                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $gig->booker->name }}</div>
                                                            @endif
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">{{ $gig->currency ?? 'N/A' }}</span>
                                                        </td>
                                                        <td class="px-6 py-4">
                                                            @if($gig->artist)
                                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $gig->artist->name }}</div>
                                                            @endif
                                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $gig->contract_number ?? 'N/A' }}</div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $gig->currency }} {{ number_format($audit['valor_contrato'] ?? 0, 2, ',', '.') }}</div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                                            <div class="text-sm font-medium text-green-600 dark:text-green-400">
                                                                {{ $gig->currency }} {{ number_format($audit['total_pago'] ?? 0, 2, ',', '.') }}
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                                            <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">
                                                                {{ $gig->currency }} {{ number_format($audit['total_pendente'] ?? 0, 2, ',', '.') }}
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                                            @php
                                                                $divergencia = $audit['divergencia'] ?? 0;
                                                                $divergenciaClass = abs($divergencia) <= 0.01 ? 'text-green-600 dark:text-green-400' : ($divergencia > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400');
                                                            @endphp
                                                            <div class="text-sm font-medium {{ $divergenciaClass }}">
                                                                {{ $gig->currency }} {{ number_format($divergencia, 2, ',', '.') }}
                                                            </div>
                                                            @if(abs($divergencia) > 0.01)
                                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                                    {{ number_format($audit['divergencia_percentual'] ?? 0, 1) }}%
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td class="px-6 py-4">
                                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $audit['observacao'] ?? 'N/A' }}</div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                                            <div class="flex justify-center space-x-2">
                                                                <a href="{{ route('audit.show', $gig) }}" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300" title="Ver Detalhes">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <a href="{{ route('gigs.show', $gig) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300" title="Ver Gig">
                                                                    <i class="fas fa-external-link-alt"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                <div class="text-green-400 dark:text-green-500 text-6xl mb-4">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Nenhum gig com problemas encontrado</h3>
                <p class="text-gray-500 dark:text-gray-400">Todos os gigs estão com pagamentos em dia ou foram filtrados.</p>
            </div>
        @endif
    </div>

    @push('styles')
<style>
.table-success {
    --bs-table-bg: rgba(25, 135, 84, 0.1);
}
.table-warning {
    --bs-table-bg: rgba(255, 193, 7, 0.1);
}
.table-danger {
    --bs-table-bg: rgba(220, 53, 69, 0.1);
}
.table-secondary {
    --bs-table-bg: rgba(108, 117, 125, 0.1);
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.alert-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
}

.table th a {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.table th a:hover {
    text-decoration: underline !important;
}
    </style>
    @endpush

    @push('scripts')
<script>
$(document).ready(function() {
    // Auto-submit form on filter change
    $('#currency, #payment_status').on('change', function() {
        $(this).closest('form').submit();
    });
    
    // Tooltip initialization
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
    </script>
    @endpush
</x-app-layout>