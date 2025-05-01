<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;   // Importar Event
use App\Models\Artist;  // Importar Artist
use Faker\Generator as Faker; // Importar Faker

class EventSeeder extends Seeder // Apenas UMA definição da classe
{
    /**
     * Run the database seeds.
     *
     * @param \Faker\Generator $faker // Injeta o Faker aqui
     * @return void
     */
    public function run(Faker $faker): void // Método run com Faker injetado
    {
        // Cria 100 eventos fake usando a factory
        Event::factory()
            ->count(100) // Quantidade de eventos a criar
            ->create()   // Cria os eventos
            ->each(function ($event) use ($faker) { // Itera sobre cada evento criado
                // Para cada evento, anexa artistas adicionais (além do principal)

                // Pega de 0 a 5 IDs de artistas aleatórios (excluindo o principal)
                $additionalArtists = Artist::where('id', '!=', $event->main_artist_id) // Não pega o artista principal
                                           ->inRandomOrder() // Pega aleatoriamente
                                           ->limit($faker->numberBetween(0, 5)) // Limita entre 0 e 5 artistas
                                           ->pluck('id'); // Pega apenas os IDs

                // Anexa os artistas adicionais na tabela pivot 'event_artist'
                // O método artists() é o relacionamento belongsToMany definido no modelo Event
                if ($additionalArtists->isNotEmpty()) { // Garante que só anexa se encontrou algum
                    $event->artists()->attach($additionalArtists);
                }
            });
    }
}