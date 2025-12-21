@props(['gig', 'interactive' => false, 'size' => 'sm'])

@php
    $stage = $gig->settlement?->settlement_stage ?? 'aguardando_conferencia';
    
    $config = [
        'aguardando_conferencia' => [
            'label' => 'Conferir',
            'bg' => 'bg-gray-100 dark:bg-gray-700',
            'text' => 'text-gray-700 dark:text-gray-300',
            'icon' => 'fa-clipboard-check',
        ],
        'fechamento_enviado' => [
            'label' => 'Ag. NF/Recibo',
            'bg' => 'bg-yellow-100 dark:bg-yellow-900/30',
            'text' => 'text-yellow-700 dark:text-yellow-400',
            'icon' => 'fa-paper-plane',
        ],
        'documentacao_recebida' => [
            'label' => 'Pronto p/ Pgto',
            'bg' => 'bg-blue-100 dark:bg-blue-900/30',
            'text' => 'text-blue-700 dark:text-blue-400',
            'icon' => 'fa-file-invoice',
        ],
        'pago' => [
            'label' => 'Pago',
            'bg' => 'bg-green-100 dark:bg-green-900/30',
            'text' => 'text-green-700 dark:text-green-400',
            'icon' => 'fa-check-circle',
        ],
    ];
    
    $current = $config[$stage] ?? $config['aguardando_conferencia'];
    
    // Override for "Ag. ND" state: pago but requires ND and doesn't have one
    if ($stage === 'pago') {
        $requiresNd = $gig->settlement?->requires_debit_note ?? false;
        if ($requiresNd && !$gig->hasDebitNote()) {
            $current = [
                'label' => 'Ag. ND',
                'bg' => 'bg-orange-100 dark:bg-orange-900/30',
                'text' => 'text-orange-700 dark:text-orange-400',
                'icon' => 'fa-file-invoice-dollar',
            ];
        }
    }
    
    $sizeClasses = match($size) {
        'xs' => 'px-1.5 py-0.5 text-xs',
        'sm' => 'px-2 py-1 text-xs',
        'md' => 'px-2.5 py-1 text-sm',
        'lg' => 'px-3 py-1.5 text-sm',
        default => 'px-2 py-1 text-xs',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 font-medium rounded-full whitespace-nowrap {$current['bg']} {$current['text']} {$sizeClasses}"]) }}>
    <i class="fas {{ $current['icon'] }}"></i>
    <span>{{ $current['label'] }}</span>
</span>
