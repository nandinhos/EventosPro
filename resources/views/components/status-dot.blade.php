@props(['status', 'size' => 'md', 'title' => ''])

@php
    $sizes = [
        'xs' => 'h-2 w-2',
        'sm' => 'h-2.5 w-2.5',
        'md' => 'h-3 w-3',
        'lg' => 'h-4 w-4',
        'xl' => 'h-5 w-5',
    ];
    
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    
    $colorClasses = match(strtolower(trim($status))) {
        'assinado', 'concluido', 'pago', 'confirmado', 'paid', 'completed' => 'bg-green-500',
        'para_assinatura', 'pendente', 'pending' => 'bg-yellow-500',
        'expirado', 'cancelado', 'vencido', 'overdue', 'expired', 'cancelled' => 'bg-red-500',
        'rascunho', 'draft' => 'bg-gray-400',
        default => 'bg-gray-300',
    };
    
    $title = $title ?: ucfirst(str_replace('_', ' ', $status));
@endphp

<span class="inline-flex items-center" title="{{ $title }}">
    <span class="rounded-full {{ $sizeClass }} {{ $colorClasses }} mr-1"></span>
</span>
