<?php

namespace Tests\Unit\Exports;

use App\Exports\OverviewReportExport;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\TestCase;

class OverviewReportExportTest extends TestCase
{
    private function getSampleData(): array
    {
        return [
            'dataByArtist' => [
                [
                    'artist_name' => 'Artist One',
                    'gigs' => [
                        [
                            'gig_date' => '2024-01-15',
                            'artist_name' => 'Artist One',
                            'booker_name' => 'Booker One',
                            'location_event_details' => 'Venue A - Event X',
                            'cache_bruto_original' => 'USD 1,000.00',
                            'cache_bruto_brl' => 5000.00,
                            'total_despesas_confirmadas_brl' => 500.00,
                            'cache_liquido_base_brl' => 4500.00,
                            'repasse_estimado_artista_brl' => 3600.00,
                            'comissao_agencia_brl' => 450.00,
                            'comissao_booker_brl' => 450.00,
                            'comissao_agencia_liquida_brl' => 450.00,
                            'contract_status' => 'Confirmado',
                            'payment_status' => 'Pago',
                        ],
                        [
                            'gig_date' => '2024-01-20',
                            'artist_name' => 'Artist One',
                            'booker_name' => 'Booker Two',
                            'location_event_details' => 'Venue B - Event Y',
                            'cache_bruto_original' => 'EUR 800.00',
                            'cache_bruto_brl' => 4800.00,
                            'total_despesas_confirmadas_brl' => 300.00,
                            'cache_liquido_base_brl' => 4500.00,
                            'repasse_estimado_artista_brl' => 3600.00,
                            'comissao_agencia_brl' => 450.00,
                            'comissao_booker_brl' => 450.00,
                            'comissao_agencia_liquida_brl' => 450.00,
                            'contract_status' => 'Pendente',
                            'payment_status' => 'Pendente',
                        ],
                    ],
                ],
                [
                    'artist_name' => 'Artist Two',
                    'gigs' => [
                        [
                            'gig_date' => '2024-01-25',
                            'artist_name' => 'Artist Two',
                            'booker_name' => 'Booker Three',
                            'location_event_details' => 'Venue C - Event Z',
                            'cache_bruto_original' => 'BRL 3,000.00',
                            'cache_bruto_brl' => 3000.00,
                            'total_despesas_confirmadas_brl' => 200.00,
                            'cache_liquido_base_brl' => 2800.00,
                            'repasse_estimado_artista_brl' => 2240.00,
                            'comissao_agencia_brl' => 280.00,
                            'comissao_booker_brl' => 280.00,
                            'comissao_agencia_liquida_brl' => 280.00,
                            'contract_status' => 'Confirmado',
                            'payment_status' => 'Pago',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_it_can_be_instantiated()
    {
        $data = $this->getSampleData();
        $export = new OverviewReportExport($data);

        $this->assertInstanceOf(OverviewReportExport::class, $export);
    }

    public function test_collection_flattens_data_correctly()
    {
        $data = $this->getSampleData();
        $export = new OverviewReportExport($data);

        $collection = $export->collection();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(3, $collection); // 2 gigs from Artist One + 1 gig from Artist Two

        // Verify first gig
        $firstGig = $collection->first();
        $this->assertEquals('2024-01-15', $firstGig['gig_date']);
        $this->assertEquals('Artist One', $firstGig['artist_name']);
        $this->assertEquals('Booker One', $firstGig['booker_name']);

        // Verify last gig
        $lastGig = $collection->last();
        $this->assertEquals('2024-01-25', $lastGig['gig_date']);
        $this->assertEquals('Artist Two', $lastGig['artist_name']);
        $this->assertEquals('Booker Three', $lastGig['booker_name']);
    }

    public function test_headings_returns_correct_headers()
    {
        $data = $this->getSampleData();
        $export = new OverviewReportExport($data);

        $headings = $export->headings();

        $expected = [
            'Data Gig', 'Artista', 'Booker', 'Local/Evento',
            'Cachê (Orig)', 'Cachê (BRL)', 'Despesas', 'Cachê Líq. Base',
            'Repasse Artista', 'Com. Agência', 'Com. Booker', 'Com. Ag. Líq.',
            'Status Contrato', 'Status Pgto.',
        ];

        $this->assertEquals($expected, $headings);
    }

    public function test_map_transforms_row_data_with_formatting()
    {
        $data = $this->getSampleData();
        $export = new OverviewReportExport($data);

        $sampleRow = [
            'gig_date' => '2024-01-15',
            'artist_name' => 'Artist One',
            'booker_name' => 'Booker One',
            'location_event_details' => 'Venue A - Event X',
            'cache_bruto_original' => 'USD 1,000.00',
            'cache_bruto_brl' => 5000.00,
            'total_despesas_confirmadas_brl' => 500.00,
            'cache_liquido_base_brl' => 4500.00,
            'repasse_estimado_artista_brl' => 3600.00,
            'comissao_agencia_brl' => 450.00,
            'comissao_booker_brl' => 450.00,
            'comissao_agencia_liquida_brl' => 450.00,
            'contract_status' => 'Confirmado',
            'payment_status' => 'Pago',
        ];

        $mappedRow = $export->map($sampleRow);

        $expected = [
            '2024-01-15',
            'Artist One',
            'Booker One',
            'Venue A - Event X',
            'USD 1,000.00',
            '5.000,00', // Formatted with Brazilian number format
            '500,00',
            '4.500,00',
            '3.600,00',
            '450,00',
            '450,00',
            '450,00',
            'Confirmado',
            'Pago',
        ];

        $this->assertEquals($expected, $mappedRow);
    }

    public function test_styles_returns_bold_header_style()
    {
        $data = $this->getSampleData();
        $export = new OverviewReportExport($data);

        $worksheet = $this->createMock(Worksheet::class);
        $styles = $export->styles($worksheet);

        $expected = [
            1 => ['font' => ['bold' => true]],
        ];

        $this->assertEquals($expected, $styles);
    }

    public function test_implements_required_interfaces()
    {
        $data = $this->getSampleData();
        $export = new OverviewReportExport($data);

        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\FromCollection::class, $export);
        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\WithHeadings::class, $export);
        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\WithMapping::class, $export);
        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\WithStyles::class, $export);
    }

    public function test_handles_empty_data()
    {
        $data = ['dataByArtist' => []];
        $export = new OverviewReportExport($data);

        $collection = $export->collection();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(0, $collection);
    }

    public function test_handles_artists_with_no_gigs()
    {
        $data = [
            'dataByArtist' => [
                [
                    'artist_name' => 'Artist One',
                    'gigs' => [],
                ],
                [
                    'artist_name' => 'Artist Two',
                    'gigs' => [],
                ],
            ],
        ];
        $export = new OverviewReportExport($data);

        $collection = $export->collection();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(0, $collection);
    }
}
