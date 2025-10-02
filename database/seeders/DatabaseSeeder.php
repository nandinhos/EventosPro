<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

            // Seeder de testes de auditoria (opcional)
            // AuditTestSeeder::class,
        ]);

        $this->command->info('\n=== SEEDS DE MÚSICA ELETRÔNICA CRIADOS COM SUCESSO! ===');
        $this->command->info('✓ Artistas brasileiros de música eletrônica');
        $this->command->info('✓ Bookers e agências especializadas');
        $this->command->info('✓ Tags específicas para eventos eletrônicos');
        $this->command->info('✓ Centros de custo para eventos');
        $this->command->info('✓ Gigs realistas em locais brasileiros');
        $this->command->info('✓ Pagamentos e custos variados');
        $this->command->info('\n📋 Para criar dados de teste de auditoria:');
        $this->command->info('   php artisan db:seed --class=AuditTestSeeder');
        $this->command->info('\nExecute: php artisan db:seed para popular o banco!');
    }
}
