<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CostCenter; // Importar Modelo

class CostCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Criando Centros de Custo padrão...');

        $costCenters = [
            ['name' => 'Catering', 'description' => 'Despesas com alimentação para artista e equipe.'],
            ['name' => 'Logistics', 'description' => 'Despesas com transporte (aéreo, terrestre), carregadores, etc.'],
            ['name' => 'Hotel', 'description' => 'Despesas com hospedagem para artista e equipe.'],
            // Adicione outros centros de custo comuns, se desejar
            // ['name' => 'Marketing', 'description' => 'Custos de divulgação específicos da data.'],
            // ['name' => 'Produção Local', 'description' => 'Custos de produção de responsabilidade da agência.'],
        ];

        $createdCount = 0;
        foreach ($costCenters as $center) {
            $costCenter = CostCenter::firstOrCreate(
                ['name' => $center['name']], // Cria se o nome não existir
                ['description' => $center['description']]
            );
            if ($costCenter->wasRecentlyCreated) {
                $createdCount++;
            }
        }

        $this->command->info($createdCount . ' novos Centros de Custo criados.');
    }
}