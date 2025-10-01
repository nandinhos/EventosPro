@props([
    'title',
    'value',
    'icon' => '',
    'color' => 'primary',
])

@php
$colorClasses = [
    'primary' => 'text-blue-600 dark:text-blue-400',
    'success' => 'text-green-600 dark:text-green-400',
    'warning' => 'text-yellow-600 dark:text-yellow-400',
    'danger' => 'text-red-600 dark:text-red-400',
    'info' => 'text-cyan-600 dark:text-cyan-400',
];
$colorClass = $colorClasses[$color] ?? $colorClasses['primary'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-lg shadow p-4']) }}>
    <div class="flex items-center">
        @if($icon)
            <div class="{{ $colorClass }} text-2xl me-3">
                <i class="{{ $icon }}"></i>
            </div>
        @endif
        <div>
            <h3 class="text-2xl font-bold {{ $colorClass }} mb-1">{{ $value }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $title }}</p>
        </div>
    </div>
</div>