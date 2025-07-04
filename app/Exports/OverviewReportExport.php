<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class OverviewReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection(): Collection
    {
        // Achata a estrutura de dados para uma lista simples de gigs
        $flattenedData = [];
        foreach ($this->data['dataByArtist'] as $artistGroup) {
            foreach ($artistGroup['gigs'] as $gig) {
                $flattenedData[] = $gig; // A coluna 'artist_name' já está em cada gig
            }
        }
        return collect($flattenedData);
    }

    public function headings(): array
    {
        // Cabeçalhos do Excel
        return [
            'Data Gig', 'Artista', 'Booker', 'Local/Evento',
            'Cachê (Orig)', 'Cachê (BRL)', 'Despesas', 'Cachê Líq. Base',
            'Repasse Artista', 'Com. Agência', 'Com. Booker', 'Com. Ag. Líq.',
            'Status Contrato', 'Status Pgto.',
        ];
    }

    public function map($row): array
    {
        // Mapeia cada linha da coleção para as colunas do Excel
        return [
            $row['gig_date'],
            $row['artist_name'],
            $row['booker_name'],
            $row['location_event_details'],
            $row['cache_bruto_original'],
            number_format($row['cache_bruto_brl'], 2, ',', '.'),
            number_format($row['total_despesas_confirmadas_brl'], 2, ',', '.'),
            number_format($row['cache_liquido_base_brl'], 2, ',', '.'),
            number_format($row['repasse_estimado_artista_brl'], 2, ',', '.'),
            number_format($row['comissao_agencia_brl'], 2, ',', '.'),
            number_format($row['comissao_booker_brl'], 2, ',', '.'),
            number_format($row['comissao_agencia_liquida_brl'], 2, ',', '.'),
            $row['contract_status'],
            $row['payment_status'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}