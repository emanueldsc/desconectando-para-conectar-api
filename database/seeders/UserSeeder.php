<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'joao@exemplo.com'],
            [
                'name' => 'João Silva Santos',
                'password' => Hash::make('senha123'),
                'phone' => '(87) 99999-0000',
                'avatar' => 'https://cdn.exemplo.com/avatar-joao.jpg',
                'address' => 'Rua das Flores, 123 - Sertânia, PE 56500-000',
                'role' => 'buyer',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}