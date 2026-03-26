<?php

namespace Database\Seeders;

use App\Models\LegalEntity;
use Illuminate\Database\Seeder;

class LegalEntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entities = [
            1 => 'Agência Principal',
            2 => 'Entidade Secundária',
            3 => 'Parceiro X',
            4 => 'Coral',
        ];

        foreach ($entities as $id => $name) {
            LegalEntity::updateOrCreate(
                ['id' => $id],
                ['name' => $name, 'description' => $name === 'Coral' ? 'Sistema Legado Coral' : null]
            );
        }
    }
}
