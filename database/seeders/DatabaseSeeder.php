<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\ArtistSeeder;
use Database\Seeders\BookerSeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\CostCenterSeeder; // <-- Importar
use Database\Seeders\GigSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Criar usuários padrão
        User::firstOrCreate(
            ['email' => 'admin@eventospro.com'],
            ['name' => 'Admin EventosPro', 'password' => Hash::make('password')]
        );
        User::firstOrCreate(
            ['email' => 'nandinhos@gmail.com'],
            ['name' => 'Nando DEV', 'password' => Hash::make('123456789')]
        );

        $this->call([
            ArtistSeeder::class,
            BookerSeeder::class,
            TagSeeder::class,
            CostCenterSeeder::class, // <-- Chamar ANTES de GigSeeder
            GigSeeder::class,        // <-- Agora importa Gigs e GigCosts
            // SettlementSeeder::class,
        ]);
    }
}