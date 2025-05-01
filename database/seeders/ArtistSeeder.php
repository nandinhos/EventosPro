<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Artist; // Importe o modelo Artist
use Illuminate\Support\Facades\DB; // Importar DB Facade para usar insert

class ArtistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpa a tabela antes de inserir para evitar duplicações em re-seed
        // DB::table('artists')->truncate(); // Opcional: usar se for rodar o seeder isoladamente várias vezes

        $artists = [
            'BIGFETT', 'MISS NATÁLIA', 'NERY', 'MARY MESK', 'MOSER', 'DEEFT',
            'SCHILLIST', 'GREG', 'NICOLAU MARINHO', 'CAROL FÁVERO', 'KVSH',
            'TALBOT', 'MARIZ', 'SOUTH BIRDS', 'RAGIE BAN', 'SILVER PANDA',
            'CAROL SEUBERT', 'PITHMAN', 'FANCY INC', 'DOT', 'BRUNO MARTINI',
            'SCORZ', 'BINARYH', 'DRE GUAZZELLI', 'RYAN LOPES', 'RIKO & GUGGA',
            'OWNBOSS', 'WADE', 'SILVIO SOUL', 'DROPACK', 'ETTA', 'BLURRYVISION',
        ];

        $dataToInsert = [];
        $now = now(); // Pegar o timestamp atual uma vez

        foreach ($artists as $name) {
            $dataToInsert[] = [
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Inserir todos os dados de uma vez para performance
        Artist::insert($dataToInsert);

        // Ou, se quiser usar a factory para preencher outros campos (se os tiver adicionado):
        // foreach ($artists as $name) {
        //     Artist::factory()->create(['name' => $name]);
        // }
    }
}