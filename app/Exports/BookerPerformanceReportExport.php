<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BookerPerformanceReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Collection $realizedEvents;

    protected Collection $futureEvents;

    public function __construct(Collection $realizedEvents, Collection $futureEvents)
    {
        $this->realizedEvents = $realizedEvents;
        $this->futureEvents = $futureEvents;
    }

    public function collection(): Collection
    {
        // Combine realized and future events with a status indicator
        $data = collect();

        foreach ($this->realizedEvents as $event) {
            $data->push([
                ...$event,
                'event_type' => 'Realizado',
            ]);
        }

        foreach ($this->futureEvents as $event) {
            $data->push([
                ...$event,
                'event_type' => 'Futuro',
            ]);
        }

        return $data;
    }

    /**
     * Define os cabeçalhos das colunas na planilha.
     */
    public function headings(): array
    {
        return [
            'Tipo',
            'Data Evento',
            'Artista',
            'Local/Evento',
            'Valor Contrato (BRL)',
            'Comissão (BRL)',
            'Status Pagamento',
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
            $row['event_type'],
            $row['gig_date'],
            $row['artist_name'],
            $row['location'],
            $row['cache_value_brl'],
            $row['booker_commission_brl'],
            ucfirst($row['booker_payment_status']),
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
