@props([
    'fallback' => null,
    'class' => '',
])

@php
    // Define classes padrão para o botão
    $defaultClasses = 'inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors';
    $classes = $class ?: $defaultClasses;
@endphp

<button type="button"
        onclick="if (document.referrer && document.referrer.includes(window.location.host)) { history.back(); } else { window.location.href = '{{ $fallback ?? url()->previous() }}'; }"
        {{ $attributes->merge(['class' => $classes]) }}>
    <i class="fas fa-arrow-left mr-2"></i>{{ $slot->isEmpty() ? 'Voltar' : $slot }}
</button>
