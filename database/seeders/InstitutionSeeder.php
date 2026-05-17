<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        $institutions = [
            ['name' => 'Associação Sertaneja', 'description' => 'Apoio às famílias do sertão nordestino com ações de impacto social', 'image' => 'https://cdn.exemplo.com/instituicao-1.jpg', 'image_position' => 'left center'],
            ['name' => 'Instituto Raízes', 'description' => 'Educação e cultura para comunidades rurais', 'image' => 'https://cdn.exemplo.com/instituicao-2.jpg', 'image_position' => 'center center'],
            ['name' => 'Rede Caatinga', 'description' => 'Preservação da Caatinga e tecnologias sustentáveis', 'image' => 'https://cdn.exemplo.com/instituicao-3.jpg', 'image_position' => 'right center'],
            ['name' => 'Projeto Mandacaru', 'description' => 'Fortalecimento da agricultura familiar local', 'image' => 'https://cdn.exemplo.com/instituicao-4.jpg', 'image_position' => 'center top'],
        ];

        foreach ($institutions as $institution) {
            Institution::query()->updateOrCreate(
                ['name' => $institution['name']],
                $institution + ['status' => 'active']
            );
        }
    }
}