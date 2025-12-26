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
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Botão Visualizar ND (Prévia) - Sempre visível --}}
    <div class="mb-4 flex items-center gap-3">
        <a href="{{ route('debit-notes.preview', $gig) }}" 
           target="_blank"
           class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors">
            <i class="fas fa-file-invoice-dollar mr-2"></i>
            Visualizar ND (Prévia)
        </a>
        <span class="text-xs text-gray-500 dark:text-gray-400">
            <i class="fas fa-info-circle mr-1"></i>Abre em nova aba sem gerar numeração
        </span>
        @if(!$gig->serviceTaker)
            <span class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-2 py-1 rounded">
                <i class="fas fa-exclamation-triangle mr-1"></i>Sem tomador definido
            </span>
        @endif
    </div>

    {{-- Layout em 2 colunas --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Coluna Esquerda: Card Principal de Fechamento (2/3) --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
                {{-- Cabeçalho do Card --}}
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">FECHAMENTO</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Artista: <span class="font-medium">{{ $gig->artist->name ?? 'N/A' }}</span>
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Evento: {{ $gig->gig_date->isoFormat('L') }} - {{ $gig->location_event_details }}
                    </p>
                </div>

                {{-- Seção: Tomador de Serviço --}}
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2 flex items-center gap-2">
                        <i class="fas fa-building text-primary-500"></i>
                        Tomador de Serviço
                    </h4>
                    
                    @if($gig->serviceTaker)
                        <div class="text-sm space-y-1">
                            <p class="text-gray-800 dark:text-gray-200">
                                <span class="font-medium">{{ $gig->serviceTaker->organization ?? 'N/A' }}</span>
                            </p>
                            @if($gig->serviceTaker->document)
                                <p class="text-gray-600 dark:text-gray-400 text-xs">
                                    <i class="fas fa-id-card mr-1"></i>
                                    {{ $gig->serviceTaker->formatted_document }}
                                </p>
                            @endif
                            @if($gig->serviceTaker->state_registration || $gig->serviceTaker->municipal_registration)
                                <p class="text-gray-600 dark:text-gray-400 text-xs">
                                    @if($gig->serviceTaker->state_registration)
                                        <span class="mr-3"><i class="fas fa-file-alt mr-1"></i>IE: {{ $gig->serviceTaker->state_registration }}</span>
                                    @endif
                                    @if($gig->serviceTaker->municipal_registration)
                                        <span><i class="fas fa-file-alt mr-1"></i>IM: {{ $gig->serviceTaker->municipal_registration }}</span>
                                    @endif
                                </p>
                            @endif
                            @if($gig->serviceTaker->street || $gig->serviceTaker->city)
                                <p class="text-gray-600 dark:text-gray-400 text-xs">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    {{ $gig->serviceTaker->full_address }}
                                </p>
                            @endif
                            @if($gig->serviceTaker->contact || $gig->serviceTaker->email || $gig->serviceTaker->phone)
                                <p class="text-gray-600 dark:text-gray-400 text-xs">
                                    @if($gig->serviceTaker->contact)
                                        <span class="mr-3"><i class="fas fa-user mr-1"></i>{{ $gig->serviceTaker->contact }}</span>
                                    @endif
                                    @if($gig->serviceTaker->email)
                                        <span class="mr-3"><i class="fas fa-envelope mr-1"></i>{{ $gig->serviceTaker->email }}</span>
                                    @endif
                                    @if($gig->serviceTaker->phone)
                                        <span><i class="fas fa-phone mr-1"></i>{{ $gig->serviceTaker->phone }}</span>
                                    @endif
                                </p>
                            @endif
                            <div class="pt-2">
                                <a href="{{ route('gigs.edit', ['gig' => $gig, 'active_tab' => 1]) }}" 
                                   class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                                    <i class="fas fa-edit mr-1"></i> Alterar Tomador
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="text-sm">
                            <p class="text-yellow-600 dark:text-yellow-400 mb-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Nenhum tomador de serviço definido para esta gig.
                            </p>
                            <a href="{{ route('gigs.edit', ['gig' => $gig, 'active_tab' => 1]) }}" 
                               class="inline-flex items-center px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-medium rounded-md transition-colors">
                                <i class="fas fa-plus-circle mr-1"></i> Definir Tomador de Serviço
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Seção: Cálculo do Valor da NF --}}
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
        </div>

        {{-- Coluna Direita: Histórico e Ações (1/3) --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Card: Histórico do Workflow --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4">
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
                                <p class="text-gray-700 dark:text-gray-300 flex items-center gap-2 flex-wrap">
                                    <strong>Tipo Doc:</strong> 
                                    {{ $settlement->documentation_type === 'nf' ? 'NOTA FISCAL' : 'RECIBO' }}
                                    @if($settlement->documentation_number)
                                        - {{ $settlement->documentation_number }}
                                    @endif
                                    @if($settlement->documentation_file_path)
                                        - <a href="{{ Storage::url($settlement->documentation_file_path) }}" 
                                             target="_blank" 
                                             class="text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center">
                                            <i class="fas fa-paperclip mr-1"></i>Ver Arquivo
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

            {{-- Card: Ações do Workflow --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Ações</h4>
                <x-settlement-workflow-actions :gig="$gig" />
            </div>
        </div>
    </div>
</x-app-layout>