{{-- resources/views/artistas.blade.php --}}

<x-app-layout>
    <div x-show="$store.layout.currentPage === 'artistas'">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
             <div class="mb-6 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Gestão de Artistas</h3>
                 <button @click="$store.layout.openModal('novoArtista')" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md flex items-center">
                     <i class="fas fa-plus mr-2"></i> Novo Artista
                 </button>
             </div>
            <p class="text-gray-600 dark:text-gray-400">Esta seção permitirá gerenciar os artistas cadastrados no sistema.</p>
            {{-- A tabela de artistas virá aqui --}}
        </div>
         {{-- Modais específicos para Artistas podem ir aqui, dentro do x-show ou globalmente no app.blade --}}
         {{-- Se forem genéricos como o de exclusão, é melhor no app.blade --}}
    </div>
</x-app-layout>
{{-- Aqui você pode adicionar o modal de novo artista --}}