<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\Gig;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service para calcular e agregar as métricas financeiras de um Artista.
 */
class ArtistFinancialsService
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
     * Calcula as principais métricas financeiras para um artista.
     *
     * @param Artist $artist
     * @param Collection|null $gigs Uma coleção de gigs pré-filtradas (opcional, para performance).
     * @return array
     */
    public function getFinancialMetrics(Artist $artist, ?Collection $gigs = null): array
    {
        // Se uma coleção de gigs não for passada, busca todas as gigs do artista.
        // Isso permite reutilizar a lógica se já tivermos as gigs filtradas no controller.
        if (is_null($gigs)) {
            $gigs = $artist->gigs()->get();
        }

        $totalGigs = $gigs->count();
        $cacheReceivedBrl = 0;
        $cachePendingBrl = 0;

        foreach ($gigs as $gig) {
            // Utiliza o service central para obter o valor líquido do artista para esta gig.
            // Isso garante que a regra de negócio (Cachê Bruto - Comissão Agência) seja sempre a mesma.
            $artistNetPayout = $this->gigCalculator->calculateArtistNetPayoutBrl($gig);

            if ($gig->artist_payment_status === 'pago') {
                $cacheReceivedBrl += $artistNetPayout;
            } else { // 'pendente' ou qualquer outro status
                $cachePendingBrl += $artistNetPayout;
            }
        }

        return [
            'total_gigs' => $totalGigs,
            'cache_received_brl' => $cacheReceivedBrl,
            'cache_pending_brl' => $cachePendingBrl,
        ];
    }
}