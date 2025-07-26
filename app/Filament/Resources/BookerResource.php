<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookerResource\Pages;
use App\Filament\Resources\BookerResource\Widgets; // Adicionar este use
use App\Models\Booker;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookerResource extends Resource
{
    protected static ?string $model = Booker::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form->schema([ /* ... campos se necessário ... */ ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Booker')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('gigs_count')->counts('gigs')->label('Qtd. Gigs'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), // Ação para ir para a página de detalhes
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookers::route('/'),
            // ***** REGISTRA A NOVA PÁGINA DE VISUALIZAÇÃO *****
            'view' => Pages\ViewBooker::route('/{record}'),
        ];
    }

    // ***** REGISTRA OS WIDGETS QUE APARECERÃO NA PÁGINA DE VISUALIZAÇÃO *****
    public static function getWidgets(): array
    {
        return [
            Widgets\BookerStatsOverview::class,
            Widgets\BookerCommissionsChart::class,
            Widgets\TopArtistsTable::class,
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
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