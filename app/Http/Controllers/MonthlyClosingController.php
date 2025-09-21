<?php

namespace App\Http\Controllers;

use App\Models\Booker;
use App\Models\Gig;
use App\Services\GigFinancialCalculatorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MonthlyClosingController extends Controller
{
    protected $gigCalculator;

    public function __construct(GigFinancialCalculatorService $gigCalculator)
    {
        $this->gigCalculator = $gigCalculator;
    }

    public function index(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));
        $bookerId = $request->input('booker_id');

        // Definir período do mês
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Obter dados do relatório
        $reportData = $this->generateReportData($startDate, $endDate, $bookerId);

        // Obter lista de bookers para o filtro
        $bookers = Booker::orderBy('name')->get();
        $artists = \App\Models\Artist::orderBy('name')->get();

        // Preparar dados para os gráficos
        $bookerRevenueData = $this->prepareBookerRevenueData($reportData['booker_data']);
        $bookerPieData = $this->prepareBookerPieData($reportData['booker_data']);
        $artistPerformanceData = $this->prepareArtistPerformanceData($reportData['artist_gigs']);

        return view('finance.monthly-closing.index', [
            'reportData' => $reportData,
            'bookers' => $bookers,
            'artists' => $artists,
            'selectedBookerId' => $bookerId,
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'months' => $this->getMonthsList(),
            'years' => $this->getYearsList(),
            'bookerRevenueData' => $bookerRevenueData,
            'bookerPieData' => $bookerPieData,
            'artistPerformanceData' => $artistPerformanceData,
        ]);
    }

    public function export(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));
        $bookerId = $request->input('booker_id');
        $artistId = $request->input('artist_id');
        $format = $request->input('format', 'pdf');

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $reportData = $this->generateReportData($startDate, $endDate, $bookerId, $artistId);
        $booker = $bookerId ? Booker::find($bookerId) : null;
        $artist = $artistId ? \App\Models\Artist::find($artistId) : null;

        $filename = $this->generateFilename($startDate, $booker, $artist, $format);

        switch ($format) {
            case 'pdf':
                return $this->generatePdf($reportData, $booker, $artist, $startDate, $filename);
            case 'csv':
                return $this->exportCsv($reportData, $filename);
            case 'json':
                return $this->exportJson($reportData, $filename);
            default:
                return $this->generatePdf($reportData, $booker, $artist, $startDate, $filename);
        }
    }

    public function exportPdf(Request $request)
    {
        return $this->export($request->merge(['format' => 'pdf']));
    }

    protected function generatePdf($reportData, $booker, $artist, $startDate, $filename)
    {
        // Preparar dados para os gráficos no PDF
        $bookerRevenueData = $this->prepareBookerRevenueData($reportData['booker_data']);
        $bookerPieData = $this->prepareBookerPieData($reportData['booker_data']);
        $artistPerformanceData = $this->prepareArtistPerformanceData($reportData['artist_gigs']);

        $pdf = PDF::loadView('reports.exports.monthly_closing_pdf', [
            'reportData' => $reportData,
            'booker' => $booker,
            'artist' => $artist,
            'month' => $startDate->format('m/Y'),
            'bookerRevenueData' => $bookerRevenueData,
            'bookerPieData' => $bookerPieData,
            'artistPerformanceData' => $artistPerformanceData,
        ]);

        return $pdf->download($filename);
    }

    protected function exportCsv($reportData, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($reportData) {
            $file = fopen('php://output', 'w');
            
            // Cabeçalho CSV
            fputcsv($file, [
                'Data',
                'Artista',
                'Booker',
                'Local',
                'Cidade/Estado',
                'Cachê Bruto (R$)',
                'Comissão Booker (R$)',
                'Comissão Agência (R$)',
                'Valor Líquido (R$)'
            ]);

            // Dados das gigs
            if (isset($reportData['artist_gigs'])) {
                foreach ($reportData['artist_gigs'] as $artistData) {
                    foreach ($artistData['gigs'] as $gig) {
                        fputcsv($file, [
                            $gig->gig_date->format('d/m/Y'),
                            $artistData['artist']->name,
                            $gig->booker->name ?? '',
                            $gig->location_event_details,
                            $gig->location_city . '/' . $gig->location_state,
                            number_format($gig->cache_value_brl, 2, ',', '.'),
                            number_format($gig->booker_commission_value, 2, ',', '.'),
                            number_format($gig->agency_commission_value, 2, ',', '.'),
                            number_format($gig->cache_value_brl - $gig->booker_commission_value - $gig->agency_commission_value, 2, ',', '.')
                        ]);
                    }
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function exportJson($reportData, $filename)
    {
        $data = [
            'periodo' => [
                'inicio' => $reportData['start_date']->format('Y-m-d'),
                'fim' => $reportData['end_date']->format('Y-m-d')
            ],
            'resumo' => [
                'total_gigs' => $reportData['total_gigs'],
                'total_cache_brl' => $reportData['total_cache_brl'],
                'total_booker_commission' => $reportData['total_booker_commission'],
                'total_agency_commission' => $reportData['total_agency_commission']
            ],
            'bookers' => $reportData['booker_data']->values()->map(function($data) {
                return [
                    'nome' => $data['booker']->name,
                    'total_gigs' => $data['total_gigs'],
                    'cache_liquido_base' => $data['cache_liquido_base'],
                    'total_booker_commission' => $data['total_booker_commission'],
                    'total_agency_commission' => $data['total_agency_commission'],
                    'valor_liquido' => $data['net_value']
                ];
            }),
            'artistas' => $reportData['artist_gigs']->values()->map(function($data) {
                return [
                    'nome' => $data['artist']->name,
                    'total_gigs' => $data['total_gigs'],
                    'total_cache_brl' => $data['total_cache_brl'],
                    'gigs' => $data['gigs']->map(function($gig) {
                        return [
                            'data' => $gig->gig_date->format('Y-m-d'),
                            'local' => $gig->location_event_details,
                            'cidade' => $gig->location_city,
                            'estado' => $gig->location_state,
                            'cache_bruto' => $gig->cache_value_brl,
                            'comissao_booker' => $gig->booker_commission_value,
                            'comissao_agencia' => $gig->agency_commission_value
                        ];
                    })
                ];
            })
        ];

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    protected function generateFilename($startDate, $booker, $artist, $format)
    {
        $filename = 'fechamento-mensal-' . $startDate->format('m-Y');
        
        if ($booker) {
            $filename .= '-booker-' . Str::slug($booker->name);
        }
        
        if ($artist) {
            $filename .= '-artista-' . Str::slug($artist->name);
        }
        
        return $filename . '.' . $format;
    }

    protected function generateReportData($startDate, $endDate, $bookerId = null, $artistId = null)
    {
        $query = Gig::with(['artist', 'booker', 'payments', 'costs'])
            ->whereBetween('gig_date', [$startDate, $endDate])
            ->orderBy('gig_date');

        if ($bookerId) {
            $query->where('booker_id', $bookerId);
        }

        if ($artistId) {
            $query->where('artist_id', $artistId);
        }

        $gigs = $query->get();

        // Calcular totais e agregações
        $totalGigs = $gigs->count();

        // Usar o serviço para calcular os valores corretos
        $totalCacheLiquidoBase = $gigs->sum(function ($gig) {
            return $this->gigCalculator->calculateGrossCashBrl($gig);
        });

        $totalBookerCommission = $gigs->sum(function ($gig) {
            return $this->gigCalculator->calculateBookerCommissionBrl($gig);
        });

        $totalAgencyCommission = $gigs->sum(function ($gig) {
            return $this->gigCalculator->calculateAgencyNetCommissionBrl($gig);
        });

        $totalCacheBrl = $gigs->sum('cache_value_brl');
        $totalDespesas = $gigs->sum(function ($gig) {
            return $gig->costs->where('is_confirmed', true)->sum('value');
        });

        // Agrupar por booker para o gráfico e tabela
        $bookerData = $gigs->groupBy('booker_id')->map(function ($bookerGigs) {
            $booker = $bookerGigs->first()->booker;

            // Calcular totais usando o serviço para cada gig
            $totalCacheLiquidoBase = 0;
            $totalBookerCommission = 0;
            $totalAgencyCommission = 0;
            $totalCacheBrl = 0;
            $totalDespesas = 0;
            $totalPago = 0;
            $totalPendente = 0;

            foreach ($bookerGigs as $gig) {
                $cacheLiquido = $this->gigCalculator->calculateGrossCashBrl($gig);
                $bookerCommission = $this->gigCalculator->calculateBookerCommissionBrl($gig);
                $agencyCommission = $this->gigCalculator->calculateAgencyNetCommissionBrl($gig);
                $pago = $gig->payments->sum('amount_paid_brl');
                $despesas = $gig->costs->where('is_confirmed', true)->sum('value');

                $totalCacheLiquidoBase += $cacheLiquido;
                $totalBookerCommission += $bookerCommission;
                $totalAgencyCommission += $agencyCommission;
                $totalCacheBrl += $gig->cache_value_brl;
                $totalDespesas += $despesas;
                $totalPago += $pago;
                $totalPendente += ($gig->cache_value_brl - $pago) > 0 ? ($gig->cache_value_brl - $pago) : 0;
            }

            $lucroLiquido = $totalCacheLiquidoBase - $totalBookerCommission - $totalAgencyCommission;

            return [
                'booker' => $booker,
                'total_gigs' => $bookerGigs->count(),
                'total_cache_brl' => $totalCacheBrl,
                'total_despesas' => $totalDespesas,
                'cache_liquido_base' => $totalCacheLiquidoBase,
                'total_booker_commission' => $totalBookerCommission,
                'total_agency_commission' => $totalAgencyCommission,
                'total_paid' => $totalPago,
                'pending_payments' => $totalPendente,
                'net_value' => $lucroLiquido,
                'lucro_percentual' => $totalCacheLiquidoBase > 0 ? ($lucroLiquido / $totalCacheLiquidoBase) * 100 : 0,
            ];
        })->filter()->sortByDesc('cache_liquido_base'); // Remove entradas nulas e ordena por faturamento

        // Agrupar por artista e ordenar por data
        $artistGigs = $gigs->groupBy('artist_id')
            ->map(function ($gigs) {
                $artist = $gigs->first()->artist;

                // Ordenar as gigs por data
                $sortedGigs = $gigs->sortBy('gig_date');

                return [
                    'artist' => $artist,
                    'gigs' => $sortedGigs,
                    'total_gigs' => $gigs->count(),
                    'total_cache_brl' => $gigs->sum('cache_value_brl'),
                    'total_booker_commission' => $gigs->sum('booker_commission_value'),
                    'total_agency_commission' => $gigs->sum('agency_commission_value'),
                ];
            })
            ->sortBy('artist.name'); // Ordenar artistas por nome

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_gigs' => $totalGigs,
            'total_cache_brl' => $totalCacheBrl,
            'total_booker_commission' => $totalBookerCommission,
            'total_agency_commission' => $totalAgencyCommission,
            'booker_data' => $bookerData,
            'artist_gigs' => $artistGigs,
            'gigs' => $gigs,
        ];
    }

    protected function getMonthsList()
    {
        return [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
            '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
            '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
            '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro',
        ];
    }

    protected function getYearsList()
    {
        $startYear = 2023; // Ano de início dos registros
        $currentYear = (int) date('Y');
        $years = [];

        for ($year = $startYear; $year <= $currentYear; $year++) {
            $years[$year] = $year;
        }

        return array_reverse($years, true);
    }

    /**
     * Prepara dados para o gráfico de barras de faturamento por booker
     */
    protected function prepareBookerRevenueData($bookerData)
    {
        $colors = [
            '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
            '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'
        ];

        return $bookerData->values()->map(function ($data, $index) use ($colors) {
            return [
                'label' => $data['booker']->name,
                'revenue' => $data['cache_liquido_base'],
                'booker_commission' => $data['total_booker_commission'],
                'agency_commission' => $data['total_agency_commission'],
                'net_value' => $data['net_value'],
                'color' => $colors[$index % count($colors)],
            ];
        })->toArray();
    }

    /**
     * Prepara dados para o gráfico de pizza de distribuição por booker
     */
    protected function prepareBookerPieData($bookerData)
    {
        $colors = [
            '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
            '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'
        ];

        return $bookerData->values()->map(function ($data, $index) use ($colors) {
            return [
                'label' => $data['booker']->name,
                'value' => $data['cache_liquido_base'],
                'color' => $colors[$index % count($colors)],
            ];
        })->filter(function ($item) {
            return $item['value'] > 0;
        })->values()->toArray();
    }

    /**
     * Prepara dados para o gráfico de performance dos artistas
     */
    protected function prepareArtistPerformanceData($artistGigs)
    {
        $colors = [
            '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
            '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'
        ];

        return $artistGigs->values()->map(function ($data, $index) use ($colors) {
            return [
                'label' => $data['artist']->name,
                'gigs_count' => $data['total_gigs'],
                'total_revenue' => $data['total_cache_brl'],
                'color' => $colors[$index % count($colors)],
            ];
        })->sortByDesc('gigs_count')->take(10)->values()->toArray();
    }
}
