<?php

namespace App\Filament\Resources\BookerResource\Widgets;

use App\Models\Artist;
use App\Models\Booker;
use App\Services\BookerFinancialsService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopArtistsTable extends BaseWidget
{
    public ?Booker $record = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        if (! $this->record) {
            // Retorna uma tabela vazia se o booker não estiver definido
            return $table->query(Booker::query()->where('id', -1));
        }

        $financialService = app(BookerFinancialsService::class);
        // Busca os dados já processados pelo service
        $topArtistsData = $financialService->getTopArtists($this->record);

        // Como o Filament Table espera um Eloquent Query Builder,
        // e nosso service retorna uma coleção, temos um pequeno truque:
        // Passamos os IDs para a query do Filament.
        $artistIds = $topArtistsData->pluck('artist.id');

        return $table
            ->query(
                Artist::query()->whereIn('id', $artistIds)
            )
            ->heading('Artistas em Destaque (Lifetime)')
            ->columns([
                TextColumn::make('name')->label('Artista'),
                // Colunas calculadas que buscam os dados da nossa coleção
                TextColumn::make('gigs_count')->label('Qtd. Gigs')
                    ->getStateUsing(fn ($record) => $topArtistsData->firstWhere('artist.id', $record->id)->gigs_count ?? 0),
                TextColumn::make('total_value')->label('Valor Vendido (BRL)')
                    ->money('BRL')
                    ->getStateUsing(fn ($record) => $topArtistsData->firstWhere('artist.id', $record->id)->total_value ?? 0),
            ])
            ->paginated(false); // Remove paginação para esta tabela pequena
    }
}
