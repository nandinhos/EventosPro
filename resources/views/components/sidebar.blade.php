<!-- Sidebar Fixa -->
<aside x-data="{ sidebarOpen: true }"
       :class="sidebarOpen ? 'w-64' : 'w-20'"
       class="bg-white dark:bg-gray-800 flex flex-col justify-between transition-all duration-300 shadow-lg border-r border-gray-200 dark:border-gray-700 fixed inset-y-0 left-0 z-40">

    <div>
        <!-- Logo e Toggle -->
        <div class="h-16 flex items-center justify-between px-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <a href="{{ route('dashboard') }}" :class="sidebarOpen ? 'block' : 'hidden'" class="text-xl font-bold text-primary-600 dark:text-primary-400">EventosPro</a>
            <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
                <i class="fas text-gray-500 dark:text-gray-400" :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
            </button>
        </div>

        <!-- Navegação -->
        <nav class="flex-1 overflow-y-auto py-4">
            <ul class="space-y-1 px-2">

                <!-- Dashboard -->
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center py-2.5 px-4 rounded-md transition-colors duration-200 group
                       {{ request()->routeIs('dashboard') ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-300 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-tachometer-alt fa-fw w-6 text-center text-lg"></i>
                        <span x-show="sidebarOpen" class="ml-3">Dashboard</span>
                    </a>
                </li>

                <!-- Operacional -->
                <li class="px-4 pt-4 pb-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase" x-show="sidebarOpen">Operacional</li>

                <li>
                    <a href="{{ route('gigs.index') }}"
                       class="flex items-center py-2.5 px-4 rounded-md transition-colors duration-200 group
                       {{ request()->is('gigs*') ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-300 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-calendar-check fa-fw w-6 text-center text-lg"></i>
                        <span x-show="sidebarOpen" class="ml-3">Gigs/Datas</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('artists.index') }}"
                       class="flex items-center py-2.5 px-4 rounded-md transition-colors duration-200 group
                       {{ request()->is('artists*') ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-300 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        <i class="fas fa-music fa-fw w-6 text-center text-lg"></i>
                        <span x-show="sidebarOpen" class="ml-3">Artistas</span>
                    </a>
                </li>

                <!-- Bookers -->
                <li x-data="{ open: @js(request()->routeIs('bookers.index') || request()->routeIs('reports.performance.index')) }"
    :class="open ? 'text-primary-600 dark:text-primary-300' : ''">
    <button @click="open = !open" class="w-full flex items-center justify-between py-2.5 px-4 rounded-md transition-colors duration-200 text-left"
            :class="open ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-300 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'">
        <div class="flex items-center">
            <i class="fas fa-user-tie fa-fw w-6 text-center text-lg"
               :class="open ? 'text-primary-600 dark:text-primary-300' : ''"></i>
            <span x-show="sidebarOpen" class="ml-3">Bookers</span>
        </div>
        <i x-show="sidebarOpen" :class="open ? 'rotate-90' : ''" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="sidebarOpen && open" x-collapse class="ml-6 mt-1 space-y-1 border-l border-gray-200 dark:border-gray-700">
        <a href="{{ route('bookers.index') }}" class="flex items-center py-2 px-4 text-sm rounded-md transition-colors
           {{ request()->routeIs('bookers.index') ? 'text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
            <i class="fas fa-list fa-fw w-4 mr-2 text-center"></i>
            Lista de Bookers
        </a>
        <a href="{{ route('reports.performance.index') }}" class="flex items-center py-2 px-4 text-sm rounded-md transition-colors
           {{ request()->routeIs('reports.performance.index') ? 'text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
            <i class="fas fa-rocket fa-fw w-4 mr-2 text-center"></i>
            Performance
        </a>
    </div>
</li>

                <!-- Análise -->
                <li class="px-4 pt-4 pb-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase" x-show="sidebarOpen">Análise</li>

                <!-- Financeiro -->
                <li x-data="{ open: @js(request()->routeIs('reports.delinquency') || request()->routeIs('reports.index') || request()->routeIs('projections.index') || request()->routeIs('reports.due-dates') || request()->routeIs('finance.monthly-closing')) }"
    :class="open ? 'text-primary-600 dark:text-primary-300' : ''">
    <button @click="open = !open" class="w-full flex items-center justify-between py-2.5 px-4 rounded-md transition-colors duration-200 text-left"
            :class="open ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-300 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'">
        <div class="flex items-center">
            <i class="fas fa-wallet fa-fw w-6 text-center text-lg"
               :class="open ? 'text-primary-600 dark:text-primary-300' : ''"></i>
            <span x-show="sidebarOpen" class="ml-3">Financeiro</span>
        </div>
        <i x-show="sidebarOpen" :class="open ? 'rotate-90' : ''" class="fas fa-chevron-right text-xs transition-transform"></i>
    </button>
    <div x-show="sidebarOpen && open" x-collapse class="ml-6 mt-1 space-y-1 border-l border-gray-200 dark:border-gray-700">
        <a href="{{ route('reports.delinquency') }}" class="flex items-center py-2 px-4 text-sm rounded-md transition-colors
           {{ request()->routeIs('reports.delinquency') ? 'text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
            <i class="fas fa-file-invoice-dollar fa-fw w-4 mr-2 text-center"></i> Status Contratos
        </a>
        <a href="{{ route('reports.index') }}" class="flex items-center py-2 px-4 text-sm rounded-md transition-colors
           {{ request()->routeIs('reports.index') ? 'text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
            <i class="fas fa-chart-bar fa-fw w-4 mr-2 text-center"></i> Relatórios
        </a>
        <a href="{{ route('projections.index') }}" class="flex items-center py-2 px-4 text-sm rounded-md transition-colors
           {{ request()->routeIs('projections.index') ? 'text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
            <i class="fas fa-chart-line fa-fw w-4 mr-2 text-center"></i> Projeções
        </a>
        <a href="{{ route('reports.due-dates') }}" class="flex items-center py-2 px-4 text-sm rounded-md transition-colors
           {{ request()->routeIs('reports.due-dates') ? 'text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
            <i class="fas fa-calendar-day fa-fw w-4 mr-2 text-center"></i> Vencimentos
        </a>
        <a href="{{ route('finance.monthly-closing') }}" class="flex items-center py-2 px-4 text-sm rounded-md transition-colors
           {{ request()->routeIs('finance.monthly-closing') ? 'text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
            <i class="fas fa-file-invoice-dollar fa-fw w-4 mr-2 text-center"></i> Fechamento Mensal
        </a>
    </div>
</li>
            </ul>
        </nav>
    </div>

    <!-- Perfil -->
    <div class="border-t border-gray-200 dark:border-gray-700 p-2 flex-shrink-0">
        <ul class="space-y-1">
            <li>
                <a href="{{ route('users.index') }}"
                   class="flex items-center py-2.5 px-4 rounded-md transition-colors duration-200 group
                   {{ request()->is('users*') ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-300 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-users-cog fa-fw w-6 text-center text-lg"></i>
                    <span x-show="sidebarOpen" class="ml-3">Usuários</span>
                </a>
            </li>
            <li>
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center py-2.5 px-4 rounded-md transition-colors duration-200 group
                   {{ request()->routeIs('profile.edit') ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-300 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=7F9CF5&background=EBF4FF"
                         alt="Foto do usuário" class="w-6 h-6 rounded-full">
                    <span x-show="sidebarOpen" class="ml-3">Meu Perfil</span>
                </a>
            </li>
        </ul>
    </div>
</aside>