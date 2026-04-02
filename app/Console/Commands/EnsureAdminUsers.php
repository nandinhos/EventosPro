<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EnsureAdminUsers extends Command
{
    protected $signature = 'app:ensure-admin-users';

    protected $description = 'Garante que os usuários administradores existam no banco (idempotente, seguro em todo deploy)';

    public function handle(): int
    {
        $this->info('Verificando usuários administradores...');

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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

        $roleAdmin = Role::firstOrCreate(['name' => 'ADMIN']);
        $roleAdmin->syncPermissions(Permission::all());

        $admins = config('admin.users');

        foreach ($admins as $data) {
            $user = User::withTrashed()->where('email', $data['email'])->first();

            if ($user) {
                if ($user->trashed()) {
                    $user->restore();
                }
                $user->forceFill([
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
                $status = 'senha/dados sincronizados';
            } else {
                $user = User::create([
                    'email' => $data['email'],
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'email_verified_at' => now(),
                ]);
                $status = 'criado';
            }

            $user->syncRoles(['ADMIN']);

            $this->line("  → {$data['name']} ({$data['email']}): {$status}");
        }

        $this->info('Usuários administradores garantidos com sucesso.');

        return self::SUCCESS;
    }
}
