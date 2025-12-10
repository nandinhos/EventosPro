{{--
    Componente: x-reimbursement-badge
    Exibe badge visual do estágio de comprovante de despesa.
    Workflow simplificado: aguardando_comprovante | pago
    
    Props:
    - $cost: Modelo GigCost (opcional)
    - $stage: String do estágio (usa este se $cost não for passado)
    - $size: xs, sm, md, lg (default: sm)
--}}
@props([
    'cost' => null,
    'stage' => null,
    'size' => 'sm'
])

@php
    // Determinar o estágio (normaliza estágios legados para 'pago')
    $rawStage = $stage ?? ($cost?->reimbursement_stage ?? 'aguardando_comprovante');
    $legacyStages = ['comprovante_recebido', 'conferido', 'reembolsado'];
    $effectiveStage = in_array($rawStage, $legacyStages) ? 'pago' : $rawStage;
    
    // Configurações simplificadas (2 estágios)
    $stageConfig = [
        'aguardando_comprovante' => [
            'label' => 'Aguardando',
            'shortLabel' => 'Aguard.',
            'bg' => 'bg-gray-100 dark:bg-gray-700',
            'text' => 'text-gray-700 dark:text-gray-300',
            'icon' => 'clock',
            'iconColor' => 'text-gray-500',
        ],
        'pago' => [
            'label' => 'Pago',
            'shortLabel' => 'Pago',
            'bg' => 'bg-green-100 dark:bg-green-900/40',
            'text' => 'text-green-700 dark:text-green-300',
            'icon' => 'check-circle',
            'iconColor' => 'text-green-500',
        ],
    ];
    
    $config = $stageConfig[$effectiveStage] ?? $stageConfig['aguardando_comprovante'];
    
    // Tamanhos
    $sizes = [
        'xs' => 'text-[10px] px-1.5 py-0.5',
        'sm' => 'text-xs px-2 py-1',
        'md' => 'text-sm px-2.5 py-1',
        'lg' => 'text-base px-3 py-1.5',
    ];
    
    $sizeClass = $sizes[$size] ?? $sizes['sm'];
@endphp

<span class="inline-flex items-center gap-1 rounded-full font-medium {{ $config['bg'] }} {{ $config['text'] }} {{ $sizeClass }}">
    <i class="fas fa-{{ $config['icon'] }} {{ $config['iconColor'] }}"></i>
    <span class="hidden sm:inline">{{ $config['label'] }}</span>
    <span class="sm:hidden">{{ $config['shortLabel'] }}</span>
</span>
