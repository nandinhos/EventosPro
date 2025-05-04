<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booker;
use Illuminate\Support\Facades\Log;

class BookerSeeder extends Seeder
{
    public function run(): void
    {
        $csvFile = fopen(base_path("database/data/tabela.csv"), "r");
        $firstline = true;
        $bookersToCreate = [];

         if ($csvFile === false) {
            Log::error("Não foi possível abrir o arquivo CSV para Bookers.");
            $this->command->error("Arquivo CSV não encontrado ou não pôde ser aberto.");
            return;
        }

        while (($data = fgetcsv($csvFile, 2000, ",")) !== false) {
            if (!$firstline) {
                $bookerName = trim($data[4] ?? ''); // Coluna 5 (índice 4) é booker_name
                // Ignora 'CORAL' se for a agência, ou nomes vazios
                if (!empty($bookerName) && strtoupper($bookerName) !== 'CORAL' && !isset($bookersToCreate[$bookerName])) {
                    $bookersToCreate[$bookerName] = ['name' => $bookerName];
                }
            }
            $firstline = false;
        }
        fclose($csvFile);

        // Inserir bookers que ainda não existem
        $existingBookers = Booker::pluck('name')->toArray();
        $newBookersData = [];
        $now = now();

        foreach ($bookersToCreate as $bookerData) {
            if (!in_array($bookerData['name'], $existingBookers)) {
                $newBookersData[] = [
                    'name' => $bookerData['name'],
                    // Pode definir default_commission_rate aqui se quiser
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                 $existingBookers[] = $bookerData['name'];
            }
        }

        if (!empty($newBookersData)) {
             foreach (array_chunk($newBookersData, 50) as $chunk) {
                 Booker::insert($chunk);
             }
            $this->command->info(count($newBookersData) . ' novos bookers inseridos do CSV.');
        } else {
            $this->command->info('Nenhum novo booker encontrado no CSV para inserir.');
        }
    }
}