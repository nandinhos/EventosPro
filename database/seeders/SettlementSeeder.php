<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Settlement; // Importar modelos
use Illuminate\Database\Seeder;

class SettlementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Garante que temos eventos realizados para criar acertos
        if (Event::where('status', 'realizado')->count() === 0) {
            // Pode ser necessário criar alguns eventos realizados aqui ou garantir que EventSeeder crie alguns
            Event::factory()->count(10)->create(['status' => 'realizado']); // Cria 10 eventos realizados se não houver
        }
        Settlement::factory()->count(30)->create(); // Cria 30 acertos fake para eventos realizados
    }
}
