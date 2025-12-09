@props([
    'cost' => null,
    'stage' => null,
    'size' => 'sm'
])

@php
    // Determinar o estágio
    $stage = $stage ?? ($cost?->reimbursement_stage ?? 'aguardando_comprovante');
    
    // Configurações de cada estágio (workflow de Despesas/Custos: 3 estágios)
    $stageConfig = [
        'aguardando_comprovante' => [
            'label' => 'Aguardando',
            'shortLabel' => 'Aguard.',
            'bg' => 'bg-gray-100 dark:bg-gray-700',
            'text' => 'text-gray-700 dark:text-gray-300',
            'icon' => 'clock',
            'iconColor' => 'text-gray-500',
        ],
        'comprovante_recebido' => [
            'label' => 'Recebido',
            'shortLabel' => 'Receb.',
            'bg' => 'bg-yellow-100 dark:bg-yellow-900/40',
            'text' => 'text-yellow-700 dark:text-yellow-300',
            'icon' => 'file-alt',
            'iconColor' => 'text-yellow-500',
        ],
        // Estágio "conferido" removido do workflow de despesas (mantido apenas para compatibilidade)
        'conferido' => [
            'label' => 'Pago',
            'shortLabel' => 'Pago',
            'bg' => 'bg-green-100 dark:bg-green-900/40',
            'text' => 'text-green-700 dark:text-green-300',
            'icon' => 'check-circle',
            'iconColor' => 'text-green-500',
        ],
        'reembolsado' => [
            'label' => 'Pago',
            'shortLabel' => 'Pago',
            'bg' => 'bg-green-100 dark:bg-green-900/40',
            'text' => 'text-green-700 dark:text-green-300',
            'icon' => 'check-circle',
            'iconColor' => 'text-green-500',
        ],
    ];
    
    $config = $stageConfig[$stage] ?? $stageConfig['aguardando_comprovante'];
    
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
