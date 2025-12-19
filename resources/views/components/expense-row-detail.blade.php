@props(['cost'])

@php
    $gig = $cost->gig;
    $effectiveStage = $cost->effective_reimbursement_stage ?? 'aguardando_comprovante';
    
    // Preparar dados para JavaScript
    $costData = [
        'id' => $cost->id,
        'gig_id' => $cost->gig_id,
        'description' => $cost->description,
        'value' => $cost->value,
        'currency' => $cost->currency,
        'is_confirmed' => $cost->is_confirmed,
        'is_invoice' => $cost->is_invoice,
        'effective_stage' => $effectiveStage,
        'expense_date' => $cost->expense_date?->format('Y-m-d'),
        'cost_center_name' => $cost->costCenter->name ?? 'Não definido',
        'has_proof' => !empty($cost->reimbursement_notes) || !empty($cost->reimbursement_proof_file),
        'has_file' => !empty($cost->reimbursement_proof_file),
        'proof_file_url' => $cost->reimbursement_proof_file ? Storage::url($cost->reimbursement_proof_file) : null,
        'proof_type' => $cost->reimbursement_proof_type,
        'proof_number' => $cost->reimbursement_notes,
    ];
@endphp

{{-- Card de Detalhes Expandido --}}
<div x-data="expenseRowDetail({{ json_encode($costData) }})" 
     class="p-4 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-200 dark:border-gray-600">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        
        {{-- Card 1: Informações do Evento --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 flex items-center">
                <i class="fas fa-calendar-alt mr-2 text-primary-500"></i>Evento
            </h5>
            <div class="space-y-2">
                <p class="font-medium text-gray-800 dark:text-white">
                    <a href="{{ route('gigs.show', $gig) }}" class="hover:text-primary-600 dark:hover:text-primary-400">
                        Gig #{{ $gig->id }}
                    </a>
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $gig->location_event_details ?? 'Sem detalhes' }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <i class="far fa-calendar mr-1"></i>{{ $gig->gig_date?->isoFormat('L') ?? '-' }}
                </p>
                @if($gig->artist)
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-user mr-1"></i>{{ $gig->artist->name }}
                    </p>
                @endif
            </div>
        </div>
        
        {{-- Card 2: Dados da Despesa --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 flex items-center">
                <i class="fas fa-receipt mr-2 text-primary-500"></i>Despesa
            </h5>
            <div class="space-y-2">
                <p class="font-medium text-gray-800 dark:text-white" x-text="cost.description || 'Sem descrição'"></p>
                <p class="text-lg font-bold text-primary-600 dark:text-primary-400">
                    <span x-text="cost.currency + ' ' + formatCurrency(cost.value)"></span>
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Centro: <span x-text="cost.cost_center_name"></span>
                </p>
            </div>
        </div>
        
        {{-- Card 3: Confirmação + NF --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700"
             :class="{ 'opacity-60': isPaid() }">
            <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 flex items-center">
                <i class="fas fa-check-square mr-2 text-primary-500"></i>Confirmação & NF
                <template x-if="isPaid()">
                    <span class="ml-2 text-xs text-yellow-600 dark:text-yellow-400">
                        <i class="fas fa-lock"></i> Bloqueado
                    </span>
                </template>
            </h5>
            <div class="space-y-3">
                {{-- Status de Confirmação --}}
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                          :class="cost.is_confirmed 
                              ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' 
                              : 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'"
                          x-text="cost.is_confirmed ? 'Confirmado' : 'Pendente'">
                    </span>
                </div>
                
                {{-- Botão Confirmar/Reverter --}}
                <button @click="handleConfirmationToggle()" 
                        :disabled="loading || isPaid()"
                        class="w-full px-3 py-2 text-xs rounded-md flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="cost.is_confirmed 
                            ? 'bg-yellow-100 hover:bg-yellow-200 text-yellow-800 dark:bg-yellow-900/50 dark:hover:bg-yellow-900 dark:text-yellow-200' 
                            : 'bg-green-500 hover:bg-green-600 text-white'">
                    <i class="fas" :class="cost.is_confirmed ? 'fa-undo-alt' : 'fa-check-circle'"></i>
                    <span x-text="loading ? 'Salvando...' : (cost.is_confirmed ? 'Reverter Confirmação' : 'Confirmar Despesa')"></span>
                </button>
                
                {{-- Checkbox NF (desabilitado se pago ou não confirmado) --}}
                <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <input type="checkbox" 
                           :checked="cost.is_invoice" 
                           @change="handleInvoiceToggle()"
                           :disabled="!cost.is_confirmed || loading || isPaid()"
                           class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <label class="text-sm" :class="(!cost.is_confirmed || isPaid()) ? 'text-gray-400' : 'text-gray-700 dark:text-gray-300'">
                        Marcar para NF (Reembolsável)
                    </label>
                </div>
                
                {{-- Mensagens de ajuda --}}
                <template x-if="isPaid()">
                    <p class="text-xs text-yellow-600 dark:text-yellow-400 italic">
                        * Reverta o pagamento primeiro para alterar
                    </p>
                </template>
                <template x-if="!cost.is_confirmed && !isPaid()">
                    <p class="text-xs text-gray-500 italic">
                        * Confirme a despesa primeiro para marcar NF
                    </p>
                </template>
            </div>
        </div>
        
        {{-- Card 4: Reembolso --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 flex items-center">
                <i class="fas fa-exchange-alt mr-2 text-primary-500"></i>Reembolso
            </h5>
            
            <template x-if="cost.is_invoice">
                <div class="space-y-3">
                    {{-- Status do Reembolso --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                              :class="(cost.effective_stage === 'pago' || cost.effective_stage === 'anexo_pendente') 
                                  ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' 
                                  : 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'">
                            <span x-text="(cost.effective_stage === 'pago' || cost.effective_stage === 'anexo_pendente') ? 'Pago' : 'Aguardando'"></span>
                        </span>
                    </div>
                    
                    {{-- Botão Pagar/Reverter --}}
                    <template x-if="cost.effective_stage === 'aguardando_comprovante'">
                        <button @click="handlePayReimbursement()" 
                                :disabled="loading"
                                class="w-full px-3 py-2 text-xs rounded-md bg-green-500 hover:bg-green-600 text-white flex items-center justify-center gap-2 disabled:opacity-50">
                            <i class="fas fa-money-bill-wave"></i>
                            <span x-text="loading ? 'Salvando...' : 'Marcar como Pago'"></span>
                        </button>
                    </template>
                    <template x-if="cost.effective_stage === 'pago' || cost.effective_stage === 'anexo_pendente'">
                        <div class="space-y-3">
                            {{-- Badge Pago com número --}}
                            <div class="flex flex-col items-start gap-1 text-green-600 dark:text-green-400 py-1">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-check-circle"></i>
                                    <span class="font-medium text-sm">Reembolsado ✓</span>
                                </div>
                                
                                {{-- Aviso de Anexo Pendente --}}
                                <template x-if="cost.effective_stage === 'anexo_pendente' && !cost.proof_number">
                                    <div class="flex items-center gap-1 text-orange-600 dark:text-orange-400 text-xs font-medium px-2 py-0.5 bg-orange-50 dark:bg-orange-900/20 rounded border border-orange-100 dark:border-orange-800/30">
                                        <i class="fas fa-paperclip"></i> Anexo Pendente
                                    </div>
                                </template>

                                {{-- Número do documento formatado: Nº [Tipo]: número --}}
                                <template x-if="cost.proof_number">
                                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-5">
                                        <span class="font-medium" x-text="getProofTypeLabel()"></span>
                                        <span x-text="cost.proof_number"></span>
                                    </span>
                                </template>
                            </div>
                            
                            {{-- Seção interativa de comprovante --}}
                            <div class="border-t border-gray-200 dark:border-gray-600 pt-3 space-y-2">
                                <p class="text-xxs text-gray-500 dark:text-gray-400 font-medium uppercase flex items-center gap-1">
                                    <i class="fas fa-paperclip"></i> Comprovante
                                </p>
                                
                                {{-- Campo número do documento --}}
                                <input type="text" 
                                       x-model="editProofNumber"
                                       placeholder="Nº do documento (ex: NF-12345)"
                                       class="w-full text-xs px-2 py-1.5 rounded border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                                
                                {{-- Área de arquivo: dinâmico com Alpine --}}
                                <template x-if="cost.has_file">
                                    {{-- Arquivo JÁ ANEXADO: mostrar link + botão remover --}}
                                    <div class="flex items-center justify-between gap-2 p-2 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800">
                                        <a :href="cost.proof_file_url" 
                                           target="_blank"
                                           class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-1 hover:underline">
                                            <i class="fas fa-file-alt"></i> Ver comprovante
                                        </a>
                                        <button @click="removeProofFile()" 
                                                :disabled="loading"
                                                class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 flex items-center gap-1 disabled:opacity-50"
                                                title="Remover arquivo">
                                            <i class="fas fa-trash-alt"></i> Remover
                                        </button>
                                    </div>
                                </template>
                                
                                <template x-if="!cost.has_file">
                                    {{-- SEM arquivo: mostrar área de upload --}}
                                    <div class="flex items-center gap-2">
                                        <label class="flex-1 cursor-pointer">
                                            <input type="file" 
                                                   x-ref="proofFileInput"
                                                   @change="handleFileSelect($event)"
                                                   accept=".pdf,.jpg,.jpeg,.png"
                                                   class="hidden">
                                            <div class="w-full text-xs px-2 py-1.5 rounded border border-dashed border-gray-300 dark:border-gray-600 text-center text-gray-500 hover:border-primary-500 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                                                 :class="selectedFileName ? 'border-green-500 text-green-600 bg-green-50 dark:bg-green-900/20' : ''">
                                                <template x-if="!selectedFileName">
                                                    <span><i class="fas fa-cloud-upload-alt mr-1"></i> Clique para anexar arquivo</span>
                                                </template>
                                                <template x-if="selectedFileName">
                                                    <span><i class="fas fa-check mr-1"></i> <span x-text="selectedFileName"></span></span>
                                                </template>
                                            </div>
                                        </label>
                                        {{-- Botão limpar seleção --}}
                                        <template x-if="selectedFileName">
                                            <button @click="clearFileSelection()" 
                                                    class="text-xs text-gray-400 hover:text-red-500 p-1"
                                                    title="Limpar seleção">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                                
                                {{-- Botões de ação --}}
                                <template x-if="!cost.has_file">
                                    <button @click="saveProofData()" 
                                            :disabled="loading || (!editProofNumber && !selectedFileName)"
                                            class="w-full px-2 py-1.5 text-xs rounded bg-primary-500 hover:bg-primary-600 text-white flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                        <i class="fas fa-save"></i>
                                        <span x-text="loading ? 'Salvando...' : 'Salvar Comprovante'"></span>
                                    </button>
                                </template>
                                
                                <template x-if="cost.has_file">
                                    {{-- Se já tem arquivo, mostrar botão apenas para atualizar número --}}
                                    <button @click="updateProofNumber()" 
                                            :disabled="loading || editProofNumber === cost.proof_number"
                                            class="w-full px-2 py-1.5 text-xs rounded bg-gray-500 hover:bg-gray-600 text-white flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                        <i class="fas fa-edit"></i>
                                        <span x-text="loading ? 'Salvando...' : 'Atualizar Número'"></span>
                                    </button>
                                </template>
                            </div>
                            
                            {{-- Botão Reverter --}}
                            <button @click="handleRevertPayment()" 
                                    :disabled="loading"
                                    class="w-full px-3 py-1.5 text-xs rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-1 disabled:opacity-50">
                                <i class="fas fa-undo"></i>
                                <span x-text="loading ? 'Salvando...' : 'Reverter Pagamento'"></span>
                            </button>
                        </div>
                    </template>
                </div>
            </template>
            
            <template x-if="!cost.is_invoice">
                <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                    <p class="text-sm">Marque "NF" para habilitar reembolso</p>
                </div>
            </template>
        </div>
    </div>
</div>
