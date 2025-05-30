<ul class="flex border-b border-gray-200 dark:border-gray-700">
    @foreach ($tabs as $tab)
        <li class="mr-1">
            <button
                @click="activeTab = '{{ $tab['id'] }}'"
                :class="{ 'bg-white dark:bg-gray-800 text-primary-600 dark:text-primary-400': activeTab === '{{ $tab['id'] }}', 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200': activeTab !== '{{ $tab['id'] }}' }"
                class="inline-block py-2 px-4 font-medium text-sm rounded-t-lg"
            >
                {{ $tab['label'] }}
            </button>
        </li>
    @endforeach
</ul>