{{-- resources/views/components/status-badge.blade.php --}}
@props(['status', 'type' => 'default'])

@php
    $baseClasses = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full'; // Removido capitalize daqui
    $colorClasses = '';
    $processedStatus = strtolower(trim($status ?? '')); // Garante minúsculo e remove espaços

    switch (strtolower(trim($type))) {
        case 'contract':
            switch ($processedStatus) {
                case 'assinado':
                case 'concluido':
                    $colorClasses = 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'; break;
                case 'para_assinatura':
                    $colorClasses = 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'; break;
                case 'expirado':
                case 'cancelado':
                    $colorClasses = 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'; break;
                case 'n/a':
                    $colorClasses = 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'; break;
                default:
                     $colorClasses = 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-400'; break;
            }
            break;

        // Usado para status de pagamento (Geral da Gig E Parcelas Individuais)
        case 'payment':
             switch ($processedStatus) {
                case 'pago':
                case 'confirmado': // <-- ADICIONADO 'confirmado' AQUI
                    $colorClasses = 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'; break;
                case 'a_vencer':
                     $colorClasses = 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200'; break;
                case 'vencido':
                     $colorClasses = 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'; break;
                case 'pendente': // Para status da Agência -> Artista/Booker, ou parcela não confirmada e não vencida
                     $colorClasses = 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'; break;
                 case 'cancelado':
                     $colorClasses = 'bg-gray-300 dark:bg-gray-700 text-gray-600 dark:text-gray-300'; break;
                default:
                     $colorClasses = 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-400'; break;
            }
            break;

            case 'payment-internal':
      switch ($processedStatus) { // $processedStatus é strtolower($status)
        case 'pago':
            $colorClasses = 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'; break;
        case 'pendente': // <-- JÁ TEMOS ESTE!
             $colorClasses = 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'; break;
        default:
             $colorClasses = 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-400'; break;
    }
    break;    

        // Se precisar de distinção visual para pagamento interno e pagamento de cliente, podemos separar.
        // Por enquanto, 'payment-internal' usará a mesma lógica de 'payment' se os status forem os mesmos.
        case 'payment-artist':
        case 'payment-booker':
              switch ($processedStatus) {
                case 'pago':
                    $colorClasses = 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'; break;
                case 'pendente':
                     $colorClasses = 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'; break;
                default:
                     $colorClasses = 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-400'; break;
            }
            break;

        case 'cost-confirmation':
              switch ($processedStatus) {
                  case 'confirmado':
                     $colorClasses = 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'; break;
                  case 'pendente':
                     $colorClasses = 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'; break;
                  default: $colorClasses = 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-400'; break;
              }
              break;

        case 'reimbursement':
              switch ($processedStatus) {
                  case 'pago':
                     $colorClasses = 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'; break;
                  case 'aguardando_comprovante':
                     $colorClasses = 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'; break;
                  default: $colorClasses = 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-400'; break;
              }
              break;

        default: // Fallback
             $colorClasses = 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-400'; break;
    }

    // Preparar o texto a ser exibido
    if ($processedStatus === 'n/a') {
        $statusText = 'N/A';
    } else {
        $statusText = str_replace(['_', '-'], ' ', $processedStatus);
        $statusText = ucwords($statusText); // Capitaliza todas as palavras (ex: "Para Assinatura")
        // Ou se preferir só a primeira: $statusText = ucfirst($statusText);
    }

@endphp

<span {{ $attributes->merge(['class' => $baseClasses . ' ' . $colorClasses]) }}>
    {{ $statusText }}
</span>