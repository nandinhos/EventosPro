<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
    <div class="overflow-x-auto">
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
                @forelse($payments as $payment)
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
                        
                        $statusInfo = $statusMap[strtolower($contractStatus)] ?? ['title' => 'Desconhecido', 'color' => 'gray'];
                        
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
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <i class="fas fa-inbox text-3xl text-gray-300 dark:text-gray-600"></i>
                                <p class="mt-2">Nenhum vencimento pendente encontrado para os filtros selecionados.</p>
                                @if(request()->hasAny(['start_date', 'end_date', 'contract_status', 'status', 'currency']))
                                    <a href="{{ route('reports.due-dates') }}" class="mt-2 inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-800/50 transition-colors">
                                        <i class="fas fa-times-circle mr-1.5"></i> Limpar filtros
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>