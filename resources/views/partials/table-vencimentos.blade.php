<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Booker</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Artista</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Local</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data do Evento</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Vencimento</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Parcela</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($payments as $payment)
                    @php
                        $gig = $payment->gig;
                        $status = $payment->inferred_status; // Retorna 'vencido' ou 'a_vencer'

                        $statusInfo = [
                            'vencido' => ['title' => 'Vencido', 'color' => 'red'],
                            'a_vencer' => ['title' => 'A Vencer', 'color' => 'yellow'],
                        ][$status] ?? ['title' => '?', 'color' => 'gray'];
                        
                        $rowClass = [
                            'vencido' => 'bg-red-50 dark:bg-red-900/10',
                            'a_vencer' => 'bg-yellow-50 dark:bg-yellow-900/10',
                        ][$status] ?? '';
                    @endphp
                    
                    <tr class="{{ $rowClass }} hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-status-dot :status="$status" :title="$statusInfo['title']" size="md" />
                            <span class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $statusInfo['title'] }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $gig?->booker?->name ?? 'Agência Direta' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $gig?->artist?->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 break-words max-w-[200px]">{{ $gig?->location_event_details }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $gig?->gig_date?->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold {{ $status === 'vencido' ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $payment->due_date?->format('d/m/Y') }}
                                @if($status === 'vencido')
                                    <div class="text-xs text-red-500 dark:text-red-400">{{ $payment->due_date->diffForHumans() }}</div>
                                @else
                                    <div class="text-xs text-yellow-500 dark:text-yellow-400">Vence {{ $payment->due_date->diffForHumans() }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $payment->description ?: 'Parcela' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="text-gray-900 dark:text-white">
                                {{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }}
                                @if($payment->currency !== 'BRL')
                                    <div class="text-xs text-gray-500 dark:text-gray-400">~R$ {{ number_format($payment->due_value_brl, 2, ',', '.') }}</div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            Nenhum vencimento pendente encontrado para os filtros selecionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>