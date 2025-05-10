{{-- resources/views/settlements/_settle_artist_modal.blade.php --}}
{{-- Espera $gig (para action da rota) e $artistPaymentValue (valor líquido estimado) --}}
<div x-data="{
        showModal: false,
        gigId: null,
        artistName: '',
        amountDue: 0,
        formData: {
            artist_payment_date: '{{ today()->format('Y-m-d') }}',
            artist_payment_value_paid: '',
            artist_payment_notes: '',
            artist_payment_proof_file: null
        }
    }"
     @open-settle-artist-modal.window="
        showModal = true;
        gigId = $event.detail.gigId;
        artistName = $event.detail.artistName;
        amountDue = parseFloat($event.detail.amountDue) || 0;
        formData.artist_payment_value_paid = amountDue.toFixed(2); // Preenche com valor devido
        formData.artist_payment_date = '{{ today()->format('Y-m-d') }}';
        formData.artist_payment_notes = '';
        formData.artist_payment_proof_file = null;
        $nextTick(() => $refs.artist_payment_date_input?.focus());
     "
     @if(session('open_modal') === 'settleArtist' && $errors->settleArtist->any())
        x-init="showModal = true; gigId = {{ $gig->id }}; artistName='{{ $gig->artist->name }}'; amountDue={{ old('artist_payment_value_paid', $gig->cache_value_brl - $gig->confirmed_expenses_total_brl - ($gig->agency_commission_value ?? 0)) }};"
     @endif
     x-show="showModal"
     @keydown.escape.window="showModal = false"
     class="fixed inset-0 z-[99] overflow-y-auto flex items-center justify-center backdrop-blur-sm" style="display: none;">

    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity duration-300" @click="showModal = false"></div>
    <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-auto shadow-2xl p-6 transform transition-all duration-300 ease-out" @click.away="showModal = false">
        <div class="flex justify-between items-center pb-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white leading-6">Registrar Pagamento ao Artista: <span x-text="artistName" class="text-blue-600 dark:text-blue-400"></span></h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 my-3 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md">Valor Líquido Estimado: <strong x-text="`R$ ${amountDue.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2})}`" class="text-gray-800 dark:text-white"></strong></p>
        <form :action="`/gigs/${gigId}/settle-artist`" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label for="artist_payment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Pagamento ao Artista <span class="text-red-500">*</span></label>
                <input type="date" name="artist_payment_date" id="artist_payment_date" x-model="formData.artist_payment_date" x-ref="artist_payment_date_input" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 transition duration-200 @error('artist_payment_date', 'settleArtist') border-red-500 @enderror">
                @error('artist_payment_date', 'settleArtist') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="artist_payment_value_paid" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor Efetivamente Pago <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" name="artist_payment_value_paid" id="artist_payment_value_paid" x-model="formData.artist_payment_value_paid" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 transition duration-200 @error('artist_payment_value_paid', 'settleArtist') border-red-500 @enderror">
                @error('artist_payment_value_paid', 'settleArtist') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="artist_payment_proof_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comprovante (PDF, JPG, PNG - Max 2MB)</label>
                <input type="file" name="artist_payment_proof_file" id="artist_payment_proof_file" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 transition duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-600 dark:file:text-gray-200 @error('artist_payment_proof_file', 'settleArtist') border-red-500 @enderror">
                @error('artist_payment_proof_file', 'settleArtist') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="artist_payment_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas Adicionais</label>
                <textarea name="artist_payment_notes" id="artist_payment_notes" x-model="formData.artist_payment_notes" rows="2" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 transition duration-200"></textarea>
            </div>
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700 mt-4">
                <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-md transition-colors duration-200">Cancelar</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Registrar Pagamento</button>
            </div>
        </form>
    </div>
</div>