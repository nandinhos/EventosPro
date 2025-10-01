<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GigResource\Pages;
use App\Models\Gig;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GigResource extends Resource
{
    protected static ?string $model = Gig::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract_date')->label('Data Contrato')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('gig_date')->label('Data Evento')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('artist.name')->sortable(),
                Tables\Columns\TextColumn::make('booker.name')->sortable(),
                Tables\Columns\TextColumn::make('cache_value_brl')->label('Valor (BRL)')->money('BRL')->sortable(),
            ])
            ->filters([
                // Filtros de data, etc.
            ])
            ->actions([]) // Sem ações de linha
            ->bulkActions([]); // Sem ações em massa
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
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
            'index' => Pages\ListGigs::route('/'),
            'create' => Pages\CreateGig::route('/create'),
            'edit' => Pages\EditGig::route('/{record}/edit'),
        ];
    }
}
