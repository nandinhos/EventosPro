<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Fechamentos de Artistas') }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Gerencie os pagamentos de cachês aos artistas</p>
    </x-slot>

    <div class="container mx-auto px-4 py-6" x-data="settlementsManager()">
        {{-- Alertas são exibidos via SweetAlert no final da página --}}

        {{-- Cards de Resumo por Estágio (4 cards) --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            {{-- Card: Aguardando Conferência --}}
            <a href="{{ route('artists.settlements.index', ['stage' => 'aguardando_conferencia']) }}" 
               class="block p-4 rounded-lg shadow transition-all hover:shadow-lg hover:scale-105 {{ request('stage') === 'aguardando_conferencia' ? 'ring-2 ring-gray-500 bg-gray-200 dark:bg-gray-700' : 'bg-gray-100 dark:bg-gray-800/50' }}">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-clipboard-check text-gray-500 dark:text-gray-400"></i>
                    <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400">Conferir</h3>
                </div>
                <p class="text-2xl font-bold text-gray-600 dark:text-gray-300">{{ $stageMetrics['aguardando_conferencia'] ?? 0 }}</p>
            </a>

            {{-- Card: Fechamento Enviado --}}
            <a href="{{ route('artists.settlements.index', ['stage' => 'fechamento_enviado']) }}" 
               class="block p-4 rounded-lg shadow transition-all hover:shadow-lg hover:scale-105 {{ request('stage') === 'fechamento_enviado' ? 'ring-2 ring-blue-500 bg-blue-200 dark:bg-blue-700' : 'bg-blue-100 dark:bg-blue-900/30' }}">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-paper-plane text-blue-500 dark:text-blue-400"></i>
                    <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400">Ag. NF/Recibo</h3>
                </div>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stageMetrics['fechamento_enviado'] ?? 0 }}</p>
            </a>

            {{-- Card: Documentação Recebida --}}
            <a href="{{ route('artists.settlements.index', ['stage' => 'documentacao_recebida']) }}" 
               class="block p-4 rounded-lg shadow transition-all hover:shadow-lg hover:scale-105 {{ request('stage') === 'documentacao_recebida' ? 'ring-2 ring-yellow-500 bg-yellow-200 dark:bg-yellow-700' : 'bg-yellow-100 dark:bg-yellow-900/20' }}">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-file-invoice text-yellow-600 dark:text-yellow-400"></i>
                    <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400">Pronto p/ Pagar</h3>
                </div>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stageMetrics['documentacao_recebida'] ?? 0 }}</p>
            </a>

            {{-- Card: Pagos --}}
            <a href="{{ route('artists.settlements.index', ['stage' => 'pago']) }}" 
               class="block p-4 rounded-lg shadow transition-all hover:shadow-lg hover:scale-105 {{ request('stage') === 'pago' ? 'ring-2 ring-green-500 bg-green-200 dark:bg-green-700' : 'bg-green-100 dark:bg-green-900/20' }}">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fas fa-check-circle text-green-500 dark:text-green-400"></i>
                    <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400">Pagos</h3>
                </div>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stageMetrics['pago'] ?? 0 }}</p>
            </a>
        </div>

        {{-- Card Total Pendente --}}
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-4 rounded-lg shadow-lg mb-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm opacity-80">Total a Pagar (Pendentes)</h3>
                    <p class="text-3xl font-bold">R$ {{ number_format($pendingTotal, 2, ',', '.') }}</p>
                </div>
                <i class="fas fa-wallet text-5xl opacity-30"></i>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
            <form method="GET" action="{{ route('artists.settlements.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Busca Livre</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" 
                           placeholder="Artista, booker, local, ID..."
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label for="artist_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Artista</label>
                    <select name="artist_id" id="artist_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Todos</option>
                        @foreach ($artists as $artist)
                            <option value="{{ $artist->id }}" {{ request('artist_id') == $artist->id ? 'selected' : '' }}>{{ $artist->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Evento (De)</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label for="date_until" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Evento (Até)</label>
                    <input type="date" name="date_until" id="date_until" value="{{ request('date_until') }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label for="stage" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estágio</label>
                    <select name="stage" id="stage" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Todos</option>
                        <option value="aguardando_conferencia" {{ request('stage') == 'aguardando_conferencia' ? 'selected' : '' }}>Aguardando Conferência</option>
                        <option value="fechamento_enviado" {{ request('stage') == 'fechamento_enviado' ? 'selected' : '' }}>Ag. NF/Recibo</option>
                        <option value="documentacao_recebida" {{ request('stage') == 'documentacao_recebida' ? 'selected' : '' }}>Pronto p/ Pagar</option>
                        <option value="pago" {{ request('stage') == 'pago' ? 'selected' : '' }}>Pago</option>
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-5 flex gap-2">
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-search mr-2"></i>Filtrar
                    </button>
                    <a href="{{ route('artists.settlements.index') }}" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-times mr-2"></i>Limpar
                    </a>
                </div>
            </form>
        </div>

        {{-- Ações em Massa --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
            <div class="flex flex-wrap items-end gap-3">
                {{-- Botão: Enviar Fechamentos --}}
                <button type="button" @click="submitBatchAction('send')"
                        :disabled="selectedGigs.length === 0"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-paper-plane mr-2"></i>Enviar (<span x-text="selectedGigs.length"></span>)
                </button>

                {{-- Data do Pagamento --}}
                <div class="flex-grow sm:flex-grow-0">
                    <label for="payment_date" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Data Pagamento</label>
                    <input type="date" id="payment_date" x-model="paymentDate" :max="today"
                           class="w-full sm:w-auto rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                {{-- Botão: Pagar Selecionados --}}
                <button type="button" @click="submitBatchAction('pay')"
                        :disabled="selectedGigs.length === 0"
                        class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-check-double mr-2"></i>Pagar (<span x-text="selectedGigs.length"></span>)
                </button>

                {{-- Botão: Reverter --}}
                <button type="button" @click="submitBatchAction('unsettle')"
                        :disabled="selectedGigs.length === 0"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-4 py-2 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-undo-alt mr-2"></i>Reverter (<span x-text="selectedGigs.length"></span>)
                </button>
            </div>

            {{-- Hidden Forms --}}
            <form id="batchPaymentForm" action="{{ route('artists.settlements.settleBatch', request()->query()) }}" method="POST" class="hidden">
                @csrf
            </form>
            <form id="batchUnsettleForm" action="{{ route('artists.settlements.revertBatch', request()->query()) }}" method="POST" class="hidden">
                @method('PATCH')
                @csrf
            </form>
            <form id="batchSendForm" action="{{ route('artists.settlements.sendBatch', request()->query()) }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>

        {{-- Tabela de Fechamentos --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-3 py-3 w-10">
                                <input type="checkbox" 
                                       @change="toggleSelectAll($event.target.checked)"
                                       :checked="areAllSelected()"
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Artista / Booker</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Gig / Local</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cachê (R$)</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Despesas</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Estágio</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($gigs as $gig)
                            @php
                                $stage = $gig->settlement?->settlement_stage ?? 'aguardando_conferencia';
                                $requiresNd = $gig->settlement?->requires_debit_note ?? false;
                                
                                // Override stage for "Ag. ND" state
                                $displayStage = $stage;
                                if ($stage === 'pago' && $requiresNd && !$gig->hasDebitNote()) {
                                    $displayStage = 'aguardando_nd';
                                }
                                
                                $stageColors = [
                                    'aguardando_conferencia' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    'fechamento_enviado' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                    'documentacao_recebida' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                    'pago' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                    'aguardando_nd' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                                ];
                                $stageIcons = [
                                    'aguardando_conferencia' => 'clipboard-check',
                                    'fechamento_enviado' => 'paper-plane',
                                    'documentacao_recebida' => 'file-invoice',
                                    'pago' => 'check-circle',
                                    'aguardando_nd' => 'file-invoice-dollar',
                                ];
                                $stageLabels = [
                                    'aguardando_conferencia' => 'Conferir',
                                    'fechamento_enviado' => 'Ag. NF',
                                    'documentacao_recebida' => 'Pronto',
                                    'pago' => 'Pago',
                                    'aguardando_nd' => 'Ag. ND',
                                ];
                                $reimbursableCosts = $gig->gigCosts ?? collect();
                                $totalCosts = $reimbursableCosts->count();
                                // Contagem simplificada: 2 estágios (mapeando legados para 'pago')
                                $legacyPaidStages = ['comprovante_recebido', 'conferido', 'reembolsado', 'pago'];
                                $pendingCosts = $reimbursableCosts->filter(fn($c) => 
                                    !$c->reimbursement_stage || $c->reimbursement_stage === 'aguardando_comprovante'
                                )->count();
                                $paidCosts = $reimbursableCosts->filter(fn($c) => 
                                    in_array($c->reimbursement_stage, $legacyPaidStages)
                                )->count();
                            @endphp
                            {{-- Linha Principal --}}
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $stage === 'pago' ? 'opacity-60' : '' }} cursor-pointer"
                                @click="toggleExpand({{ $gig->id }})">
                                <td class="px-3 py-2" @click.stop>
                                    <input type="checkbox" 
                                           value="{{ $gig->id }}"
                                           x-model="selectedGigs"
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-chevron-right text-gray-400 transition-transform duration-200" :class="isExpanded({{ $gig->id }}) && 'rotate-90'"></i>
                                        {{ $gig->gig_date->isoFormat('L') }}
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="text-xs font-bold text-gray-900 dark:text-white uppercase">
                                        {{ $gig->artist->name ?? 'N/A' }}
                                    </div>
                                    <div class="text-xs italic text-gray-500 dark:text-gray-400 uppercase">
                                        {{ $gig->booker->name ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    <div>
                                        <a href="{{ route('gigs.show', $gig) }}" @click.stop class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium" title="Ver detalhes da Gig">
                                            @if($gig->contract_number)
                                                #{{ $gig->contract_number }}
                                            @else
                                                #{{ $gig->id }}
                                            @endif
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[150px]">
                                        {{ $gig->location_event_details ?: '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-xs text-right font-mono text-gray-700 dark:text-gray-200">
                                    R$ {{ number_format($gig->calculated_artist_net_payout_brl ?? 0, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-2 text-center">
                                    @if($totalCosts > 0)
                                        <span class="text-xs whitespace-nowrap" title="{{ $pendingCosts }} aguardando, {{ $paidCosts }} pago">
                                            @if($pendingCosts > 0)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                                    <i class="fas fa-clock mr-1"></i>{{ $pendingCosts }}
                                                </span>
                                            @endif
                                            @if($paidCosts > 0)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400">
                                                    <i class="fas fa-check mr-1"></i>{{ $paidCosts }}
                                                </span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $stageColors[$displayStage] }}" 
                                          title="{{ $gig->settlement?->settlement_sent_at ? 'Enviado: ' . $gig->settlement->settlement_sent_at->format('d/m/Y H:i') : '' }}">
                                        <i class="fas fa-{{ $stageIcons[$displayStage] }} mr-1"></i>{{ $stageLabels[$displayStage] }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-center" @click.stop>
                                    @if($stage === 'aguardando_conferencia')
                                        <button type="button" 
                                                @click="openSendModal({{ $gig->id }})"
                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 p-1" 
                                                title="Enviar Fechamento">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    @elseif($stage === 'fechamento_enviado')
                                        <button type="button" 
                                                @click="openReceiveDocModal({{ $gig->id }})"
                                                class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300 p-1" 
                                                title="Registrar NF/Recibo">
                                            <i class="fas fa-file-upload"></i>
                                        </button>
                                    @elseif($stage === 'documentacao_recebida')
                                        <span class="text-green-600 dark:text-green-400" title="Pronto para pagamento em massa">
                                            <i class="fas fa-dollar-sign"></i>
                                        </span>
                                    @elseif($stage === 'pago')
                                        <span class="text-gray-400" title="Fechamento concluído">
                                            <i class="fas fa-check"></i>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            
                            {{-- Linha Expansível com Detalhes --}}
                            <tr x-show="isExpanded({{ $gig->id }})"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-cloak
                                class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-750">
                                <td colspan="8" class="px-4 py-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        
                                        {{-- Card: Dados Financeiros --}}
                                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                                <i class="fas fa-calculator text-indigo-500"></i>Resumo Financeiro
                                            </h4>
                                            <div class="space-y-2 text-xs">
                                                {{-- Valor do Contrato --}}
                                                <div class="flex justify-between">
                                                    <span class="text-gray-500 dark:text-gray-400">Contrato ({{ $gig->currency }}):</span>
                                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                                        {{ $gig->currency }} {{ number_format($gig->cache_value ?? 0, 2, ',', '.') }}
                                                        @if($gig->currency !== 'BRL')
                                                            <span class="text-gray-400">(R$ {{ number_format($gig->cache_value_brl ?? 0, 2, ',', '.') }})</span>
                                                        @endif
                                                    </span>
                                                </div>
                                                {{-- Despesas Confirmadas (subtraídas do contrato para base de comissão) --}}
                                                <div class="flex justify-between">
                                                    <span class="text-gray-500 dark:text-gray-400">(-) Despesas Conf.:</span>
                                                    <span class="font-medium text-orange-500">- R$ {{ number_format($gig->total_confirmed_expenses_brl ?? 0, 2, ',', '.') }}</span>
                                                </div>
                                                {{-- Cachê Bruto (Base de Comissão) --}}
                                                <div class="flex justify-between border-t border-dashed border-gray-200 dark:border-gray-600 pt-1">
                                                    <span class="text-gray-500 dark:text-gray-400">= Cachê Bruto (base com.):</span>
                                                    <span class="font-medium text-gray-700 dark:text-gray-300">R$ {{ number_format($gig->gross_cash_brl ?? 0, 2, ',', '.') }}</span>
                                                </div>
                                                {{-- Comissão Agência --}}
                                                <div class="flex justify-between">
                                                    <span class="text-gray-500 dark:text-gray-400">(-) Com. Agência ({{ $gig->agency_commission_rate ?? 20 }}%):</span>
                                                    <span class="font-medium text-red-500">- R$ {{ number_format($gig->calculated_agency_gross_commission_brl ?? 0, 2, ',', '.') }}</span>
                                                </div>
                                                @if($gig->booker_id)
                                                {{-- Comissão Booker --}}
                                                <div class="flex justify-between">
                                                    <span class="text-gray-500 dark:text-gray-400">(-) Com. Booker ({{ $gig->booker_commission_rate ?? 0 }}%):</span>
                                                    <span class="font-medium text-red-500">- R$ {{ number_format($gig->calculated_booker_commission_brl ?? 0, 2, ',', '.') }}</span>
                                                </div>
                                                @endif
                                                {{-- Líquido Artista (antes reembolso) --}}
                                                <div class="flex justify-between border-t border-gray-200 dark:border-gray-600 pt-1">
                                                    <span class="text-gray-500 dark:text-gray-400">= Líquido Artista:</span>
                                                    <span class="font-medium text-indigo-600 dark:text-indigo-400">R$ {{ number_format($gig->calculated_artist_net_payout_brl ?? 0, 2, ',', '.') }}</span>
                                                </div>
                                                @if($gig->total_reimbursable_expenses_brl > 0)
                                                {{-- Despesas Reembolsáveis (NF Artista) --}}
                                                <div class="flex justify-between">
                                                    <span class="text-gray-500 dark:text-gray-400">(+) Reembolsáveis (NF):</span>
                                                    <span class="font-medium text-green-600 dark:text-green-400">+ R$ {{ number_format($gig->total_reimbursable_expenses_brl ?? 0, 2, ',', '.') }}</span>
                                                </div>
                                                @endif
                                                {{-- Total NF Artista --}}
                                                <div class="flex justify-between border-t-2 border-gray-300 dark:border-gray-500 pt-2 font-bold">
                                                    <span class="text-gray-700 dark:text-gray-200">TOTAL NF ARTISTA:</span>
                                                    <span class="text-green-600 dark:text-green-400">R$ {{ number_format($gig->calculated_artist_invoice_value_brl ?? 0, 2, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Card: Status do Fechamento (interativo) --}}
                                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                                <i class="fas fa-tasks text-blue-500"></i>Status do Fechamento
                                            </h4>
                                            <x-settlement-workflow-actions :gig="$gig" :stage="$stage" />
                                        </div>

                                        {{-- Card: Comprovantes de Despesas (interativo) --}}
                                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                                <i class="fas fa-receipt text-yellow-500"></i>Comprovantes de Despesas
                                            </h4>
                                            @if($totalCosts > 0)
                                                <div class="space-y-1">
                                                    @foreach($reimbursableCosts as $cost)
                                                        <x-cost-reimbursement-inline :cost="$cost" />
                                                    @endforeach
                                                </div>
                                                <div class="mt-3 pt-2 border-t border-gray-200 dark:border-gray-600">
                                                    <a href="{{ route('gigs.show', $gig) }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                                        <i class="fas fa-external-link-alt"></i>Ver todos na Gig
                                                    </a>
                                                </div>
                                            @else
                                                <p class="text-xs text-gray-400 italic">Nenhuma despesa reembolsável</p>
                                            @endif
                                        </div>
                                        
                                    </div>

                                    {{-- Datas e Informações Adicionais --}}
                                    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400">
                                        @if($gig->settlement?->settlement_sent_at)
                                            <span><i class="fas fa-paper-plane mr-1 text-blue-400"></i>Enviado: {{ $gig->settlement->settlement_sent_at->format('d/m/Y H:i') }}</span>
                                        @endif
                                        @if($gig->settlement?->documentation_received_at)
                                            <span><i class="fas fa-file-invoice mr-1 text-yellow-400"></i>NF/Recibo: {{ $gig->settlement->documentation_received_at->format('d/m/Y H:i') }}</span>
                                        @endif
                                        @if($gig->settlement?->paid_at)
                                            <span><i class="fas fa-check-circle mr-1 text-green-400"></i>Pago: {{ $gig->settlement->paid_at->format('d/m/Y H:i') }}</span>
                                        @endif
                                        @if($gig->created_at)
                                            <span><i class="fas fa-calendar mr-1 text-gray-400"></i>Criado: {{ $gig->created_at->format('d/m/Y') }}</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-search text-4xl mb-3 opacity-50"></i>
                                    <p>Nenhum fechamento encontrado com os filtros aplicados.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginação --}}
            @if ($gigs->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    {{ $gigs->links() }}
                </div>
            @endif
        </div>

        {{-- Modal: Enviar Fechamento --}}
        <div x-show="showSendModal" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto" 
             aria-labelledby="modal-title" 
             role="dialog" 
             aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showSendModal" 
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                     @click="showSendModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showSendModal" 
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <form :action="`{{ url('/artists-settlements') }}/${sendGigId}/send`" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-paper-plane text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Enviar Fechamento
                                </h3>
                                <div class="mt-4">
                                    <label for="send_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Notas (opcional)
                                    </label>
                                    <textarea name="communication_notes" 
                                              id="send_notes" 
                                              rows="3" 
                                              placeholder="Registro de comunicação com o artista..."
                                              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                                <i class="fas fa-paper-plane mr-2"></i>Enviar
                            </button>
                            <button type="button" @click="showSendModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal: Registrar Documentação --}}
        <div x-show="showReceiveDocModal" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto" 
             aria-labelledby="modal-title-doc" 
             role="dialog" 
             aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showReceiveDocModal" 
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                     @click="showReceiveDocModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showReceiveDocModal" 
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <form :action="`{{ url('/artists-settlements') }}/${receiveDocGigId}/receive-document`" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-file-invoice text-yellow-600 dark:text-yellow-400"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title-doc">
                                    Registrar NF/Recibo
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label for="doc_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Tipo de Documento *
                                        </label>
                                        <select name="documentation_type" id="doc_type" required
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                                            <option value="">Selecione...</option>
                                            <option value="nf">Nota Fiscal</option>
                                            <option value="recibo">Recibo</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="doc_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Número do Documento (opcional)
                                        </label>
                                        <input type="text" name="documentation_number" id="doc_number" 
                                               placeholder="Ex: NF-e 123456"
                                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                                    </div>
                                    <div>
                                        <label for="doc_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Arquivo (opcional)
                                        </label>
                                        <input type="file" name="documentation_file" id="doc_file" 
                                               accept=".pdf,.jpg,.jpeg,.png"
                                               class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-gray-700 dark:file:text-gray-200">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PDF, JPG ou PNG (máx. 5MB)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:w-auto sm:text-sm">
                                <i class="fas fa-check mr-2"></i>Registrar
                            </button>
                            <button type="button" @click="showReceiveDocModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function settlementsManager() {
            return {
                selectedGigs: [],
                expandedGigs: [],
                paymentDate: '{{ now()->format('Y-m-d') }}',
                today: '{{ now()->format('Y-m-d') }}',
                allGigIds: @json($gigs->pluck('id')->toArray()),
                showSendModal: false,
                sendGigId: null,
                showReceiveDocModal: false,
                receiveDocGigId: null,

                toggleExpand(gigId) {
                    const index = this.expandedGigs.indexOf(gigId);
                    if (index === -1) {
                        this.expandedGigs.push(gigId);
                    } else {
                        this.expandedGigs.splice(index, 1);
                    }
                },

                isExpanded(gigId) {
                    return this.expandedGigs.includes(gigId);
                },

                toggleSelectAll(checked) {
                    if (checked) {
                        this.selectedGigs = [...this.allGigIds];
                    } else {
                        this.selectedGigs = [];
                    }
                },

                areAllSelected() {
                    return this.allGigIds.length > 0 && this.selectedGigs.length === this.allGigIds.length;
                },

                formatDate(dateString) {
                    const date = new Date(dateString + 'T00:00:00');
                    return date.toLocaleDateString('pt-BR');
                },

                openSendModal(gigId) {
                    this.sendGigId = gigId;
                    this.showSendModal = true;
                },

                openReceiveDocModal(gigId) {
                    this.receiveDocGigId = gigId;
                    this.showReceiveDocModal = true;
                },

                submitBatchAction(actionType) {
                    if (this.selectedGigs.length === 0) {
                        Swal.fire('Atenção!', 'Nenhum fechamento selecionado.', 'warning');
                        return;
                    }

                    let form, confirmationText;

                    if (actionType === 'pay') {
                        if (!this.paymentDate) {
                            Swal.fire('Atenção!', 'Por favor, selecione a data do pagamento.', 'warning');
                            return;
                        }
                        form = document.getElementById('batchPaymentForm');
                        confirmationText = `Confirmar pagamento de ${this.selectedGigs.length} fechamento(s) com data ${this.formatDate(this.paymentDate)}?`;
                    } else if (actionType === 'send') {
                        form = document.getElementById('batchSendForm');
                        confirmationText = `Confirmar envio de ${this.selectedGigs.length} fechamento(s)?`;
                    } else {
                        form = document.getElementById('batchUnsettleForm');
                        confirmationText = `Confirmar a reversão de ${this.selectedGigs.length} fechamento(s)?`;
                    }

                    Swal.fire({
                        title: 'Confirmar Ação',
                        text: confirmationText,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sim, confirmar!',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Clear existing hidden inputs
                            form.querySelectorAll('input[name^="gig_ids"]').forEach(el => el.remove());
                            form.querySelectorAll('input[name="payment_date"]').forEach(el => el.remove());

                            // Add selected gigs
                            this.selectedGigs.forEach(gigId => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'gig_ids[]';
                                input.value = gigId;
                                form.appendChild(input);
                            });

                            // Add payment date for pay action
                            if (actionType === 'pay') {
                                const dateInput = document.createElement('input');
                                dateInput.type = 'hidden';
                                dateInput.name = 'payment_date';
                                dateInput.value = this.paymentDate;
                                form.appendChild(dateInput);
                            }

                            form.submit();
                        }
                    });
                }
            };
        }

        // Exibir mensagens de sessão via SweetAlert
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: @json(session('success')),
                timer: 4000,
                timerProgressBar: true
            });
        @endif
        @if (session('warning'))
            Swal.fire({
                icon: 'warning',
                title: 'Atenção!',
                text: @json(session('warning')),
                timer: 5000,
                timerProgressBar: true
            });
        @endif
        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: @json(session('error'))
            });
        @endif
    </script>
    @endpush
</x-app-layout>
