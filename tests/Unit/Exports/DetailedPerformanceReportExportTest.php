<?php

namespace Tests\Unit\Exports;

use App\Exports\DetailedPerformanceReportExport;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\TestCase;

class DetailedPerformanceReportExportTest extends TestCase
{
    private function getSampleData(): Collection
    {
        return collect([
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
                'artist_name' => 'Artist Two',
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
        ]);
    }

    public function test_it_can_be_instantiated()
    {
        $data = $this->getSampleData();
        $export = new DetailedPerformanceReportExport($data);

        $this->assertInstanceOf(DetailedPerformanceReportExport::class, $export);
    }

    public function test_collection_returns_provided_data()
    {
        $data = $this->getSampleData();
        $export = new DetailedPerformanceReportExport($data);

        $collection = $export->collection();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(2, $collection);
        $this->assertEquals($data, $collection);
    }

    public function test_headings_returns_correct_headers()
    {
        $data = $this->getSampleData();
        $export = new DetailedPerformanceReportExport($data);

        $headings = $export->headings();

        $expected = [
            'Data Gig',
            'Artista',
            'Booker',
            'Local/Evento',
            'Cachê (Orig)',
            'Cachê (BRL)',
            'Despesas (BRL)',
            'Cachê Líq. Base (BRL)',
            'Repasse Artista (BRL)',
            'Com. Agência (BRL)',
            'Com. Booker (BRL)',
            'Com. Ag. Líquida (BRL)',
            'Status Contrato',
            'Status Pgto.',
        ];

        $this->assertEquals($expected, $headings);
    }

    public function test_map_transforms_row_data_correctly()
    {
        $data = $this->getSampleData();
        $export = new DetailedPerformanceReportExport($data);

        $sampleRow = $data->first();
        $mappedRow = $export->map($sampleRow);

        $expected = [
            '2024-01-15',
            'Artist One',
            'Booker One',
            'Venue A - Event X',
            'USD 1,000.00',
            5000.00,
            500.00,
            4500.00,
            3600.00,
            450.00,
            450.00,
            450.00,
            'Confirmado',
            'Pago',
        ];

        $this->assertEquals($expected, $mappedRow);
    }

    public function test_styles_returns_bold_header_style()
    {
        $data = $this->getSampleData();
        $export = new DetailedPerformanceReportExport($data);

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
        $export = new DetailedPerformanceReportExport($data);

        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\FromCollection::class, $export);
        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\WithHeadings::class, $export);
        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\WithMapping::class, $export);
        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\WithStyles::class, $export);
    }

    public function test_handles_empty_collection()
    {
        $data = collect([]);
        $export = new DetailedPerformanceReportExport($data);

        $collection = $export->collection();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(0, $collection);
    }
}
