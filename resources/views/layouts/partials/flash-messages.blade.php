{{-- resources/views/layouts/partials/flash-messages.blade.php --}}

@if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative dark:bg-green-900 dark:border-green-700 dark:text-green-200" role="alert">
        <strong class="font-bold"><i class="fas fa-check-circle mr-2"></i> Sucesso!</strong>
        <span class="block sm:inline">{{ session('success') }}</span>
        <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3 text-green-700 dark:text-green-200 hover:text-green-900 dark:hover:text-green-100">
             <i class="fas fa-times"></i>
        </button>
    </div>
@endif

@if (session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 8000)"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-90"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-90"
         class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative dark:bg-red-900 dark:border-red-700 dark:text-red-200" role="alert">
        <strong class="font-bold"><i class="fas fa-exclamation-triangle mr-2"></i> Erro!</strong>
        <span class="block sm:inline">{{ session('error') }}</span>
         <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3 text-red-700 dark:text-red-200 hover:text-red-900 dark:hover:text-red-100">
             <i class="fas fa-times"></i>
        </button>
    </div>
@endif

 {{-- Exibir erros de validação gerais (não específicos de campo) --}}
 @if ($errors->any() && !$errors->hasAny(array_keys($errors->getMessages()))) {{-- Exibe apenas se houver erros que NÃO estão associados a um campo específico --}}
     <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative dark:bg-red-900 dark:border-red-700 dark:text-red-200" role="alert">
        <strong class="font-bold">Ops! Algo deu errado.</strong>
         <ul class="mt-1 list-disc list-inside text-sm">
             @foreach ($errors->all() as $error)
                 <li>{{ $error }}</li>
             @endforeach
        </ul>
    </div>
@endif