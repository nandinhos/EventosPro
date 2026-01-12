{{-- resources/views/gigs/_show_payments.blade.php --}}
{{-- Recebe $gig e $payments (Coleção de objetos Payment relacionados à $gig) --}}

<div x-data="{ showPaymentForm: false }" class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mt-6">
    {{-- Header do Card --}}
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap justify-between items-center gap-2 bg-gray-50 dark:bg-gray-700/50">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Parcelas / Recebimentos</h3>
        {{-- Botão para mostrar/ocultar formulário de adicionar --}}
        <button type="button" @click="showPaymentForm = !showPaymentForm"
                class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
            <i class="fas fa-plus mr-1"></i> <span x-text="showPaymentForm ? 'Ocultar Formulário' : 'Adicionar Parcela Prevista'"></span>
        </button>
    </div>

    {{-- Formulário para Adicionar Nova Parcela Prevista (controlado pelo Alpine) --}}
    <div x-show="showPaymentForm" x-transition class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-b-md">
        <h4 class="text-md font-semibold mb-3 text-gray-700 dark:text-gray-200">Adicionar Parcela Prevista</h4>
        {{-- Formulário aponta para a rota store --}}
        <form action="{{ route('gigs.payments.store', $gig) }}" method="POST" class="space-y-4">
            @csrf
            {{-- Campos do formulário --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="payment_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição (Opcional)</label>
                    <input type="text" name="description" id="payment_description" value="{{ old('description', 'Parcela') }}" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    @error('description', 'paymentStore') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Vencimento <span class="text-red-500">*</span></label>
                    <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 @error('due_date', 'paymentStore') border-red-500 @enderror">
                    @error('due_date', 'paymentStore') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
             <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="due_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor Devido <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="due_value" id="due_value" value="{{ old('due_value') }}" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 @error('due_value', 'paymentStore') border-red-500 @enderror">
                    @error('due_value', 'paymentStore') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                 <div>
                    <label for="payment_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Moeda <span class="text-red-500">*</span></label>
                    <select name="currency" id="payment_currency" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 @error('currency', 'paymentStore') border-red-500 @enderror">
                         <option value="BRL" @selected(old('currency', 'BRL') == 'BRL')>BRL</option>
                         <option value="USD" @selected(old('currency') == 'USD')>USD</option>
                         <option value="EUR" @selected(old('currency') == 'EUR')>EUR</option>
                         <option value="GBP" @selected(old('currency') == 'GBP')>GBP</option>
                    </select>
                    @error('currency', 'paymentStore') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="payment_exchange_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Câmbio (se não BRL)</label>
                    <input type="number" step="0.000001" name="exchange_rate" id="payment_exchange_rate" value="{{ old('exchange_rate') }}" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 @error('exchange_rate', 'paymentStore') border-red-500 @enderror">
                     @error('exchange_rate', 'paymentStore') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
             <div>
                 <label for="payment_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas (Opcional)</label>
                 <textarea name="notes" id="payment_notes" rows="2" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 @error('notes', 'paymentStore') border-red-500 @enderror">{{ old('notes') }}</textarea>
                 @error('notes', 'paymentStore') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
             </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm">Salvar Parcela</button>
            </div>
        </form>
         {{-- Exibir erros específicos do formulário de adicionar pagamento --}}
        @if ($errors->paymentStore->any())
            <div class="mt-3 text-xs text-red-500">
                <strong>Ops! Algo deu errado ao salvar a parcela:</strong>
                <ul> @foreach ($errors->paymentStore->all() as $error) <li>{{ $error }}</li> @endforeach </ul>
            </div>
        @endif
    </div>

    {{-- Listagem de Pagamentos Existentes --}}
    <div class="p-6">
        @if($payments->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma parcela de pagamento prevista/recebida para esta Gig.</p>
        @else
            <ul class="space-y-3">
                @foreach($payments as $payment)
                    <li class="p-3 rounded-md border dark:border-gray-700 {{ $payment->confirmed_at ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700/50' : ($payment->due_date->isPast() ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700/50' : 'bg-gray-50 dark:bg-gray-800/50 border-gray-200') }}">
                        <div class="flex flex-wrap justify-between items-start text-sm gap-2">
                            {{-- Detalhes do Pagamento --}}
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

                             {{-- Ações --}}
                            <div class="flex items-center space-x-2 flex-shrink-0">
                                @if(!$payment->confirmed_at)
                                    {{-- Botão para abrir MODAL de Confirmação (precisa do modal incluso na página) --}}
                                    <button type="button"
                                            @click="$dispatch('open-confirm-payment-modal', {
                                                gigId: {{ $gig->id }},
                                                paymentId: {{ $payment->id }},
                                                dueValue: '{{ $payment->due_value }}',
                                                dueDate: '{{ $payment->due_date?->format('Y-m-d') }}',
                                                currency: '{{ $payment->currency }}',
                                                exchangeRate: '{{ $payment->exchange_rate }}' // Passa câmbio previsto
                                            })"
                                            title="Confirmar Recebimento" class="text-green-500 hover:text-green-700 dark:hover:text-green-400 p-1 focus:outline-none">
                                        <i class="fas fa-check-circle fa-fw"></i>
                                    </button>
                                    {{-- Link para editar parcela PREVISTA --}}
                                    <a href="{{ route('gigs.payments.edit', ['gig' => $gig, 'payment' => $payment]) }}" title="Editar Parcela Prevista" class="text-primary-500 hover:text-primary-700 dark:hover:text-primary-400 p-1 focus:outline-none"><i class="fas fa-edit fa-fw"></i></a>
                                @else
                                    {{-- Form para Desconfirmar --}}
                                    <form action="{{ route('gigs.payments.unconfirm', ['gig' => $gig, 'payment' => $payment]) }}" method="POST" class="inline" onsubmit="return confirm('Reverter confirmação deste pagamento?');">
                                        @csrf @method('PATCH')
                                        <button type="submit" title="Reverter Confirmação" class="text-yellow-500 hover:text-yellow-700 dark:hover:text-yellow-400 p-1 focus:outline-none"><i class="fas fa-undo-alt fa-fw"></i></button>
                                    </form>
                                    {{-- Desabilita Edição se confirmado --}}
                                    <span title="Edição desabilitada para pagamentos confirmados" class="text-gray-400 p-1 cursor-not-allowed opacity-50"><i class="fas fa-edit fa-fw"></i></span>
                                @endif
                                @if(!$payment->confirmed_at)
                                    {{-- Form para Excluir --}}
                                    <form action="{{ route('gigs.payments.destroy', ['gig' => $gig, 'payment' => $payment]) }}" method="POST" onsubmit="return confirm('Excluir esta parcela de pagamento?');" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Excluir Parcela" class="text-red-500 hover:text-red-700 dark:hover:text-red-400 p-1 focus:outline-none"><i class="fas fa-trash-alt fa-fw"></i></button>
                                    </form>
                                @else
                                    {{-- Botão de excluir desabilitado para pagamentos confirmados --}}
                                    <span title="Exclusão desabilitada para pagamentos confirmados" class="text-gray-400 p-1 cursor-not-allowed opacity-50"><i class="fas fa-trash-alt fa-fw"></i></span>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

{{-- Incluir o Modal de Confirmação em gigs.show ou app.blade.php --}}
{{-- @include('payments._confirm-modal') --}}