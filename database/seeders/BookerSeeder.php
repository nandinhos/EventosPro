<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Booker; // Importe o modelo Booker
use Illuminate\Support\Facades\DB; // Importar DB Facade para usar insert

class BookerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // DB::table('bookers')->truncate(); // Opcional

        $bookers = [
            'CORAL', 'SCOOB', 'KLAH', 'BONET', 'JORDO',
            'VICTOR HUGO', 'PEDRO', 'JOTTA', 'KLAH/JORDO', 'GUI',
        ];

        $dataToInsert = [];
        $now = now();

        foreach ($bookers as $name) {
            $dataToInsert[] = [
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
                // 'commission_rate' => 5.00, // Pode definir um padrão aqui se a factory não o fizer
            ];
        }

        Booker::insert($dataToInsert);

        // Ou usando factory:
        // foreach ($bookers as $name) {
        //     Booker::factory()->create(['name' => $name]);
        // }
    }
}