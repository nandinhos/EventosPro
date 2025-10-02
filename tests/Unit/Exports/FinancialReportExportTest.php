<?php

namespace Tests\Unit\Exports;

use App\Exports\FinancialReportExport;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class FinancialReportExportTest extends TestCase
{
    public function test_it_can_be_instantiated()
    {
        $data = [
            ['contract' => 'C001', 'date' => '2024-01-01', 'artist' => 'Artist 1', 'booker' => 'Booker 1', 'revenue' => 1000, 'costs' => 200, 'commission' => 100, 'profit' => 700]
        ];
        $type = 'overview';

        $export = new FinancialReportExport($data, $type);

        $this->assertInstanceOf(FinancialReportExport::class, $export);
    }

    public function test_collection_returns_data_as_collection()
    {
        $data = [
            ['contract' => 'C001', 'date' => '2024-01-01', 'artist' => 'Artist 1'],
            ['contract' => 'C002', 'date' => '2024-01-02', 'artist' => 'Artist 2']
        ];
        $type = 'overview';

        $export = new FinancialReportExport($data, $type);
        $collection = $export->collection();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(2, $collection);
        $this->assertEquals($data[0]['contract'], $collection->first()['contract']);
    }

    public function test_headings_for_overview_type()
    {
        $data = [];
        $type = 'overview';

        $export = new FinancialReportExport($data, $type);
        $headings = $export->headings();

        $expected = ['Contrato', 'Data', 'Artista', 'Booker', 'Receita (BRL)', 'Custos (BRL)', 'Comissão (BRL)', 'Lucro Líquido (BRL)'];
        $this->assertEquals($expected, $headings);
    }

    public function test_headings_for_profitability_type()
    {
        $data = [];
        $type = 'profitability';

        $export = new FinancialReportExport($data, $type);
        $headings = $export->headings();

        $expected = ['Contrato', 'Data', 'Artista', 'Booker', 'Receita (BRL)', 'Custos (BRL)', 'Comissão (BRL)', 'Lucro Líquido (BRL)'];
        $this->assertEquals($expected, $headings);
    }

    public function test_headings_for_cashflow_type()
    {
        $data = [];
        $type = 'cashflow';

        $export = new FinancialReportExport($data, $type);
        $headings = $export->headings();

        $expected = ['Tipo', 'Data', 'Descrição', 'Valor (BRL)'];
        $this->assertEquals($expected, $headings);
    }

    public function test_headings_for_commissions_type()
    {
        $data = [];
        $type = 'commissions';

        $export = new FinancialReportExport($data, $type);
        $headings = $export->headings();

        $expected = ['Contrato', 'Data', 'Comissão (BRL)'];
        $this->assertEquals($expected, $headings);
    }

    public function test_headings_for_expenses_type()
    {
        $data = [];
        $type = 'expenses';

        $export = new FinancialReportExport($data, $type);
        $headings = $export->headings();

        $expected = ['Contrato', 'Descrição', 'Data', 'Valor (BRL)', 'Moeda'];
        $this->assertEquals($expected, $headings);
    }

    public function test_headings_for_unknown_type_returns_empty_array()
    {
        $data = [];
        $type = 'unknown';

        $export = new FinancialReportExport($data, $type);
        $headings = $export->headings();

        $this->assertEquals([], $headings);
    }

    public function test_implements_required_interfaces()
    {
        $export = new FinancialReportExport([], 'overview');

        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\FromCollection::class, $export);
        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\WithHeadings::class, $export);
    }
}