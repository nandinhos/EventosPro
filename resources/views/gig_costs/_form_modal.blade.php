{{-- resources/views/gig_costs/_form_modal.blade.php --}}
{{--
    Este é o MODAL que contém o formulário para Adicionar ou Editar uma Despesa.
    Ele é controlado pelo Alpine.js do seu componente pai (_show_costs.blade.php).
--}}
<div x-show="showCostFormModal"
     class="fixed inset-0 z-[99] overflow-y-auto flex items-center justify-center"
     style="display: none;"
     x-trap.inert.noscroll="showCostFormModal">
    
    {{-- Overlay --}}
    <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" @click="showCostFormModal = false"></div>

    {{-- Conteúdo do Modal --}}
    <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-lg w-full mx-auto shadow-xl p-6" @click.away="showCostFormModal = false">
        <div class="flex justify-between items-center pb-3 border-b dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white" x-text="isEditMode ? 'Editar Despesa' : 'Adicionar Nova Despesa'"></h3>
            <button @click="showCostFormModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form @submit.prevent="submitCostForm()" class="space-y-4 mt-4">
            {{--
                Aqui incluímos APENAS os campos do formulário.
                O arquivo _form_fields_modal.blade.php contém estes campos.
            --}}
            @include('gig_costs._form_fields_modal')
            
            <div class="flex justify-end space-x-3 pt-4 border-t dark:border-gray-700 mt-4">
                <button type="button" @click="showCostFormModal = false" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">Cancelar</button>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm">
                    <span x-text="isEditMode ? 'Atualizar Despesa' : 'Salvar Despesa'"></span>
                </button>
            </div>
        </form>
    </div>
</div>