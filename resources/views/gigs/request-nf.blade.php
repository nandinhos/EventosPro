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
            @php
                $stageColors = [
                    'aguardando_conferencia' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                    'fechamento_enviado' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                    'documentacao_recebida' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                    'pago' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                ];
                $stageIcons = [
                    'aguardando_conferencia' => 'clipboard-check',
                    'fechamento_enviado' => 'paper-plane',
                    'documentacao_recebida' => 'file-invoice',
                    'pago' => 'check-circle',
                ];
                $stageLabels = [
                    'aguardando_conferencia' => 'Aguardando Conferência',
                    'fechamento_enviado' => 'Ag. NF/Recibo',
                    'documentacao_recebida' => 'Pronto p/ Pagar',
                    'pago' => 'Pago',
                ];
            @endphp
            <span class="px-3 py-1.5 text-sm font-semibold rounded-full {{ $stageColors[$settlementStage] }}">
                <i class="fas fa-{{ $stageIcons[$settlementStage] }} mr-1"></i>{{ $stageLabels[$settlementStage] }}
            </span>
            <a href="{{ route('gigs.show', $backUrlParams) }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Voltar
            </a>
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

    <div x-data="settlementWorkflow()">
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
                
                {{-- Timeline Visual --}}
                <div class="flex items-center justify-between text-xs mb-4">
                    <div class="flex flex-col items-center {{ $settlementStage !== 'aguardando_conferencia' ? 'text-green-600' : 'text-gray-500' }}">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $settlementStage !== 'aguardando_conferencia' ? 'bg-green-100 dark:bg-green-900' : 'bg-gray-200 dark:bg-gray-700' }}">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <span class="mt-1 text-center">Conferir</span>
                    </div>
                    <div class="flex-1 h-0.5 {{ in_array($settlementStage, ['fechamento_enviado', 'documentacao_recebida', 'pago']) ? 'bg-green-400' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                    <div class="flex flex-col items-center {{ in_array($settlementStage, ['fechamento_enviado', 'documentacao_recebida', 'pago']) ? 'text-green-600' : 'text-gray-500' }}">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center {{ in_array($settlementStage, ['fechamento_enviado', 'documentacao_recebida', 'pago']) ? 'bg-green-100 dark:bg-green-900' : 'bg-gray-200 dark:bg-gray-700' }}">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <span class="mt-1 text-center">Enviado</span>
                    </div>
                    <div class="flex-1 h-0.5 {{ in_array($settlementStage, ['documentacao_recebida', 'pago']) ? 'bg-green-400' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                    <div class="flex flex-col items-center {{ in_array($settlementStage, ['documentacao_recebida', 'pago']) ? 'text-green-600' : 'text-gray-500' }}">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center {{ in_array($settlementStage, ['documentacao_recebida', 'pago']) ? 'bg-green-100 dark:bg-green-900' : 'bg-gray-200 dark:bg-gray-700' }}">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <span class="mt-1 text-center">NF Recebida</span>
                    </div>
                    <div class="flex-1 h-0.5 {{ $settlementStage === 'pago' ? 'bg-green-400' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                    <div class="flex flex-col items-center {{ $settlementStage === 'pago' ? 'text-green-600' : 'text-gray-500' }}">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $settlementStage === 'pago' ? 'bg-green-100 dark:bg-green-900' : 'bg-gray-200 dark:bg-gray-700' }}">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <span class="mt-1 text-center">Pago</span>
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

            {{-- Botões de ação --}}
            <div class="flex flex-wrap gap-2 justify-center">
                {{-- Capturar Snapshot --}}
                <button type="button" onclick="captureSnapshot()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm">
                    <i class="fas fa-camera mr-2"></i> Capturar Snapshot
                </button>

                @if($settlementStage === 'aguardando_conferencia')
                    {{-- Enviar Fechamento --}}
                    <button type="button" @click="showSendModal = true" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm">
                        <i class="fas fa-paper-plane mr-2"></i> Enviar Fechamento
                    </button>
                @elseif($settlementStage === 'fechamento_enviado')
                    {{-- Registrar Documentação --}}
                    <button type="button" @click="showDocModal = true" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm">
                        <i class="fas fa-file-upload mr-2"></i> Registrar NF/Recibo
                    </button>
                    {{-- Botão Reverter --}}
                    <button type="button" @click="showRevertModal = true" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-md text-sm">
                        <i class="fas fa-undo mr-2"></i> Reverter Envio
                    </button>
                @elseif($settlementStage === 'documentacao_recebida')
                    {{-- Registrar Pagamento --}}
                    <button type="button" @click="showPayModal = true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">
                        <i class="fas fa-dollar-sign mr-2"></i> Registrar Pagamento
                    </button>
                    {{-- Botão Reverter --}}
                    <button type="button" @click="showRevertModal = true" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-md text-sm">
                        <i class="fas fa-undo mr-2"></i> Reverter Documentação
                    </button>
                @elseif($settlementStage === 'pago')
                    <span class="bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-check-circle mr-2"></i> Fechamento Concluído
                    </span>
                    {{-- Botão Reverter --}}
                    <button type="button" @click="showRevertModal = true" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-md text-sm">
                        <i class="fas fa-undo mr-2"></i> Reverter Pagamento
                    </button>
                @endif
            </div>
        </div>

        {{-- Modal: Enviar Fechamento --}}
        <div x-show="showSendModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showSendModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showSendModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="showSendModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <form action="{{ route('artists.settlements.send', $gig) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="redirect_to" value="gig">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-paper-plane text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Enviar Fechamento</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Confirme o envio do demonstrativo ao artista.</p>
                                <div class="mt-4">
                                    <label for="communication_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas (opcional)</label>
                                    <textarea name="communication_notes" id="communication_notes" rows="3" placeholder="Registro de comunicação com o artista..." class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:w-auto sm:text-sm">
                                <i class="fas fa-paper-plane mr-2"></i>Enviar
                            </button>
                            <button type="button" @click="showSendModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal: Registrar Documentação --}}
        <div x-show="showDocModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showDocModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDocModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="showDocModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <form action="{{ route('artists.settlements.receiveDocument', $gig) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="redirect_to" value="gig">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-file-invoice text-yellow-600 dark:text-yellow-400"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Registrar NF/Recibo</h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label for="documentation_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Documento *</label>
                                        <select name="documentation_type" id="documentation_type" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                                            <option value="">Selecione...</option>
                                            <option value="nf">Nota Fiscal</option>
                                            <option value="recibo">Recibo</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="documentation_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número do Documento (opcional)</label>
                                        <input type="text" name="documentation_number" id="documentation_number" placeholder="Ex: NF-e 123456" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                                    </div>
                                    <div>
                                        <label for="documentation_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Arquivo (opcional)</label>
                                        <input type="file" name="documentation_file" id="documentation_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-gray-700 dark:file:text-gray-200">
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PDF, JPG ou PNG (máx. 5MB)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none sm:w-auto sm:text-sm">
                                <i class="fas fa-check mr-2"></i>Registrar
                            </button>
                            <button type="button" @click="showDocModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal: Registrar Pagamento --}}
        <div x-show="showPayModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showPayModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showPayModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="showPayModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <form action="{{ route('artists.settlements.settle', $gig) }}" method="POST">
                        @csrf
                        <input type="hidden" name="redirect_to" value="gig">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-dollar-sign text-green-600 dark:text-green-400"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Registrar Pagamento</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Confirme o pagamento ao artista.</p>
                                <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Valor a Pagar:</span>
                                        <span class="font-bold text-lg text-primary-600 dark:text-primary-400">R$ {{ number_format($finalArtistInvoiceValueBrl, 2, ',', '.') }}</span>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label for="payment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data do Pagamento *</label>
                                    <input type="date" name="payment_date" id="payment_date" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:w-auto sm:text-sm">
                                <i class="fas fa-check-circle mr-2"></i>Confirmar Pagamento
                            </button>
                            <button type="button" @click="showPayModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal: Confirmar Reversão --}}
        <div x-show="showRevertModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showRevertModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showRevertModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="showRevertModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <form action="{{ route('artists.settlements.revert', $gig) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="redirect_to" value="gig">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Confirmar Reversão</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                    @if($settlementStage === 'fechamento_enviado')
                                        Deseja reverter o envio do fechamento? O estágio voltará para "Aguardando Conferência".
                                    @elseif($settlementStage === 'documentacao_recebida')
                                        Deseja reverter o registro de documentação? Os dados da NF/Recibo serão removidos e o estágio voltará para "Ag. NF/Recibo".
                                    @elseif($settlementStage === 'pago')
                                        Deseja reverter o pagamento? O estágio voltará para "Pronto p/ Pagar" e o status de pagamento da gig será alterado para "Pendente".
                                    @else
                                        Deseja reverter para o estágio anterior?
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none sm:w-auto sm:text-sm">
                                <i class="fas fa-undo mr-2"></i>Reverter
                            </button>
                            <button type="button" @click="showRevertModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <script>
        function settlementWorkflow() {
            return {
                showSendModal: false,
                showDocModal: false,
                showPayModal: false,
                showRevertModal: false
            };
        }

        function captureSnapshot() {
            const element = document.querySelector('.bg-white.dark\\:bg-gray-800.rounded-xl');
            
            html2canvas(element, {
                scale: 2,
                useCORS: true,
                backgroundColor: null
            }).then(canvas => {
                const image = canvas.toDataURL('image/png');
                const link = document.createElement('a');
                link.download = 'demonstrativo-nf-gig-{{ $gig->id }}.png';
                link.href = image;
                link.click();
            });
        }
    </script>
</x-app-layout>