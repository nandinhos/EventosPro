@props([
    'title' => '',
    'icon' => '',
    'headerClass' => '',
    'bodyClass' => '',
    'actions' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-lg shadow']) }}>
    @if($title || $icon || $actions)
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 {{ $headerClass }}">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    @if($icon)
                        <i class="{{ $icon }} mr-2"></i>
                    @endif
                    @if($title)
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-0">{{ $title }}</h3>
                    @endif
                </div>
                @if($actions)
                    <div class="flex gap-2">
                        {{ $actions }}
                    </div>
                @endif
            </div>
        </div>
    @endif
    
    <div class="p-4 {{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>