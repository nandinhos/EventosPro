{{-- resources/views/settlements/_settle_booker_modal.blade.php --}}
{{-- Espera $gig e $commissionAmount (valor da comissão do booker) --}}
<div x-data="{
        showModal: false,
        gigId: null,
        bookerName: '',
        commissionAmount: 0,
        formData: {
            booker_commission_date: '{{ today()->format('Y-m-d') }}',
            booker_commission_value_paid: '',
            booker_commission_notes: '',
            booker_commission_proof_file: null
        }
    }"
     @open-settle-booker-modal.window="
        showModal = true;
        gigId = $event.detail.gigId;
        bookerName = $event.detail.bookerName;
        commissionAmount = parseFloat($event.detail.commissionAmount) || 0;
        formData.booker_commission_value_paid = commissionAmount.toFixed(2);
        formData.booker_commission_date = '{{ today()->format('Y-m-d') }}';
        formData.booker_commission_notes = '';
        formData.booker_commission_proof_file = null;
        $nextTick(() => $refs.booker_commission_date_input?.focus());
     "
      @if(session('open_modal') === 'settleBooker' && $errors->settleBooker->any())
        x-init="showModal = true; gigId = {{ $gig->id }}; bookerName='{{ $gig->booker?->name }}'; commissionAmount={{ old('booker_commission_value_paid', $gig->booker_commission_value ?? 0) }};"
     @endif
     x-show="showModal"
     @keydown.escape.window="showModal = false"
     class="fixed inset-0 z-[99] overflow-y-auto flex items-center justify-center backdrop-blur-sm" style="display: none;">

    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity duration-300" @click="showModal = false"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-auto shadow-2xl p-6 transform transition-all duration-300 ease-out" @click.away="showModal = false">
        <div class="flex justify-between items-center pb-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white leading-6">Registrar Pagamento ao Booker: <span x-text="bookerName" class="text-blue-600 dark:text-blue-400"></span></h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 my-3 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md">Valor da Comissão: <strong x-text="`R$ ${commissionAmount.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2})}`" class="text-gray-800 dark:text-white"></strong></p>
        <form :action="`/gigs/${gigId}/settle-booker`" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label for="booker_commission_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Pagamento ao Booker <span class="text-red-500">*</span></label>
                <input type="date" name="booker_commission_date" id="booker_commission_date" x-model="formData.booker_commission_date" x-ref="booker_commission_date_input" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 transition duration-200 @error('booker_commission_date', 'settleBooker') border-red-500 @enderror">
                @error('booker_commission_date', 'settleBooker') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="booker_commission_value_paid" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor Efetivamente Pago <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" name="booker_commission_value_paid" id="booker_commission_value_paid" x-model="formData.booker_commission_value_paid" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 transition duration-200 @error('booker_commission_value_paid', 'settleBooker') border-red-500 @enderror">
                @error('booker_commission_value_paid', 'settleBooker') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="booker_commission_proof_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comprovante (PDF, JPG, PNG - Max 2MB)</label>
                <input type="file" name="booker_commission_proof_file" id="booker_commission_proof_file" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 transition duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-600 dark:file:text-gray-200 @error('booker_commission_proof_file', 'settleBooker') border-red-500 @enderror">
                @error('booker_commission_proof_file', 'settleBooker') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="booker_commission_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas Adicionais</label>
                <textarea name="booker_commission_notes" id="booker_commission_notes" x-model="formData.booker_commission_notes" rows="2" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 transition duration-200"></textarea>
            </div>
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700 mt-4">
                <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-md transition-colors duration-200">Cancelar</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Registrar Pagamento</button>
            </div>
        </form>
    </div>
</div>