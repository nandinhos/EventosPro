<div class="space-y-6">
    @php
        $groupTitles = [
            'evento_realizado_vencimento_pendente' => [
                'title' => 'Eventos Realizados com Vencimento Pendente',
                'description' => 'Prioridade máxima - Eventos já aconteceram mas ainda têm parcelas em aberto',
                'color' => 'red',
                'icon' => 'fas fa-exclamation-triangle'
            ],
            'evento_futuro_multiplas_vencidas' => [
                'title' => 'Eventos Futuros com Múltiplas Parcelas Vencidas',
                'description' => 'Alta prioridade - Eventos futuros com mais de uma parcela vencida',
                'color' => 'orange',
                'icon' => 'fas fa-clock'
            ],
            'evento_futuro_parcela_vencida' => [
                'title' => 'Eventos Futuros com Parcela Vencida',
                'description' => 'Média prioridade - Eventos futuros com parcela vencida',
                'color' => 'yellow',
                'icon' => 'fas fa-calendar-times'
            ],
            'evento_futuro_parcela_a_vencer' => [
                'title' => 'Eventos Futuros com Parcela a Vencer',
                'description' => 'Baixa prioridade - Eventos futuros com parcelas ainda não vencidas',
                'color' => 'blue',
                'icon' => 'fas fa-calendar-check'
            ]
        ];
    @endphp

    @foreach($groupedPayments as $groupKey => $groupPayments)
        @if(($groupKey === 'evento_futuro_multiplas_vencidas' && !empty($groupPayments)) || ($groupKey !== 'evento_futuro_multiplas_vencidas' && $groupPayments->count() > 0))
            @php
                $groupInfo = $groupTitles[$groupKey];
                $colorClasses = [
                    'red' => 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300',
                    'orange' => 'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-800 dark:bg-orange-900/20 dark:text-orange-300',
                    'yellow' => 'border-yellow-200 bg-yellow-50 text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
                    'blue' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300'
                ];
                
                // Calcular contagem de itens baseado no tipo de grupo
                $itemCount = $groupKey === 'evento_futuro_multiplas_vencidas' 
                    ? collect($groupPayments)->sum(function($subGroup) { return $subGroup['payments']->count(); })
                    : $groupPayments->count();
            @endphp

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                {{-- Cabeçalho do Grupo --}}
                <div class="border-l-4 {{ $colorClasses[$groupInfo['color']] }} px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <i class="{{ $groupInfo['icon'] }} text-lg"></i>
                            <div>
                                <h3 class="text-lg font-semibold">{{ $groupInfo['title'] }}</h3>
                                <p class="text-sm opacity-75">{{ $groupInfo['description'] }}</p>
                                @if($groupKey === 'evento_futuro_multiplas_vencidas' && !empty($groupPayments))
                                    <p class="text-xs opacity-60 mt-1">{{ count($groupPayments) }} {{ count($groupPayments) === 1 ? 'evento' : 'eventos' }} com múltiplas parcelas</p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            @if($groupKey === 'evento_futuro_multiplas_vencidas')
                                @php
                                    $totalOverdue = collect($groupPayments)->sum('overdue_count');
                                    $totalUpcoming = collect($groupPayments)->sum('upcoming_count');
                                @endphp
                                <div class="space-y-1">
                                    <div class="text-xs font-semibold text-red-700 dark:text-red-400">
                                        {{ $totalOverdue }} vencidas
                                    </div>
                                    <div class="text-xs font-semibold text-blue-700 dark:text-blue-400">
                                        {{ $totalUpcoming }} à vencer
                                    </div>
                                </div>
                            @else
                                @php
                                    $groupSubtotal = $groupPayments->sum('due_value_brl');
                                @endphp
                                <div class="text-right">
                                    <div class="text-sm font-bold {{ $colorClasses[$groupInfo['color']] }} mb-1">
                                        R$ {{ number_format($groupSubtotal, 2, ',', '.') }}
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClasses[$groupInfo['color']] }}">
                                        {{ $itemCount }} {{ $itemCount === 1 ? 'item' : 'itens' }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tabela do Grupo --}}
                <div class="overflow-x-auto">
                    @if($groupKey === 'evento_futuro_multiplas_vencidas')
                        {{-- Sub-agrupamentos por Gig para múltiplas parcelas vencidas --}}
                        @foreach($groupPayments as $subGroupIndex => $subGroup)
                            <div class="{{ $subGroupIndex > 0 ? 'mt-4' : '' }}">
                                {{-- Cabeçalho do Sub-grupo (Gig) --}}
                                <div class="bg-orange-100 dark:bg-orange-900/30 px-4 py-3 border-b border-orange-200 dark:border-orange-800">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-calendar-alt text-orange-600 dark:text-orange-400"></i>
                                            <div>
                                                <h4 class="text-md font-semibold text-orange-800 dark:text-orange-200">
                                                    <a href="{{ route('gigs.show', $subGroup['gig']) }}" class="hover:underline">
                                                        Gig #{{ $subGroup['gig']->id }} - {{ $subGroup['gig']->artist?->name ?? 'N/A' }}
                                                    </a>
                                                </h4>
                                                <p class="text-sm text-orange-700 dark:text-orange-300">
                                                    {{ $subGroup['gig']->location_event_details ?? 'Local não informado' }} • 
                                                    {{ $subGroup['gig']->gig_date?->isoFormat('L') ?? 'Data não informada' }}
                                                </p>
                                                <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">
                                                    {{ $subGroup['parcelas_vencidas_count'] }} parcelas vencidas • 
                                                    {{ $subGroup['parcelas_a_vencer_count'] }} parcelas a vencer
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right space-y-1">
                                            <div class="text-sm font-semibold text-red-700 dark:text-red-400">
                                                Vencidas: R$ {{ number_format($subGroup['overdue_total'], 2, ',', '.') }}
                                                <span class="text-xs font-normal">({{ $subGroup['overdue_count'] }})</span>
                                            </div>
                                            <div class="text-sm font-semibold text-blue-700 dark:text-blue-400">
                                                À Vencer: R$ {{ number_format($subGroup['upcoming_total'], 2, ',', '.') }}
                                                <span class="text-xs font-normal">({{ $subGroup['upcoming_count'] }})</span>
                                            </div>
                                            <div class="text-lg font-bold text-orange-800 dark:text-orange-200 pt-1 border-t border-orange-300 dark:border-orange-700">
                                                Total: R$ {{ number_format($subGroup['grand_total'], 2, ',', '.') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                {{-- PARCELAS VENCIDAS --}}
                                @if($subGroup['overdue_payments']->count() > 0)
                                    <div class="mt-3">
                                        <h5 class="px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 font-semibold text-sm uppercase tracking-wide">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>Parcelas Vencidas
                                        </h5>
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-800">
                                                <tr>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-20">Status Contrato</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-20">Booker</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-24">Vencimento</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-24">Parcela</th>
                                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-24">Valor</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach($subGroup['overdue_payments'] as $payment)
                                                    @php
                                                        $gig = $payment->gig;
                                                        $status = $payment->inferred_status;
                                                        $contractStatus = $gig?->contract_status ?? 'rascunho';

                                                        $statusMap = [
                                                            'assinado' => ['title' => 'Assinado', 'color' => 'blue', 'bg' => 'bg-blue-50', 'text' => 'text-blue-800', 'dark:bg' => 'dark:bg-blue-900/20', 'dark:text' => 'dark:text-blue-300'],
                                                            'cancelado' => ['title' => 'Cancelado', 'color' => 'red', 'bg' => 'bg-red-50', 'text' => 'text-red-800', 'dark:bg' => 'dark:bg-red-900/20', 'dark:text' => 'dark:text-red-300'],
                                                            'concluido' => ['title' => 'Concluído', 'color' => 'green', 'bg' => 'bg-green-50', 'text' => 'text-green-800', 'dark:bg' => 'dark:bg-green-900/20', 'dark:text' => 'dark:text-green-300'],
                                                            'expirado' => ['title' => 'Expirado', 'color' => 'orange', 'bg' => 'bg-orange-50', 'text' => 'text-orange-800', 'dark:bg' => 'dark:bg-orange-900/20', 'dark:text' => 'dark:text-orange-300'],
                                                            'n/a' => ['title' => 'N/A', 'color' => 'gray', 'bg' => 'bg-gray-50', 'text' => 'text-gray-800', 'dark:bg' => 'dark:bg-gray-900/20', 'dark:text' => 'dark:text-gray-300'],
                                                            'para_assinatura' => ['title' => 'Para Assinatura', 'color' => 'yellow', 'bg' => 'bg-yellow-50', 'text' => 'text-yellow-800', 'dark:bg' => 'dark:bg-yellow-900/20', 'dark:text' => 'dark:text-yellow-300'],
                                                            'rascunho' => ['title' => 'Rascunho', 'color' => 'gray', 'bg' => 'bg-gray-50', 'text' => 'text-gray-800', 'dark:bg' => 'dark:bg-gray-900/20', 'dark:text' => 'dark:text-gray-300'],
                                                        ];

                                                        $statusInfo = $statusMap[strtolower($contractStatus)] ?? ['title' => 'Desconhecido', 'color' => 'gray', 'bg' => 'bg-gray-50', 'text' => 'text-gray-800', 'dark:bg' => 'dark:bg-gray-900/20', 'dark:text' => 'dark:text-gray-300'];
                                                        $rowClass = 'bg-red-50 dark:bg-red-900/10';
                                                    @endphp

                                                    <tr class="{{ $rowClass }} hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                                        <td class="px-4 py-3 whitespace-nowrap">
                                                            <div class="flex items-center">
                                                                <x-status-dot :status="strtolower($contractStatus)" :title="$statusInfo['title']" size="md" />
                                                                <span class="ml-2 text-sm font-medium {{ $statusInfo['text'] }} dark:{{ $statusInfo['dark:text'] }}">{{ $statusInfo['title'] }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                                            <div class="font-medium">{{ $gig?->booker?->name ?? 'Agência Direta' }}</div>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap">
                                                            <div class="text-sm font-semibold text-red-600 dark:text-red-400">
                                                                {{ $payment->due_date?->isoFormat('L') }}
                                                                <div class="text-xs font-medium text-red-500 dark:text-red-400 mt-1">
                                                                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $payment->due_date->diffForHumans() }}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                            <div class="whitespace-normal text-xs bg-gray-50 dark:bg-gray-700/50 rounded px-2 py-1">
                                                                {{ $payment->description ?: 'Parcela' }}
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                                            <div class="font-bold text-gray-900 dark:text-white">
                                                                {{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }}
                                                                @if($payment->currency !== 'BRL')
                                                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                                                        ~R$ {{ number_format($payment->due_value_brl, 2, ',', '.') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                {{-- Subtotal Vencidas --}}
                                                <tr class="bg-red-100 dark:bg-red-900/20 border-t-2 border-red-300 dark:border-red-700">
                                                    <td colspan="4" class="px-4 py-3 text-right font-semibold text-red-800 dark:text-red-200">
                                                        Subtotal Vencidas ({{ $subGroup['overdue_count'] }} parcelas)
                                                    </td>
                                                    <td class="px-4 py-3 text-right font-bold text-red-800 dark:text-red-200">
                                                        R$ {{ number_format($subGroup['overdue_total'], 2, ',', '.') }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                {{-- PARCELAS À VENCER --}}
                                @if($subGroup['upcoming_payments']->count() > 0)
                                    <div class="mt-3">
                                        <h5 class="px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 font-semibold text-sm uppercase tracking-wide">
                                            <i class="far fa-clock mr-2"></i>Parcelas à Vencer
                                        </h5>
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-800">
                                                <tr>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-20">Status Contrato</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-20">Booker</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-24">Vencimento</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-24">Parcela</th>
                                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-24">Valor</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach($subGroup['upcoming_payments'] as $payment)
                                                    @php
                                                        $gig = $payment->gig;
                                                        $status = $payment->inferred_status;
                                                        $contractStatus = $gig?->contract_status ?? 'rascunho';

                                                        $statusMap = [
                                                            'assinado' => ['title' => 'Assinado', 'color' => 'blue', 'bg' => 'bg-blue-50', 'text' => 'text-blue-800', 'dark:bg' => 'dark:bg-blue-900/20', 'dark:text' => 'dark:text-blue-300'],
                                                            'cancelado' => ['title' => 'Cancelado', 'color' => 'red', 'bg' => 'bg-red-50', 'text' => 'text-red-800', 'dark:bg' => 'dark:bg-red-900/20', 'dark:text' => 'dark:text-red-300'],
                                                            'concluido' => ['title' => 'Concluído', 'color' => 'green', 'bg' => 'bg-green-50', 'text' => 'text-green-800', 'dark:bg' => 'dark:bg-green-900/20', 'dark:text' => 'dark:text-green-300'],
                                                            'expirado' => ['title' => 'Expirado', 'color' => 'orange', 'bg' => 'bg-orange-50', 'text' => 'text-orange-800', 'dark:bg' => 'dark:bg-orange-900/20', 'dark:text' => 'dark:text-orange-300'],
                                                            'n/a' => ['title' => 'N/A', 'color' => 'gray', 'bg' => 'bg-gray-50', 'text' => 'text-gray-800', 'dark:bg' => 'dark:bg-gray-900/20', 'dark:text' => 'dark:text-gray-300'],
                                                            'para_assinatura' => ['title' => 'Para Assinatura', 'color' => 'yellow', 'bg' => 'bg-yellow-50', 'text' => 'text-yellow-800', 'dark:bg' => 'dark:bg-yellow-900/20', 'dark:text' => 'dark:text-yellow-300'],
                                                            'rascunho' => ['title' => 'Rascunho', 'color' => 'gray', 'bg' => 'bg-gray-50', 'text' => 'text-gray-800', 'dark:bg' => 'dark:bg-gray-900/20', 'dark:text' => 'dark:text-gray-300'],
                                                        ];

                                                        $statusInfo = $statusMap[strtolower($contractStatus)] ?? ['title' => 'Desconhecido', 'color' => 'gray', 'bg' => 'bg-gray-50', 'text' => 'text-gray-800', 'dark:bg' => 'dark:bg-gray-900/20', 'dark:text' => 'dark:text-gray-300'];
                                                        $rowClass = 'bg-yellow-50 dark:bg-yellow-900/10';
                                                    @endphp

                                                    <tr class="{{ $rowClass }} hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                                        <td class="px-4 py-3 whitespace-nowrap">
                                                            <div class="flex items-center">
                                                                <x-status-dot :status="strtolower($contractStatus)" :title="$statusInfo['title']" size="md" />
                                                                <span class="ml-2 text-sm font-medium {{ $statusInfo['text'] }} dark:{{ $statusInfo['dark:text'] }}">{{ $statusInfo['title'] }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                                            <div class="font-medium">{{ $gig?->booker?->name ?? 'Agência Direta' }}</div>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap">
                                                            <div class="text-sm font-semibold text-blue-700 dark:text-blue-400">
                                                                {{ $payment->due_date?->isoFormat('L') }}
                                                                <div class="text-xs font-medium text-amber-600 dark:text-amber-400 mt-1">
                                                                    <i class="far fa-clock mr-1"></i> Vence {{ $payment->due_date->diffForHumans() }}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                                            <div class="whitespace-normal text-xs bg-gray-50 dark:bg-gray-700/50 rounded px-2 py-1">
                                                                {{ $payment->description ?: 'Parcela' }}
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                                            <div class="font-bold text-gray-900 dark:text-white">
                                                                {{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }}
                                                                @if($payment->currency !== 'BRL')
                                                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                                                        ~R$ {{ number_format($payment->due_value_brl, 2, ',', '.') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                {{-- Subtotal À Vencer --}}
                                                <tr class="bg-blue-100 dark:bg-blue-900/20 border-t-2 border-blue-300 dark:border-blue-700">
                                                    <td colspan="4" class="px-4 py-3 text-right font-semibold text-blue-800 dark:text-blue-200">
                                                        Subtotal À Vencer ({{ $subGroup['upcoming_count'] }} parcelas)
                                                    </td>
                                                    <td class="px-4 py-3 text-right font-bold text-blue-800 dark:text-blue-200">
                                                        R$ {{ number_format($subGroup['upcoming_total'], 2, ',', '.') }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        {{-- Tabela normal para outros grupos --}}
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-20">Status Contrato</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-20">Booker</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-20">Artista</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-64">Local</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-24">Data Evento</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-24">Vencimento</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-24">Parcela</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider w-24">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($groupPayments as $payment)
                                @php
                                    $gig = $payment->gig;
                                    $status = $payment->inferred_status; // Retorna 'vencido' ou 'a_vencer'
                                    $contractStatus = $gig?->contract_status ?? 'rascunho';
                                    
                                    // Mapeamento de status do contrato com base nos valores reais do banco
                                    $statusMap = [
                                        'assinado' => ['title' => 'Assinado', 'color' => 'blue', 'bg' => 'bg-blue-50', 'text' => 'text-blue-800', 'dark:bg' => 'dark:bg-blue-900/20', 'dark:text' => 'dark:text-blue-300'],
                                        'cancelado' => ['title' => 'Cancelado', 'color' => 'red', 'bg' => 'bg-red-50', 'text' => 'text-red-800', 'dark:bg' => 'dark:bg-red-900/20', 'dark:text' => 'dark:text-red-300'],
                                        'concluido' => ['title' => 'Concluído', 'color' => 'green', 'bg' => 'bg-green-50', 'text' => 'text-green-800', 'dark:bg' => 'dark:bg-green-900/20', 'dark:text' => 'dark:text-green-300'],
                                        'expirado' => ['title' => 'Expirado', 'color' => 'orange', 'bg' => 'bg-orange-50', 'text' => 'text-orange-800', 'dark:bg' => 'dark:bg-orange-900/20', 'dark:text' => 'dark:text-orange-300'],
                                        'n/a' => ['title' => 'N/A', 'color' => 'gray', 'bg' => 'bg-gray-50', 'text' => 'text-gray-800', 'dark:bg' => 'dark:bg-gray-900/20', 'dark:text' => 'dark:text-gray-300'],
                                        'para_assinatura' => ['title' => 'Para Assinatura', 'color' => 'yellow', 'bg' => 'bg-yellow-50', 'text' => 'text-yellow-800', 'dark:bg' => 'dark:bg-yellow-900/20', 'dark:text' => 'dark:text-yellow-300'],
                                        'rascunho' => ['title' => 'Rascunho', 'color' => 'gray', 'bg' => 'bg-gray-50', 'text' => 'text-gray-800', 'dark:bg' => 'dark:bg-gray-900/20', 'dark:text' => 'dark:text-gray-300'],
                                    ];
                                    
                                    $statusInfo = $statusMap[strtolower($contractStatus)] ?? ['title' => 'Desconhecido', 'color' => 'gray', 'bg' => 'bg-gray-50', 'text' => 'text-gray-800', 'dark:bg' => 'dark:bg-gray-900/20', 'dark:text' => 'dark:text-gray-300'];
                                    
                                    $rowClass = [
                                        'vencido' => 'bg-red-50 dark:bg-red-900/10',
                                        'a_vencer' => 'bg-yellow-50 dark:bg-yellow-900/10',
                                    ][$status] ?? '';
                                @endphp
                                
                                <tr class="{{ $rowClass }} hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <x-status-dot :status="strtolower($contractStatus)" :title="$statusInfo['title']" size="md" />
                                            <span class="ml-2 text-sm font-medium {{ $statusInfo['text'] }} dark:{{ $statusInfo['dark:text'] }}">{{ $statusInfo['title'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                        <div class="font-medium">{{ $gig?->booker?->name ?? 'Agência Direta' }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $gig?->artist?->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 break-words">
                                        @if($gig)
                                            <a href="{{ route('gigs.show', $gig) }}" class="font-semibold text-indigo-600 hover:text-indigo-800 hover:underline dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors" title="Ver detalhes da Gig">
                                                Gig #{{ $gig->id }}
                                            </a>
                                            @if($gig->location_event_details)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[200px]" title="{{ $gig->location_event_details }}">
                                                    {{ $gig->location_event_details }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                        {{ $gig?->gig_date?->isoFormat('L') ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-semibold {{ $status === 'vencido' ? 'text-red-600 dark:text-red-400' : 'text-blue-700 dark:text-blue-400' }}">
                                            {{ $payment->due_date?->isoFormat('L') }}
                                            @if($status === 'vencido')
                                                <div class="text-xs font-medium text-red-500 dark:text-red-400 mt-1">
                                                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $payment->due_date->diffForHumans() }}
                                                </div>
                                            @else
                                                <div class="text-xs font-medium text-amber-600 dark:text-amber-400 mt-1">
                                                    <i class="far fa-clock mr-1"></i> Vence {{ $payment->due_date->diffForHumans() }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                        <div class="whitespace-normal text-xs bg-gray-50 dark:bg-gray-700/50 rounded px-2 py-1">
                                            {{ $payment->description ?: 'Parcela' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            {{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }}
                                            @if($payment->currency !== 'BRL')
                                                <div class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                                    ~R$ {{ number_format($payment->due_value_brl, 2, ',', '.') }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        @endif
    @endforeach

    @if(collect($groupedPayments)->flatten()->isEmpty())
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                <div class="flex flex-col items-center justify-center space-y-2">
                    <i class="fas fa-inbox text-3xl text-gray-300 dark:text-gray-600"></i>
                    <p class="mt-2">Nenhum vencimento pendente encontrado para os filtros selecionados.</p>
                    @if(request()->hasAny(['start_date', 'end_date', 'contract_status', 'status', 'currency']))
                        <a href="{{ route('reports.due-dates') }}" class="mt-2 inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-800/50 transition-colors">
                            <i class="fas fa-times-circle mr-1.5"></i> Limpar filtros
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>