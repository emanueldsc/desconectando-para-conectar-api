<?php

namespace Database\Seeders;

use App\Models\Raffle;
use Illuminate\Database\Seeder;

class RaffleSeeder extends Seeder
{
    public function run(): void
    {
        $raffles = [
            [
                'title' => 'Cesta Regional Nordestina',
                'slug' => 'cesta-regional-nordestina',
                'description' => 'Arrecadação de cestas básicas para famílias em vulnerabilidade',
                'full_description' => '<h3>Objetivo</h3><p>Arrecadar recursos para...</p>',
                'image' => 'https://cdn.exemplo.com/rifa-1.jpg',
                'gallery' => [
                    'https://cdn.exemplo.com/rifa-1-gallery-1.jpg',
                    'https://cdn.exemplo.com/rifa-1-gallery-2.jpg',
                    'https://cdn.exemplo.com/rifa-1-gallery-3.jpg',
                ],
                'goal' => 5000,
                'current' => 3200,
                'status' => 'active',
                'draw_date' => now()->addMonths(7),
                'category' => 'Alimentação',
                'ticket_price' => 10,
                'tickets_available' => 5000,
                'tickets_sold' => 3200,
                'rules' => '<h4>Regras da Rifa</h4><p>1. Participação aberta ao público em geral...</p>',
                'numbers' => [
                    ['number' => 1, 'status' => 'available'],
                    ['number' => 2, 'status' => 'selected'],
                    ['number' => 3, 'status' => 'available'],
                    ['number' => 4, 'status' => 'occupied'],
                    ['number' => 5, 'status' => 'occupied'],
                ],
                'winner_info' => null,
                'featured' => true,
                'meta_description' => 'Participe da Cesta Regional Nordestina - rifa solidária',
                'meta_keywords' => ['rifa', 'solidária', 'sertão', 'nordeste'],
            ],
            [
                'title' => 'Escola do Campo',
                'slug' => 'escola-campo',
                'description' => 'Material escolar para crianças da zona rural',
                'full_description' => '<h3>Objetivo</h3><p>Garantir material escolar...</p>',
                'image' => 'https://cdn.exemplo.com/rifa-2.jpg',
                'gallery' => [],
                'goal' => 3000,
                'current' => 1800,
                'status' => 'active',
                'draw_date' => now()->addMonths(6),
                'category' => 'Educação',
                'ticket_price' => 5,
                'tickets_available' => 3000,
                'tickets_sold' => 1800,
                'rules' => null,
                'numbers' => null,
                'winner_info' => null,
                'featured' => true,
                'meta_description' => 'Material escolar para crianças da zona rural',
                'meta_keywords' => ['educação', 'escola', 'rifa'],
            ],
            [
                'title' => 'Poço Artesiano',
                'slug' => 'poco-artesiano',
                'description' => 'Construção de poço para comunidade',
                'full_description' => '<h3>Objetivo</h3><p>Levar água para a comunidade...</p>',
                'image' => 'https://cdn.exemplo.com/rifa-3.jpg',
                'gallery' => [],
                'goal' => 12000,
                'current' => 12000,
                'status' => 'finished',
                'draw_date' => now()->subMonths(7),
                'category' => 'Infraestrutura',
                'ticket_price' => 20,
                'tickets_available' => 12000,
                'tickets_sold' => 12000,
                'rules' => null,
                'numbers' => [
                    ['number' => 1, 'status' => 'occupied'],
                    ['number' => 2, 'status' => 'occupied'],
                    ['number' => 3, 'status' => 'occupied'],
                ],
                'winner_info' => [
                    'id' => 1,
                    'name' => 'Maria da Silva',
                    'winnerNumber' => 2,
                    'drawDate' => now()->subMonths(7)->toISOString(),
                    'prize' => 'Poço concluído',
                ],
                'featured' => true,
                'meta_description' => 'Construção de poço para comunidade sem acesso à água',
                'meta_keywords' => ['infraestrutura', 'água', 'poço'],
            ],
        ];

        foreach ($raffles as $raffle) {
            Raffle::query()->updateOrCreate(
                ['slug' => $raffle['slug']],
                $raffle
            );
        }
    }
}