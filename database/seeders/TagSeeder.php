<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tag; // Importar o modelo Tag
use Illuminate\Support\Str; // Importar Str para gerar slugs
use Illuminate\Support\Facades\DB;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("Criando tags iniciais...");

        // Limpar tabela opcionalmente
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // DB::table('tags')->truncate();
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $tags = [
            // Tipos de Evento/Contrato
            ['name' => 'Festival', 'type' => 'tipo_evento'],
            ['name' => 'Club', 'type' => 'tipo_evento'],
            ['name' => 'Corporativo', 'type' => 'tipo_evento'],
            ['name' => 'Privado', 'type' => 'tipo_evento'],
            ['name' => 'Collab', 'type' => 'contrato'],
            ['name' => 'Internacional', 'type' => 'contrato'],
            ['name' => 'Exclusividade', 'type' => 'contrato'],

            // Gêneros Musicais (para artistas)
            ['name' => 'Tech House', 'type' => 'genero'],
            ['name' => 'Techno', 'type' => 'genero'],
            ['name' => 'House Music', 'type' => 'genero'],
            ['name' => 'Deep House', 'type' => 'genero'],
            ['name' => 'Progressive House', 'type' => 'genero'],
            ['name' => 'Brazilian Bass', 'type' => 'genero'],

            // Status Financeiro/Tags de Alerta
            ['name' => 'Pgto Atrasado', 'type' => 'financeiro'],
            ['name' => 'Comissão Pendente', 'type' => 'financeiro'],
            ['name' => 'Negociação Especial', 'type' => 'financeiro'],
            ['name' => 'Câmbio Alto', 'type' => 'financeiro'],

            // Regiões (Exemplos)
            ['name' => 'Sudeste', 'type' => 'regiao'],
            ['name' => 'Sul', 'type' => 'regiao'],
            ['name' => 'Nordeste', 'type' => 'regiao'],
            ['name' => 'Exterior', 'type' => 'regiao'],
        ];

        $createdCount = 0;
        foreach ($tags as $tagData) {
            // Cria a tag apenas se o nome (ou slug) ainda não existir
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($tagData['name'])], // Chave para verificar existência
                [                                        // Dados para criar se não existir
                    'name' => $tagData['name'],
                    'type' => $tagData['type'] ?? null, // Usa tipo ou null
                 ]
            );
             // Verifica se a tag foi realmente criada nesta execução
             if ($tag->wasRecentlyCreated) {
                 $createdCount++;
             }
        }

        $this->command->info($createdCount . " novas tags criadas.");
    }
}