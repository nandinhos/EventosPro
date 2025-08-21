<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DetailedPerformanceReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Collection $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    /**
     * Define os cabeçalhos das colunas na planilha.
     */
    public function headings(): array
    {
        return [
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
    }

    /**
     * Mapeia os dados de cada linha da coleção para o formato desejado na planilha.
     *
     * @param  mixed  $row
     */
    public function map($row): array
    {
        // $row aqui é cada item do array 'tableData' que nosso service gerou
        return [
            $row['gig_date'],
            $row['artist_name'],
            $row['booker_name'],
            $row['location_event_details'],
            $row['cache_bruto_original'],
            $row['cache_bruto_brl'],
            $row['total_despesas_confirmadas_brl'],
            $row['cache_liquido_base_brl'],
            $row['repasse_estimado_artista_brl'],
            $row['comissao_agencia_brl'],
            $row['comissao_booker_brl'],
            $row['comissao_agencia_liquida_brl'],
            $row['contract_status'],
            $row['payment_status'],
        ];
    }

    /**
     * Aplica estilos à planilha.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Estiliza a primeira linha (cabeçalhos)
            1 => ['font' => ['bold' => true]],
        ];
    }
}
