<?php

namespace App\Observers;

use App\Models\Gig;
use Illuminate\Support\Facades\Log;

class GigObserver
{
    /**
     * Handle the Gig "saving" event.
     * Este método é chamado automaticamente pelo Laravel tanto para create quanto para update,
     * interceptando a operação antes da persistência no banco de dados.
     *
     * Responsabilidades:
     * 1. Calcular e atualizar a comissão da agência (quando baseada em percentual)
     * 2. Calcular e atualizar a comissão do booker (quando baseada em percentual)
     * 3. Calcular e atualizar a comissão líquida (diferença entre comissão da agência e do booker)
     * 4. Registrar logs para auditoria de alterações significativas
     *
     * @param Gig $gig Instância do modelo Gig que está sendo salvo
     */
    public function saving(Gig $gig): void
    {
        try {
            // Obtém todas as despesas da Gig
            $allExpenses = $gig->costs()->get();
            $confirmedExpenses = $allExpenses->where('is_confirmed', true);
            $unconfirmedExpenses = $allExpenses->where('is_confirmed', false);

            // Log dos valores iniciais com detalhamento das despesas
            Log::info('Iniciando cálculo de comissões', [
                'gig_id' => $gig->id ?? 'novo',
                'cache_value' => $gig->cache_value,
                'currency' => $gig->currency,
                'cache_value_brl' => $gig->cache_value_brl,
                'despesas' => [
                    'confirmadas' => $confirmedExpenses->map(function($expense) {
                        return [
                            'id' => $expense->id,
                            'descricao' => $expense->description,
                            'valor' => $expense->value,
                            'centro_custo' => $expense->costCenter->name ?? 'N/A',
                            'data_confirmacao' => $expense->confirmed_at
                        ];
                    }),
                    'nao_confirmadas' => $unconfirmedExpenses->map(function($expense) {
                        return [
                            'id' => $expense->id,
                            'descricao' => $expense->description,
                            'valor' => $expense->value,
                            'centro_custo' => $expense->costCenter->name ?? 'N/A'
                        ];
                    }),
                    'total_confirmadas' => $gig->confirmed_expenses_total_brl,
                ],
                'net_cache_value' => $gig->net_cache_value_brl,
                'commission_base' => $gig->commission_base_brl
            ]);


            // Armazena os valores originais para log
            $originalAgencyCommission = $gig->agency_commission_value;
            $originalBookerCommission = $gig->booker_commission_value;
            $originalLiquidCommission = $gig->liquid_commission_value;

            // Calcula e atualiza a comissão da agência se for do tipo percentual
            // Calcula a comissão da agência
            if (strtoupper($gig->agency_commission_type ?? '') === 'PERCENT' && isset($gig->agency_commission_rate)) {
                $base = $gig->commission_base_brl;
                $taxa = $gig->agency_commission_rate;
                $valor_calculado = ($base * $taxa) / 100;
                $gig->agency_commission_value = $valor_calculado;
                
                Log::info('Comissão da agência calculada', [
                    'gig_id' => $gig->id ?? 'novo',
                    'tipo' => 'percentual',
                    'taxa' => $taxa,
                    'valor_calculado' => $valor_calculado,
                    'base_calculo' => $base
                ]);
            } else {
                Log::info('Comissão da agência mantida fixa', [
                    'gig_id' => $gig->id ?? 'novo',
                    'tipo' => $gig->agency_commission_type,
                    'valor_fixo' => $gig->agency_commission_value
                ]);
            }

            // Calcula a comissão do booker
            if (strtoupper($gig->booker_commission_type ?? '') === 'PERCENT' && isset($gig->booker_commission_rate)) {
                $base = $gig->commission_base_brl;
                $taxa = $gig->booker_commission_rate;
                $valor_calculado = ($base * $taxa) / 100;
                $gig->booker_commission_value = $valor_calculado;
                
                Log::info('Comissão do booker calculada', [
                    'gig_id' => $gig->id ?? 'novo',
                    'tipo' => 'percentual',
                    'taxa' => $taxa,
                    'valor_calculado' => $valor_calculado,
                    'base_calculo' => $base
                ]);
            } else {
                Log::info('Comissão do booker mantida fixa', [
                    'gig_id' => $gig->id ?? 'novo',
                    'tipo' => $gig->booker_commission_type,
                    'valor_fixo' => $gig->booker_commission_value
                ]);
            }

            // Calcula a comissão líquida
            $gig->liquid_commission_value = ($gig->agency_commission_value ?? 0) - ($gig->booker_commission_value ?? 0);
            Log::info('Valores finais das comissões calculados', [
                'gig_id' => $gig->id ?? 'novo',
                'agency_commission' => [
                    'anterior' => $gig->getOriginal('agency_commission_value'),
                    'atual' => $gig->agency_commission_value,
                    'diferenca' => ($gig->agency_commission_value ?? 0) - ($gig->getOriginal('agency_commission_value') ?? 0)
                ],
                'booker_commission' => [
                    'anterior' => $gig->getOriginal('booker_commission_value'),
                    'atual' => $gig->booker_commission_value,
                    'diferenca' => ($gig->booker_commission_value ?? 0) - ($gig->getOriginal('booker_commission_value') ?? 0)
                ],
                'liquid_commission' => [
                    'anterior' => $gig->getOriginal('liquid_commission_value'),
                    'atual' => $gig->liquid_commission_value,
                    'diferenca' => ($gig->liquid_commission_value ?? 0) - ($gig->getOriginal('liquid_commission_value') ?? 0)
                ]
            ]);

            // Calcula e atualiza a comissão do booker se for do tipo percentual
            if (strtoupper($gig->booker_commission_type ?? '') === 'PERCENT' && isset($gig->booker_commission_rate)) {
                $gig->booker_commission_value = $gig->getBookerCommissionValueAttribute(null);
                Log::info('Comissão do booker calculada', [
                    'gig_id' => $gig->id ?? 'novo',
                    'tipo' => 'percentual',
                    'taxa' => $gig->booker_commission_rate,
                    'valor_calculado' => $gig->booker_commission_value,
                    'base_calculo' => $gig->commission_base_brl
                ]);
            } else {
                Log::info('Comissão do booker mantida fixa', [
                    'gig_id' => $gig->id ?? 'novo',
                    'tipo' => $gig->booker_commission_type,
                    'valor_fixo' => $gig->booker_commission_value
                ]);
            }

            // Calcula e atualiza a comissão líquida (sempre calculada, pois depende das outras comissões)
            $gig->liquid_commission_value = $gig->getLiquidCommissionValueAttribute(null);

            // Registra log das alterações nos valores das comissões
            Log::info('Valores finais das comissões calculados', [
                'gig_id' => $gig->id ?? 'novo',
                'agency_commission' => [
                    'anterior' => $originalAgencyCommission,
                    'atual' => $gig->agency_commission_value,
                    'diferenca' => $gig->agency_commission_value - ($originalAgencyCommission ?? 0)
                ],
                'booker_commission' => [
                    'anterior' => $originalBookerCommission,
                    'atual' => $gig->booker_commission_value,
                    'diferenca' => $gig->booker_commission_value - ($originalBookerCommission ?? 0)
                ],
                'liquid_commission' => [
                    'anterior' => $originalLiquidCommission,
                    'atual' => $gig->liquid_commission_value,
                    'diferenca' => $gig->liquid_commission_value - ($originalLiquidCommission ?? 0)
                ]
            ]);
            if ($originalAgencyCommission !== $gig->agency_commission_value ||
                $originalBookerCommission !== $gig->booker_commission_value ||
                $originalLiquidCommission !== $gig->liquid_commission_value) {
                
                Log::info('Valores de comissão atualizados', [
                    'gig_id' => $gig->id,
                    'alterações' => [
                        'comissão_agência' => [
                            'anterior' => $originalAgencyCommission,
                            'novo' => $gig->agency_commission_value
                        ],
                        'comissão_booker' => [
                            'anterior' => $originalBookerCommission,
                            'novo' => $gig->booker_commission_value
                        ],
                        'comissão_líquida' => [
                            'anterior' => $originalLiquidCommission,
                            'novo' => $gig->liquid_commission_value
                        ]
                    ]
                ]);
            }
        } catch (\Exception $e) {
            // Registra qualquer erro durante o cálculo das comissões
            Log::error('Erro ao calcular comissões', [
                'gig_id' => $gig->id ?? 'novo',
                'erro' => $e->getMessage()
            ]);
            throw $e; // Relança a exceção para que o Laravel possa tratá-la
        }
    }
}