<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
    @forelse ($gigs as $gig)
        @php
            $gigCosts = $gig->gigCosts;
            $confirmedCosts = $gigCosts->where('is_confirmed', true);
            $pendingCosts = $gigCosts->where('is_confirmed', false);
            $totalConfirmed = $confirmedCosts->sum('value_brl');
            $totalPending = $pendingCosts->sum('value_brl');
            $hasExpenses = $gigCosts->count() > 0;
        @endphp

        <div x-data="{ open: false }" class="py-3 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
            {{-- Linha Resumida Clicável --}}
            <div @click="open = !open" class="flex items-center justify-between cursor-pointer group">
                {{-- Coluna Esquerda: Gig, Booker, Local --}}
                <div class="flex items-center space-x-4">
                    {{-- Coluna 1: Link da Gig --}}
                    <a href="{{ route('gigs.show', $gig->id) }}" @click.stop class="text-sm font-semibold text-primary-600 hover:underline" title="Ver detalhes completos da Gig">
                        #{{ $gig->id }}
                    </a>
                    {{-- Coluna 2: Booker e Local --}}
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $gig->booker->name ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($gig->location_event_details, 35) }}</p>
                    </div>
                </div>

                {{-- Coluna Direita: Status, Data, Valor --}}
                <div class="flex items-center space-x-4">
                    {{-- Coluna 3: Ícones de Status --}}
                    <div class="flex space-x-2" title="Status do Ciclo Financeiro (Cliente | Despesas | Artista)">
                        {{-- Status Pagamento Cliente --}}
                        <i class="fas fa-dollar-sign fa-fw {{ $gig->payment_status === 'pago' ? 'text-green-500' : ($gig->payment_status === 'vencido' ? 'text-red-500' : 'text-gray-400') }}"></i>
                        {{-- Status Pagamento Despesas --}}
                        <i class="fas fa-receipt fa-fw {{ $hasExpenses && $totalConfirmed > 0 ? 'text-green-500' : ($hasExpenses ? 'text-yellow-500' : 'text-gray-400') }}"></i>
                        {{-- Status Pagamento Artista --}}
                        <i class="fas fa-user-check fa-fw {{ $gig->artist_payment_status === 'pago' ? 'text-green-500' : 'text-gray-400' }}"></i>
                    </div>
                    {{-- Coluna 4: Data e Valor --}}
                    <div class="text-sm text-right">
                        <p class="text-gray-700 dark:text-gray-300">{{ $gig->gig_date->isoFormat('L') }}</p>
                        <p class="text-xs font-semibold text-gray-500">R$ {{ number_format($gig->calculated_artist_net_payout_brl ?? 0, 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Área Expandida com Detalhes --}}
            <div x-show="open" x-transition class="mt-4 pl-8 border-l-2 border-gray-200 dark:border-gray-700 ml-2">
                <div class="space-y-2 text-xs">
                    @php
                        $statuses = [
                            'Pagamento Cliente' => ['status' => $gig->payment_status, 'color' => ($gig->payment_status === 'pago' ? 'green' : ($gig->payment_status === 'vencido' ? 'red' : 'gray'))],
                            'Repasse Artista' => ['status' => $gig->artist_payment_status, 'color' => $gig->artist_payment_status === 'pago' ? 'green' : 'gray'],
                        ];
                    @endphp
                    @foreach($statuses as $label => $info)
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-{{ $info['color'] }}-500 mr-2"></span>
                            <span class="font-medium text-gray-600 dark:text-gray-400 w-32">{{ $label }}:</span>
                            <span class="font-semibold text-gray-800 dark:text-white">{{ ucfirst($info['status']) }}</span>
                        </div>
                    @endforeach

                    {{-- Cachê Líquido --}}
                    <div class="flex items-center mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="font-medium text-gray-600 dark:text-gray-400 w-32">Cachê Líquido:</span>
                        <span class="font-semibold text-green-600 dark:text-green-400">R$ {{ number_format($gig->calculated_artist_net_payout_brl ?? 0, 2, ',', '.') }}</span>
                    </div>

                    {{-- Despesas --}}
                    @if ($hasExpenses)
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-medium text-gray-800 dark:text-white">Despesas do Evento</h4>
                                <div class="text-xs space-x-2">
                                    @if ($totalConfirmed > 0)
                                        <span class="text-green-600 dark:text-green-400">
                                            Conf: R$ {{ number_format($totalConfirmed, 2, ',', '.') }}
                                        </span>
                                    @endif
                                    @if ($totalPending > 0)
                                        <span class="text-yellow-600 dark:text-yellow-400">
                                            Pend: R$ {{ number_format($totalPending, 2, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-3 space-y-2">
                                @foreach ($gigCosts as $cost)
                                    <div class="flex items-center justify-between text-xs {{ !$cost->is_confirmed ? 'bg-yellow-50 dark:bg-yellow-900/10 p-2 rounded' : '' }}">
                                        <div class="flex-1">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $cost->costCenter->name ?? 'N/A' }}</span>
                                            @if($cost->description)
                                                <span class="text-gray-500 dark:text-gray-400"> - {{ $cost->description }}</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="font-mono text-gray-700 dark:text-gray-300">
                                                {{ $cost->currency }} {{ number_format($cost->value, 2, ',', '.') }}
                                            </span>
                                            @if ($cost->is_invoice)
                                                <i class="fas fa-file-invoice text-green-500" title="Incluído na NF do Artista"></i>
                                            @endif
                                            @if ($cost->is_confirmed)
                                                <i class="fas fa-check-circle text-green-500" title="Confirmado"></i>
                                            @else
                                                <i class="fas fa-clock text-yellow-500" title="Pendente"></i>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 italic">
                                O pagamento do artista só pode ser realizado quando todas as despesas estiverem confirmadas.
                            </p>
                        </div>
                    @else
                        <div class="flex items-center mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-gray-400 dark:text-gray-500 text-xs italic">Sem despesas registradas</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-12">
            <i class="fas fa-calendar-times text-gray-400 text-4xl mb-3"></i>
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum evento encontrado para este período.</p>
        </div>
    @endforelse
</div>
