{{-- resources/views/gigs/_show_payments.blade.php --}}
{{--
    Exibe a lista de pagamentos recebidos/previstos para uma Gig e permite registrar/confirmar/editar/excluir.
    Recebe:
    - $payments (Coleção de objetos Payment relacionados à $gig)
    - $gig (O objeto Gig atual)
--}}

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden"
     x-data="{
         editingPaymentId: null, // ID do pagamento em edição, 'new' para novo, ou null
         showConfirmModalId: null, // ID do pagamento para confirmação
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
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Parcelas / Recebimentos</h3>
        <button type="button" @click="initiateNew()"
                class="text-primary-600 dark:text-primary-400 text-sm font-medium hover:underline focus:outline-none">
            <i class="fas fa-plus mr-1"></i> Adicionar Parcela
        </button>
    </div>

    {{-- Conteúdo do Card --}}
    <div class="p-6">
        {{-- Lista de Pagamentos Existentes --}}
        @if($payments->isEmpty() && !$errors->hasBag('paymentStore') && !$errors->hasBag('paymentUpdate') && !$errors->hasBag('paymentConfirm'))
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Nenhuma parcela/pagamento registrado para esta Gig.</p>
        @else
            <ul class="space-y-1 mb-6"> {{-- Reduzido espaço entre itens --}}
                @foreach($payments as $payment)
                <li class="border-b border-gray-100 dark:border-gray-700 py-2" x-ref="paymentItem{{ $payment->id }}">

{{-- Div para EXIBIR dados --}}
<div x-show="editingPaymentId !== {{ $payment->id }} && showConfirmModalId !== {{ $payment->id }}"
     x-transition.opacity
     class="flex justify-between items-start text-sm gap-4">
    {{-- ... (Exibição do Valor Devido, Vencimento, Status Inferido) ... --}}
     <div class="flex-1">
         <span class="font-medium text-gray-800 dark:text-gray-200 block">
             {{ $payment->currency }} {{ number_format($payment->due_value ?? 0, 2, ',', '.') }}
             <span class="text-gray-500 dark:text-gray-400 font-normal ml-1">
                 (Vence: {{ $payment->due_date ? $payment->due_date->format('d/m/Y') : 'N/A' }})
             </span>
             <x-status-badge :status="$payment->inferred_status" :type="'payment'" class="ml-2"/>
         </span>
         @if($payment->confirmed_at)
             <span class="block text-xs text-green-600 dark:text-green-400 mt-0.5">
                 Confirmado: {{ $payment->currency }} {{ number_format($payment->received_value_actual ?? 0, 2, ',', '.') }}
                 em {{ $payment->received_date_actual?->format('d/m/Y') ?? 'N/A' }}
                 @if($payment->confirmer) por {{ $payment->confirmer->name }} @endif
                 em {{ $payment->confirmed_at->format('d/m/y H:i') }}
             </span>
         @endif
          @if($payment->notes)
             <span class="block text-xs text-gray-500 dark:text-gray-400 italic mt-1">Nota: {{ $payment->notes }}</span>
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
        class="text-gray-400 p-1 focus:outline-none"
        :class="{ 'hover:text-primary-500 dark:hover:text-primary-400': !Boolean({{ $payment->confirmed_at ? 'true' : 'false' }}), 'opacity-50 cursor-not-allowed': Boolean({{ $payment->confirmed_at ? 'true' : 'false' }}) }">
    <i class="fas fa-edit fa-fw"></i>
</button>
         {{-- Botão Excluir --}}
        <form action="{{ route('gigs.payments.destroy', [$gig, $payment]) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta parcela/pagamento?');" class="inline">
            @csrf @method('DELETE')
            <button type="submit" title="Excluir Parcela" class="text-gray-400 hover:text-red-500 dark:hover:text-red-400 p-1 focus:outline-none">
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
                <button type="button" @click="cancelEdit()" class="bg-gray-200 dark:bg-gray-600 ... text-xs">Cancelar</button>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 ... text-xs">Salvar Edição</button> {{-- ESTE É O BOTÃO QUE DEVE APARECER --}}
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
                                                   class="w-full text-sm rounded-md border-gray-300 ... @error('received_value_actual', 'paymentConfirm'.$payment->id) border-red-500 @enderror">
                                             @error('received_value_actual', 'paymentConfirm'.$payment->id) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                         <div>
                                            <label for="confirm_currency_{{ $payment->id }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Moeda Receb.*</label>
                                            <select name="currency" id="confirm_currency_{{ $payment->id }}" required x-model="confirmFormData.currency" class="w-full text-sm rounded-md ... @error('currency', 'paymentConfirm'.$payment->id) border-red-500 @enderror">
                                                <option value="BRL">BRL</option> <option value="USD">USD</option> <option value="EUR">EUR</option> <option value="GPB">GBP</option>
                                            </select>
                                             @error('currency', 'paymentConfirm'.$payment->id) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="confirm_received_date_actual_{{ $payment->id }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Data Real Receb.*</label>
                                            <input type="date" name="received_date_actual" id="confirm_received_date_actual_{{ $payment->id }}" required
                                                   x-model="confirmFormData.received_date_actual"
                                                   class="w-full text-sm rounded-md ... @error('received_date_actual', 'paymentConfirm'.$payment->id) border-red-500 @enderror">
                                             @error('received_date_actual', 'paymentConfirm'.$payment->id) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                     <div>
                                        <label for="confirm_exchange_rate_{{ $payment->id }}" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Taxa Câmbio (se moeda != BRL)</label>
                                        <input type="number" step="0.000001" name="exchange_rate" id="confirm_exchange_rate_{{ $payment->id }}"
                                               x-model="confirmFormData.exchange_rate" placeholder="Taxa do dia do recebimento"
                                               class="w-full text-sm rounded-md ... @error('exchange_rate', 'paymentConfirm'.$payment->id) border-red-500 @enderror">
                                          @error('exchange_rate', 'paymentConfirm'.$payment->id) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                     </div>
                                    {{-- Notas da Confirmação (Opcional) --}}
                                    {{-- <div>
                                        <label for="confirm_notes_{{ $payment->id }}">Notas Confirmação</label>
                                        <textarea name="confirmation_notes" ...></textarea>
                                    </div> --}}
                                    <div class="flex justify-end space-x-2 pt-2">
                                        <button type="button" @click="cancelEdit()" class="bg-gray-200 dark:bg-gray-600 ... text-xs">Cancelar</button>
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
        <template x-if="editingPaymentId === 'new'">
            <div id="payment-form-new" x-transition class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <h4 class="text-md font-semibold text-gray-700 dark:text-white mb-3">Adicionar Parcela Prevista</h4>
                <form action="{{ route('gigs.payments.store', $gig) }}" method="POST" class="space-y-4">
                    @csrf
                    {{-- !! CORREÇÃO AQUI !!
                        Garante que o include passe um objeto Payment VAZIO
                        para que o _payment_form_fields use os nomes corretos (due_value, due_date)
                        e os valores padrão definidos DENTRO do _payment_form_fields.
                    --}}
                    @include('gigs._payment_form_fields', [
                        'payment' => new \App\Models\Payment(), // Passa um objeto Payment completamente novo e vazio
                        'gig' => $gig,
                        'prefix' => 'new'
                    ])
                    {{-- Botões --}}
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="cancelEdit()" class="bg-gray-200 dark:bg-gray-600 ... text-xs">Cancelar</button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                            <i class="fas fa-plus mr-1"></i> Salvar Parcela
                        </button>
                    </div>
                    {{-- ... (Exibição de erros paymentStore) ... --}}
                </form>
            </div>
        </template>

    </div> {{-- Fim do p-6 --}}
</div> {{-- Fim do Card --}}