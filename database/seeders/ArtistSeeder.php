<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Artist;
use Illuminate\Support\Facades\Log; // Para logs

class ArtistSeeder extends Seeder
{
    public function run(): void
    {
        $csvFile = fopen(base_path("database/data/tabela.csv"), "r"); // Assume que o CSV está em database/data/
        $firstline = true;
        $artistsToCreate = [];

        if ($csvFile === false) {
            Log::error("Não foi possível abrir o arquivo CSV para Artistas.");
            $this->command->error("Arquivo CSV não encontrado ou não pôde ser aberto.");
            return;
        }

        while (($data = fgetcsv($csvFile, 2000, ",")) !== false) {
            if (!$firstline) {
                $artistName = trim($data[5] ?? ''); // Coluna 6 (índice 5) é artist_name
                if (!empty($artistName) && !isset($artistsToCreate[$artistName])) {
                    // Adiciona à lista para criação única
                    $artistsToCreate[$artistName] = ['name' => $artistName];
                }
            }
            $firstline = false;
        }
        fclose($csvFile);

        // Inserir artistas que ainda não existem
        $existingArtists = Artist::pluck('name')->toArray();
        $newArtistsData = [];
        $now = now();

        foreach ($artistsToCreate as $artistData) {
            if (!in_array($artistData['name'], $existingArtists)) {
                 $newArtistsData[] = [
                    'name' => $artistData['name'],
                    'contact_info' => null, // Pode adicionar se tiver no CSV
                    'created_at' => $now,
                    'updated_at' => $now,
                 ];
                 $existingArtists[] = $artistData['name']; // Adiciona à lista para evitar duplicidade no loop
            }
        }

        if (!empty($newArtistsData)) {
            // Chunk insert para performance se forem muitos artistas novos
            foreach (array_chunk($newArtistsData, 50) as $chunk) {
                 Artist::insert($chunk);
            }
            $this->command->info(count($newArtistsData) . ' novos artistas inseridos do CSV.');
        } else {
            $this->command->info('Nenhum novo artista encontrado no CSV para inserir.');
        }
    }
}