<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\ArtistSeeder;
use Database\Seeders\BookerSeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\CostCenterSeeder;
use Database\Seeders\GigSeeder;
use Database\Seeders\PaymentSeeder;
use Database\Seeders\SettlementSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

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
            // Seeders de dados básicos (executar primeiro)
            RolesAndPermissionsSeeder::class,
            CostCenterSeeder::class,
            TagSeeder::class,
            
            // Seeders de entidades principais
            ArtistSeeder::class,
            BookerSeeder::class,
            
            // Seeders que dependem das entidades acima
            GigSeeder::class,
            PaymentSeeder::class,
            
            // Outros seeders
            // SettlementSeeder::class,
        ]);
        
        $this->command->info('\n=== SEEDS DE MÚSICA ELETRÔNICA CRIADOS COM SUCESSO! ===');
        $this->command->info('✓ Artistas brasileiros de música eletrônica');
        $this->command->info('✓ Bookers e agências especializadas');
        $this->command->info('✓ Tags específicas para eventos eletrônicos');
        $this->command->info('✓ Centros de custo para eventos');
        $this->command->info('✓ Gigs realistas em locais brasileiros');
        $this->command->info('✓ Pagamentos e custos variados');
        $this->command->info('\nExecute: php artisan db:seed para popular o banco!');
    }
}