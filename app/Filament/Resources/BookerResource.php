<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookerResource\Pages\ListBookers;
use App\Filament\Resources\BookerResource\Pages\ViewBooker;
use App\Filament\Resources\BookerResource\Widgets;
use App\Filament\Resources\BookerResource\Widgets\BookerCommissionsChart;
use App\Filament\Resources\BookerResource\Widgets\BookerStatsOverview;
use App\Filament\Resources\BookerResource\Widgets\TopArtistsTable;
use App\Models\Booker;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; // Adicionar este use
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookerResource extends Resource
{
    protected static ?string $model = Booker::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([/* ... campos se necessário ... */]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Booker')->searchable()->sortable(),
                TextColumn::make('gigs_count')->counts('gigs')->label('Qtd. Gigs'),
            ])
            ->recordActions([
                ViewAction::make(), // Ação para ir para a página de detalhes
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookers::route('/'),
            // ***** REGISTRA A NOVA PÁGINA DE VISUALIZAÇÃO *****
            'view' => ViewBooker::route('/{record}'),
        ];
    }

    // ***** REGISTRA OS WIDGETS QUE APARECERÃO NA PÁGINA DE VISUALIZAÇÃO *****
    public static function getWidgets(): array
    {
        return [
            BookerStatsOverview::class,
            BookerCommissionsChart::class,
            TopArtistsTable::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if ($user->hasRole('BOOKER')) {
            // Booker só pode ver a si mesmo na lista
            return parent::getEloquentQuery()->where('id', $user->booker_id);
        }

        // Admin e Diretor veem todos os bookers
        return parent::getEloquentQuery();
    }
}
