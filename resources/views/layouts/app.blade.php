<!DOCTYPE html>
{{-- Define o idioma e inicializa o estado Alpine para tema escuro --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }"
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
      x-cloak
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'EventosPro') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%236366f1'%3E%3Cpath d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z'/%3E%3C/svg%3E">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts e Estilos (Vite) -->
    {{-- Inclui app.css (Tailwind, Font Awesome) e app.js (Alpine) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Estilos adicionais específicos da página (raro, mas possível) --}}
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">

    {{-- Estado global para Alpine (sidebar e funções auxiliares) --}}
    <div x-data="{
            sidebarOpen: localStorage.getItem('sidebarOpen') === 'true' || true,
            inArray(needle, haystack) {
                if (Array.isArray(haystack)) {
                    return haystack.includes(needle);
                }
                if (typeof haystack === 'string') {
                    return haystack === needle;
                }
                return false;
            }
        }"
         x-init="$watch('sidebarOpen', val => localStorage.setItem('sidebarOpen', val))"
         x-cloak
         class="min-h-screen flex flex-col md:flex-row bg-gray-100 dark:bg-gray-900" {{-- Container Flex Principal --}}
    >
        <!-- Sidebar -->
        {{-- Inclui o componente da sidebar, passando o estado via prop --}}
        {{-- Ou a sidebar pode ler o estado global 'sidebarOpen' diretamente se preferir --}}
        <x-sidebar />

        <!-- Área de Conteúdo Principal -->
        <div class="flex-1 flex flex-col overflow-hidden md:ml-64 transition-all duration-300" :class="{ 'md:ml-20': !sidebarOpen }">

            <!-- Header Principal (dentro da área de conteúdo) -->
            <header class="bg-white dark:bg-gray-800 shadow-md z-10">
                <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8"> {{-- max-w-full para ocupar largura --}}
                    <div class="flex justify-between items-center h-16">
                        {{-- Slot para Título vindo das Views (se não usar header fixo) --}}
                        <div>
                            @isset($header)
                                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                                    {{ $header }}
                                </h2>
                            @endisset
                        </div>

                        <!-- Lado Direito do Header (Notificações, User) -->
                        <div class="flex items-center space-x-4">
                            {{-- Notificações (Componente ou direto) --}}
                            <x-header-notifications /> {{-- Exemplo de componente --}}

                            {{-- Menu Usuário (Componente Dropdown do Breeze ou customizado) --}}
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                                        <div>{{ Auth::user()->name }}</div>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-dropdown-link :href="route('profile.edit')">
                                        {{ __('Profile') }}
                                    </x-dropdown-link>
                                    <!-- Logout -->
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <x-dropdown-link :href="route('logout')"
                                                onclick="event.preventDefault();
                                                            this.closest('form').submit();">
                                            {{ __('Log Out') }}
                                        </x-dropdown-link>
                                    </form>
                                </x-slot>
                            </x-dropdown>

                        </div>
                    </div>
                </div>
            </header>

            <!-- Conteúdo da Página (Rolável) -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900">
                <div class="container mx-auto px-6 py-8"> {{-- Container para o conteúdo --}}
                    {{-- Mensagens Flash --}}
                    @include('layouts.partials.flash-messages')

                    {{-- O conteúdo principal da view específica será injetado aqui --}}
                    {{ $slot }}
                </div>
            </main>

        </div> {{-- Fim da Área de Conteúdo Principal --}}

    </div> {{-- Fim do Container Flex Principal --}}

    {{-- Modais Globais (se houver - Ex: Confirmação de Exclusão Genérica) --}}
    {{-- @include('layouts.partials.confirmation-modal') --}}

    {{-- jQuery (necessário para alguns componentes) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
    {{-- Configuração do CSRF Token para jQuery --}}
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    
    {{-- Bootstrap JS (se necessário) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Scripts específicos da página --}}
    @stack('scripts')

</body>
</html>