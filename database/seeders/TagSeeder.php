<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder; // Importar o modelo Tag
use Illuminate\Support\Facades\DB; // Importar Str para gerar slugs
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Criando tags específicas para música eletrônica...');

        // Limpar tabela opcionalmente
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // DB::table('tags')->truncate();
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $tags = [
            // Tipo de evento - Música Eletrônica
            ['name' => 'Festival', 'type' => 'tipo_evento'],
            ['name' => 'Club', 'type' => 'tipo_evento'],
            ['name' => 'Rave', 'type' => 'tipo_evento'],
            ['name' => 'Beach Club', 'type' => 'tipo_evento'],
            ['name' => 'Rooftop', 'type' => 'tipo_evento'],
            ['name' => 'Warehouse', 'type' => 'tipo_evento'],
            ['name' => 'Open Air', 'type' => 'tipo_evento'],
            ['name' => 'Underground', 'type' => 'tipo_evento'],
            ['name' => 'Pool Party', 'type' => 'tipo_evento'],
            ['name' => 'Boat Party', 'type' => 'tipo_evento'],
            ['name' => 'Private Party', 'type' => 'tipo_evento'],
            ['name' => 'Corporate Event', 'type' => 'tipo_evento'],
            ['name' => 'After Party', 'type' => 'tipo_evento'],
            ['name' => 'Pre Party', 'type' => 'tipo_evento'],
            ['name' => 'Sunset Session', 'type' => 'tipo_evento'],

            // Gêneros de Música Eletrônica
            ['name' => 'Tech House', 'type' => 'genero'],
            ['name' => 'Techno', 'type' => 'genero'],
            ['name' => 'House', 'type' => 'genero'],
            ['name' => 'Deep House', 'type' => 'genero'],
            ['name' => 'Progressive House', 'type' => 'genero'],
            ['name' => 'Melodic Techno', 'type' => 'genero'],
            ['name' => 'Minimal Techno', 'type' => 'genero'],
            ['name' => 'Trance', 'type' => 'genero'],
            ['name' => 'Psytrance', 'type' => 'genero'],
            ['name' => 'Progressive Trance', 'type' => 'genero'],
            ['name' => 'Drum & Bass', 'type' => 'genero'],
            ['name' => 'Dubstep', 'type' => 'genero'],
            ['name' => 'Bass House', 'type' => 'genero'],
            ['name' => 'Future House', 'type' => 'genero'],
            ['name' => 'Electro House', 'type' => 'genero'],
            ['name' => 'Tropical House', 'type' => 'genero'],
            ['name' => 'Afro House', 'type' => 'genero'],
            ['name' => 'Organic House', 'type' => 'genero'],
            ['name' => 'Downtempo', 'type' => 'genero'],
            ['name' => 'Ambient', 'type' => 'genero'],
            ['name' => 'Breakbeat', 'type' => 'genero'],
            ['name' => 'Hardstyle', 'type' => 'genero'],
            ['name' => 'Hardcore', 'type' => 'genero'],
            ['name' => 'Brazilian Bass', 'type' => 'genero'],

            // Tipo de contrato
            ['name' => 'Headliner', 'type' => 'contrato'],
            ['name' => 'Support', 'type' => 'contrato'],
            ['name' => 'Warm Up', 'type' => 'contrato'],
            ['name' => 'Closing', 'type' => 'contrato'],
            ['name' => 'B2B Set', 'type' => 'contrato'],
            ['name' => 'Live Set', 'type' => 'contrato'],
            ['name' => 'DJ Set', 'type' => 'contrato'],
            ['name' => 'Residência', 'type' => 'contrato'],
            ['name' => 'Tour Nacional', 'type' => 'contrato'],
            ['name' => 'Tour Internacional', 'type' => 'contrato'],
            ['name' => 'Exclusivo', 'type' => 'contrato'],
            ['name' => 'One-off', 'type' => 'contrato'],
            ['name' => 'Collab', 'type' => 'contrato'],
            ['name' => 'Internacional', 'type' => 'contrato'],

            // Status financeiro
            ['name' => 'Pagamento Confirmado', 'type' => 'financeiro'],
            ['name' => 'Pagamento Pendente', 'type' => 'financeiro'],
            ['name' => 'Pagamento Atrasado', 'type' => 'financeiro'],
            ['name' => 'Comissão Pendente', 'type' => 'financeiro'],
            ['name' => 'Comissão Paga', 'type' => 'financeiro'],
            ['name' => 'Liquidado', 'type' => 'financeiro'],
            ['name' => 'Pagamento Parcial', 'type' => 'financeiro'],
            ['name' => 'Adiantamento', 'type' => 'financeiro'],
            ['name' => 'Cancelado', 'type' => 'financeiro'],
            ['name' => 'Reembolso', 'type' => 'financeiro'],
            ['name' => 'Negociação Especial', 'type' => 'financeiro'],
            ['name' => 'Câmbio Alto', 'type' => 'financeiro'],

            // Regiões do Brasil
            ['name' => 'São Paulo', 'type' => 'regiao'],
            ['name' => 'Rio de Janeiro', 'type' => 'regiao'],
            ['name' => 'Minas Gerais', 'type' => 'regiao'],
            ['name' => 'Santa Catarina', 'type' => 'regiao'],
            ['name' => 'Rio Grande do Sul', 'type' => 'regiao'],
            ['name' => 'Paraná', 'type' => 'regiao'],
            ['name' => 'Bahia', 'type' => 'regiao'],
            ['name' => 'Pernambuco', 'type' => 'regiao'],
            ['name' => 'Ceará', 'type' => 'regiao'],
            ['name' => 'Distrito Federal', 'type' => 'regiao'],
            ['name' => 'Goiás', 'type' => 'regiao'],
            ['name' => 'Espírito Santo', 'type' => 'regiao'],
            ['name' => 'Sudeste', 'type' => 'regiao'],
            ['name' => 'Sul', 'type' => 'regiao'],
            ['name' => 'Nordeste', 'type' => 'regiao'],
            ['name' => 'Internacional', 'type' => 'regiao'],

            // Horários de apresentação
            ['name' => 'Matinê', 'type' => 'horario'],
            ['name' => 'Sunset', 'type' => 'horario'],
            ['name' => 'Prime Time', 'type' => 'horario'],
            ['name' => 'After Hours', 'type' => 'horario'],
            ['name' => 'All Night Long', 'type' => 'horario'],

            // Equipamentos/Rider
            ['name' => 'CDJ Setup', 'type' => 'equipamento'],
            ['name' => 'Vinyl Only', 'type' => 'equipamento'],
            ['name' => 'Live Setup', 'type' => 'equipamento'],
            ['name' => 'Special Rider', 'type' => 'equipamento'],

            // Público/Capacidade
            ['name' => 'Intimate (< 500)', 'type' => 'capacidade'],
            ['name' => 'Medium (500-2000)', 'type' => 'capacidade'],
            ['name' => 'Large (2000-10000)', 'type' => 'capacidade'],
            ['name' => 'Festival (10000+)', 'type' => 'capacidade'],
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

        $this->command->info("$createdCount novas tags de música eletrônica criadas com sucesso!");
        $this->command->info('Total de tags no sistema: '.Tag::count());
    }
}
