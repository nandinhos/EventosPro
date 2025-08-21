<?php

namespace Database\Seeders;

use App\Models\Booker;
use Illuminate\Database\Seeder;

class BookerSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Criando bookers e agências especializadas em música eletrônica...');

        $bookers = [
            // Agências Principais
            [
                'name' => 'Coral Music',
                'default_commission_rate' => 15.00,
            ],
            [
                'name' => 'Midas Music',
                'default_commission_rate' => 18.00,
            ],
            [
                'name' => 'Deckstar',
                'default_commission_rate' => 20.00,
            ],
            [
                'name' => 'Só Track Boa',
                'default_commission_rate' => 12.00,
            ],
            [
                'name' => 'Warung Music',
                'default_commission_rate' => 25.00,
            ],
            [
                'name' => 'Green Valley Music',
                'default_commission_rate' => 22.00,
            ],
            [
                'name' => 'Laroc Club Bookings',
                'default_commission_rate' => 20.00,
            ],
            [
                'name' => 'D-Edge Bookings',
                'default_commission_rate' => 18.00,
            ],

            // Bookers Independentes
            [
                'name' => 'Carlos Mendes',
                'default_commission_rate' => 10.00,
            ],
            [
                'name' => 'Fernanda Silva',
                'default_commission_rate' => 8.00,
            ],
            [
                'name' => 'Ricardo Santos',
                'default_commission_rate' => 12.00,
            ],
            [
                'name' => 'Marina Costa',
                'default_commission_rate' => 9.00,
            ],
            [
                'name' => 'Bruno Oliveira',
                'default_commission_rate' => 11.00,
            ],
            [
                'name' => 'Juliana Ferreira',
                'default_commission_rate' => 7.50,
            ],
            [
                'name' => 'André Rodrigues',
                'default_commission_rate' => 10.50,
            ],
            [
                'name' => 'Camila Alves',
                'default_commission_rate' => 8.50,
            ],

            // Agências Regionais
            [
                'name' => 'SP Underground',
                'default_commission_rate' => 15.00,
            ],
            [
                'name' => 'Rio Electronic Scene',
                'default_commission_rate' => 16.00,
            ],
            [
                'name' => 'BH Beats',
                'default_commission_rate' => 14.00,
            ],
            [
                'name' => 'Floripa House',
                'default_commission_rate' => 17.00,
            ],
            [
                'name' => 'Brasília Techno',
                'default_commission_rate' => 13.00,
            ],
            [
                'name' => 'Salvador Bass',
                'default_commission_rate' => 15.50,
            ],
            [
                'name' => 'Recife Electronic',
                'default_commission_rate' => 14.50,
            ],
            [
                'name' => 'Porto Alegre Beats',
                'default_commission_rate' => 16.50,
            ],

            // Agências Especializadas
            [
                'name' => 'Festival Bookings BR',
                'default_commission_rate' => 25.00,
            ],
            [
                'name' => 'Corporate Events Music',
                'default_commission_rate' => 30.00,
            ],
            [
                'name' => 'Private Party Specialists',
                'default_commission_rate' => 35.00,
            ],
            [
                'name' => 'Club Circuit Brazil',
                'default_commission_rate' => 18.00,
            ],
            [
                'name' => 'Beach Club Bookings',
                'default_commission_rate' => 20.00,
            ],
            [
                'name' => 'Rooftop Events',
                'default_commission_rate' => 22.00,
            ],
        ];

        $existingBookers = Booker::pluck('name')->toArray();
        $createdCount = 0;

        foreach ($bookers as $bookerData) {
            if (! in_array($bookerData['name'], $existingBookers)) {
                Booker::create($bookerData);
                $createdCount++;
                $existingBookers[] = $bookerData['name'];
            }
        }

        $this->command->info("$createdCount novos bookers e agências de música eletrônica criados com sucesso!");
        $this->command->info('Total de bookers no sistema: '.Booker::count());
    }
}
