<?php

namespace App\Services;

use App\Models\Booker;
use App\Models\Gig;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service para calcular e agregar as métricas financeiras de um Booker.
 */
class BookerFinancialsService
{
    protected GigFinancialCalculatorService $gigCalculator;

    /**
     * @param \App\Services\GigFinancialCalculatorService $gigCalculator
     */
    public function __construct(GigFinancialCalculatorService $gigCalculator)
    {
        $this->gigCalculator = $gigCalculator;
    }

    /**
     * Calcula as principais métricas de comissão para um booker.
     *
     * @param Booker $booker
     * @param Collection|null $gigs Uma coleção de gigs pré-filtradas (opcional, para performance).
     * @return array
     */
    public function getCommissionMetrics(Booker $booker, ?Collection $gigs = null): array
    {
        if (is_null($gigs)) {
            $gigs = $booker->gigs()->get();
        }

        $totalGigs = $gigs->count();
        $commissionReceivedBrl = 0;
        $commissionPendingBrl = 0;

        foreach ($gigs as $gig) {
            // Utiliza o service central para obter o valor da comissão do booker para esta gig.
            $bookerCommission = $this->gigCalculator->calculateBookerCommissionBrl($gig);

            if ($gig->booker_payment_status === 'pago') {
                $commissionReceivedBrl += $bookerCommission;
            } else { // 'pendente' ou qualquer outro status
                $commissionPendingBrl += $bookerCommission;
            }
        }

        return [
            'total_gigs' => $totalGigs,
            'commission_received_brl' => $commissionReceivedBrl,
            'commission_pending_brl' => $commissionPendingBrl,
        ];
    }
}