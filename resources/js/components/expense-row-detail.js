/**
 * Alpine.js component for expense row detail management
 * Handles confirmation, invoice toggle, and reimbursement workflow
 */
export default function expenseRowDetail(initialCost) {
    return {
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

                    this.cost.effective_stage = updatedCost.reimbursement_stage;
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

                    this.cost.effective_stage = updatedCost.reimbursement_stage;
                    this.cost.has_proof = !!updatedCost.reimbursement_notes || !!updatedCost.reimbursement_proof_file;
                    this.cost.proof_number = updatedCost.reimbursement_notes;
                    this.cost.proof_type = updatedCost.reimbursement_proof_type;
                    this.editProofNumber = updatedCost.reimbursement_notes || '';

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

                    this.cost.effective_stage = updatedCost.reimbursement_stage;
                    this.cost.proof_number = updatedCost.reimbursement_notes;
                    this.cost.has_proof = !!updatedCost.reimbursement_notes || !!updatedCost.reimbursement_proof_file;
                    this.cost.proof_type = updatedCost.reimbursement_proof_type;

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
    };
}
