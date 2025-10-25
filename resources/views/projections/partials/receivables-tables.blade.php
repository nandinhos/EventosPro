{{-- Tabelas Detalhadas de Recebíveis e Pagamentos --}}

{{-- Tabela: Contas a Receber Vencidas --}}
<x-expandable-section
    title="Recebíveis de Eventos Passados - Ação Necessária"
    :count="($accounts_receivable['overdue_count'] ?? 0) . ' pagamentos'"
    color="red"
    :expanded="true"
    :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z\' clip-rule=\'evenodd\' /></svg>'">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-red-50 dark:bg-red-900/20">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Data Vencimento</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Dias Vencido</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Gig</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Artista</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Valor</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Prioridade</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @php
                    $overduePayments = collect($accounts_receivable['payments'] ?? [])->where('is_overdue', true)->sortBy('days_until_due');
                @endphp
                @forelse($overduePayments as $payment)
                    <tr class="hover:bg-red-50/50 dark:hover:bg-red-900/10 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                            {{ $payment['due_date'] ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 font-semibold">
                            {{ abs($payment['days_until_due'] ?? 0) }} dias
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            @if(isset($payment['gig_id']))
                                <a href="{{ route('gigs.show', $payment['gig_id']) }}"
                                   class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 font-medium"
                                   title="Ver detalhes da Gig">
                                    Gig #{{ $payment['gig_id'] }}
                                </a>
                            @else
                                {{ $payment['gig_contract'] ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $payment['artist_name'] ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-red-700 dark:text-red-300">
                            R$ {{ number_format($payment['due_value_brl'] ?? 0, 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @php $daysOverdue = abs($payment['days_until_due'] ?? 0); @endphp
                            @if($daysOverdue > 30)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-600 text-white">
                                    Crítica
                                </span>
                            @elseif($daysOverdue > 15)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-orange-500 text-white">
                                    Alta
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500 text-white">
                                    Média
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Nenhuma conta a receber vencida
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($overduePayments->isNotEmpty())
                <tfoot class="bg-red-50 dark:bg-red-900/20">
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-right text-sm font-semibold text-red-700 dark:text-red-300">
                            Total Vencido:
                        </td>
                        <td class="px-6 py-4 text-right text-base font-bold text-red-800 dark:text-red-200">
                            R$ {{ number_format($accounts_receivable['total_overdue'] ?? 0, 2, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-expandable-section>

{{-- Tabela: Contas a Receber Futuras --}}
<x-expandable-section
    title="Recebíveis de Eventos Futuros"
    :count="($accounts_receivable['future_count'] ?? 0) . ' pagamentos'"
    color="green"
    :expanded="false"
    :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z\' clip-rule=\'evenodd\' /></svg>'">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-green-50 dark:bg-green-900/20">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-green-700 dark:text-green-300 uppercase tracking-wider">Data Vencimento</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-green-700 dark:text-green-300 uppercase tracking-wider">Dias Restantes</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-green-700 dark:text-green-300 uppercase tracking-wider">Gig</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-green-700 dark:text-green-300 uppercase tracking-wider">Artista</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-green-700 dark:text-green-300 uppercase tracking-wider">Valor</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-green-700 dark:text-green-300 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @php
                    $futurePayments = collect($accounts_receivable['payments'] ?? [])->where('is_overdue', false)->sortBy('days_until_due');
                @endphp
                @forelse($futurePayments as $payment)
                    <tr class="hover:bg-green-50/50 dark:hover:bg-green-900/10 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                            {{ $payment['due_date'] ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 dark:text-blue-400 font-semibold">
                            {{ $payment['days_until_due'] ?? 0 }} dias
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            @if(isset($payment['gig_id']))
                                <a href="{{ route('gigs.show', $payment['gig_id']) }}"
                                   class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 font-medium"
                                   title="Ver detalhes da Gig">
                                    Gig #{{ $payment['gig_id'] }}
                                </a>
                            @else
                                {{ $payment['gig_contract'] ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $payment['artist_name'] ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-green-700 dark:text-green-300">
                            R$ {{ number_format($payment['due_value_brl'] ?? 0, 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @php $daysUntilDue = $payment['days_until_due'] ?? 0; @endphp
                            @if($daysUntilDue <= 3)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                    Próximo
                                </span>
                            @elseif($daysUntilDue <= 7)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    Esta semana
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    Em dia
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                Nenhuma conta a receber futura
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($futurePayments->isNotEmpty())
                <tfoot class="bg-green-50 dark:bg-green-900/20">
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-right text-sm font-semibold text-green-700 dark:text-green-300">
                            Total Futuro:
                        </td>
                        <td class="px-6 py-4 text-right text-base font-bold text-green-800 dark:text-green-200">
                            R$ {{ number_format($accounts_receivable['total_future'] ?? 0, 2, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-expandable-section>

{{-- Tabela: Pagamentos a Artistas --}}
<x-expandable-section
    title="Detalhes dos Pagamentos Pendentes aos Artistas"
    :count="($artist_payment_details['gig_count'] ?? 0) . ' eventos'"
    color="red"
    :expanded="false"
    :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z\' /></svg>'">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-red-50 dark:bg-red-900/20">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Data Evento</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Gig</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Artista</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Cachê Líquido</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">A Pagar (80%)</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Pago</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Pendente</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Dias</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($artist_payment_details['payments'] ?? [] as $payment)
                    <tr class="hover:bg-red-50/50 dark:hover:bg-red-900/10 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                            {{ $payment['gig_date'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <a href="{{ route('gigs.show', $payment['gig_id']) }}"
                               class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 font-medium"
                               title="Ver detalhes da Gig">
                                {{ $payment['gig_contract'] }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $payment['artist_name'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-700 dark:text-gray-300">
                            R$ {{ number_format($payment['cachee_liquido'], 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-700 dark:text-gray-300">
                            R$ {{ number_format($payment['artist_payout_total'], 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600 dark:text-green-400 font-medium">
                            R$ {{ number_format($payment['amount_paid'], 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-red-700 dark:text-red-300">
                            R$ {{ number_format($payment['amount_pending'], 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @php $days = $payment['days_since_event']; @endphp
                            @if($days > 60)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-600 text-white">
                                    {{ $days }}d
                                </span>
                            @elseif($days > 30)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-500 text-white">
                                    {{ $days }}d
                                </span>
                            @elseif($days > 15)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-500 text-white">
                                    {{ $days }}d
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-500 text-white">
                                    {{ $days }}d
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Nenhum pagamento pendente aos artistas
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($artist_payment_details['payments'] ?? []) > 0)
                <tfoot class="bg-red-50 dark:bg-red-900/20">
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-right text-sm font-semibold text-red-700 dark:text-red-300">
                            Total Pendente:
                        </td>
                        <td class="px-6 py-4 text-right text-base font-bold text-red-800 dark:text-red-200">
                            R$ {{ number_format($artist_payment_details['total_pending'] ?? 0, 2, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-expandable-section>
