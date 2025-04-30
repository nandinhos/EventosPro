{{-- resources/views/layouts/app.blade.php --}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'EventosPro') }}</title> {{-- Pode mudar o app.name no .env --}}

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Script para inicializar o dark mode antes do Alpine --}}
    <script>
        // Check local storage first, then system preference
        const storedDarkMode = localStorage.getItem('darkMode');
        if (storedDarkMode === 'true' || (storedDarkMode === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Listen for system preference changes (optional, Alpine can handle this too)
        // window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
        //     if (event.matches) {
        //         document.documentElement.classList.add('dark');
        //     } else {
        //         document.documentElement.classList.remove('dark');
        //     }
        // });

        // Initialize Alpine.js state with stored dark mode preference
        // This is just setting the initial value. The toggle button updates localStorage.
        // The class on the <html> element is handled by the script above or Alpine's :class binding.
        document.addEventListener('alpine:init', () => {
            Alpine.data('layout', () => ({
                sidebarOpen: localStorage.getItem('sidebarOpen') === 'true' || true, // Default open, remember preference
                darkMode: document.documentElement.classList.contains('dark'), // Initialize from class on <html>
                activeTab: 'info', // Para modais/detalhes com tabs
                showModal: false,
                modalType: '',
                editingContract: null,
                currentPage: localStorage.getItem('currentPage') || 'dashboard', // Remember last page
                alertMessage: '',
                showAlert: false,
                selectedContract: null,
                contractDetailsOpen: false,

                // Watch for sidebarOpen changes and save to localStorage
                init() {
                    this.$watch('sidebarOpen', value => localStorage.setItem('sidebarOpen', value));
                    this.$watch('currentPage', value => localStorage.setItem('currentPage', value));
                    // Dark mode watch is on the button itself
                },

                // Método genérico para fechar modais
                closeModal() {
                    this.showModal = false;
                    this.modalType = '';
                    this.editingContract = null;
                    this.selectedContract = null; // Reset selected contract
                    this.contractDetailsOpen = false; // Close details modal too if open
                },

                // Método genérico para abrir modais
                openModal(type, data = null) {
                    this.modalType = type;
                    this.editingContract = data ? data.id : null; // Assume data has id for editing
                    // Load data for editing modal if needed here or in a dedicated function/component
                    this.showModal = true;
                },

                 // Método para abrir modal de detalhes de contrato
                openContractDetails(contractId) {
                     this.selectedContract = contractId;
                     this.contractDetailsOpen = true;
                     // Aqui você precisaria carregar os dados do contrato (info, pagamentos, eventos, etc.)
                     // Isso seria feito com fetch() para um endpoint Laravel
                     // Por enquanto, usaremos dados mockados/estáticos no modal de detalhes
                }
            }));
             // Re-initialize Charts when currentPage changes to dashboard
             Alpine.effect(() => {
                 if (Alpine.store('layout').currentPage === 'dashboard') {
                    // Needs to be done after the element is rendered, using $nextTick
                    Alpine.nextTick(() => {
                        initializeCharts(); // Call the function to initialize charts
                    });
                 }
             });
        });

        // Separate function for Chart.js initialization
        function initializeCharts() {
             // Certifique-se que o canvas existe antes de inicializar
             const ctxRevenue = document.getElementById('revenueChart');
             if (ctxRevenue) {
                 // Destroy existing chart if it exists
                 if (ctxRevenue.chart) {
                    ctxRevenue.chart.destroy();
                 }
                 ctxRevenue.chart = new Chart(ctxRevenue.getContext('2d'), {
                     type: 'line',
                     data: {
                         labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                         datasets: [{
                             label: 'Receita (R$)',
                             data: [120000, 135000, 142000, 155000, 132000, 148000, 160000, 175000, 182000, 187500, 0, 0],
                             backgroundColor: 'rgba(93, 92, 222, 0.2)',
                             borderColor: 'rgba(93, 92, 222, 1)',
                             borderWidth: 2,
                             tension: 0.3,
                             pointBackgroundColor: 'rgba(93, 92, 222, 1)',
                         }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         scales: {
                             y: {
                                 beginAtZero: true,
                                 ticks: {
                                     callback: function(value) {
                                         return 'R$ ' + value.toLocaleString();
                                     }
                                 }
                             }
                         },
                         plugins: {
                             tooltip: {
                                 callbacks: {
                                     label: function(context) {
                                         const value = context.raw;
                                         return 'R$ ' + value.toLocaleString();
                                     }
                                 }
                             }
                         }
                     }
                 });
             }


             const ctxEvents = document.getElementById('eventsChart');
             if (ctxEvents) {
                 // Destroy existing chart if it exists
                  if (ctxEvents.chart) {
                    ctxEvents.chart.destroy();
                 }
                 ctxEvents.chart = new Chart(ctxEvents.getContext('2d'), {
                     type: 'doughnut',
                     data: {
                         labels: ['Shows', 'Festas', 'Eventos Corporativos', 'Festivais'],
                         datasets: [{
                             data: [12, 8, 5, 3],
                             backgroundColor: [
                                 'rgba(93, 92, 222, 0.8)',
                                 'rgba(255, 99, 132, 0.8)',
                                 'rgba(255, 206, 86, 0.8)',
                                 'rgba(75, 192, 192, 0.8)',
                             ],
                             borderColor: [
                                 'rgba(93, 92, 222, 1)',
                                 'rgba(255, 99, 132, 1)',
                                 'rgba(255, 206, 86, 1)',
                                 'rgba(75, 192, 192, 1)',
                             ],
                             borderWidth: 1
                         }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         plugins: {
                             legend: {
                                 position: 'bottom'
                             }
                         }
                     }
                 });
             }
        }

         // Initialize charts on first page load if dashboard is the initial page
        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('currentPage') === 'dashboard' || !localStorage.getItem('currentPage')) {
                 initializeCharts();
            }
        });
    </script>

</head>
{{-- O estado global do layout vive no body com x-data="layout()" --}}
<body class="font-sans antialiased bg-gray-50 min-h-screen"
      x-data="layout()"
      :class="{ 'dark': darkMode }">

    <!-- Alerta de sucesso/erro -->
    <div x-show="showAlert"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-md shadow-lg z-50 flex items-center"
         x-init="
            $watch('showAlert', value => {
                if (value) {
                    setTimeout(() => { showAlert = false }, 3000);
                }
            })
         ">
        <i class="fas fa-check-circle mr-2"></i>
        <span x-text="alertMessage"></span>
        <button @click="showAlert = false" class="ml-4 text-white">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Container principal flex -->
    <div class="flex h-screen"> {{-- h-screen para garantir que o flex container ocupe a altura total --}}

        <!-- Sidebar Component -->
        <x-sidebar/>

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto bg-gray-100 dark:bg-gray-900">
            <!-- Cabeçalho do Conteúdo Principal -->
            {{-- O cabeçalho agora faz parte da área de conteúdo rolavel --}}
            <header class="bg-white dark:bg-gray-800 shadow-md sticky top-0 z-10"> {{-- sticky para fixar o header no topo --}}
                <div class="flex justify-between items-center h-16 px-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white"
                        x-text="currentPage === 'dashboard' ? 'Dashboard' :
                               currentPage === 'contratos' ? 'Gestão de Contratos' :
                               currentPage === 'artistas' ? 'Gestão de Artistas' :
                               currentPage === 'bookers' ? 'Gestão de Bookers' :
                               currentPage === 'locais' ? 'Gestão de Locais' :
                               currentPage === 'eventos' ? 'Gestão de Eventos' :
                               currentPage === 'pagamentos' ? 'Gestão de Pagamentos' :
                               currentPage === 'relatorios' ? 'Relatórios' :
                               currentPage === 'projecoes' ? 'Projeções Futuras' :
                               currentPage === 'usuarios' ? 'Gestão de Usuários' : ''">
                    </h2>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            {{-- A pesquisa pode ser implementada globalmente ou por página --}}
                            <input type="text" class="w-64 rounded-full border border-gray-300 dark:border-gray-600 py-2 px-4 pl-10 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100" placeholder="Pesquisar...">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400"></i>
                        </div>
                        {{-- Componente de Notificações - pode ser refatorado depois --}}
                        <div class="relative" x-data="{ notificationsOpen: false }">
                            <button @click="notificationsOpen = !notificationsOpen" class="relative p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
                                <i class="fas fa-bell"></i>
                                <span class="absolute top-0 right-0 bg-red-500 text-white w-4 h-4 text-xs flex items-center justify-center rounded-full">3</span>
                            </button>
                            <div x-show="notificationsOpen"
                                 @click.away="notificationsOpen = false"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-200"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-md shadow-lg overflow-hidden z-10">
                                <div class="py-2">
                                    <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Notificações</h3>
                                    </div>
                                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <a href="#" class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 bg-primary-500 rounded-full p-1">
                                                    <i class="fas fa-money-bill-wave text-white text-xs"></i>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Pagamento recebido</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Contrato #1023 - R$ 5.000,00</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">10 minutos atrás</p>
                                                </div>
                                            </div>
                                        </a>
                                        <a href="#" class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 bg-yellow-500 rounded-full p-1">
                                                    <i class="fas fa-exclamation-triangle text-white text-xs"></i>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Pagamento atrasado</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Contrato #982 - Vencimento: 28/05/2023</p> {{-- Corrigir data --}}
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">2 horas atrás</p>
                                                </div>
                                            </div>
                                        </a>
                                        <a href="#" class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 bg-green-500 rounded-full p-1">
                                                    <i class="fas fa-file-contract text-white text-xs"></i>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Novo contrato</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Contrato #1024 - Festival de Verão</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">1 dia atrás</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                                        <a href="#" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300">Ver todas as notificações</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="#" class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
                            <i class="fas fa-cog"></i>
                        </a>
                         {{-- Botão de Logout - Adicionado para ter logout fácil --}}
                         <form method="POST" action="{{ route('logout') }}">
                             @csrf
                             <button type="submit" class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full" title="Sair">
                                 <i class="fas fa-sign-out-alt"></i>
                             </button>
                         </form>
                    </div>
                </div>
            </header>

            <!-- Conteúdo Dinâmico da Página -->
            <div class="p-6">
                 {{-- Aqui é onde o conteúdo de cada página será injetado via $slot --}}
                 {{-- Cada página (dashboard, contratos, etc.) terá seu próprio x-show --}}
                 {{ $slot }}
            </div>
        </main>

    </div>

    {{-- Modais - Colocados fora do flex container principal para cobrir toda a tela --}}

    <!-- Modal para Contratos -->
    <div x-show="showModal"
         class="fixed inset-0 overflow-y-auto z-50 flex items-center justify-center"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModal()"></div> {{-- Usar closeModal --}}

        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full mx-auto shadow-xl overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95">

            <!-- Modal de Novo Contrato -->
            <div x-show="modalType === 'novoContrato'">
                <div class="bg-primary-600 text-white px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-medium">Novo Contrato</h3>
                    <button @click="closeModal()" class="text-white hover:text-gray-200"> {{-- Usar closeModal --}}
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select id="status" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                                <option value="emVigor">Em Vigor</option>
                                <option value="cancelado">Cancelado</option>
                                <option value="pendente">Pendente</option>
                                <option value="concluido">Concluído</option>
                            </select>
                        </div>
                        <div>
                            <label for="artista" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Artista</label>
                            {{-- Estes selects serão preenchidos dinamicamente com dados do backend --}}
                            <select id="artista" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                                <option value="">Selecione um artista</option>
                                <option value="1">Banda Estrela</option>
                                <option value="2">Cantora Lua</option>
                                <option value="3">DJ Mixer</option>
                            </select>
                        </div>
                        <div>
                            <label for="booker" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Booker</label>
                            <select id="booker" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                                <option value="">Selecione um booker</option>
                                <option value="1">João Silva</option>
                                <option value="2">Maria Oliveira</option>
                                <option value="3">Carlos Santos</option>
                            </select>
                        </div>
                        <div>
                            <label for="local" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Local</label>
                            <select id="local" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                                <option value="">Selecione um local</option>
                                <option value="1">Praia Central</option>
                                <option value="2">Teatro Municipal</option>
                                <option value="3">Hotel Luxo</option>
                            </select>
                        </div>
                        <div>
                            <label for="dataContrato" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data do Contrato</label>
                            <input type="date" id="dataContrato" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label for="valorContrato" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor do Contrato (R$)</label>
                            <input type="number" id="valorContrato" step="0.01" min="0" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                        </div>
                         <div>
                            <label for="dataEvento" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data do Evento</label>
                            <input type="date" id="dataEvento" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                        </div>
                         <div>
                            <label for="horaEvento" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hora do Evento</label>
                            <input type="time" id="horaEvento" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                        </div>
                        <div class="md:col-span-2">
                            <label for="arquivoContrato" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Arquivo do Contrato (Opcional)</label>
                            <input type="file" id="arquivoContrato" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 py-2 px-3">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="closeModal()" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md mr-2">
                            Cancelar
                        </button>
                        {{-- O @click aqui apenas simula o sucesso. A lógica real de save virá depois --}}
                        <button type="button" @click="closeModal(); alertMessage = 'Contrato cadastrado com sucesso!'; showAlert = true" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Modal de Editar Contrato -->
            <div x-show="modalType === 'editarContrato'">
                <div class="bg-primary-600 text-white px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-medium">Editar Contrato #<span x-text="editingContract"></span></h3>
                    <button @click="closeModal()" class="text-white hover:text-gray-200"> {{-- Usar closeModal --}}
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                 {{-- O formulário de edição seria similar ao de novo, pré-preenchido --}}
                 <form class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="statusEdit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select id="statusEdit" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                                <option value="emVigor" selected>Em Vigor</option>
                                <option value="cancelado">Cancelado</option>
                                <option value="pendente">Pendente</option>
                                <option value="concluido">Concluído</option>
                            </select>
                        </div>
                        <div>
                            <label for="artistaEdit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Artista</label>
                            <select id="artistaEdit" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                                <option value="1" selected>Banda Estrela</option>
                                <option value="2">Cantora Lua</option>
                                <option value="3">DJ Mixer</option>
                            </select>
                        </div>
                        <div>
                            <label for="bookerEdit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Booker</label>
                            <select id="bookerEdit" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                                <option value="1" selected>João Silva</option>
                                <option value="2">Maria Oliveira</option>
                                <option value="3">Carlos Santos</option>
                            </select>
                        </div>
                        <div>
                            <label for="localEdit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Local</label>
                            <select id="localEdit" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                                <option value="1" selected>Praia Central</option>
                                <option value="2">Teatro Municipal</option>
                                <option value="3">Hotel Luxo</option>
                            </select>
                        </div>
                        <div>
                            <label for="dataContratoEdit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data do Contrato</label>
                            <input type="date" id="dataContratoEdit" value="2023-10-01" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label for="valorContratoEdit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor do Contrato (R$)</label>
                            <input type="number" id="valorContratoEdit" value="50000" step="0.01" min="0" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                        </div>
                         <div>
                            <label for="dataEventoEdit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data do Evento</label>
                            <input type="date" id="dataEventoEdit" value="2023-12-15" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                        </div>
                         <div>
                            <label for="horaEventoEdit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hora do Evento</label>
                            <input type="time" id="horaEventoEdit" value="20:00" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                        </div>
                        <div class="md:col-span-2">
                            <label for="arquivoContratoEdit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Arquivo do Contrato (Opcional)</label>
                            <div class="flex items-center">
                                <input type="file" id="arquivoContratoEdit" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 py-2 px-3">
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Arquivo atual: contrato_1024.pdf</p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="closeModal()" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md mr-2">
                            Cancelar
                        </button>
                         {{-- O @click aqui apenas simula o sucesso --}}
                        <button type="button" @click="closeModal(); alertMessage = 'Contrato atualizado com sucesso!'; showAlert = true" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md">
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>

            <!-- Modal de Excluir Contrato -->
            <div x-show="modalType === 'excluirContrato'">
                <div class="bg-red-600 text-white px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-medium">Confirmar Exclusão</h3>
                    <button @click="closeModal()" class="text-white hover:text-gray-200"> {{-- Usar closeModal --}}
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="mb-6">
                        <p class="text-gray-700 dark:text-gray-300">Tem certeza que deseja excluir este contrato? Esta ação não pode ser desfeita.</p>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" @click="closeModal()" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md mr-2">
                            Cancelar
                        </button>
                         {{-- O @click aqui apenas simula o sucesso --}}
                        <button type="button" @click="closeModal(); alertMessage = 'Contrato excluído com sucesso!'; showAlert = true" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                            Sim, Excluir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes do Contrato -->
     <div x-show="contractDetailsOpen"
          class="fixed inset-0 overflow-y-auto z-50 flex items-center justify-center"
          x-transition:enter="transition ease-out duration-300"
          x-transition:enter-start="opacity-0"
          x-transition:enter-end="opacity-100"
          x-transition:leave="transition ease-in duration-200"
          x-transition:leave-start="opacity-100"
          x-transition:leave-end="opacity-0">
         <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModal()"></div> {{-- Usar closeModal --}}

         <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-4xl w-full mx-auto shadow-xl overflow-hidden"
              x-transition:enter="transition ease-out duration-300"
              x-transition:enter-start="opacity-0 transform scale-95"
              x-transition:enter-end="opacity-100 transform scale-100"
              x-transition:leave="transition ease-in duration-200"
              x-transition:leave-start="opacity-100 transform scale-100"
              x-transition:leave-end="opacity-0 transform scale-95">

             <div class="bg-primary-600 text-white px-6 py-4 flex justify-between items-center">
                 <h3 class="text-lg font-medium">Detalhes do Contrato #<span x-text="selectedContract"></span></h3>
                 <button @click="closeModal()" class="text-white hover:text-gray-200"> {{-- Usar closeModal --}}
                     <i class="fas fa-times"></i>
                 </button>
             </div>

             <div class="p-6">
                 {{-- activeTab está no x-data principal agora --}}
                 <div class="mb-6">
                     <div class="border-b border-gray-200 dark:border-gray-700">
                         <nav class="-mb-px flex space-x-8">
                             <button @click="activeTab = 'info'"
                                     :class="activeTab === 'info' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                                     class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                 Informações Gerais
                             </button>
                             <button @click="activeTab = 'pagamentos'"
                                     :class="activeTab === 'pagamentos' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                                     class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                 Pagamentos
                             </button>
                             <button @click="activeTab = 'eventos'"
                                     :class="activeTab === 'eventos' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                                     class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                 Eventos
                             </button>
                             <button @click="activeTab = 'arquivos'"
                                     :class="activeTab === 'arquivos' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                                     class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                 Arquivos
                             </button>
                             <button @click="activeTab = 'historico'"
                                     :class="activeTab === 'historico' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                                     class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                 Histórico
                             </button>
                         </nav>
                     </div>

                     <!-- Conteúdo das Tabs -->
                     <div class="mt-6">
                         <!-- Informações Gerais -->
                         <div x-show="activeTab === 'info'">
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                 <div class="border dark:border-gray-700 rounded-lg p-4">
                                     <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Informações do Contrato</h4>
                                     <div class="space-y-3">
                                         <div class="flex justify-between">
                                             <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                                             <span class="text-sm font-medium text-green-600 dark:text-green-400">Em Vigor</span>
                                         </div>
                                         <div class="flex justify-between">
                                             <span class="text-sm text-gray-600 dark:text-gray-400">Data de Assinatura:</span>
                                             <span class="text-sm font-medium text-gray-900 dark:text-white">01/10/2023</span>
                                         </div>
                                         <div class="flex justify-between">
                                             <span class="text-sm text-gray-600 dark:text-gray-400">Valor do Contrato:</span>
                                             <span class="text-sm font-medium text-gray-900 dark:text-white">R$ 50.000,00</span>
                                         </div>
                                     </div>
                                 </div>

                                 <div class="border dark:border-gray-700 rounded-lg p-4">
                                     <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Informações do Artista</h4>
                                     <div class="space-y-3">
                                         <div class="flex justify-between">
                                             <span class="text-sm text-gray-600 dark:text-gray-400">Nome:</span>
                                             <span class="text-sm font-medium text-gray-900 dark:text-white">Banda Estrela</span>
                                         </div>
                                         <div class="flex justify-between">
                                             <span class="text-sm text-gray-600 dark:text-gray-400">Contato:</span>
                                             <span class="text-sm font-medium text-gray-900 dark:text-white">bandaestrela@email.com</span>
                                         </div>
                                         <div class="flex justify-between">
                                             <span class="text-sm text-gray-600 dark:text-gray-400">Cachê Padrão:</span>
                                             <span class="text-sm font-medium text-gray-900 dark:text-white">R$ 45.000,00</span>
                                         </div>
                                     </div>
                                 </div>

                                 <div class="border dark:border-gray-700 rounded-lg p-4">
                                     <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Informações do Booker</h4>
                                     <div class="space-y-3">
                                         <div class="flex justify-between">
                                             <span class="text-sm text-gray-600 dark:text-gray-400">Nome:</span>
                                             <span class="text-sm font-medium text-gray-900 dark:text-white">João Silva</span>
                                         </div>
                                         <div class="flex justify-between">
                                             <span class="text-sm text-gray-600 dark:text-gray-400">Contato:</span>
                                             <span class="text-sm font-medium text-gray-900 dark:text-white">joao.silva@email.com</span>
                                         </div>
                                         <div class="flex justify-between">
                                             <span class="text-sm text-gray-600 dark:text-gray-400">Comissão:</span>
                                             <span class="text-sm font-medium text-gray-900 dark:text-white">10%</span>
                                         </div>
                                     </div>
                                 </div>

                                 <div class="border dark:border-gray-700 rounded-lg p-4">
                                     <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Informações do Local</h4>
                                     <div class="space-y-3">
                                         <div class="flex justify-between">
                                             <span class="text-sm text-gray-600 dark:text-gray-400">Nome:</span>
                                             <span class="text-sm font-medium text-gray-900 dark:text-white">Praia Central</span>
                                         </div>
                                         <div class="flex justify-between">
                                             <span class="text-sm text-gray-600 dark:text-gray-400">Endereço:</span>
                                             <span class="text-sm font-medium text-gray-900 dark:text-white">Av. Beira Mar, 1000</span>
                                         </div>
                                         <div class="flex justify-between">
                                             <span class="text-sm text-gray-600 dark:text-gray-400">Capacidade:</span>
                                             <span class="text-sm font-medium text-gray-900 dark:text-white">5.000 pessoas</span>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>

                         <!-- Pagamentos -->
                         <div x-show="activeTab === 'pagamentos'">
                             <div class="flex justify-between items-center mb-4">
                                 <h4 class="text-lg font-medium text-gray-900 dark:text-white">Pagamentos</h4>
                                 <button class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-1 rounded-md text-sm flex items-center">
                                     <i class="fas fa-plus mr-1"></i> Novo Pagamento
                                 </button>
                             </div>
                             <div class="overflow-x-auto">
                                 <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                     <thead class="bg-gray-50 dark:bg-gray-800">
                                         <tr>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data Vencimento</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valor</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data Pagamento</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                                         </tr>
                                     </thead>
                                     <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                         <tr>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-900 dark:text-white">#5001</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">01/11/2023</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">R$ 25.000,00</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">31/10/2023</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">Pago</span>
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
                                                 <span class="text-sm text-gray-900 dark:text-white">#5002</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">01/12/2023</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">R$ 25.000,00</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">-</span>
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
                                     </tbody>
                                 </table>
                             </div>
                         </div>

                         <!-- Eventos -->
                         <div x-show="activeTab === 'eventos'">
                             <div class="flex justify-between items-center mb-4">
                                 <h4 class="text-lg font-medium text-gray-900 dark:text-white">Eventos</h4>
                                 <button class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-1 rounded-md text-sm flex items-center">
                                     <i class="fas fa-plus mr-1"></i> Novo Evento
                                 </button>
                             </div>
                             <div class="overflow-x-auto">
                                 <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                     <thead class="bg-gray-50 dark:bg-gray-800">
                                         <tr>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nome</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Local</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                                         </tr>
                                     </thead>
                                     <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                         <tr>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-900 dark:text-white">#3001</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">Festival de Verão</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">15/12/2023</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">Show</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">Praia Central</span>
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

                         <!-- Arquivos -->
                         <div x-show="activeTab === 'arquivos'">
                             <div class="flex justify-between items-center mb-4">
                                 <h4 class="text-lg font-medium text-gray-900 dark:text-white">Arquivos</h4>
                                 <button class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-1 rounded-md text-sm flex items-center">
                                     <i class="fas fa-upload mr-1"></i> Enviar Arquivo
                                 </button>
                             </div>
                             <div class="overflow-x-auto">
                                 <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                     <thead class="bg-gray-50 dark:bg-gray-800">
                                         <tr>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nome</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tamanho</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data de Upload</th>
                                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                                         </tr>
                                     </thead>
                                     <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                         <tr>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">contrato_1024.pdf</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">PDF</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">1.2 MB</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">01/10/2023</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                 <div class="flex space-x-2">
                                                     <button class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                         <i class="fas fa-download"></i>
                                                     </button>
                                                     <button class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                         <i class="fas fa-trash-alt"></i>
                                                     </button>
                                                 </div>
                                             </td>
                                         </tr>
                                         <tr>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">rider_tecnico.docx</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">DOCX</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">850 KB</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap">
                                                 <span class="text-sm text-gray-700 dark:text-gray-300">02/10/2023</span>
                                             </td>
                                             <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                 <div class="flex space-x-2">
                                                     <button class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                         <i class="fas fa-download"></i>
                                                     </button>
                                                     <button class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                         <i class="fas fa-trash-alt"></i>
                                                     </button>
                                                 </div>
                                             </td>
                                         </tr>
                                     </tbody>
                                 </table>
                             </div>
                         </div>

                         <!-- Histórico -->
                         <div x-show="activeTab === 'historico'">
                             <div class="flow-root">
                                 <ul class="-my-6 divide-y divide-gray-200 dark:divide-gray-700">
                                     <li class="py-6">
                                         <div class="flex space-x-3">
                                             <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                                 <i class="fas fa-file-contract text-primary-600 dark:text-primary-400"></i>
                                             </div>
                                             <div>
                                                 <p class="text-sm font-medium text-gray-900 dark:text-white">Contrato criado</p>
                                                 <p class="text-sm text-gray-500 dark:text-gray-400">O contrato foi criado por Admin.</p>
                                                 <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">01/10/2023 às 10:35</p>
                                             </div>
                                         </div>
                                     </li>
                                     <li class="py-6">
                                         <div class="flex space-x-3">
                                             <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                                 <i class="fas fa-upload text-blue-600 dark:text-blue-400"></i>
                                             </div>
                                             <div>
                                                 <p class="text-sm font-medium text-gray-900 dark:text-white">Arquivo adicionado</p>
                                                 <p class="text-sm text-gray-500 dark:text-gray-400">O arquivo "contrato_1024.pdf" foi adicionado por Admin.</p>
                                                 <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">01/10/2023 às 10:40</p>
                                             </div>
                                         </div>
                                     </li>
                                     <li class="py-6">
                                         <div class="flex space-x-3">
                                             <div class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                                                 <i class="fas fa-calendar-check text-green-600 dark:text-green-400"></i>
                                             </div>
                                             <div>
                                                 <p class="text-sm font-medium text-gray-900 dark:text-white">Evento adicionado</p>
                                                 <p class="text-sm text-gray-500 dark:text-gray-400">O evento "Festival de Verão" foi adicionado por Admin.</p>
                                                 <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">02/10/2023 às 14:15</p>
                                             </div>
                                         </div>
                                     </li>
                                     <li class="py-6">
                                         <div class="flex space-x-3">
                                             <div class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                                                 <i class="fas fa-money-bill-wave text-green-600 dark:text-green-400"></i>
                                             </div>
                                             <div>
                                                 <p class="text-sm font-medium text-gray-900 dark:text-white">Pagamento registrado</p>
                                                 <p class="text-sm text-gray-500 dark:text-gray-400">Um pagamento de R$ 25.000,00 foi registrado por Admin.</p>
                                                 <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">31/10/2023 às 16:20</p>
                                             </div>
                                         </div>
                                     </li>
                                 </ul>
                             </div>
                         </div>
                     </div>
                 </div>

                 <div class="flex justify-end">
                     <button type="button" @click="closeModal()" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md">
                         Fechar
                     </button>
                 </div>
             </div>
         </div>
     </div>


</body>
</html>