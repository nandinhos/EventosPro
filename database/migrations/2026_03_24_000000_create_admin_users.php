<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Garantir que as permissões existam
        $permissions = [
            'manage users',
            'manage cost-centers',
            'view performance reports',
            'view all gigs',
            'view own gigs',
            'view booker dashboard',
            'manage backups',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Criar role ADMIN se não existir
        $roleAdmin = Role::firstOrCreate(['name' => 'ADMIN']);
        $roleAdmin->givePermissionTo(Permission::all());

        // Criar usuários admin
        $angelica = User::updateOrCreate(
            ['email' => 'angelica.domingos@hotmail.com'],
            ['name' => 'Angélica Domingos', 'password' => Hash::make('password')]
        );

        $nando = User::updateOrCreate(
            ['email' => 'nandinhos@gmail.com'],
            ['name' => 'Nando Dev', 'password' => Hash::make('Aer0G@cembraer')]
        );

        // Atribuir ADMIN
        $angelica->syncRoles(['ADMIN']);
        $nando->syncRoles(['ADMIN']);
    }

    public function down(): void
    {
        User::whereIn('email', [
            'angelica.domingos@hotmail.com',
            'nandinhos@gmail.com',
        ])->delete();
    }
};
