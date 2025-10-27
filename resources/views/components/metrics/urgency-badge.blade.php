@props([
    'level' => 'normal', // critical, high, medium, normal
    'label' => null,
    'showIcon' => true,
])

@php
$configs = [
    'critical' => [
        'bg' => 'bg-red-600',
        'text' => 'text-white',
        'icon' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>',
        'label' => 'Crítica',
    ],
    'high' => [
        'bg' => 'bg-orange-500',
        'text' => 'text-white',
        'icon' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>',
        'label' => 'Alta',
    ],
    'medium' => [
        'bg' => 'bg-yellow-500',
        'text' => 'text-white',
        'icon' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" /></svg>',
        'label' => 'Média',
    ],
    'normal' => [
        'bg' => 'bg-green-500',
        'text' => 'text-white',
        'icon' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>',
        'label' => 'Normal',
    ],
    'upcoming' => [
        'bg' => 'bg-blue-100 dark:bg-blue-900',
        'text' => 'text-blue-800 dark:text-blue-200',
        'icon' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>',
        'label' => 'Próximo',
    ],
    'week' => [
        'bg' => 'bg-indigo-100 dark:bg-indigo-900',
        'text' => 'text-indigo-800 dark:text-indigo-200',
        'icon' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" /></svg>',
        'label' => 'Esta semana',
    ],
    'ok' => [
        'bg' => 'bg-green-100 dark:bg-green-900',
        'text' => 'text-green-800 dark:text-green-200',
        'icon' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>',
        'label' => 'Em dia',
    ],
];

$config = $configs[$level] ?? $configs['normal'];
$displayLabel = $label ?? $config['label'];
@endphp

<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold {{ $config['bg'] }} {{ $config['text'] }}">
    @if($showIcon)
        {!! $config['icon'] !!}
    @endif
    {{ $displayLabel }}
</span>
