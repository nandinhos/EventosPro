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
        $angelica = User::withTrashed()->where('email', 'angelica.domingos@hotmail.com')->first();
        if ($angelica) {
            if ($angelica->trashed()) {
                $angelica->restore();
            }
            $angelica->forceFill([
                'name' => 'Angélica Domingos',
                'password' => Hash::make('password'),
            ])->save();
        } else {
            User::create([
                'email' => 'angelica.domingos@hotmail.com',
                'name' => 'Angélica Domingos',
                'password' => Hash::make('password'),
            ]);
        }
        $nando = User::withTrashed()->where('email', 'nandinhos@gmail.com')->first();
        if ($nando) {
            if ($nando->trashed()) {
                $nando->restore();
            }
            $nando->forceFill([
                'name' => 'Nando Dev',
                'password' => Hash::make('Aer0G@cembraer'),
            ])->save();
        } else {
            User::create([
                'email' => 'nandinhos@gmail.com',
                'name' => 'Nando Dev',
                'password' => Hash::make('Aer0G@cembraer'),
            ]);
        }

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
