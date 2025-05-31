@props(['tabs' => []])

<div class="w-full">
    <!-- Navegação de Abas -->
    <nav class="flex space-x-4 border-b border-gray-200 dark:border-gray-700 mb-4">
        @foreach ($tabs as $tab)
            <a href="#{{ $tab['id'] }}" 
               class="tab-link" 
               :class="activeTab === '{{ $tab['id'] }}' ? 'active' : ''" 
               data-tab="{{ $tab['id'] }}" 
               @click="activeTab = '{{ $tab['id'] }}'">
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
        @apply text-sm font-medium text-gray-500 dark:text-gray-400 px-4 py-2 rounded-t-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors;
    }
    .tab-link.active {
        @apply text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-800 border-b-2 border-indigo-500;
    }
</style>