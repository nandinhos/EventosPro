{{-- resources/views/components/status-badge.blade.php --}}

{{--
    Componente Blade para exibir um badge de status colorido.
    Uso: <x-status-badge :status="$variavelStatus" type="tipoDoStatus" />

    Props:
    - status (string, required): O valor do status (ex: 'pago', 'pendente', 'assinado', 'a_vencer').
    - type (string, optional, default: 'default'): O tipo de status para aplicar as cores corretas
      (ex: 'contract', 'payment', 'payment-artist', 'payment-booker').
--}}
@props(['status', 'type' => 'default'])

@php
    // Define as classes base para todos os badges
    $baseClasses = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize'; // capitalize para primeira letra maiúscula

    // Define as classes de cor com base no tipo e no status
    $colorClasses = '';

    switch (strtolower($type)) { // Converte tipo para minúsculo para segurança
        // Status relacionados a Contrato Formal
        case 'contract':
            switch (strtolower($status)) { // Converte status para minúsculo
                case 'assinado':
                case 'concluido': // Concluído pode ter a mesma cor de assinado
                    $colorClasses = 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'; break;
                case 'para_assinatura':
                    $colorClasses = 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'; break;
                case 'expirado':
                case 'cancelado':
                    $colorClasses = 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'; break;
                case 'n/a': // Sem contrato formal
                    $colorClasses = 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'; break;
                default: // Status desconhecido
                     $colorClasses = 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-400'; break;
            }
            break;

        // Status relacionados a Pagamento (Geral do Cliente ou Parcela Individual)
        case 'payment':
             switch (strtolower($status)) {
                case 'pago':
                    $colorClasses = 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'; break;
                case 'a_vencer': // Pagamento futuro, ainda não vencido
                     $colorClasses = 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200'; break;
                case 'vencido': // Passou da data e ainda não foi pago
                     $colorClasses = 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'; break;
                case 'pendente': // Usado para status individual de parcela, antes de vencer
                     $colorClasses = 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'; break;
                 case 'cancelado': // Pagamento cancelado
                     $colorClasses = 'bg-gray-300 dark:bg-gray-700 text-gray-600 dark:text-gray-300'; break;
                default:
                     $colorClasses = 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-400'; break;
            }
            break;

        // Status específico do pagamento para Artista ou Booker
         case 'payment-artist':
         case 'payment-booker':
              switch (strtolower($status)) {
                case 'pago':
                    $colorClasses = 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'; break;
                case 'pendente':
                     $colorClasses = 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'; break;
                default:
                     $colorClasses = 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-400'; break;
            }
            break;

        // Tipo padrão ou desconhecido
        default:
             $colorClasses = 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-400'; break;
    }

    // Preparar o texto a ser exibido (remove underscores, capitaliza palavras)
    $statusText = str_replace(['_', '-'], ' ', $status ?? ''); // Troca _ e - por espaço
    // $statusText = ucwords($statusText); // Capitaliza todas as palavras
    $statusText = $status === 'n/a' ? 'N/A' : ucfirst($statusText); // Capitaliza só a primeira, mantém N/A

@endphp

{{-- Renderiza o span com as classes calculadas --}}
{{-- $attributes->merge permite passar classes adicionais ao chamar o componente --}}
<span {{ $attributes->merge(['class' => $baseClasses . ' ' . $colorClasses]) }}>
    {{ $statusText }}
</span>