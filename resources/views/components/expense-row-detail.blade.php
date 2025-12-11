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

@pushOnce('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('expenseRowDetail', (initialCost) => ({
        cost: initialCost,
        loading: false,
        editProofNumber: initialCost.proof_number || '',
        selectedFileName: null,
        selectedFile: null,
        
        formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
        },
        
        isPaid() {
            return this.cost.is_invoice && (this.cost.effective_stage === 'pago' || this.cost.effective_stage === 'anexo_pendente');
        },
        
        getProofTypeLabel() {
            const types = {
                'nf': 'Nº NF:',
                'recibo': 'Nº Recibo:',
                'transferencia': 'Nº Transf.:',
                'outro': 'Nº Doc.:'
            };
            return types[this.cost.proof_type] || 'Nº Doc.:';
        },
        
        dispatchUpdate() {
            window.dispatchEvent(new CustomEvent('cost-updated', {
                detail: {
                    id: this.cost.id,
                    value: this.cost.value,
                    is_confirmed: this.cost.is_confirmed,
                    is_invoice: this.cost.is_invoice,
                    effective_stage: this.cost.effective_stage,
                    has_proof: this.cost.has_proof,
                    proof_number: this.cost.proof_number
                }
            }));
        },
        
        reloadWithTab() {
            // Preserva a aba ativa ao recarregar
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'expenses');
            window.location.href = url.toString();
        },
        
        showSuccess(message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Sucesso!', text: message, timer: 2000, showConfirmButton: false });
            }
        },
        
        showError(message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Erro', text: message });
            } else {
                alert(message);
            }
        },
        
        async showConfirm(title, text, confirmText = 'Sim') {
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: title,
                    text: text,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: confirmText,
                    cancelButtonText: 'Cancelar'
                });
                return result.isConfirmed;
            }
            return confirm(text);
        },
        
        async handleConfirmationToggle() {
            if (this.isPaid()) {
                this.showError('Reverta o pagamento primeiro para alterar a confirmação.');
                return;
            }
            
            if (this.cost.is_confirmed) {
                // Reverter confirmação
                if (this.cost.is_invoice) {
                    const confirmed = await this.showConfirm(
                        'Reverter Confirmação',
                        'Esta despesa está marcada como NF. Deseja desmarcar o NF e reverter a confirmação?',
                        'Sim, reverter tudo'
                    );
                    if (!confirmed) return;
                }
                await this.toggleConfirmation(false);
            } else {
                // Confirmar - precisa informar a data
                await this.confirmWithDate();
            }
        },
        
        async confirmWithDate() {
            if (typeof Swal === 'undefined') {
                this.showError('SweetAlert não disponível');
                return;
            }
            
            const { value: date } = await Swal.fire({
                title: 'Confirmar Despesa',
                html: `
                    <label class="block text-sm text-left mb-2">Data de confirmação:</label>
                    <input type="date" id="confirm-date" class="swal2-input" 
                           value="${new Date().toISOString().split('T')[0]}"
                           max="${new Date().toISOString().split('T')[0]}">
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const dateInput = document.getElementById('confirm-date');
                    if (!dateInput.value) {
                        Swal.showValidationMessage('Informe a data de confirmação');
                        return false;
                    }
                    return dateInput.value;
                }
            });
            
            if (date) {
                await this.toggleConfirmation(true, date);
            }
        },
        
        async toggleConfirmation(confirm, confirmDate = null) {
            this.loading = true;
            const url = confirm 
                ? `/gigs/${this.cost.gig_id}/costs/${this.cost.id}/confirm`
                : `/gigs/${this.cost.gig_id}/costs/${this.cost.id}/unconfirm`;
            
            const body = confirm ? { confirmed_at_date: confirmDate } : {};
            
            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                
                if (response.ok) {
                    this.cost.is_confirmed = confirm;
                    if (!confirm) {
                        this.cost.is_invoice = false;
                        this.cost.effective_stage = 'aguardando_comprovante';
                    }
                    this.dispatchUpdate();
                    this.showSuccess(confirm ? 'Despesa confirmada!' : 'Confirmação revertida!');
                } else {
                    const data = await response.json();
                    this.showError(data.message || 'Erro ao atualizar confirmação');
                }
            } catch (e) {
                console.error('Erro:', e);
                this.showError('Erro ao atualizar confirmação');
            } finally {
                this.loading = false;
            }
        },
        
        async handleInvoiceToggle() {
            if (this.isPaid()) {
                this.showError('Reverta o pagamento primeiro para alterar o NF.');
                return;
            }
            
            if (this.cost.is_invoice) {
                // Desmarcar NF
                const confirmed = await this.showConfirm(
                    'Remover NF',
                    'Deseja remover a marcação de NF desta despesa?',
                    'Sim, remover'
                );
                if (!confirmed) return;
            }
            
            await this.toggleInvoice();
        },
        
        async toggleInvoice() {
            this.loading = true;
            try {
                const response = await fetch(`/gigs/${this.cost.gig_id}/costs/${this.cost.id}/toggle-invoice`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    this.cost.is_invoice = !this.cost.is_invoice;
                    if (!this.cost.is_invoice) {
                        this.cost.effective_stage = 'aguardando_comprovante';
                    }
                    this.dispatchUpdate();
                    this.showSuccess(this.cost.is_invoice ? 'NF marcada!' : 'NF desmarcada!');
                } else {
                    this.showError('Erro ao atualizar NF');
                }
            } catch (e) {
                console.error('Erro:', e);
                this.showError('Erro ao atualizar NF');
            } finally {
                this.loading = false;
            }
        },
        
        async handlePayReimbursement() {
            // Modal com campos opcionais para pagamento individual
            const { value: formData } = await Swal.fire({
                title: 'Registrar Pagamento',
                html: `
                    <div class="text-left text-sm space-y-3">
                        <p class="text-gray-600 mb-4">Confirma o pagamento/reembolso desta despesa?</p>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Tipo de Comprovante (opcional)</label>
                            <select id="proof-type" class="swal2-input mt-0 w-full text-sm" style="font-size: 14px; padding: 8px;">
                                <option value="">-- Selecione --</option>
                                <option value="nf">Nota Fiscal</option>
                                <option value="recibo">Recibo</option>
                                <option value="transferencia">Comprovante de Transferência</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Número do Documento (opcional)</label>
                            <input type="text" id="proof-number" class="swal2-input mt-0 w-full" style="font-size: 14px;" placeholder="Ex: NF-12345">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Arquivo do Comprovante (opcional)</label>
                            <input type="file" id="proof-file" accept=".pdf,.jpg,.jpeg,.png" 
                                   class="w-full text-sm text-gray-500 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 p-2">
                            <p class="text-xs text-gray-400 mt-1">PDF, JPG ou PNG (máx. 5MB)</p>
                        </div>
                        
                        <p class="text-xs text-gray-500 italic mt-2">
                            * Todos os campos são opcionais. Você pode anexar depois.
                        </p>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonColor: '#10B981',
                confirmButtonText: '<i class="fas fa-money-bill-wave mr-1"></i> Pagar',
                cancelButtonText: 'Cancelar',
                width: '420px',
                preConfirm: () => {
                    const fileInput = document.getElementById('proof-file');
                    return {
                        proof_type: document.getElementById('proof-type').value || null,
                        proof_number: document.getElementById('proof-number').value || null,
                        proof_file: fileInput.files[0] || null
                    };
                }
            });
            
            if (formData) {
                await this.updateReimbursementStageWithFile('pago', formData.proof_type, formData.proof_number, formData.proof_file);
            }
        },
        
        async handleRevertPayment() {
            const confirmed = await this.showConfirm(
                'Reverter Pagamento',
                'Deseja reverter o pagamento desta despesa?',
                'Sim, reverter'
            );
            if (!confirmed) return;
            
            await this.updateReimbursementStage('aguardando_comprovante');
        },
        
        async updateReimbursementStage(newStage, proofType = null, proofNumber = null) {
            this.loading = true;
            try {
                const body = { stage: newStage };
                if (proofType) body.proof_type = proofType;
                if (proofNumber) body.proof_number = proofNumber;
                
                const response = await fetch(`/api/costs/${this.cost.id}/reimbursement-stage`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                
                if (response.ok) {
                    const data = await response.json();
                    const updatedCost = data.cost;

                    // Atualiza dados reativamente com a resposta do backend
                    this.cost.effective_stage = updatedCost.reimbursement_stage; // Backend retorna o estágio correto
                    this.cost.has_proof = !!updatedCost.reimbursement_notes || !!updatedCost.reimbursement_proof_file;
                    this.cost.proof_number = updatedCost.reimbursement_notes;
                    this.cost.proof_type = updatedCost.reimbursement_proof_type;
                    
                    if (!this.cost.has_proof && newStage === 'aguardando_comprovante') {
                         this.cost.proof_file_url = null;
                         this.cost.has_file = false;
                    }

                    this.dispatchUpdate();
                    this.showSuccess(this.cost.effective_stage === 'pago' ? 'Pagamento registrado!' : 'Status atualizado!');
                } else {
                    this.showError('Erro ao atualizar reembolso');
                }
            } catch (e) {
                console.error('Erro:', e);
                this.showError('Erro ao atualizar reembolso');
            } finally {
                this.loading = false;
            }
        },
        
        async updateReimbursementStageWithFile(newStage, proofType = null, proofNumber = null, proofFile = null) {
            this.loading = true;
            try {
                const formData = new FormData();
                formData.append('stage', newStage);
                formData.append('_method', 'PATCH');
                
                if (proofType) formData.append('proof_type', proofType);
                if (proofNumber) formData.append('proof_number', proofNumber);
                if (proofFile) formData.append('proof_file', proofFile);
                
                const response = await fetch(`/api/costs/${this.cost.id}/reimbursement-stage`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                if (response.ok) {
                    const data = await response.json();
                    const updatedCost = data.cost;
                    
                    // Atualiza dados reativamente com a resposta do backend
                    this.cost.effective_stage = updatedCost.reimbursement_stage; // Pode ser 'pago' ou 'anexo_pendente'
                    this.cost.has_proof = !!updatedCost.reimbursement_notes || !!updatedCost.reimbursement_proof_file;
                    this.cost.proof_number = updatedCost.reimbursement_notes;
                    this.cost.proof_type = updatedCost.reimbursement_proof_type;
                    this.editProofNumber = updatedCost.reimbursement_notes || '';
                    
                    // Atualiza dados do arquivo se foi anexado
                    if (updatedCost.reimbursement_proof_file) {
                        this.cost.has_file = true;
                        this.cost.proof_file_url = `/storage/${updatedCost.reimbursement_proof_file}`;
                    }
                    
                    this.dispatchUpdate();
                    this.showSuccess('Pagamento registrado!');
                } else {
                    const data = await response.json();
                    this.showError(data.message || 'Erro ao registrar pagamento');
                }
            } catch (e) {
                console.error('Erro:', e);
                this.showError('Erro ao registrar pagamento');
            } finally {
                this.loading = false;
            }
        },
        
        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.selectedFile = file;
                this.selectedFileName = file.name.length > 25 ? file.name.substring(0, 22) + '...' : file.name;
            }
        },
        
        async saveProofData() {
            if (!this.editProofNumber && !this.selectedFile) {
                this.showError('Informe o número do documento ou anexe um arquivo.');
                return;
            }
            
            this.loading = true;
            try {
                const formData = new FormData();
                formData.append('stage', this.cost.effective_stage);
                formData.append('_method', 'PATCH');
                
                if (this.editProofNumber) {
                    formData.append('proof_number', this.editProofNumber);
                }
                
                if (this.selectedFile) {
                    formData.append('proof_file', this.selectedFile);
                }
                
                const response = await fetch(`/api/costs/${this.cost.id}/reimbursement-stage`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                if (response.ok) {
                    const data = await response.json();
                    const updatedCost = data.cost;
                    const hadFile = !!this.selectedFile;
                    
                    // Atualiza dados reativamente com a resposta do backend
                    this.cost.effective_stage = updatedCost.reimbursement_stage;
                    this.cost.proof_number = updatedCost.reimbursement_notes;
                    this.cost.has_proof = !!updatedCost.reimbursement_notes || !!updatedCost.reimbursement_proof_file;
                    this.cost.proof_type = updatedCost.reimbursement_proof_type;
                    
                    // Atualiza dados do arquivo se foi anexado
                    if (updatedCost.reimbursement_proof_file) {
                        this.cost.has_file = true;
                        this.cost.proof_file_url = `/storage/${updatedCost.reimbursement_proof_file}`;
                    }
                    
                    this.selectedFileName = null;
                    this.selectedFile = null;
                    if (this.$refs.proofFileInput) {
                         this.$refs.proofFileInput.value = '';
                    }
                    this.dispatchUpdate();
                    this.showSuccess('Comprovante salvo com sucesso!');
                } else {
                    const data = await response.json();
                    this.showError(data.message || 'Erro ao salvar comprovante');
                }
            } catch (e) {
                console.error('Erro:', e);
                this.showError('Erro ao salvar comprovante');
            } finally {
                this.loading = false;
            }
        },
        
        clearFileSelection() {
            this.selectedFile = null;
            this.selectedFileName = null;
            if (this.$refs.proofFileInput) {
                this.$refs.proofFileInput.value = '';
            }
        },
        
        async removeProofFile() {
            const confirmed = await this.showConfirm(
                'Remover Comprovante',
                'Deseja remover o arquivo anexado? O número do documento será mantido.',
                'Sim, remover'
            );
            if (!confirmed) return;
            
            this.loading = true;
            try {
                const response = await fetch(`/api/costs/${this.cost.id}/remove-proof-file`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    // Atualiza dados reativamente
                    this.cost.has_file = false;
                    this.cost.proof_file_url = null;
                    this.dispatchUpdate();
                    this.showSuccess('Arquivo removido!');
                } else {
                    const data = await response.json();
                    this.showError(data.message || 'Erro ao remover arquivo');
                }
            } catch (e) {
                console.error('Erro:', e);
                this.showError('Erro ao remover arquivo');
            } finally {
                this.loading = false;
            }
        },
        
        async updateProofNumber() {
            this.loading = true;
            try {
                const response = await fetch(`/api/costs/${this.cost.id}/reimbursement-stage`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        stage: this.cost.effective_stage,
                        proof_number: this.editProofNumber
                    })
                });
                
                if (response.ok) {
                    this.cost.proof_number = this.editProofNumber;
                    this.dispatchUpdate();
                    this.showSuccess('Número atualizado!');
                } else {
                    const data = await response.json();
                    this.showError(data.message || 'Erro ao atualizar número');
                }
            } catch (e) {
                console.error('Erro:', e);
                this.showError('Erro ao atualizar número');
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>
@endPushOnce
