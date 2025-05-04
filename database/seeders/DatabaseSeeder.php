<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\ArtistSeeder;
use Database\Seeders\BookerSeeder;
use Database\Seeders\TagSeeder; // Mantém se quiser tags
use Database\Seeders\GigSeeder; // << NOVO SEEDER PRINCIPAL

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

        // Chamar seeders na ordem correta
        $this->call([
            ArtistSeeder::class,  // Garante que artistas existem
            BookerSeeder::class,  // Garante que bookers existem
            TagSeeder::class,     // Cria tags
            GigSeeder::class,     // << IMPORTA DADOS REAIS de Gigs e Payments
            // SettlementSeeder::class, // Pode rodar depois se precisar criar acertos fake
        ]);
    }
}