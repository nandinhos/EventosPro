{{-- ... (Props) ... --}}
@php
    // ...
    // Garante defaults corretos para NOVO pagamento
    $currentDueDate = old('due_date', $payment->due_date?->format('Y-m-d') ?? today()->format('Y-m-d'));
    $currentDueValue = old('due_value', $payment->due_value); // Sem default numérico aqui, validação cuida
    $currentCurrency = old('currency', $payment->currency ?? $gig->currency); // Usa moeda da gig como fallback
    $currentExchangeRate = old('exchange_rate', $payment->exchange_rate);
    $currentNotes = old('notes', $payment->notes);
@endphp

{{-- Linha 1: Valor DEVIDO, Moeda, Data VENCIMENTO --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
        <label for="{{ $prefix }}_due_value" class="block text-xs font-medium ...">Valor Devido <span class="text-red-500">*</span></label>
        <input type="number" step="0.01" min="0.01" name="due_value" id="{{ $prefix }}_due_value" required
               value="{{ $currentDueValue }}" {{-- Usa variável PHP --}}
               class="w-full text-sm ... @error('due_value') border-red-500 @enderror">
        @error('due_value') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="{{ $prefix }}_currency" class="block text-xs font-medium ...">Moeda <span class="text-red-500">*</span></label>
        <select name="currency" id="{{ $prefix }}_currency" required class="w-full text-sm ... @error('currency') border-red-500 @enderror">
            <option value="BRL" @selected($currentCurrency == 'BRL')>BRL</option>
            <option value="USD" @selected($currentCurrency == 'USD')>USD</option>
            <option value="EUR" @selected($currentCurrency == 'EUR')>EUR</option>
            <option value="GPB" @selected($currentCurrency == 'GPB')>GBP</option>
        </select>
        @error('currency') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="{{ $prefix }}_due_date" class="block text-xs font-medium ...">Data Vencimento <span class="text-red-500">*</span></label>
        <input type="date" name="due_date" id="{{ $prefix }}_due_date" required
               value="{{ $currentDueDate }}" {{-- Usa variável PHP --}}
               class="w-full text-sm ... @error('due_date') border-red-500 @enderror">
         @error('due_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </div>
</div>

{{-- Linha 2: Câmbio e Notas --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label for="{{ $prefix }}_exchange_rate" class="block text-xs font-medium ...">Taxa Câmbio (se moeda != BRL)</label>
        <input type="number" step="0.000001" name="exchange_rate" id="{{ $prefix }}_exchange_rate"
               value="{{ $currentExchangeRate }}" {{-- Usa variável PHP --}}
               placeholder="Taxa p/ valor devido"
               class="w-full text-sm ... @error('exchange_rate') border-red-500 @enderror">
         @error('exchange_rate') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </div>
    <div>
        <label for="{{ $prefix }}_notes" class="block text-xs font-medium ...">Notas (Opcional)</label>
        <textarea name="notes" id="{{ $prefix }}_notes" rows="1"
                  class="w-full text-sm ... @error('notes') border-red-500 @enderror"
        >{{ $currentNotes }}</textarea> {{-- Usa variável PHP --}}
         @error('notes') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </div>
</div>