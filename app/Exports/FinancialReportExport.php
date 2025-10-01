<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinancialReportExport implements FromCollection, WithHeadings
{
    protected $data;

    protected $type;

    public function __construct($data, $type)
    {
        $this->data = $data;
        $this->type = $type;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return match ($this->type) {
            'overview', 'profitability' => ['Contrato', 'Data', 'Artista', 'Booker', 'Receita (BRL)', 'Custos (BRL)', 'Comissão (BRL)', 'Lucro Líquido (BRL)'],
            'cashflow' => ['Tipo', 'Data', 'Descrição', 'Valor (BRL)'],
            'commissions' => ['Contrato', 'Data', 'Comissão (BRL)'],
            'expenses' => ['Contrato', 'Descrição', 'Data', 'Valor (BRL)', 'Moeda'],
            default => [],
        };
    }
}
