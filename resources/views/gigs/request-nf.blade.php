<x-app-layout>
    {{-- Cabeçalho da Página --}}
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                Fechamento - Gig #{{ $gig->id }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $gig->artist->name ?? 'N/A' }} • {{ $gig->gig_date->isoFormat('L') }}</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Badge de Status do Workflow --}}
            <x-workflow-badge :gig="$gig" size="md" />
            <x-back-button :fallback="route('gigs.show', $backUrlParams)" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm" />
        </div>
    </div>

    {{-- Alertas de Sucesso/Erro --}}
    @if (session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 rounded-lg max-w-lg mx-auto">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 rounded-lg max-w-lg mx-auto">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    <div>
        {{-- Card com Detalhes para NF --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden max-w-lg mx-auto">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">FECHAMENTO</h3>
                Artista: <span class="font-medium">{{ $gig->artist->name ?? 'N/A' }}</span> <br>
                Evento: {{ $gig->gig_date->isoFormat('L') }} - {{ $gig->location_event_details }}
            </div>

            <div class="p-4 space-y-3 text-xs sm:text-sm">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Cálculo do Valor da NF:</h4>

                {{-- Valor do Contrato (Original BRL) --}}
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Valor Contrato ({{ $gig->currency }}):</span>
                    <span class="font-medium text-gray-800 dark:text-white">
                        {{ $gig->currency }} {{ number_format($gig->cache_value, 2, ',', '.') }}
                        @if($gig->currency !== 'BRL')
                            (aprox. R$ {{ number_format($gigCacheValueBrl, 2, ',', '.') }})
                        @endif
                    </span>
                </div>

                {{-- Total de TODAS as Despesas Confirmadas --}}
                <div class="pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50">
                    <span class="text-gray-600 dark:text-gray-400 block mb-1">(-) Total Despesas Confirmadas (Deduzidas da Base):</span>
                    <div class="pl-2 space-y-1">
                        @forelse($gig->gigCosts->where('is_confirmed', true) as $cost)
                            <div class="flex justify-between text-[11px] sm:text-xs">
                                <span class="text-gray-500 dark:text-gray-400">- {{ $cost->costCenter->name ?? 'N/A' }}: {{ $cost->description }}</span>
                                <span class="font-medium text-red-500 dark:text-red-400">R$ {{ number_format($cost->value, 2, ',', '.') }}</span>
                            </div>
                        @empty
                            <div class="text-gray-500 dark:text-gray-400 text-[11px] sm:text-xs">- Nenhuma despesa confirmada.</div>
                        @endforelse
                        <div class="flex justify-between text-xs font-semibold pt-1 border-t border-dashed border-gray-200 dark:border-gray-600 mt-1">
                            <span class="text-gray-500 dark:text-gray-400">Total Geral Despesas Confirmadas:</span>
                            <span class="text-red-500 dark:text-red-400">R$ {{ number_format($totalConfirmedExpensesBrl, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Cachê Bruto (Base para Comissões) --}}
                <div class="flex justify-between pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50">
                    <span class="text-gray-600 dark:text-gray-400">= Cachê Bruto (Base para Comissões):</span>
                    <span class="font-medium text-gray-800 dark:text-white">R$ {{ number_format($calculatedGrossCashBrl, 2, ',', '.') }}</span>
                </div>

                {{-- Cachê Líquido do Artista (antes do reembolso) --}}
                <div class="flex justify-between pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-700/30 px-3 -mx-3 rounded-t-md">
                    <span class="text-gray-600 dark:text-gray-400 font-semibold">= Cachê Líquido do Artista (para NF):</span>
                    <span class="font-semibold text-gray-800 dark:text-white">R$ {{ number_format($artistNetPayoutBeforeReimbursement, 2, ',', '.') }}</span>
                </div>

                {{-- Despesas Pagas pelo Artista (Reembolsáveis, is_invoice = true) --}}
                @if($totalReimbursableExpensesBrl > 0)
                    <div class="pt-2 mt-0 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-700/30 px-3 -mx-3">
                        <span class="text-gray-600 dark:text-gray-400 block mb-1 font-semibold">(+) Reembolso Despesas Pagas pelo Artista:</span>
                        <div class="pl-2 space-y-1">
                            @foreach($gig->gigCosts->where('is_confirmed', true)->where('is_invoice', true) as $cost)
                                <div class="flex justify-between text-[11px] sm:text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">- {{ $cost->costCenter->name ?? 'N/A' }}: {{ $cost->description }}</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">R$ {{ number_format($cost->value, 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between text-xs font-semibold pt-1 border-t border-dashed border-gray-200 dark:border-gray-600 mt-1">
                                <span class="text-gray-500 dark:text-gray-400">Total Reembolsável ao Artista:</span>
                                <span class="text-green-600 dark:text-green-400">R$ {{ number_format($totalReimbursableExpensesBrl, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- VALOR FINAL DA NOTA FISCAL --}}
                <div class="flex justify-between items-center py-3 mt-0 border-t-2 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700/50 px-3 -mx-3 rounded-b-md">
                    <span class="text-md font-semibold text-gray-700 dark:text-gray-200">VALOR NOTA FISCAL:</span>
                    <span class="text-xl font-bold text-primary-600 dark:text-primary-400">
                        R$ {{ number_format($finalArtistInvoiceValueBrl, 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Histórico do Workflow --}}
        <div class="mt-6 max-w-lg mx-auto">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 mb-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Histórico do Fechamento</h4>
                
                {{-- Timeline Visual - Agora com 5 passos --}}
                @php
                    $requiresNd = $settlement?->requires_debit_note ?? false;
                    $hasNd = $gig->hasDebitNote();
                    $isFullyCompleted = ($settlementStage === 'pago') && (!$requiresNd || $hasNd);
                @endphp
                <div class="flex items-center justify-between text-xs mb-4">
                    <div class="flex flex-col items-center {{ $settlementStage !== 'aguardando_conferencia' ? 'text-green-600' : 'text-gray-500' }}">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center {{ $settlementStage !== 'aguardando_conferencia' ? 'bg-green-100 dark:bg-green-900' : 'bg-gray-200 dark:bg-gray-700' }}">
                            <i class="fas fa-clipboard-check text-xs"></i>
                        </div>
                        <span class="mt-1 text-center text-[10px]">Conferir</span>
                    </div>
                    <div class="flex-1 h-0.5 {{ in_array($settlementStage, ['fechamento_enviado', 'documentacao_recebida', 'pago']) ? 'bg-green-400' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                    <div class="flex flex-col items-center {{ in_array($settlementStage, ['fechamento_enviado', 'documentacao_recebida', 'pago']) ? 'text-green-600' : 'text-gray-500' }}">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center {{ in_array($settlementStage, ['fechamento_enviado', 'documentacao_recebida', 'pago']) ? 'bg-green-100 dark:bg-green-900' : 'bg-gray-200 dark:bg-gray-700' }}">
                            <i class="fas fa-paper-plane text-xs"></i>
                        </div>
                        <span class="mt-1 text-center text-[10px]">Enviado</span>
                    </div>
                    <div class="flex-1 h-0.5 {{ in_array($settlementStage, ['documentacao_recebida', 'pago']) ? 'bg-green-400' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                    <div class="flex flex-col items-center {{ in_array($settlementStage, ['documentacao_recebida', 'pago']) ? 'text-green-600' : 'text-gray-500' }}">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center {{ in_array($settlementStage, ['documentacao_recebida', 'pago']) ? 'bg-green-100 dark:bg-green-900' : 'bg-gray-200 dark:bg-gray-700' }}">
                            <i class="fas fa-file-invoice text-xs"></i>
                        </div>
                        <span class="mt-1 text-center text-[10px]">NF</span>
                    </div>
                    <div class="flex-1 h-0.5 {{ $settlementStage === 'pago' ? 'bg-green-400' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                    <div class="flex flex-col items-center {{ $settlementStage === 'pago' ? 'text-green-600' : 'text-gray-500' }}">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center {{ $settlementStage === 'pago' ? 'bg-green-100 dark:bg-green-900' : 'bg-gray-200 dark:bg-gray-700' }}">
                            <i class="fas fa-dollar-sign text-xs"></i>
                        </div>
                        <span class="mt-1 text-center text-[10px]">Pago</span>
                    </div>
                    <div class="flex-1 h-0.5 {{ $isFullyCompleted ? 'bg-green-400' : ($settlementStage === 'pago' && $requiresNd && !$hasNd ? 'bg-orange-400' : 'bg-gray-300 dark:bg-gray-600') }}"></div>
                    <div class="flex flex-col items-center {{ $isFullyCompleted ? 'text-green-600' : ($settlementStage === 'pago' && $requiresNd && !$hasNd ? 'text-orange-600' : 'text-gray-500') }}">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center {{ $isFullyCompleted ? 'bg-green-100 dark:bg-green-900' : ($settlementStage === 'pago' && $requiresNd && !$hasNd ? 'bg-orange-100 dark:bg-orange-900' : 'bg-gray-200 dark:bg-gray-700') }}">
                            <i class="fas {{ $isFullyCompleted ? 'fa-check-circle' : 'fa-file-invoice-dollar' }} text-xs"></i>
                        </div>
                        <span class="mt-1 text-center text-[10px]">{{ $isFullyCompleted ? '✓' : 'ND' }}</span>
                    </div>
                </div>

                {{-- Detalhes do Histórico --}}
                @if($settlement)
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3 space-y-2 text-sm">
                        @if($settlement->settlement_sent_at)
                            <p class="text-gray-700 dark:text-gray-300">
                                <strong>Enviado ao Artista:</strong> {{ $settlement->settlement_sent_at->format('d/m/Y H:i') }}
                            </p>
                        @endif
                        
                        @if($settlement->documentation_received_at)
                            <p class="text-gray-700 dark:text-gray-300">
                                <strong>Documentação Recebida em:</strong> {{ $settlement->documentation_received_at->format('d/m/Y H:i') }}
                            </p>
                            @if($settlement->documentation_type)
                                <p class="text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <strong>Tipo Doc:</strong> 
                                    {{ $settlement->documentation_type === 'nf' ? 'NOTA FISCAL' : 'RECIBO' }}
                                    @if($settlement->documentation_number)
                                        - {{ $settlement->documentation_number }}
                                    @endif
                                    @if($settlement->documentation_file_path)
                                        - <a href="{{ Storage::url($settlement->documentation_file_path) }}" 
                                             target="_blank" 
                                             class="text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center">
                                            <i class="fas fa-paperclip mr-1"></i> Ver Arquivo
                                        </a>
                                    @endif
                                </p>
                            @endif
                        @endif
                        
                        @if($settlement->artist_payment_paid_at)
                            <p class="text-green-700 dark:text-green-300 font-medium">
                                <strong>Pagamento Realizado:</strong> {{ $settlement->artist_payment_paid_at->format('d/m/Y') }}
                                @if($settlement->artist_payment_value)
                                    - R$ {{ number_format($settlement->artist_payment_value, 2, ',', '.') }}
                                @endif
                            </p>
                        @endif
                        
                        @if($settlement->communication_notes)
                            <p class="text-gray-500 dark:text-gray-400 text-xs mt-2 pt-2 border-t border-dashed border-gray-200 dark:border-gray-600">
                                <i class="fas fa-comment-dots mr-1"></i> {{ $settlement->communication_notes }}
                            </p>
                        @endif
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm border-t border-gray-200 dark:border-gray-700 pt-3">
                    Nenhuma ação registrada ainda.
                </p>
            @endif
        </div>

        {{-- Componente de Workflow - Single Source of Truth --}}
        <x-settlement-workflow-actions :gig="$gig" />
    </div>
</div>
</x-app-layout>