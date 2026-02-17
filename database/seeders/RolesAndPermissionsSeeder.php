<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Resetar permissões em cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Criar Permissões
        Permission::firstOrCreate(['name' => 'manage users']); // Uma permissão geral para o CRUD de usuários
        Permission::firstOrCreate(['name' => 'manage cost-centers']); // Permissão para gerenciar centros de custo
        Permission::firstOrCreate(['name' => 'view performance reports']);
        Permission::firstOrCreate(['name' => 'view all gigs']);
        Permission::firstOrCreate(['name' => 'view own gigs']);
        Permission::firstOrCreate(['name' => 'view booker dashboard']);
        Permission::firstOrCreate(['name' => 'manage backups']); // Permissão para gerenciar backups do sistema

        // Criar Papéis e Atribuir Permissões
        $roleAdmin = Role::firstOrCreate(['name' => 'ADMIN']);
        $roleAdmin->givePermissionTo(Permission::all()); // Admin pode tudo

        $roleDiretor = Role::firstOrCreate(['name' => 'DIRETOR']);
        $roleDiretor->givePermissionTo(['view performance reports', 'view all gigs']);

        $roleBooker = Role::firstOrCreate(['name' => 'BOOKER']);
        $roleBooker->givePermissionTo(['view own gigs', 'view booker dashboard']);

        // Atribuir roles aos usuários existentes
        $adminUser = User::where('email', 'admin@eventospro.com')->first();
        if ($adminUser && ! $adminUser->hasRole('ADMIN')) {
            $adminUser->assignRole($roleAdmin);
        }

        $devUser = User::where('email', 'nandinhos@gmail.com')->first();
        if ($devUser && ! $devUser->hasRole('ADMIN')) {
            $devUser->assignRole($roleAdmin);
        }
    }
}
