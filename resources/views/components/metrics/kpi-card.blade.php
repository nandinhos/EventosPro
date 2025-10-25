@props([
    'title',
    'value',
    'subtitle' => '',
    'threshold' => null,
    'thresholdType' => 'min', // 'min' or 'max'
    'icon' => null,
    'tooltip' => null,
])

@php
// Determina a cor baseada no threshold
$statusColor = 'gray';
if ($threshold !== null && is_numeric($value)) {
    $numericValue = (float) str_replace([',', '.'], ['', '.'], str_replace('.', '', $value));
    if ($thresholdType === 'min') {
        // Valores maiores que threshold são bons
        if ($numericValue >= $threshold['good']) {
            $statusColor = 'green';
        } elseif ($numericValue >= $threshold['warning']) {
            $statusColor = 'yellow';
        } else {
            $statusColor = 'red';
        }
    } else {
        // Valores menores que threshold são bons (commitment_rate, por exemplo)
        if ($numericValue <= $threshold['good']) {
            $statusColor = 'green';
        } elseif ($numericValue <= $threshold['warning']) {
            $statusColor = 'yellow';
        } else {
            $statusColor = 'red';
        }
    }
}

$borderColors = [
    'green' => 'border-green-500',
    'yellow' => 'border-yellow-500',
    'red' => 'border-red-500',
    'gray' => 'border-gray-500',
];

$iconBgColors = [
    'green' => 'bg-green-100 dark:bg-green-900',
    'yellow' => 'bg-yellow-100 dark:bg-yellow-900',
    'red' => 'bg-red-100 dark:bg-red-900',
    'gray' => 'bg-gray-100 dark:bg-gray-900',
];

$iconColors = [
    'green' => 'text-green-600 dark:text-green-400',
    'yellow' => 'text-yellow-600 dark:text-yellow-400',
    'red' => 'text-red-600 dark:text-red-400',
    'gray' => 'text-gray-600 dark:text-gray-400',
];

$borderClass = $borderColors[$statusColor];
$iconBgClass = $iconBgColors[$statusColor];
$iconColorClass = $iconColors[$statusColor];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 border-l-4 ' . $borderClass]) }}>
    <div class="flex items-center justify-between">
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

        <div class="ml-4">
            <div class="w-16 h-16 rounded-full {{ $iconBgClass }} flex items-center justify-center">
                @if($icon)
                    <div class="{{ $iconColorClass }}">
                        {!! $icon !!}
                    </div>
                @else
                    <svg class="w-8 h-8 {{ $iconColorClass }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                    </svg>
                @endif
            </div>
        </div>
    </div>
</div>
