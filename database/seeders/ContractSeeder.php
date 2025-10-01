<?php

namespace Database\Seeders;

use App\Models\Contract;
use Illuminate\Database\Seeder; // Importar modelo

class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Contract::factory()->count(50)->create(); // Cria 50 contratos fake
    }
}
