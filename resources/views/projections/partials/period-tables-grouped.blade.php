{{-- Tabelas Agrupadas com Subtotais --}}
<div class="space-y-6">

    {{-- 1. CONTAS A RECEBER - Agrupado por Status de Vencimento --}}
    @if($summary['receivable']['count'] > 0)
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
            {{-- Cabeçalho --}}
            <div class="border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-dollar-sign text-lg text-green-600 dark:text-green-400"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Contas a Receber</h3>
                            <p class="text-sm text-green-700 dark:text-green-300">Pagamentos pendentes de clientes</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            {{ $summary['receivable']['count'] }} {{ $summary['receivable']['count'] === 1 ? 'item' : 'itens' }}
                        </span>
                        <div class="text-lg font-bold text-green-800 dark:text-green-200 mt-1">
                            R$ {{ number_format($summary['receivable']['total'], 2, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabelas por Status --}}
            <div class="overflow-x-auto">
                @php
                    $statusColors = [
                        'vencido' => ['bg' => 'bg-red-50 dark:bg-red-900/10', 'text' => 'text-red-800 dark:text-red-300', 'border' => 'border-red-200 dark:border-red-800'],
                        'vence_7_dias' => ['bg' => 'bg-orange-50 dark:bg-orange-900/10', 'text' => 'text-orange-800 dark:text-orange-300', 'border' => 'border-orange-200 dark:border-orange-800'],
                        'vence_30_dias' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/10', 'text' => 'text-yellow-800 dark:text-yellow-300', 'border' => 'border-yellow-200 dark:border-yellow-800'],
                        'a_vencer' => ['bg' => 'bg-blue-50 dark:bg-blue-900/10', 'text' => 'text-blue-800 dark:text-blue-300', 'border' => 'border-blue-200 dark:border-blue-800'],
                    ];
                @endphp

                @foreach($summary['receivable']['grouped'] as $statusGroup)
                    @php $colors = $statusColors[$statusGroup['status']] ?? ['bg' => '', 'text' => '', 'border' => '']; @endphp

                    {{-- Sub-cabeçalho por Status --}}
                    <div class="{{ $colors['bg'] }} {{ $colors['border'] }} px-4 py-2 border-b">
                        <div class="flex items-center justify-between">
                            <span class="{{ $colors['text'] }} font-semibold text-sm">{{ $statusGroup['label'] }}</span>
                            <div class="flex items-center space-x-4">
                                <span class="{{ $colors['text'] }} text-xs">{{ $statusGroup['count'] }} parcelas</span>
                                <span class="{{ $colors['text'] }} font-bold">R$ {{ number_format($statusGroup['subtotal'], 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Tabela de Itens --}}
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contrato</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Artista</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Vencimento</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($statusGroup['items'] as $payment)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <a href="{{ route('gigs.show', $payment->gig_id) }}" class="text-primary-600 dark:text-primary-400 hover:underline">
                                            {{ $payment->gig->contract_number ?? 'N/A' }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $payment->gig->artist->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($payment->due_date)->isoFormat('L') }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-right text-gray-900 dark:text-white">
                                        R$ {{ number_format($payment->due_value_brl, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 2. PAGAMENTOS A ARTISTAS - Agrupado por Artista --}}
    @if($summary['artists']['count'] > 0)
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
            {{-- Cabeçalho --}}
            <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-users text-lg text-red-600 dark:text-red-400"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-red-800 dark:text-red-200">Pagamentos a Artistas</h3>
                            <p class="text-sm text-red-700 dark:text-red-300">Cachês pendentes por artista</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            {{ $summary['artists']['count'] }} {{ $summary['artists']['count'] === 1 ? 'gig' : 'gigs' }}
                        </span>
                        <div class="text-lg font-bold text-red-800 dark:text-red-200 mt-1">
                            R$ {{ number_format($summary['artists']['total'], 2, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabelas por Artista --}}
            <div class="overflow-x-auto">
                @foreach($summary['artists']['grouped'] as $artistGroup)
                    {{-- Sub-cabeçalho por Artista --}}
                    <div class="bg-red-50 dark:bg-red-900/10 px-4 py-2 border-b border-red-200 dark:border-red-800">
                        <div class="flex items-center justify-between">
                            <span class="text-red-800 dark:text-red-300 font-semibold">{{ $artistGroup['artist_name'] }}</span>
                            <div class="flex items-center space-x-4">
                                <span class="text-red-700 dark:text-red-400 text-xs">{{ $artistGroup['count'] }} gigs</span>
                                <span class="text-red-800 dark:text-red-200 font-bold">R$ {{ number_format($artistGroup['subtotal'], 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Tabela de Gigs do Artista --}}
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Evento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor NF</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($artistGroup['items'] as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">
                                        <a href="{{ route('gigs.show', $item['gig_id']) }}" class="text-primary-600 dark:text-primary-400 hover:underline">
                                            {{ $item['event_name'] }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($item['gig_date'])->isoFormat('L') }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-right text-gray-900 dark:text-white">
                                        R$ {{ number_format($item['amount'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 3. COMISSÕES DE BOOKERS - Agrupado por Booker --}}
    @if($summary['bookers']['count'] > 0)
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
            {{-- Cabeçalho --}}
            <div class="border-l-4 border-orange-500 bg-orange-50 dark:bg-orange-900/20 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-handshake text-lg text-orange-600 dark:text-orange-400"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-orange-800 dark:text-orange-200">Comissões de Bookers</h3>
                            <p class="text-sm text-orange-700 dark:text-orange-300">Comissões pendentes por booker</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                            {{ $summary['bookers']['count'] }} {{ $summary['bookers']['count'] === 1 ? 'gig' : 'gigs' }}
                        </span>
                        <div class="text-lg font-bold text-orange-800 dark:text-orange-200 mt-1">
                            R$ {{ number_format($summary['bookers']['total'], 2, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabelas por Booker --}}
            <div class="overflow-x-auto">
                @foreach($summary['bookers']['grouped'] as $bookerGroup)
                    {{-- Sub-cabeçalho por Booker --}}
                    <div class="bg-orange-50 dark:bg-orange-900/10 px-4 py-2 border-b border-orange-200 dark:border-orange-800">
                        <div class="flex items-center justify-between">
                            <span class="text-orange-800 dark:text-orange-300 font-semibold">{{ $bookerGroup['booker_name'] }}</span>
                            <div class="flex items-center space-x-4">
                                <span class="text-orange-700 dark:text-orange-400 text-xs">{{ $bookerGroup['count'] }} gigs</span>
                                <span class="text-orange-800 dark:text-orange-200 font-bold">R$ {{ number_format($bookerGroup['subtotal'], 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Tabela de Gigs do Booker --}}
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Evento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Comissão</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($bookerGroup['items'] as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">
                                        <a href="{{ route('gigs.show', $item['gig_id']) }}" class="text-primary-600 dark:text-primary-400 hover:underline">
                                            {{ $item['event_name'] }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($item['gig_date'])->isoFormat('L') }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-right text-gray-900 dark:text-white">
                                        R$ {{ number_format($item['amount'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 4. DESPESAS POR CENTRO DE CUSTO (já vem agrupado) --}}
    @if($summary['expenses']['count'] > 0)
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
            {{-- Cabeçalho --}}
            <div class="border-l-4 border-purple-500 bg-purple-50 dark:bg-purple-900/20 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-receipt text-lg text-purple-600 dark:text-purple-400"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-purple-800 dark:text-purple-200">Despesas Previstas</h3>
                            <p class="text-sm text-purple-700 dark:text-purple-300">Custos operacionais por centro de custo</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                            {{ $summary['expenses']['count'] }} {{ $summary['expenses']['count'] === 1 ? 'item' : 'itens' }}
                        </span>
                        <div class="text-lg font-bold text-purple-800 dark:text-purple-200 mt-1">
                            R$ {{ number_format($summary['expenses']['total'], 2, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabelas por Centro de Custo --}}
            <div class="overflow-x-auto">
                @foreach($summary['expenses']['grouped'] as $costCenterGroup)
                    {{-- Sub-cabeçalho por Centro de Custo --}}
                    <div class="bg-purple-50 dark:bg-purple-900/10 px-4 py-2 border-b border-purple-200 dark:border-purple-800">
                        <div class="flex items-center justify-between">
                            <span class="text-purple-800 dark:text-purple-300 font-semibold">{{ $costCenterGroup['cost_center_name'] }}</span>
                            <div class="flex items-center space-x-4">
                                <span class="text-purple-700 dark:text-purple-400 text-xs">{{ $costCenterGroup['expenses']->count() }} itens</span>
                                <span class="text-purple-800 dark:text-purple-200 font-bold">R$ {{ number_format($costCenterGroup['total_brl'], 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Tabela de Despesas do Centro de Custo --}}
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descrição</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gig</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($costCenterGroup['expenses'] as $expense)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">
                                        {{ $expense['description'] }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <a href="{{ route('gigs.show', $expense['gig_id']) }}" class="text-primary-600 dark:text-primary-400 hover:underline">
                                            {{ $expense['gig_contract_number'] }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $expense['expense_date_formatted'] }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-right text-gray-900 dark:text-white">
                                        R$ {{ number_format($expense['value_brl'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>
        </div>
    @endif

</div>
