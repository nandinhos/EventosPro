@props([
    'title',
    'value',
    'subtitle' => '',
    'color' => 'blue',
    'icon' => null,
    'tooltip' => null,
])

@php
$colorClasses = [
    'blue' => 'border-blue-500',
    'purple' => 'border-purple-500',
    'green' => 'border-green-500',
    'red' => 'border-red-500',
    'yellow' => 'border-yellow-500',
    'orange' => 'border-orange-500',
];

$textColors = [
    'blue' => 'text-blue-600',
    'purple' => 'text-purple-600',
    'green' => 'text-green-600',
    'red' => 'text-red-600',
    'yellow' => 'text-yellow-600',
    'orange' => 'text-orange-600',
];

$borderClass = $colorClasses[$color] ?? $colorClasses['blue'];
$textColorClass = $textColors[$color] ?? $textColors['blue'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 border-l-4 ' . $borderClass]) }}>
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <div class="flex items-center gap-2">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    {{ $title }}
                </p>
                @if($tooltip)
                    <div x-data="{ show: false }" class="relative">
                        <button @mouseenter="show = true" @mouseleave="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="show"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             class="absolute z-10 w-64 px-4 py-2 text-xs text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 bottom-full mb-2 left-1/2 transform -translate-x-1/2"
                             style="display: none;">
                            {{ $tooltip }}
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                                <div class="border-4 border-transparent border-t-white dark:border-t-gray-700"></div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">
                {{ $value }}
            </p>
            @if($subtitle)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @if($icon)
            <div class="ml-4 flex-shrink-0">
                <div class="w-12 h-12 rounded-full bg-{{ $color }}-100 dark:bg-{{ $color }}-900/30 flex items-center justify-center">
                    {!! $icon !!}
                </div>
            </div>
        @endif
    </div>
</div>
