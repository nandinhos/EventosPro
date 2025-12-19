{{-- resources/views/components/status-badge.blade.php --}}
@props(['status', 'type' => 'default'])

@php
    $baseClasses = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full';
    $processedStatus = strtolower(trim($status ?? ''));
    $processedType = strtolower(trim($type));

    // Configuração de cores por tipo e status
    $statusConfig = [
        'contract' => [
            'assinado' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
            'concluido' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
            'para_assinatura' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
            'expirado' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
            'cancelado' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
            'n/a' => 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
        ],
        'payment' => [
            'pago' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
            'confirmado' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
            'a_vencer' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
            'vencido' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
            'pendente' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
            'cancelado' => 'bg-gray-300 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
        ],
        'payment-internal' => [
            'pago' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
            'pendente' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
        ],
        'payment-artist' => [
            'pago' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
            'pendente' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
        ],
        'payment-booker' => [
            'pago' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
            'pendente' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
        ],
        'cost-confirmation' => [
            'confirmado' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
            'pendente' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
        ],
        'reimbursement' => [
            'pago' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
            'aguardando_comprovante' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
        ],
    ];

    $defaultClasses = 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-400';
    $colorClasses = $statusConfig[$processedType][$processedStatus] ?? $defaultClasses;

    // Preparar o texto a ser exibido
    $statusText = $processedStatus === 'n/a'
        ? 'N/A'
        : ucwords(str_replace(['_', '-'], ' ', $processedStatus));
@endphp

<span {{ $attributes->merge(['class' => $baseClasses . ' ' . $colorClasses]) }}>
    {{ $statusText }}
</span>
