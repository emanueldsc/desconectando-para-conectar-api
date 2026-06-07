<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminDefaultSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@desconectando.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('adminSenha@0123'),
                'phone' => null,
                'avatar' => null,
                'address' => null,
                'notes' => 'Usuário administrador padrão - não pode ser excluído',
                'role' => 'manager',
                'status' => 'active',
                'is_default' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
