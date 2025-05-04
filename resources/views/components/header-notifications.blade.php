{{-- resources/views/components/header-notifications.blade.php --}}

{{-- Este componente usa Alpine.js para o dropdown --}}
<div class="relative" x-data="{ notificationsOpen: false }">
    {{-- Botão do Sino --}}
    <button @click="notificationsOpen = !notificationsOpen" class="relative p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 rounded-full">
        <i class="fas fa-bell"></i>
        {{-- Badge de Contagem (Pode ser dinâmico no futuro) --}}
        <span class="absolute top-0 right-0 block h-2 w-2 transform -translate-y-1/2 translate-x-1/2 rounded-full bg-red-500 ring-2 ring-white dark:ring-gray-800"></span>
        {{-- Ou um número: --}}
        {{-- <span class="absolute top-0 right-0 -mt-1 -mr-1 bg-red-500 text-white w-4 h-4 text-xs flex items-center justify-center rounded-full">3</span> --}}
    </button>

    {{-- Dropdown de Notificações --}}
    <div x-show="notificationsOpen"
         @click.away="notificationsOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="absolute right-0 mt-2 w-80 origin-top-right bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-20" {{-- z-20 para ficar sobre o conteúdo --}}
         style="display: none;" {{-- Evita flash inicial antes do Alpine controlar --}}
         >
        <div class="py-1">
            {{-- Cabeçalho do Dropdown --}}
            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Notificações</h3>
            </div>

            {{-- Lista de Notificações (Estático por enquanto) --}}
            <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-80 overflow-y-auto"> {{-- Altura máxima e scroll --}}
                {{-- Exemplo 1 --}}
                <a href="#" class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 bg-primary-500 rounded-full p-1.5"> {{-- Ajuste padding --}}
                            <i class="fas fa-money-bill-wave text-white text-xs fa-fw"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Pagamento recebido</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Gig #123 - R$ 5.000,00</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">10 minutos atrás</p>
                        </div>
                    </div>
                </a>
                {{-- Exemplo 2 --}}
                <a href="#" class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 bg-yellow-500 rounded-full p-1.5">
                            <i class="fas fa-exclamation-triangle text-white text-xs fa-fw"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Pagamento vencido</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Gig #98 - Vencimento: 01/05/2025</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">2 horas atrás</p>
                        </div>
                    </div>
                </a>
                 {{-- Exemplo 3 --}}
                <a href="#" class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 bg-blue-500 rounded-full p-1.5">
                            <i class="fas fa-calendar-check text-white text-xs fa-fw"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Nova Gig Agendada</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Artista XYZ - 15/06/2025</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">1 dia atrás</p>
                        </div>
                    </div>
                </a>
                {{-- Mensagem se não houver notificações --}}
                {{-- <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">Nenhuma nova notificação.</div> --}}
            </div>

            {{-- Rodapé do Dropdown --}}
            <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                <a href="#" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300">
                    Ver todas as notificações
                </a>
            </div>
        </div>
    </div>
</div>