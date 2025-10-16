<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArtistPerformanceReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Collection $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection(): Collection
    {
        // Flatten the data: for each artist, return all their gigs
        $flatData = collect();

        foreach ($this->data as $artistData) {
            foreach ($artistData['gigs'] as $gig) {
                $flatData->push([
                    'artist_name' => $artistData['artist_name'],
                    ...$gig,
                ]);
            }
        }

        return $flatData;
    }

    /**
     * Define os cabeçalhos das colunas na planilha.
     */
    public function headings(): array
    {
        return [
            'Artista',
            'Data Venda',
            'Data Evento',
            'Booker',
            'Local/Evento',
            'Valor Contrato (BRL)',
            'Cachê Bruto (BRL)',
            'Repasse Líquido (BRL)',
        ];
    }

    /**
     * Mapeia os dados de cada linha da coleção para o formato desejado na planilha.
     *
     * @param  mixed  $row
     */
    public function map($row): array
    {
        return [
            $row['artist_name'],
            $row['sale_date'],
            $row['gig_date'],
            $row['booker_name'],
            $row['location_event_details'],
            $row['contract_value'],
            $row['gross_cash_brl'],
            $row['net_payout_brl'],
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
