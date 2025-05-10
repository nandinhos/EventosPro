{{-- resources/views/components/sidebar.blade.php --}}

{{-- A sidebar usa as variáveis sidebarOpen e currentPage do x-data pai (no body) --}}
<aside :class="sidebarOpen ? 'w-64 translate-x-0' : 'w-20 translate-x-0'"
       class="bg-white dark:bg-gray-800 transform transition-transform duration-300 shadow-lg flex flex-col justify-between origin-left"> {{-- Adicionado flex para empurrar user info para baixo --}}

    <!-- Header da Sidebar -->
    <div class="h-16 flex items-center justify-between px-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"> {{-- flex-shrink-0 para não diminuir --}}
        <h1 :class="sidebarOpen ? 'block' : 'hidden'" class="text-xl font-bold text-primary-600 dark:text-primary-400">EventosPro</h1>
        {{-- Usamos tailwind apply para um hover mais suave --}}
        <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
            <i :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'" class="fas text-gray-500 dark:text-gray-400"></i>
        </button>
    </div>

    <!-- Links da Sidebar (rolável se necessário) -->
    <nav class="flex-1 overflow-y-auto py-4"> {{-- flex-1 para ocupar espaço, overflow para rolar --}}
        <ul>
        <li>
    {{-- Aponta para a rota 'dashboard' e ajusta a lógica da classe ativa --}}
    <a href="{{ route('dashboard') }}"
       :class="window.location.pathname === '/dashboard' || window.location.pathname === '/' ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 dark:text-gray-300'"
       class="flex items-center py-3 px-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
        <i class="fas fa-tachometer-alt w-6 text-center"></i>
        <span :class="sidebarOpen ? 'ml-3 block' : 'hidden'">Dashboard</span>
    </a>
</li>
            <li>
    {{-- Link principal agora para Gigs --}}
    <a href="{{ route('gigs.index') }}"
       :class="window.location.pathname.startsWith('/gigs') ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 dark:text-gray-300'"
       class="flex items-center py-3 px-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
        <i class="fas fa-calendar-check w-6 text-center"></i> {{-- Ícone pode ser de calendário/check --}}
        <span :class="sidebarOpen ? 'ml-3 block' : 'hidden'">Gigs/Datas</span> 
    </a>
</li>
                  
<li>
    <a href="{{ route('artists.index') }}"
       :class="window.location.pathname.startsWith('/artists') ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 dark:text-gray-300'"
       class="flex items-center py-3 px-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
        <i class="fas fa-music w-6 text-center"></i>
        <span :class="sidebarOpen ? 'ml-3 block' : 'hidden'">Artistas</span>
    </a>
</li>

    
<li>
    <a href="{{ route('bookers.index') }}"
       :class="window.location.pathname.startsWith('/bookers') ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-600 dark:text-gray-300'"
       class="flex items-center py-3 px-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
        <i class="fas fa-user-tie w-6 text-center"></i>
        <span :class="sidebarOpen ? 'ml-3 block' : 'hidden'">Bookers</span>
    </a>
</li>
            
            <li>
                <a href="#" class="flex items-center py-3 px-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 text-gray-600 dark:text-gray-300">
                    <i class="fas fa-money-bill-wave w-6 text-center"></i>
                    <span :class="sidebarOpen ? 'ml-3 block' : 'hidden'">Pagamentos</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center py-3 px-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 text-gray-600 dark:text-gray-300">
                    <i class="fas fa-chart-bar w-6 text-center"></i>
                    <span :class="sidebarOpen ? 'ml-3 block' : 'hidden'">Relatórios</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center py-3 px-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 text-gray-600 dark:text-gray-300">
                    <i class="fas fa-chart-line w-6 text-center"></i>
                    <span :class="sidebarOpen ? 'ml-3 block' : 'hidden'">Projeções</span>
                </a>
            </li>
            <li>
                <a href="#" class="flex items-center py-3 px-4 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 text-gray-600 dark:text-gray-300">
                    <i class="fas fa-users w-6 text-center"></i>
                    <span :class="sidebarOpen ? 'ml-3 block' : 'hidden'">Usuários</span>
                </a>
            </li>
        </ul>
    </nav>

    {{-- Informações do usuário e Toggle Dark Mode --}}
    <div class="w-full border-t border-gray-200 dark:border-gray-700 py-4 flex-shrink-0"> {{-- flex-shrink-0 para não diminuir --}}
        <div class="flex items-center px-4">
            {{-- Pode substituir por avatar dinâmico se tiver --}}
            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                 alt="Foto do usuário"
                 class="w-10 h-10 rounded-full border-2 border-primary-500">
            <div :class="sidebarOpen ? 'ml-3 block' : 'hidden'">
                {{-- Aqui você pode exibir o nome e email do usuário logado --}}
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ Auth::user()->name ?? 'Admin' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ Auth::user()->email ?? 'admin@exemplo.com' }}</p>
            </div>
            {{-- Toggle Dark Mode --}}
            <button @click="darkMode = !darkMode; $nextTick(() => localStorage.setItem('darkMode', darkMode));" class="ml-auto p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                <i :class="darkMode ? 'fa-sun' : 'fa-moon'" class="fas text-gray-600 dark:text-gray-300"></i> {{-- Adicionado cores para icon --}}
            </button>
        </div>
    </div>
</aside>

{{-- Script para inicializar dark mode baseado no localStorage ou preferência do sistema --}}
{{-- Este script deve ser colocado fora do componente, no body do layout, antes do x-data --}}
{{-- Mas para manter o componente auto-suficiente em termos de dark mode inicial, vamos colocar aqui --}}
{{-- Idealmente, a lógica de inicialização do dark mode global ficaria no app.js ou no layout pai --}}
{{-- Vamos mover isso para o layout pai para melhor prática --}}