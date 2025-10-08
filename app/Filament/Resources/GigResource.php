<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GigResource\Pages\CreateGig;
use App\Filament\Resources\GigResource\Pages\EditGig;
use App\Filament\Resources\GigResource\Pages\ListGigs;
use App\Models\Gig;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GigResource extends Resource
{
    protected static ?string $model = Gig::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contract_date')->label('Data Contrato')->date('d/m/Y')->sortable(),
                TextColumn::make('gig_date')->label('Data Evento')->date('d/m/Y')->sortable(),
                TextColumn::make('artist.name')->sortable(),
                TextColumn::make('booker.name')->sortable(),
                TextColumn::make('cache_value_brl')->label('Valor (BRL)')->money('BRL')->sortable(),
            ])
            ->filters([
                // Filtros de data, etc.
            ])
            ->recordActions([]) // Sem ações de linha
            ->toolbarActions([]); // Sem ações em massa
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->hasRole('BOOKER')) {
            // Se for booker, filtra as gigs pelo seu booker_id
            return parent::getEloquentQuery()->where('booker_id', $user->booker_id);
        }

        // Admin e Diretor veem tudo
        return parent::getEloquentQuery();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGigs::route('/'),
            'create' => CreateGig::route('/create'),
            'edit' => EditGig::route('/{record}/edit'),
        ];
    }
}
