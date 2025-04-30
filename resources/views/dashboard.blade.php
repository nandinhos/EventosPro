{{-- resources/views/dashboard.blade.php --}}

<x-app-layout> {{-- Extende o layout que acabamos de refatorar --}}
    {{-- O conteúdo daqui será injetado no $slot do layouts/app.blade.php --}}
    {{-- E será exibido/ocultado pelo x-show="currentPage === 'dashboard'" --}}

    <div x-show="$store.layout.currentPage === 'dashboard'"> {{-- Acessa currentPage via store --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            {{-- Seus cards de métricas aqui --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                <div class="flex items-center">
                    <div class="bg-primary-100 dark:bg-primary-900/30 p-3 rounded-lg">
                        <i class="fas fa-file-contract text-primary-600 dark:text-primary-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Contratos Ativos</h3>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">32</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center">
                    <span class="text-green-500 text-sm font-medium flex items-center">
                        <i class="fas fa-arrow-up mr-1"></i> 12%
                    </span>
                    <span class="text-gray-500 dark:text-gray-400 text-sm ml-2">vs. mês anterior</span>
                </div>
            </div>

            {{-- Outros cards aqui --}}
             <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                 <div class="flex items-center">
                     <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-lg">
                         <i class="fas fa-money-bill-wave text-green-600 dark:text-green-400 text-xl"></i>
                     </div>
                     <div class="ml-4">
                         <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Receita do Mês</h3>
                         <p class="text-2xl font-bold text-gray-800 dark:text-white">R$ 187.500</p>
                     </div>
                 </div>
                 <div class="mt-4 flex items-center">
                     <span class="text-green-500 text-sm font-medium flex items-center">
                         <i class="fas fa-arrow-up mr-1"></i> 8%
                     </span>
                     <span class="text-gray-500 dark:text-gray-400 text-sm ml-2">vs. mês anterior</span>
                 </div>
             </div>

             <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                 <div class="flex items-center">
                     <div class="bg-yellow-100 dark:bg-yellow-900/30 p-3 rounded-lg">
                         <i class="fas fa-calendar-alt text-yellow-600 dark:text-yellow-400 text-xl"></i>
                     </div>
                     <div class="ml-4">
                         <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Eventos Planejados</h3>
                         <p class="text-2xl font-bold text-gray-800 dark:text-white">18</p>
                     </div>
                 </div>
                 <div class="mt-4 flex items-center">
                     <span class="text-yellow-500 text-sm font-medium flex items-center">
                         <i class="fas fa-equals mr-1"></i> 0%
                     </span>
                     <span class="text-gray-500 dark:text-gray-400 text-sm ml-2">vs. mês anterior</span>
                 </div>
             </div>

             <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                 <div class="flex items-center">
                     <div class="bg-red-100 dark:bg-red-900/30 p-3 rounded-lg">
                         <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                     </div>
                     <div class="ml-4">
                         <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pagamentos Atrasados</h3>
                         <p class="text-2xl font-bold text-gray-800 dark:text-white">3</p>
                     </div>
                 </div>
                 <div class="mt-4 flex items-center">
                     <span class="text-red-500 text-sm font-medium flex items-center">
                         <i class="fas fa-arrow-down mr-1"></i> 25%
                     </span>
                     <span class="text-gray-500 dark:text-gray-400 text-sm ml-2">vs. mês anterior</span>
                 </div>
             </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
             {{-- Seus gráficos aqui --}}
             <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                 <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Receita Mensal</h3>
                 <div class="h-80">
                     {{-- x-init chama initializeCharts quando este elemento aparece --}}
                     {{-- Certifique-se de que a função initializeCharts está no escopo global (no <script> do app.blade.php) --}}
                     <canvas id="revenueChart" x-init="$nextTick(() => initializeCharts())"></canvas>
                 </div>
             </div>

             <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                 <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Eventos por Tipo</h3>
                 <div class="h-80">
                      {{-- x-init chama initializeCharts quando este elemento aparece --}}
                     <canvas id="eventsChart" x-init="$nextTick(() => initializeCharts())"></canvas>
                 </div>
             </div>
         </div>

         <div class="grid grid-cols-1 gap-6">
             {{-- Sua tabela de próximos eventos aqui --}}
             <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                 <div class="flex justify-between items-center mb-4">
                     <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Próximos Eventos</h3>
                     <a href="#" class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 text-sm font-medium">Ver Todos</a>
                 </div>
                 <div class="overflow-x-auto">
                     <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                         <thead>
                             <tr>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Evento</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Artista</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Local</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                             </tr>
                         </thead>
                         <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                             <tr>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="text-sm font-medium text-gray-900 dark:text-white">Festival de Verão</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="text-sm text-gray-700 dark:text-gray-300">Banda Estrela</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="text-sm text-gray-700 dark:text-gray-300">Praia Central</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="text-sm text-gray-700 dark:text-gray-300">15/12/2023</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">Confirmado</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap text-sm">
                                     <div class="flex space-x-2">
                                         <button class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                             <i class="fas fa-eye"></i>
                                         </button>
                                         <button class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                                             <i class="fas fa-edit"></i>
                                         </button>
                                     </div>
                                 </td>
                             </tr>
                             <tr>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="text-sm font-medium text-gray-900 dark:text-white">Show Beneficente</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="text-sm text-gray-700 dark:text-gray-300">Cantora Lua</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="text-sm text-gray-700 dark:text-gray-300">Teatro Municipal</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="text-sm text-gray-700 dark:text-gray-300">20/12/2023</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">Pendente</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap text-sm">
                                     <div class="flex space-x-2">
                                         <button class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                             <i class="fas fa-eye"></i>
                                         </button>
                                         <button class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                                             <i class="fas fa-edit"></i>
                                         </button>
                                     </div>
                                 </td>
                             </tr>
                             <tr>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="text-sm font-medium text-gray-900 dark:text-white">Festa Corporativa</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="text-sm text-gray-700 dark:text-gray-300">DJ Mixer</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="text-sm text-gray-700 dark:text-gray-300">Hotel Luxo</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="text-sm text-gray-700 dark:text-gray-300">28/12/2023</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap">
                                     <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">Confirmado</span>
                                 </td>
                                 <td class="px-6 py-4 whitespace-nowrap text-sm">
                                     <div class="flex space-x-2">
                                         <button class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                             <i class="fas fa-eye"></i>
                                         </button>
                                         <button class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                                             <i class="fas fa-edit"></i>
                                         </button>
                                     </div>
                                 </td>
                             </tr>
                         </tbody>
                     </table>
                 </div>
             </div>
         </div>
    </div>
</x-app-layout>