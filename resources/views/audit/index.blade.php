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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-8 gap-4 items-end">
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

                    {{-- Moeda --}}
                    <div class="lg:col-span-1">
                        <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Moeda</label>
                        <select name="currency" id="currency" 
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="all">Todas</option>
                            @foreach($currencies as $currency)
                                <option value="{{ $currency }}" {{ ($filters['currency'] ?? '') == $currency ? 'selected' : '' }}>{{ $currency }}</option>
                            @endforeach
                        </select>
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
        @php
            $totalGigs = collect($groupedResults)->sum(function($group) { return count($group['items']); });
            $totalContratos = collect($groupedResults)->flatten(1)->pluck('items')->flatten()->sum('valor_contrato');
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-blue-500 p-5">
                <div class="flex items-center">
                    <div class="text-blue-500 text-3xl">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total de Gigs</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $totalGigs }}</dd>
                    </div>
                </div>
            </div>
            
            @foreach($groupedResults as $groupKey => $group)
                @php
                    $colorMap = [
                        'discrepancia_valores' => 'red',
                        'falta_lancamento' => 'orange', 
                        'gigs_vencidas' => 'yellow',
                        'gigs_a_vencer' => 'blue'
                    ];
                    $iconMap = [
                        'discrepancia_valores' => 'fas fa-exclamation-triangle',
                        'falta_lancamento' => 'fas fa-plus-circle',
                        'gigs_vencidas' => 'fas fa-clock',
                        'gigs_a_vencer' => 'fas fa-calendar-alt'
                    ];
                    $color = $colorMap[$groupKey] ?? 'gray';
                    $icon = $iconMap[$groupKey] ?? 'fas fa-list';
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-{{ $color }}-500 p-5">
                    <div class="flex items-center">
                        <div class="text-{{ $color }}-500 text-3xl">
                            <i class="{{ $icon }}"></i>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ $group['title'] }}</dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ count($group['items']) }}</dd>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>



        {{-- Grupos de Auditoria --}}
        @if(count($groupedResults) > 0)
            <div class="space-y-6">
                @foreach($groupedResults as $groupKey => $group)
                    @php
                        $colorMap = [
                            'discrepancia_valores' => 'red',
                            'falta_lancamento' => 'orange', 
                            'gigs_vencidas' => 'yellow',
                            'gigs_a_vencer' => 'blue'
                        ];
                        $iconMap = [
                            'discrepancia_valores' => 'fas fa-exclamation-triangle',
                            'falta_lancamento' => 'fas fa-plus-circle',
                            'gigs_vencidas' => 'fas fa-clock',
                            'gigs_a_vencer' => 'fas fa-calendar-alt'
                        ];
                        $color = $colorMap[$groupKey] ?? 'gray';
                        $icon = $iconMap[$groupKey] ?? 'fas fa-list';
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                        <div class="bg-{{ $color }}-50 dark:bg-{{ $color }}-900/20 px-6 py-3 border-b border-gray-200 dark:border-gray-600">
                            <h3 class="text-lg font-medium text-{{ $color }}-800 dark:text-{{ $color }}-200 flex items-center">
                                <i class="{{ $icon }} mr-2"></i>
                                {{ $group['title'] }} ({{ count($group['items']) }} gigs)
                            </h3>
                            <p class="text-sm text-{{ $color }}-600 dark:text-{{ $color }}-300 mt-1">{{ $group['description'] }}</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data Gig</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Local</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Artista</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor Contrato</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Pago</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Não Pago</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Diferença</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status Pagamento</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Observação</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($group['items'] as $item)
                                        @php
                                            $gig = $item['gig'];
                                            $statusClass = match($item['categoria']) {
                                                'discrepancia_valores' => 'bg-red-50 dark:bg-red-900/20',
                                                'falta_lancamento' => 'bg-orange-50 dark:bg-orange-900/20',
                                                'gigs_vencidas' => 'bg-yellow-50 dark:bg-yellow-900/20',
                                                'gigs_a_vencer' => 'bg-blue-50 dark:bg-blue-900/20',
                                                default => ''
                                            };
                                        @endphp
                                        <tr class="{{ $statusClass }} hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $gig->gig_date ? \Carbon\Carbon::parse($gig->gig_date)->isoFormat('L') : '-' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ Str::limit($gig->location_event_details ?? 'N/A', 30) }}
                                                </div>
                                                @if($gig->booker)
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $gig->booker->name }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($gig->artist)
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $gig->artist->name }}</div>
                                                @endif
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $gig->contract_number ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $gig->currency }} {{ number_format($item['valor_contrato'], 2, ',', '.') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-medium text-green-600 dark:text-green-400">
                                                    {{ $gig->currency }} {{ number_format($item['total_pago'], 2, ',', '.') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">
                                                    {{ $gig->currency }} {{ number_format($item['total_nao_pago'], 2, ',', '.') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-medium text-red-600 dark:text-red-400">
                                                    {{ $gig->currency }} {{ number_format($item['diferenca'], 2, ',', '.') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $statusColor = match($item['payment_status']) {
                                                        'pago' => 'green',
                                                        'pendente' => 'yellow',
                                                        'cancelado' => 'red',
                                                        'a_vencer' => 'blue',
                                                        'vencido' => 'orange',
                                                        default => 'gray'
                                                    };
                                                @endphp
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/20 dark:text-{{ $statusColor }}-200">
                                                    {{ ucfirst(str_replace('_', ' ', $item['payment_status'] ?? 'N/A')) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $item['observacao'] }}</div>
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
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                <div class="text-green-400 dark:text-green-500 text-6xl mb-4">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Nenhum problema encontrado na auditoria</h3>
                <p class="text-gray-500 dark:text-gray-400">Todos os gigs estão com pagamentos corretos e status atualizados.</p>
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
    $('#currency').on('change', function() {
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