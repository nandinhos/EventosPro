{{-- Recebe $cost (GigCost), $gig, $costCenters, $formType ('create' ou 'edit') --}}
@props(['cost', 'gig', 'costCenters', 'formType'])

@php
    // Determina a action e o método do formulário
    // $actionUrl = ($formType === 'edit')
    //     ? route('gigs.costs.update', ['gig' => $gig, 'cost' => $cost])
    //     : route('gigs.costs.store', $gig);
    // $httpMethod = ($formType === 'edit') ? 'PUT' : 'POST';
@endphp

{{-- Os campos do formulário --}}
<div class="p-6 space-y-4">
    {{-- Centro de Custo --}}
    <div>
        <label for="cost_center_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Centro de Custo <span class="text-red-500">*</span></label>
        <select id="cost_center_id" name="cost_center_id" required
                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('cost_center_id') border-red-500 dark:border-red-600 @enderror">
            <option value="">Selecione...</option>
            @foreach($costCenters as $id => $name) {{-- Usa $costCenters passado pelo controller --}}
                <option value="{{ $id }}" @selected(old('cost_center_id', $cost->cost_center_id) == $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('cost_center_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Descrição --}}
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição</label>
        <input type="text" id="description" name="description" value="{{ old('description', $cost->description) }}"
               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('description') border-red-500 dark:border-red-600 @enderror">
        @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Valor e Moeda --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor <span class="text-red-500">*</span></label>
            <input type="number" step="0.01" id="value" name="value" value="{{ old('value', $cost->value) }}" required
                   class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('value') border-red-500 dark:border-red-600 @enderror">
            @error('value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Moeda <span class="text-red-500">*</span></label>
            <select id="currency" name="currency" required
                    class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('currency') border-red-500 dark:border-red-600 @enderror">
                <option value="BRL" @selected(old('currency', $cost->currency ?? 'BRL') == 'BRL')>BRL</option>
                <option value="USD" @selected(old('currency', $cost->currency) == 'USD')>USD</option>
                <option value="EUR" @selected(old('currency', $cost->currency) == 'EUR')>EUR</option>
                 <option value="GBP" @selected(old('currency', $cost->currency) == 'GBP')>GBP</option>
            </select>
            @error('currency') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Pagador --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="payer_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pagador <span class="text-red-500">*</span></label>
            <select id="payer_type" name="payer_type" required x-data="{ payerTypeField: '{{ old('payer_type', $cost->payer_type ?? 'agencia') }}' }" x-model="payerTypeField"
                    class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('payer_type') border-red-500 dark:border-red-600 @enderror">
                <option value="agencia">Agência</option>
                <option value="artista">Artista</option>
                <option value="cliente">Cliente</option>
                <option value="outro">Outro</option>
            </select>
            @error('payer_type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div x-show="payerTypeField === 'outro'">
            <label for="payer_details" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Detalhes do Pagador (se "Outro") <span x-show="payerTypeField === 'outro'" class="text-red-500">*</span></label>
            <input type="text" id="payer_details" name="payer_details" value="{{ old('payer_details', $cost->payer_details) }}"
                   :required="payerTypeField === 'outro'"
                   class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('payer_details') border-red-500 dark:border-red-600 @enderror">
            @error('payer_details') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

     {{-- Data da Despesa --}}
    <div>
        <label for="expense_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data da Despesa (Opcional)</label>
        <input type="date" id="expense_date" name="expense_date" value="{{ old('expense_date', $cost->expense_date?->format('Y-m-d')) }}"
               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('expense_date') border-red-500 dark:border-red-600 @enderror">
        @error('expense_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Notas --}}
    <div>
        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas (Opcional)</label>
        <textarea id="notes" name="notes" rows="2"
                  class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('notes') border-red-500 dark:border-red-600 @enderror"
        >{{ old('notes', $cost->notes) }}</textarea>
        @error('notes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
</div>