{{-- resources/views/payments/_confirm-modal.blade.php --}}
<div x-data="{
        showModal: false, // Renomeado de showConfirmModal para evitar conflito com o do _show_payments
        gigId: null,
        paymentId: null,
        // Dados para o formulário de confirmação
        formData: {
            received_date_actual: '',
            received_value_actual: '',
            currency_received_actual: '', // Campo específico para a moeda do recebimento
            exchange_rate_received_actual: '', // Campo específico para o câmbio do recebimento
            notes: '' // Notas da confirmação
        },
        // Dados da parcela original (para exibir e pré-preencher)
        originalPayment: {
            due_value: '',
            currency: '',
            exchange_rate: ''
        }
     }"
     @open-confirm-payment-modal.window="
        showModal = true;
        gigId = $event.detail.gigId;
        paymentId = $event.detail.paymentId;

        originalPayment.due_value = parseFloat($event.detail.dueValue) || 0;
        originalPayment.currency = $event.detail.currency || 'BRL';
        originalPayment.exchange_rate = parseFloat($event.detail.exchangeRate) || null;

        // Preenche o formulário com defaults inteligentes
        formData.received_date_actual = '{{ today()->format('Y-m-d') }}';
        formData.received_value_actual = originalPayment.due_value; // Sugere o valor devido
        formData.currency_received_actual = originalPayment.currency; // Sugere a moeda da parcela
        formData.exchange_rate_received_actual = (originalPayment.currency !== 'BRL' && originalPayment.exchange_rate) ? originalPayment.exchange_rate : '';
        formData.notes = ''; // Limpa notas

        $nextTick(() => $refs.receivedValueInput?.focus()); // Adicionado ? para segurança
     "
     x-show="showModal"
     @keydown.escape.window="showModal = false"
     class="fixed inset-0 z-[99] overflow-y-auto flex items-center justify-center" {{-- Z-index alto --}}
     style="display: none;">

    {{-- Overlay --}}
    <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" @click="showModal = false"></div>

    {{-- Conteúdo do Modal --}}
    <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-lg w-full mx-auto shadow-xl overflow-hidden p-6 space-y-4"
         @click.away="showModal = false">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Confirmar Recebimento de Parcela</h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Parcela Prevista: <span x-text="originalPayment.currency"></span> <span x-text="parseFloat(originalPayment.due_value).toLocaleString('pt-BR', {minimumFractionDigits: 2})"></span>.
        </p>
        <hr class="dark:border-gray-700">

        {{-- Formulário de Confirmação --}}
        {{-- A action será definida dinamicamente no submit --}}
        <form x-ref="confirmPaymentForm" method="POST" @submit.prevent="
            const form = $event.target;
            form.action = `/gigs/${gigId}/payments/${paymentId}/confirm`; // Constrói a action aqui

            // Adicionar o token CSRF ao FormData se for enviar via fetch
            // Ou deixar o Blade cuidar se for submissão normal
            // Para submissão normal (sem fetch no Alpine aqui), o @csrf e @method abaixo cuidam disso.
            form.submit(); // Submissão de formulário HTML padrão
        ">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                <div>
                    <label for="modal_received_date_actual" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Recebimento <span class="text-red-500">*</span></label>
                    <input type="date" name="received_date_actual" id="modal_received_date_actual" x-model="formData.received_date_actual" required
                           class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('received_date_actual', 'paymentConfirm' + paymentId) border-red-500 @enderror">
                     @error('received_date_actual', 'paymentConfirm' + paymentId) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="modal_received_value_actual" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor Real Recebido <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="received_value_actual" id="modal_received_value_actual" x-ref="receivedValueInput" x-model="formData.received_value_actual" required
                           class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('received_value_actual', 'paymentConfirm' + paymentId) border-red-500 @enderror">
                    @error('received_value_actual', 'paymentConfirm' + paymentId) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
             <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                <div>
                    <label for="modal_currency_received_actual" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Moeda Recebida <span class="text-red-500">*</span></label>
                    <select name="currency_received_actual" id="modal_currency_received_actual" x-model="formData.currency_received_actual" required
                            class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('currency_received_actual', 'paymentConfirm' + paymentId) border-red-500 @enderror">
                        <option value="BRL">BRL</option> <option value="USD">USD</option> <option value="EUR">EUR</option> <option value="GBP">GBP</option>
                    </select>
                    @error('currency_received_actual', 'paymentConfirm' + paymentId) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div x-show="formData.currency_received_actual !== 'BRL'">
                    <label for="modal_exchange_rate_received_actual" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Câmbio Recebimento</label>
                    <input type="number" step="0.000001" name="exchange_rate_received_actual" id="modal_exchange_rate_received_actual" x-model="formData.exchange_rate_received_actual"
                           :required="formData.currency_received_actual !== 'BRL'" placeholder="Taxa do dia do recebimento"
                           class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('exchange_rate_received_actual', 'paymentConfirm' + paymentId) border-red-500 @enderror">
                     @error('exchange_rate_received_actual', 'paymentConfirm' + paymentId) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
            <div>
                <label for="modal_confirmation_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas da Confirmação (Opc)</label>
                <textarea name="notes" id="modal_confirmation_notes" x-model="formData.notes" rows="2" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" @click="showModal = false" class="bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">Cancelar</button>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">Confirmar Recebimento</button>
            </div>
        </form>
         {{-- Exibir erros específicos do form de confirmação --}}
         @if (session('error_payment_id') && $errors->hasBag('paymentConfirm'.session('error_payment_id')))
             <div class="mt-3 text-xs text-red-500">
                <strong>Ops! Erros na confirmação:</strong>
                <ul class="list-disc list-inside ml-4">
                    @foreach ($errors->getBag('paymentConfirm'.session('error_payment_id'))->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            {{-- Script para reabrir este modal se houver erro NELE (pode ser complexo se o erro vem do redirect) --}}
            <script>
                document.addEventListener('alpine:init', () => {
                    // Este Alpine.store('layout').initiateConfirm não existe, precisa ser adaptado
                    // A melhor forma é o controller, no redirect, passar o payment_id_with_error
                    // e o x-data principal do _show_payments ouvir isso.
                    // Ou, se o modal já estiver aberto e o erro for via AJAX (não é o caso aqui),
                    // o erro seria exibido dentro do modal.
                    @if(session('error_payment_id'))
                        // Tenta reabrir o modal via dispatch de evento se o ID do pagamento com erro for conhecido
                        Alpine.nextTick(() => {
                            window.dispatchEvent(new CustomEvent('open-confirm-payment-modal', {
                                detail: {
                                    gigId: {{ $gig->id }}, // Precisa da gig aqui
                                    paymentId: {{ session('error_payment_id') }},
                                    // Outros dados podem ser buscados via JS se necessário ou usar os valores old()
                                    dueValue: '{{ old('received_value_actual', $payments->find(session('error_payment_id'))?->due_value) }}',
                                    dueDate: '{{ old('received_date_actual', $payments->find(session('error_payment_id'))?->due_date?->format('Y-m-d')) }}',
                                    currency: '{{ old('currency_received_actual', $payments->find(session('error_payment_id'))?->currency) }}'
                                }
                            }));
                        });
                    @endif
                });
            </script>
         @endif
    </div>
</div>