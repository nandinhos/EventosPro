{{-- resources/views/gigs/_show_payments.blade.php --}}
@props(['gig', 'payments'])

<div x-data="{
    editingPaymentId: null, // ID do pagamento em edição, ou 'new_payment_form'
    showConfirmModalId: null, // ID do pagamento para mostrar o formulário de confirmação
    confirmFormData: { // Dados para o form de confirmação
        received_date_actual: '{{ today()->format('Y-m-d') }}',
        received_value_actual: '',
        currency: '{{ $gig->currency ?? 'BRL' }}',
        exchange_rate: null,
        notes: ''
    },

    initiateConfirm(payment) {
        this.editingPaymentId = null; // Fecha form de edição
        this.confirmFormData.received_date_actual = '{{ today()->format('Y-m-d') }}';
        this.confirmFormData.received_value_actual = payment.due_value;
        this.confirmFormData.currency = payment.currency;
        this.confirmFormData.exchange_rate = payment.exchange_rate || ''; // Usa o exchange_rate da parcela prevista
        this.confirmFormData.notes = payment.notes || ''; // Puxa notas da parcela
        this.showConfirmModalId = payment.id;
        this.$nextTick(() => document.getElementById('confirm_received_value_actual_' + payment.id)?.focus());
    },

    initiateEdit(paymentId) {
        this.showConfirmModalId = null; // Fecha form de confirmação
        this.editingPaymentId = paymentId; // Abre form de edição para este ID
        this.$nextTick(() => document.getElementById('edit_payment_description_' + paymentId)?.focus());
    },

    initiateNew() {
        this.showConfirmModalId = null; // Fecha form de confirmação
        this.editingPaymentId = 'new_payment_form'; // String para identificar o form de novo
        this.$nextTick(() => document.getElementById('new_payment_description')?.focus());
    },

    cancelForm() {
        this.editingPaymentId = null;
        this.showConfirmModalId = null;
    }
}"
class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mt-6">

    {{-- Header do Card --}}
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap justify-between items-center gap-2 bg-gray-50 dark:bg-gray-700/50">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Parcelas / Recebimentos</h3>
        <button type="button" @click="editingPaymentId === 'new_payment_form' ? cancelForm() : initiateNew()"
                class="text-white px-3 py-1.5 rounded-md text-xs flex items-center"
                :class="editingPaymentId === 'new_payment_form' ? 'bg-gray-500 hover:bg-gray-600' : 'bg-green-500 hover:bg-green-600'">
            <i class="fas mr-1" :class="editingPaymentId === 'new_payment_form' ? 'fa-times' : 'fa-plus'"></i>
            <span x-text="editingPaymentId === 'new_payment_form' ? 'Cancelar Novo' : 'Adicionar Parcela Prevista'"></span>
        </button>
    </div>

    {{-- Formulário para Adicionar Nova Parcela Prevista (controlado pelo Alpine) --}}
    <div x-show="editingPaymentId === 'new_payment_form'" x-transition id="new_payment_form_container"
         class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-b-md">
        <h4 class="text-md font-semibold mb-3 text-gray-700 dark:text-gray-200">Adicionar Parcela Prevista</h4>
        <form action="{{ route('gigs.payments.store', $gig) }}" method="POST" class="space-y-4">
            @csrf
            @include('payments._form', [
    'payment' => new \App\Models\Payment(['currency' => $gig->currency, 'due_date' => today()]),
    'gig' => $gig,
    'prefix' => 'new_payment_', // Prefixo para evitar conflito de ID
    'errorBag' => 'paymentStore' // Error bag para o form de store
])
            <div class="flex justify-end">
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm">Salvar Parcela</button>
            </div>
        </form>
        @if ($errors->paymentStore->any()) {{-- Exibe erros para este form --}}
            <div class="mt-3 text-xs text-red-500">
                <strong>Ops! Erros ao salvar:</strong>
                <ul class="list-disc list-inside ml-4"> @foreach ($errors->paymentStore->all() as $error) <li>{{ $error }}</li> @endforeach </ul>
            </div>
        @endif
    </div>

    {{-- Lista de Pagamentos Existentes --}}
    <div class="p-6">
        @if($payments->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma parcela de pagamento registrada para esta Gig.</p>
        @else
            <ul class="space-y-3">
                @foreach($payments as $payment)
                <li class="p-3 rounded-md border dark:border-gray-700 {{ $payment->confirmed_at ? 'bg-green-50 dark:bg-green-800/20 border-green-200 dark:border-green-600/30' : ($payment->due_date->isPast() && !$payment->confirmed_at ? 'bg-red-50 dark:bg-red-800/20 border-red-200 dark:border-red-600/30' : 'bg-gray-50 dark:bg-gray-700/30 border-gray-200 dark:border-gray-600/30') }}"
                    id="payment-item-{{ $payment->id }}">

                    {{-- Div para EXIBIR dados (quando não editando nem confirmando este item) --}}
                    <div x-show="editingPaymentId !== {{ $payment->id }} && showConfirmModalId !== {{ $payment->id }}" x-transition.opacity
                         class="flex flex-wrap justify-between items-start text-sm gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <span class="font-semibold text-gray-800 dark:text-gray-100 block">
                                {{ $payment->description ?: 'Parcela Prevista' }} -
                                {{ $payment->currency }} {{ number_format($payment->due_value ?? 0, 2, ',', '.') }}
                                @if($payment->currency !== 'BRL')
                                    <span class="text-sm text-gray-600 dark:text-gray-300">
                                        (Taxa: {{ number_format($payment->exchange_rate ?? 0, 4, ',', '.') }} - 
                                        BRL {{ number_format(($payment->due_value ?? 0) * ($payment->exchange_rate ?? 0), 2, ',', '.') }})
                                    </span>
                                @endif
                            </span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">
                                Vencimento: {{ $payment->due_date ? $payment->due_date->format('d/m/Y') : 'N/A' }}
                            </span>
                            <span class="block text-xs">
                                Status: <x-status-badge :status="$payment->inferred_status" type="payment" />
                            </span>
                            @if($payment->confirmed_at)
                                <span class="block text-xs mt-1 text-green-700 dark:text-green-300">
                                    <i class="fas fa-check-circle fa-fw"></i>
                                    Recebido: {{ $payment->currency_received_actual ?? $payment->currency }} {{ number_format($payment->received_value_actual ?? 0, 2, ',', '.') }}
                                    em {{ $payment->received_date_actual?->format('d/m/Y') ?? 'N/A' }}
                                    @if(($payment->currency_received_actual ?? $payment->currency) !== 'BRL' && $payment->exchange_rate_received_actual)
                                        (Câmbio: {{ rtrim(rtrim(number_format($payment->exchange_rate_received_actual, 6, ',', '.'),'0'),',') }})
                                    @endif
                                </span>
                                <span class="block text-xxs text-gray-400 dark:text-gray-500">Confirmado por {{ $payment->confirmer?->name ?? '?' }} em {{ $payment->confirmed_at->format('d/m/y H:i') }}</span>
                            @endif
                            @if($payment->notes)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 italic border-t border-gray-100 dark:border-gray-700 pt-1">Nota: {{ $payment->notes }}</p>
                            @endif
                        </div>

                        {{-- Botões de Ação --}}
                        <div class="flex items-center space-x-2 flex-shrink-0">
                            @if(!$payment->confirmed_at)
                            <button type="button" @click="initiateConfirm({{ $payment->toJson() }})" title="Confirmar Recebimento"
                                    class="text-green-500 hover:text-green-600 dark:hover:text-green-400 p-1 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 rounded-md transition-colors duration-200">
                                <i class="fas fa-check-circle fa-fw"></i>
                            </button>
                            <button type="button" @click="initiateEdit({{ $payment->id }})" title="Editar Parcela Prevista"
                                    class="text-primary-500 hover:text-primary-600 dark:hover:text-primary-400 p-1 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-opacity-50 rounded-md transition-colors duration-200">
                                <i class="fas fa-edit fa-fw"></i>
                            </button>
                            @else
                            <form action="{{ route('gigs.payments.unconfirm', ['gig' => $gig, 'payment' => $payment]) }}" method="POST" class="inline" onsubmit="return confirm('Reverter confirmação deste pagamento?');">
                                @csrf @method('PATCH')
                                <button type="submit" title="Reverter Confirmação" class="text-yellow-500 hover:text-yellow-600 dark:hover:text-yellow-400 p-1 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-opacity-50 rounded-md transition-colors duration-200">
                                    <i class="fas fa-undo-alt fa-fw"></i>
                                </button>
                            </form>
                            {{-- Botão Editar Desabilitado --}}
                            <span title="Edição desabilitada para pagamentos confirmados" class="text-gray-400 p-1 cursor-not-allowed opacity-50"><i class="fas fa-edit fa-fw"></i></span>
                            @endif
                            <form action="{{ route('gigs.payments.destroy', ['gig' => $gig, 'payment' => $payment]) }}" method="POST" onsubmit="return confirm('Excluir esta parcela de pagamento?');" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" title="Excluir Parcela"
                                        :disabled="Boolean({{ $payment->confirmed_at ? 'true' : 'false' }})"
                                        class="p-1 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 rounded-md transition-colors duration-200"
                                        :class="{
                                            'text-red-500 hover:text-red-600 dark:hover:text-red-400': !Boolean({{ $payment->confirmed_at ? 'true' : 'false' }}),
                                            'text-gray-400 opacity-50 cursor-not-allowed focus:ring-0': Boolean({{ $payment->confirmed_at ? 'true' : 'false' }})
                                        }">
                                    <i class="fas fa-trash-alt fa-fw"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Formulário de EDIÇÃO (inline, controlado por Alpine) --}}
                    <template x-if="editingPaymentId === {{ $payment->id }}">
                        <div x-transition class="mt-2 pt-3 border-t border-dashed border-gray-300 dark:border-gray-600" id="payment-form-edit-{{ $payment->id }}">
                            <form action="{{ route('gigs.payments.update', ['gig' => $gig, 'payment' => $payment]) }}" method="POST" class="space-y-3">
                                @csrf
                                @method('PUT')
                                <h5 class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Editando Parcela #{{ $payment->id }}</h5>
                                @include('payments._form', [
    'payment' => $payment,
    'gig' => $gig,
    'prefix' => 'edit_payment_'.$payment->id.'_', // Prefixo único
    'errorBag' => 'paymentUpdate'.$payment->id // Error bag específico
])
                                <div class="flex justify-end space-x-2">
                                    <button type="button" @click="cancelForm()" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-3 py-1.5 rounded-md text-xs transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50">Cancelar Edição</button>
                                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-1.5 rounded-md text-xs transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-opacity-50">Salvar Edição</button>
                                </div>
                            </form>
                            @if ($errors->hasBag('paymentUpdate'.$payment->id))
                                <div class="mt-2 text-xs text-red-500 ...">
                                    <strong>Ops! Erros na edição:</strong>
                                    <ul class="list-disc list-inside ml-4"> @foreach ($errors->getBag('paymentUpdate'.$payment->id)->all() as $error) <li>{{ $error }}</li> @endforeach </ul>
                                </div>
                            @endif
                        </div>
                    </template>

                    {{-- Formulário de CONFIRMAÇÃO (inline, controlado por Alpine) --}}
                    <template x-if="showConfirmModalId === {{ $payment->id }}">
                        <div x-transition class="mt-2 pt-3 border-t border-dashed border-blue-300 dark:border-blue-700">
                            <form action="{{ route('gigs.payments.confirm', ['gig' => $gig, 'payment' => $payment]) }}" method="POST" class="space-y-3">
                                @csrf
                                @method('PATCH')
                                <h5 class="text-xs font-semibold uppercase text-blue-600 dark:text-blue-400">Confirmar Recebimento da Parcela #{{ $payment->id }}</h5>
                                <input type="hidden" name="original_due_value" x-model="confirmFormData.due_value"> {{-- Passa valor devido original --}}
                                <input type="hidden" name="original_currency" x-model="confirmFormData.currency"> {{-- Passa moeda original --}}
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label for="confirm_received_value_actual_{{ $payment->id }}" class="block text-xs font-medium ...">Valor Real Recebido*</label>
                                        <input type="number" step="0.01" min="0" name="received_value_actual" id="confirm_received_value_actual_{{ $payment->id }}" required x-model="confirmFormData.received_value_actual" class="w-full text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-500 dark:focus:border-primary-500 @error('received_value_actual', 'paymentConfirm'.$payment->id) border-red-500 dark:border-red-500 @enderror">
                                        @error('received_value_actual', 'paymentConfirm'.$payment->id) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="confirm_currency_{{ $payment->id }}" class="block text-xs font-medium ...">Moeda Recebida*</label>
                                        <select name="currency_received_actual" id="confirm_currency_{{ $payment->id }}" required x-model="confirmFormData.currency" class="w-full text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-500 dark:focus:border-primary-500 @error('currency_received_actual', 'paymentConfirm'.$payment->id) border-red-500 dark:border-red-500 @enderror">
                                            <option value="BRL">BRL</option> <option value="USD">USD</option> <option value="EUR">EUR</option> <option value="GBP">GBP</option>
                                        </select>
                                        @error('currency_received_actual', 'paymentConfirm'.$payment->id) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="confirm_received_date_actual_{{ $payment->id }}" class="block text-xs font-medium ...">Data Real Recebimento*</label>
                                        <input type="date" name="received_date_actual" id="confirm_received_date_actual_{{ $payment->id }}" required x-model="confirmFormData.received_date_actual" class="w-full text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-500 dark:focus:border-primary-500 @error('received_date_actual', 'paymentConfirm'.$payment->id) border-red-500 dark:border-red-500 @enderror">
                                        @error('received_date_actual', 'paymentConfirm'.$payment->id) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                 <div x-show="confirmFormData.currency !== 'BRL'">
                                    <label for="confirm_exchange_rate_{{ $payment->id }}" class="block text-xs font-medium ...">Taxa Câmbio Recebimento</label>
                                    <input type="number" step="0.000001" name="exchange_rate_received_actual" id="confirm_exchange_rate_{{ $payment->id }}" x-model="confirmFormData.exchange_rate" :required="confirmFormData.currency !== 'BRL'" placeholder="Taxa do dia do recebimento" class="w-full text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-500 dark:focus:border-primary-500 @error('exchange_rate_received_actual', 'paymentConfirm'.$payment->id) border-red-500 dark:border-red-500 @enderror">
                                    @error('exchange_rate_received_actual', 'paymentConfirm'.$payment->id) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                 </div>
                                <div>
                                    <label for="confirm_notes_{{ $payment->id }}" class="block text-xs font-medium ...">Notas da Confirmação (Opc)</label>
                                    <textarea name="notes" id="confirm_notes_{{ $payment->id }}" x-model="confirmFormData.notes" rows="2" class="w-full text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-500 dark:focus:border-primary-500 resize-none"></textarea>
                                </div>
                                <div class="flex justify-end space-x-2 pt-2">
                                    <button type="button" @click="cancelForm()" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-3 py-1.5 rounded-md text-xs transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50">Cancelar</button>
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md text-xs transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">Confirmar Recebimento</button>
                                </div>
                                 @if ($errors->hasBag('paymentConfirm'.$payment->id)) {{-- Verifica error bag específico --}}
                                     <div class="mt-2 text-xs text-red-500 ...">
                                         <strong>Ops! Erros na confirmação:</strong>
                                         <ul class="list-disc list-inside ml-4"> @foreach ($errors->getBag('paymentConfirm'.$payment->id)->all() as $error) <li>{{ $error }}</li> @endforeach </ul>
                                     </div>
                                     {{-- Script para reabrir este formulário se houver erro --}}
                                     <script>
                                         document.addEventListener('DOMContentLoaded', function () {
                                             Alpine.nextTick(() => {
                                                 const paymentItem = document.getElementById('payment-item-{{ $payment->id }}');
                                                 if (paymentItem) {
                                                     const alpineComponent = Alpine.$data(paymentItem.closest('[x-data]'));
                                                     if (alpineComponent && alpineComponent.initiateConfirm) {
                                                         alpineComponent.initiateConfirm(@json($payment->toArray()));
                                                     }
                                                 }
                                             });
                                         });
                                     </script>
                                 @endif
                            </form>
                        </div>
                    </template>
                </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>