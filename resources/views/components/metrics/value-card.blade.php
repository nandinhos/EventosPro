@props([
    'title',
    'value',
    'subtitle' => '',
    'count' => null,
    'color' => 'blue',
    'icon' => null,
    'link' => null,
    'badge' => null,
])

@php
$gradientColors = [
    'blue' => 'from-blue-500 to-blue-600',
    'green' => 'from-green-500 to-green-600',
    'red' => 'from-red-500 to-red-600',
    'yellow' => 'from-yellow-500 to-yellow-600',
    'orange' => 'from-orange-500 to-orange-600',
    'purple' => 'from-purple-500 to-purple-600',
    'gray' => 'from-gray-500 to-gray-600',
];

$textLightColors = [
    'blue' => 'text-blue-100',
    'green' => 'text-green-100',
    'red' => 'text-red-100',
    'yellow' => 'text-yellow-100',
    'orange' => 'text-orange-100',
    'purple' => 'text-purple-100',
    'gray' => 'text-gray-100',
];

$gradientClass = $gradientColors[$color] ?? $gradientColors['blue'];
$textLightClass = $textLightColors[$color] ?? $textLightColors['blue'];

$baseClasses = 'bg-gradient-to-br ' . $gradientClass . ' rounded-lg shadow-md hover:shadow-lg transition-all p-6 text-white';
if ($link) {
    $baseClasses .= ' transform hover:scale-[1.02] cursor-pointer';
}
@endphp

@if($link)
    <a href="{{ $link }}" {{ $attributes->merge(['class' => $baseClasses]) }}>
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs font-semibold {{ $textLightClass }} uppercase tracking-wider">
                    {{ $title }}
                </p>
                <p class="text-3xl font-bold mt-2">
                    {{ $value }}
                </p>
                @if($count !== null)
                    <p class="text-sm {{ $textLightClass }} mt-1">
                        {{ $count }} {{ $subtitle }}
                    </p>
                @elseif($subtitle)
                    <p class="text-sm {{ $textLightClass }} mt-1">
                        {{ $subtitle }}
                    </p>
                @endif
                @if($badge)
                    <div class="mt-2 bg-white/20 rounded px-2 py-1 text-xs inline-block">
                        <span class="font-semibold">{{ $badge }}</span>
                    </div>
                @endif
            </div>
            <div class="bg-white/20 rounded-full p-3 ml-4">
                @if($icon)
                    {!! $icon !!}
                @else
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                    </svg>
                @endif
            </div>
        </div>
    </a>
@else
    <div {{ $attributes->merge(['class' => $baseClasses]) }}>
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-xs font-semibold {{ $textLightClass }} uppercase tracking-wider">
                    {{ $title }}
                </p>
                <p class="text-3xl font-bold mt-2">
                    {{ $value }}
                </p>
                @if($count !== null)
                    <p class="text-sm {{ $textLightClass }} mt-1">
                        {{ $count }} {{ $subtitle }}
                    </p>
                @elseif($subtitle)
                    <p class="text-sm {{ $textLightClass }} mt-1">
                        {{ $subtitle }}
                    </p>
                @endif
                @if($badge)
                    <div class="mt-2 bg-white/20 rounded px-2 py-1 text-xs inline-block">
                        <span class="font-semibold">{{ $badge }}</span>
                    </div>
                @endif
            </div>
            <div class="bg-white/20 rounded-full p-3 ml-4">
                @if($icon)
                    {!! $icon !!}
                @else
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                    </svg>
                @endif
            </div>
        </div>
    </div>
@endif
