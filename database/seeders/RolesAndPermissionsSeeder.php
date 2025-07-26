<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Resetar permissões em cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Criar Permissões
        Permission::firstOrCreate(['name' => 'manage users']); // Uma permissão geral para o CRUD de usuários
        Permission::firstOrCreate(['name' => 'view performance reports']);
        Permission::firstOrCreate(['name' => 'view all gigs']);
        Permission::firstOrCreate(['name' => 'view own gigs']);
        Permission::firstOrCreate(['name' => 'view booker dashboard']);

        // Criar Papéis e Atribuir Permissões
        $roleAdmin = Role::firstOrCreate(['name' => 'ADMIN']);
        $roleAdmin->givePermissionTo(Permission::all()); // Admin pode tudo

        $roleDiretor = Role::firstOrCreate(['name' => 'DIRETOR']);
        $roleDiretor->givePermissionTo(['view performance reports', 'view all gigs']);

        $roleBooker = Role::firstOrCreate(['name' => 'BOOKER']);
        $roleBooker->givePermissionTo(['view own gigs', 'view booker dashboard']);
        
        // (Opcional) Atribui o papel ADMIN ao seu primeiro usuário
        $adminUser = User::where('email', 'seu-email-de-admin@exemplo.com')->first();
        if ($adminUser) {
            $adminUser->assignRole($roleAdmin);
        }
    }
}