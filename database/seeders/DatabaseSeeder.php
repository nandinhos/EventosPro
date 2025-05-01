<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User; // Importar User
use Illuminate\Support\Facades\Hash; // Importar Hash para a senha

// Importar seus outros seeders específicos
use Database\Seeders\ArtistSeeder;
use Database\Seeders\BookerSeeder;
use Database\Seeders\ContractSeeder;
use Database\Seeders\EventSeeder;
use Database\Seeders\PaymentSeeder;
use Database\Seeders\SettlementSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Criar o usuário Admin padrão
        User::factory()->create([
            'name' => 'Admin EventosPro',
            'email' => 'admin@eventospro.com',
            'password' => Hash::make('password'), // Use Hash::make() ou bcrypt()
        ]);

        // Criar o seu usuário padrão Nando DEV
        User::factory()->create([
            'name' => 'Nando DEV',
            'email' => 'nandinhos@gmail.com',
            'password' => Hash::make('123456789'), // SEMPRE use Hash::make() ou bcrypt() para senhas!
        ]);

        // Chamar seus outros seeders na ordem correta de dependência
        $this->call([
            ArtistSeeder::class,      // Cria os artistas primeiro
            BookerSeeder::class,      // Cria os bookers
            ContractSeeder::class,    // Cria contratos
            EventSeeder::class,       // Cria eventos (precisa de bookers, artists, e talvez contracts)
            PaymentSeeder::class,     // Cria pagamentos (precisa de contracts)
            SettlementSeeder::class,  // Cria acertos (precisa de events)
        ]);
    }
}