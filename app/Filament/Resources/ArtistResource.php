<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ArtistResource\Pages\ListArtists;
use App\Filament\Resources\ArtistResource\Pages\CreateArtist;
use App\Filament\Resources\ArtistResource\Pages\EditArtist;
use App\Filament\Resources\ArtistResource\Pages;
use App\Models\Artist;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ArtistResource extends Resource
{
    protected static ?string $model = Artist::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-microphone'; // Ícone sugerido

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('contact_info')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Define as colunas da tabela de listagem.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ***** ADICIONE AS COLUNAS AQUI *****
                TextColumn::make('name')
                    ->label('Nome do Artista')
                    ->searchable() // Permite buscar por nome
                    ->sortable(), // Permite ordenar por nome

                TextColumn::make('gigs_count')
                    ->counts('gigs') // Conta a quantidade de gigs relacionadas
                    ->label('Qtd. Gigs')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Fica escondida por padrão
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => ListArtists::route('/'),
            'create' => CreateArtist::route('/create'),
            'edit' => EditArtist::route('/{record}/edit'),
        ];
    }
}
