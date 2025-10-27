{{-- resources/views/gigs/_show_payments.blade.php --}}
{{--
    Exibe a lista de pagamentos recebidos/previstos para uma Gig e permite registrar/confirmar/editar/excluir.
    Recebe:
    - $payments (Coleção de objetos Payment relacionados à $gig)
    - $gig (O objeto Gig atual)
--}}

<div x-data="{
    editingPaymentId: null, // ID do pagamento em edição, 'new' para novo, ou null
    showConfirmModalId: null, // ID do pagamento para confirmação
    showPaymentForm: false, // Controla visibilidade do form de nova parcela
    confirmFormData: { // Dados para o form de confirmação
        received_date_actual: '{{ today()->format('Y-m-d') }}',
        received_value_actual: '',
        currency: '{{ $gig->currency ?? 'BRL' }}',
        exchange_rate: null
    },

         // Função para abrir o modal/form de confirmação
         initiateConfirm(payment) {
             // Preenche os dados do form com valores da parcela prevista/gig
             this.confirmFormData.received_date_actual = '{{ today()->format('Y-m-d') }}'; // Default hoje
             this.confirmFormData.received_value_actual = payment.due_value; // Sugere o valor devido
             this.confirmFormData.currency = payment.currency; // Usa moeda da parcela
             this.confirmFormData.exchange_rate = payment.exchange_rate; // Usa câmbio da parcela (se houver)
             this.showConfirmModalId = payment.id; // Mostra o modal para este ID

             // Foca no campo de valor
             this.$nextTick(() => {
                 const inputEl = document.getElementById('confirm_received_value_actual_' + payment.id);
                 if(inputEl) inputEl.focus();
             });
         },

         // Função para abrir o form de edição inline
         initiateEdit(paymentId) {
             this.editingPaymentId = paymentId;
             this.showConfirmModalId = null; // Garante que modal de confirmação feche
             this.$nextTick(() => {
                 const formElement = document.getElementById('payment-form-edit-' + paymentId);
                 if (formElement) {
                     // Foca no primeiro input do formulário de edição
                     const firstInput = formElement.querySelector('input, select, textarea');
                     if(firstInput) firstInput.focus();
                 }
             });
         },

          // Função para mostrar o form de NOVO pagamento
         initiateNew() {
             this.editingPaymentId = 'new';
             this.showConfirmModalId = null;
              this.$nextTick(() => {
                 const formElement = document.getElementById('payment-form-new');
                  if (formElement) {
                     const firstInput = formElement.querySelector('input, select, textarea');
                     if(firstInput) firstInput.focus();
                 }
             });
         },

         // Função para cancelar edição ou novo
         cancelEdit() {
             this.editingPaymentId = null;
             this.showConfirmModalId = null;
         }
     }">

    {{-- Header do Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap justify-between items-center gap-2 bg-gray-50 dark:bg-gray-700/50">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Parcelas / Recebimentos</h3>
            <button type="button" @click="showPaymentForm = !showPaymentForm"
                    class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                <i class="fas fa-plus mr-1"></i> <span x-text="showPaymentForm ? 'Ocultar Formulário' : 'Adicionar Parcela Prevista'"></span>
            </button>
        </div>

    {{-- Conteúdo do Card --}}
    <div class="p-6">
        {{-- Lista de Pagamentos Existentes --}}
        @if($payments->isEmpty() && !$errors->hasBag('paymentStore') && !$errors->hasBag('paymentUpdate') && !$errors->hasBag('paymentConfirm'))
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Nenhuma parcela/pagamento registrado para esta Gig.</p>
        @else
            <ul class="space-y-3 mb-6">
                @foreach($payments as $payment)
                <li class="p-3 rounded-md border dark:border-gray-700 {{ $payment->confirmed_at ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700/50' : ($payment->due_date->isPast() ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700/50' : 'bg-gray-50 dark:bg-gray-800/50 border-gray-200') }}" x-ref="paymentItem{{ $payment->id }}">

{{-- Div para EXIBIR dados --}}
<div x-show="editingPaymentId !== {{ $payment->id }} && showConfirmModalId !== {{ $payment->id }}"
     x-transition.opacity
     class="flex flex-wrap justify-between items-start text-sm gap-4">
     <div class="flex-1 min-w-[200px]">
         <span class="font-semibold text-gray-800 dark:text-gray-100 block">
             {{ $payment->description ?: 'Parcela Prevista' }} -
             {{ $payment->currency }} {{ number_format($payment->due_value ?? 0, 2, ',', '.') }}
         </span>
         <span class="block text-xs text-gray-500 dark:text-gray-400">
             Vencimento: {{ $payment->due_date ? $payment->due_date->isoFormat('L') : 'N/A' }}
         </span>
         <span class="block text-xs">
             Status: <x-status-badge :status="$payment->inferred_status" type="payment-receipt" />
         </span>
         @if($payment->confirmed_at)
             <span class="block text-xs mt-1 text-green-700 dark:text-green-300">
                 <i class="fas fa-check-circle fa-fw"></i>
                 Recebido: {{ $payment->currency }} {{ number_format($payment->received_value_actual ?? 0, 2, ',', '.') }}
                 em {{ $payment->received_date_actual?->isoFormat('L') ?? 'N/A' }}
                 @if($payment->currency !== 'BRL' && $payment->exchange_rate)
                     (Câmbio: {{ rtrim(rtrim(number_format($payment->exchange_rate, 6, ',', '.'),'0'),',') }})
                 @endif
             </span>
             <span class="block text-xxs text-gray-400 dark:text-gray-500">Confirmado por {{ $payment->confirmer?->name ?? '?' }} em {{ $payment->confirmed_at->isoFormat('l LT') }}</span>
         @endif
         @if($payment->notes)
             <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 italic border-t border-gray-100 dark:border-gray-700 pt-1">Nota: {{ $payment->notes }}</p>
         @endif
     </div>

    {{-- Botões de Ação --}}
    <div class="flex items-center space-x-1 flex-shrink-0">
         {{-- Botão Confirmar (mostra se ainda não confirmado) --}}
        @if(!$payment->confirmed_at)
        <button type="button" @click="initiateConfirm({{ $payment->toJson() }})" title="Confirmar Recebimento" class="text-green-500 hover:text-green-700 dark:hover:text-green-400 p-1 focus:outline-none">
            <i class="fas fa-check-circle fa-fw"></i>
        </button>
        @else
        {{-- Botão Desconfirmar (mostra se JÁ confirmado) --}}
        <form action="{{ route('gigs.payments.unconfirm', [$gig, $payment]) }}" method="POST" onsubmit="return confirm('Reverter a confirmação deste pagamento?');" class="inline">
            @csrf
            @method('PATCH') {{-- Usar PATCH para desconfirmar --}}
            <button type="submit" title="Reverter Confirmação" class="text-yellow-500 hover:text-yellow-700 dark:hover:text-yellow-400 p-1 focus:outline-none">
                <i class="fas fa-undo-alt fa-fw"></i>
            </button>
        </form>
        @endif

        {{-- Botão Editar (Desabilitado se confirmado) --}}
        <button type="button"
        @click="initiateEdit({{ $payment->id }})" {{-- Chama a função Alpine --}}
        :disabled="Boolean({{ $payment->confirmed_at ? 'true' : 'false' }})"
        title="Editar Parcela"
        :class="{ 'text-primary-500 hover:text-primary-600 dark:hover:text-primary-400 p-1 focus:outline-none': !Boolean({{ $payment->confirmed_at ? 'true' : 'false' }}), 'text-gray-400 opacity-50 cursor-not-allowed p-1': Boolean({{ $payment->confirmed_at ? 'true' : 'false' }}) }">
    <i class="fas fa-edit fa-fw"></i>
</button>
         {{-- Botão Excluir --}}
        <form action="{{ route('gigs.payments.destroy', [$gig, $payment]) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta parcela/pagamento?');" class="inline">
            @csrf @method('DELETE')
            <button type="submit" title="Excluir Parcela" class="text-red-500 hover:text-red-600 dark:hover:text-red-400 p-1 focus:outline-none">
                <i class="fas fa-trash-alt fa-fw"></i>
            </button>
        </form>
    </div>
</div>

                        {{-- Formulário de EDIÇÃO (renderizado apenas se editingPaymentId corresponder) --}}
                        <template x-if="editingPaymentId === {{ $payment->id }}">
    <div x-transition class="mt-2" id="payment-form-edit-{{ $payment->id }}">
        <form action="{{ route('gigs.payments.update', [$gig, $payment]) }}" method="POST" class="space-y-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded border dark:border-gray-700">
            @csrf
            @method('PUT')
            <h5 class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Editando Parcela #{{ $payment->id }}</h5>
            {{-- Inclui os campos do formulário --}}
            @include('gigs._payment_form_fields', ['payment' => $payment, 'gig' => $gig, 'prefix' => 'edit_'.$payment->id])
            <div class="flex justify-end space-x-2">
                <button type="button" @click="cancelEdit()" class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors duration-150 text-xs">Cancelar</button>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-1 rounded-md transition-colors duration-150 text-xs">Salvar Edição</button>
            </div>
                                     {{-- Exibe erros de validação específicos deste form --}}
                                     @if($errors->paymentUpdate && session('error_payment_id') == $payment->id)
                                         <div class="text-xs text-red-600 dark:text-red-400 border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 p-2 rounded mt-2">
                                              <ul class="list-disc list-inside ml-2">
                                                  @foreach ($errors->paymentUpdate->all() as $error) <li>{{ $error }}</li> @endforeach
                                              </ul>
                                         </div>
                                     @endif
                                </form>
                             </div>
                        </template>

                         {{-- Formulário/Modal de CONFIRMAÇÃO (renderizado apenas se showConfirmModalId corresponder) --}}
                        <template x-if="showConfirmModalId === {{ $payment->id }}">
                            <div x-transition class="mt-2">
                                <form action="{{ route('gigs.payments.confirm', [$gig, $payment]) }}" method="POST" class="space-y-2 p-3 bg-blue-50 dark:bg-blue-900/50 rounded border border-blue-200 dark:border-blue-800">
                                    @csrf
                                    @method('PATCH')
                                    <h5 class="text-xs font-semibold uppercase text-blue-600 dark:text-blue-300">Confirmar Recebimento Parcela #{{ $payment->id }}</h5>
                                    {{-- Campos para confirmação --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div>
                                            <label for="confirm_received_value_actual_{{ $payment->id }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Valor Real Recebido*</label>
                                            <input type="number" step="0.01" min="0" name="received_value_actual" id="confirm_received_value_actual_{{ $payment->id }}" required
                                                   x-model="confirmFormData.received_value_actual"
                                                   x-ref="confirmValueInput{{ $payment->id }}"
                                                   class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm @error('received_value_actual', 'paymentConfirm'.$payment->id) border-red-500 @enderror">
                                             @error('received_value_actual', 'paymentConfirm'.$payment->id) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                         <div>
                                            <label for="confirm_currency_{{ $payment->id }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Moeda Receb.*</label>
                                            <select name="currency" id="confirm_currency_{{ $payment->id }}" required x-model="confirmFormData.currency" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm @error('currency', 'paymentConfirm'.$payment->id) border-red-500 @enderror">
                                                <option value="BRL">BRL</option> <option value="USD">USD</option> <option value="EUR">EUR</option> <option value="GBP">GBP</option>
                                            </select>
                                             @error('currency', 'paymentConfirm'.$payment->id) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="confirm_received_date_actual_{{ $payment->id }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Data Real Receb.*</label>
                                            <input type="date" name="received_date_actual" id="confirm_received_date_actual_{{ $payment->id }}" required
                                                   x-model="confirmFormData.received_date_actual"
                                                   class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm @error('received_date_actual', 'paymentConfirm'.$payment->id) border-red-500 @enderror">
                                             @error('received_date_actual', 'paymentConfirm'.$payment->id) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                     <div>
                                        <label for="confirm_exchange_rate_{{ $payment->id }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Taxa Câmbio (se moeda != BRL)</label>
                                        <input type="number" step="0.000001" name="exchange_rate" id="confirm_exchange_rate_{{ $payment->id }}"
                                               x-model="confirmFormData.exchange_rate" placeholder="Taxa do dia do recebimento"
                                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm @error('exchange_rate', 'paymentConfirm'.$payment->id) border-red-500 @enderror">
                                          @error('exchange_rate', 'paymentConfirm'.$payment->id) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                     </div>
                                    {{-- Notas da Confirmação (Opcional) --}}
                                    {{-- <div>
                                        <label for="confirm_notes_{{ $payment->id }}">Notas Confirmação</label>
                                        <textarea name="confirmation_notes" ...></textarea>
                                    </div> --}}
                                    <div class="flex justify-end space-x-2 pt-2">
                                        <button type="button" @click="cancelEdit()" class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors duration-150 text-xs">Cancelar</button>
                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-xs">Confirmar Recebimento</button>
                                    </div>
                                     {{-- Exibe erros de validação específicos deste form --}}
                                     @if($errors->paymentConfirm && session('error_payment_id') == $payment->id)
                                         <div class="text-xs text-red-600 dark:text-red-400 border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 p-2 rounded mt-2">
                                             <ul class="list-disc list-inside ml-2">
                                                 @foreach ($errors->paymentConfirm->all() as $error) <li>{{ $error }}</li> @endforeach
                                             </ul>
                                         </div>
                                         {{-- Script para reabrir este modal/form específico se houver erro nele --}}
                                         <script> document.addEventListener('alpine:init', () => { Alpine.store('layout').initiateConfirm(@json($payment)); }); </script>
                                     @endif
                                </form>
                            </div>
                        </template>

                    </li>
                @endforeach
            </ul>
        @endif

        {{-- Formulário para REGISTRAR NOVA Parcela Prevista --}}
        <div x-show="showPaymentForm" x-transition class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-b-md">
            <h4 class="text-md font-semibold mb-3 text-gray-700 dark:text-gray-200">Adicionar Parcela Prevista</h4>
            <form action="{{ route('gigs.payments.store', $gig) }}" method="POST" class="space-y-4">
                @csrf
                @include('gigs._payment_form_fields', [
                    'payment' => new \App\Models\Payment(),
                    'gig' => $gig,
                    'prefix' => 'new'
                ])
                <div class="flex justify-end space-x-2">
                    <button type="button" @click="showPaymentForm = false" class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 px-3 py-1.5 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors duration-150 text-xs">Cancelar</button>
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-1.5 rounded-md text-xs flex items-center">
                        <i class="fas fa-plus mr-1"></i> Salvar Parcela
                    </button>
                </div>
                @if ($errors->paymentStore->any())
                    <div class="mt-3 text-xs text-red-500">
                        <strong>Ops! Algo deu errado ao salvar a parcela:</strong>
                        <ul class="list-disc list-inside ml-2"> @foreach ($errors->paymentStore->all() as $error) <li>{{ $error }}</li> @endforeach </ul>
                    </div>
                @endif
            </form>
        </div>

    </div> {{-- Fim do p-6 --}}
</div> {{-- Fim do Card --}}