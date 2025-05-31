@props(['tabs' => []])

<div class="w-full">
    <!-- Navegação de Abas -->
    <nav class="flex space-x-4 border-b border-gray-200 dark:border-gray-700 mb-4">
        @foreach ($tabs as $tab)
            <a href="#{{ $tab['id'] }}" 
               class="tab-link px-4 py-2 text-sm font-medium rounded-t-lg transition-colors whitespace-nowrap"
               :class="activeTab === '{{ $tab['id'] }}' ? 'bg-indigo-600 text-white border-b-2 border-indigo-600 font-semibold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'"
               data-tab="{{ $tab['id'] }}"
               @click.prevent="activeTab = '{{ $tab['id'] }}'; window.history.pushState({}, '', `?tab=${$event.target.getAttribute('data-tab')}`)">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    <!-- Container para Botões de Exportação -->
    <div class="flex justify-end mb-4">
        {{ $exportButtons ?? '' }}
    </div>

    <!-- Conteúdo das Abas -->
    <div class="tab-content">
        {{ $slot }}
    </div>
</div>

<style>
    .tab-link {
        @apply text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800;
    }
    .tab-link.active {
        @apply bg-indigo-600 text-white border-b-2 border-indigo-600 font-semibold;
    }
</style>