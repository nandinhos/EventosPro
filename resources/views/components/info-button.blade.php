@props([
    'size' => 'md',
])

@php
$sizeClasses = [
    'sm' => 'px-2 py-1 text-xs',
    'md' => 'px-3 py-2 text-sm',
    'lg' => 'px-4 py-2 text-base',
];
$sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<button {{ $attributes->merge([
    'type' => 'button', 
    'class' => "inline-flex items-center {$sizeClass} bg-blue-100 dark:bg-blue-900 border border-blue-300 dark:border-blue-700 rounded-md font-medium text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
]) }}>
    {{ $slot }}
</button>