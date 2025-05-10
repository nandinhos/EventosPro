{{-- resources/views/payments/_form.blade.php --}}
{{--
    Parcial do formulário para criar/editar uma PARCELA PREVISTA de pagamento.
    Recebe:
    - $payment (Objeto Payment: novo para create, existente para edit)
    - $gig (Objeto Gig pai)
    - $prefix (String opcional para prefixar IDs de campos, útil em modais/forms múltiplos)
    - $errorBag (String, nome do error bag para validação deste form específico)
--}}

<div class="space-y-3">
    {{-- Descrição --}}
    <div>
        <label for="{{ $prefix }}description" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">Descrição (Opcional)</label>
        <input type="text" name="description" id="{{ $prefix }}description" value="{{ old('description', $payment->description) }}"
               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('description', $errorBag) border-red-500 dark:border-red-600 @enderror">
        @error('description', $errorBag) <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Linha: Valor Devido e Vencimento --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="{{ $prefix }}due_value" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">Valor Devido <span class="text-red-500">*</span></label>
            <input type="number" step="0.01" name="due_value" id="{{ $prefix }}due_value" value="{{ old('due_value', $payment->due_value) }}" required
                   class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('due_value', $errorBag) border-red-500 dark:border-red-600 @enderror">
            @error('due_value', $errorBag) <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="{{ $prefix }}due_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">Data Vencimento <span class="text-red-500">*</span></label>
            <input type="date" name="due_date" id="{{ $prefix }}due_date" value="{{ old('due_date', $payment->due_date?->format('Y-m-d')) }}" required
                   class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('due_date', $errorBag) border-red-500 dark:border-red-600 @enderror">
            @error('due_date', $errorBag) <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Linha: Moeda e Câmbio --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="{{ $prefix }}currency" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">Moeda <span class="text-red-500">*</span></label>
            <select name="currency" id="{{ $prefix }}currency" required
                    class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('currency', $errorBag) border-red-500 dark:border-red-600 @enderror">
                <option value="BRL" @selected(old('currency', $payment->currency ?? $gig->currency ?? 'BRL') == 'BRL')>BRL</option>
                <option value="USD" @selected(old('currency', $payment->currency ?? $gig->currency) == 'USD')>USD</option>
                <option value="EUR" @selected(old('currency', $payment->currency ?? $gig->currency) == 'EUR')>EUR</option>
                <option value="GPB" @selected(old('currency', $payment->currency ?? $gig->currency) == 'GPB')>GBP</option>
            </select>
            @error('currency', $errorBag) <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="{{ $prefix }}exchange_rate" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">Câmbio Previsto (se não BRL)</label>
            <input type="number" step="0.000001" name="exchange_rate" id="{{ $prefix }}exchange_rate" value="{{ old('exchange_rate', $payment->exchange_rate) }}"
                   placeholder="Câmbio da data prevista"
                   class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('exchange_rate', $errorBag) border-red-500 dark:border-red-600 @enderror">
            @error('exchange_rate', $errorBag) <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Notas --}}
    <div>
        <label for="{{ $prefix }}notes" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">Notas (Opcional)</label>
        <textarea name="notes" id="{{ $prefix }}notes" rows="2"
                  class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('notes', $errorBag) border-red-500 dark:border-red-600 @enderror"
        >{{ old('notes', $payment->notes) }}</textarea>
        @error('notes', $errorBag) <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
</div>