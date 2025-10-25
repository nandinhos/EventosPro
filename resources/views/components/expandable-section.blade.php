@props([
    'title',
    'count' => null,
    'icon' => null,
    'color' => 'gray',
    'expanded' => false,
])

@php
$bgColors = [
    'red' => 'bg-red-50 dark:bg-red-900/20',
    'green' => 'bg-green-50 dark:bg-green-900/20',
    'blue' => 'bg-blue-50 dark:bg-blue-900/20',
    'yellow' => 'bg-yellow-50 dark:bg-yellow-900/20',
    'orange' => 'bg-orange-50 dark:bg-orange-900/20',
    'gray' => 'bg-gray-50 dark:bg-gray-900/20',
];

$borderColors = [
    'red' => 'border-red-200 dark:border-red-800',
    'green' => 'border-green-200 dark:border-green-800',
    'blue' => 'border-blue-200 dark:border-blue-800',
    'yellow' => 'border-yellow-200 dark:border-yellow-800',
    'orange' => 'border-orange-200 dark:border-orange-800',
    'gray' => 'border-gray-200 dark:border-gray-800',
];

$textColors = [
    'red' => 'text-red-800 dark:text-red-200',
    'green' => 'text-green-800 dark:text-green-200',
    'blue' => 'text-blue-800 dark:text-blue-200',
    'yellow' => 'text-yellow-800 dark:text-yellow-200',
    'orange' => 'text-orange-800 dark:text-orange-200',
    'gray' => 'text-gray-800 dark:text-gray-200',
];

$badgeColors = [
    'red' => 'bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-200',
    'green' => 'bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-200',
    'blue' => 'bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200',
    'yellow' => 'bg-yellow-100 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200',
    'orange' => 'bg-orange-100 dark:bg-orange-800 text-orange-800 dark:text-orange-200',
    'gray' => 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200',
];

$bgClass = $bgColors[$color] ?? $bgColors['gray'];
$borderClass = $borderColors[$color] ?? $borderColors['gray'];
$textClass = $textColors[$color] ?? $textColors['gray'];
$badgeClass = $badgeColors[$color] ?? $badgeColors['gray'];
@endphp

<div x-data="{ open: {{ $expanded ? 'true' : 'false' }} }" class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden mt-6">
    {{-- Header --}}
    <div class="{{ $bgClass }} px-6 py-4 border-b {{ $borderClass }} cursor-pointer" @click="open = !open">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if($icon)
                    <div class="{{ $textClass }}">
                        {!! $icon !!}
                    </div>
                @endif
                <h3 class="text-base font-semibold {{ $textClass }} flex items-center gap-3">
                    {{ $title }}
                    @if($count !== null)
                        <span class="text-xs font-medium {{ $badgeClass }} px-3 py-1 rounded-full">
                            {{ $count }}
                        </span>
                    @endif
                </h3>
            </div>
            <button class="{{ $textClass }} transform transition-transform duration-200" :class="{ 'rotate-180': open }">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Content --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="overflow-hidden"
         style="{{ $expanded ? '' : 'display: none;' }}">
        {{ $slot }}
    </div>
</div>
