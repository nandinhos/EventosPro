@props([
    'value' => 0,
    'max' => 100,
    'color' => 'primary',
    'striped' => false,
    'animated' => false,
])

@php
$colorClasses = [
    'primary' => 'bg-blue-600',
    'success' => 'bg-green-600',
    'warning' => 'bg-yellow-600',
    'danger' => 'bg-red-600',
    'info' => 'bg-cyan-600',
];

$colorClass = $colorClasses[$color] ?? $colorClasses['primary'];
$percentage = $max > 0 ? ($value / $max) * 100 : 0;
$animatedClass = $animated ? 'animate-pulse' : '';
@endphp

<div {{ $attributes->merge(['class' => 'w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2']) }}>
    <div 
        class="{{ $colorClass }} h-2 rounded-full transition-all duration-300 {{ $animatedClass }}"
        role="progressbar" 
        aria-valuenow="{{ $value }}" 
        aria-valuemin="0" 
        aria-valuemax="{{ $max }}"
        data-percentage="{{ $percentage }}"
    ></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const progressBars = document.querySelectorAll('[data-percentage]');
    progressBars.forEach(bar => {
        const percentage = bar.getAttribute('data-percentage');
        bar.style.width = percentage + '%';
    });
});
</script>