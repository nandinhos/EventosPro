<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Payment; // Importar modelo
use App\Models\Contract; // Importar Contract para garantir que existam

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Garante que temos contratos antes de criar pagamentos
        if (Contract::count() === 0) {
             $this->call(ContractSeeder::class);
        }
        Payment::factory()->count(200)->create(); // Cria 200 pagamentos fake
    }
}