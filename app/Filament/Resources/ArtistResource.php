<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArtistResource\Pages;
use App\Models\Artist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ArtistResource extends Resource
{
    protected static ?string $model = Artist::class;

    protected static ?string $navigationIcon = 'heroicon-o-microphone'; // Ícone sugerido

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('contact_info')
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome do Artista')
                    ->searchable() // Permite buscar por nome
                    ->sortable(), // Permite ordenar por nome

                Tables\Columns\TextColumn::make('gigs_count')
                    ->counts('gigs') // Conta a quantidade de gigs relacionadas
                    ->label('Qtd. Gigs')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Fica escondida por padrão
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListArtists::route('/'),
            'create' => Pages\CreateArtist::route('/create'),
            'edit' => Pages\EditArtist::route('/{record}/edit'),
        ];
    }
}
