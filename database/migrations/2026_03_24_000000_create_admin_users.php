<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $angelica = User::firstOrCreate(
            ['email' => 'angelica.domingos@hotmail.com'],
            ['name' => 'Angélica Domingos', 'password' => Hash::make('password')]
        );

        $nando = User::firstOrCreate(
            ['email' => 'nandinhos@gmail.com'],
            ['name' => 'Nando Dev', 'password' => Hash::make('Aer0G@cembraer')]
        );

        // Limpar roles anteriores e atribuir ADMIN
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
