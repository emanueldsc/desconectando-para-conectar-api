<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->where('email', 'joao@exemplo.com')->first() ?? User::query()->first();

        if (! $author) {
            return;
        }

        $posts = [
            [
                'title' => 'Como a solidariedade transformou o Sertão',
                'slug' => 'como-solidariedade-transformou',
                'category' => 'Histórias',
                'eyebrow' => 'Histórias',
                'excerpt' => 'Uma história de transformação social e impacto comunitário...',
                'content' => '<h2>Introdução</h2><p>Uma história de transformação social...</p>',
                'featured_image' => 'https://cdn.exemplo.com/blog-1.jpg',
                'image_alt' => 'Paisagem do sertão com árvores e céu aberto',
                'tags' => ['Sertão', 'Solidariedade', 'Transformação'],
                'meta_description' => 'Como a solidariedade transformou o Sertão - leia a história completa de impacto social',
                'meta_keywords' => ['Sertão', 'Solidariedade', 'Transformação Social'],
                'published_at' => now()->subDays(6),
                'views' => 156,
            ],
            [
                'title' => 'Rifa do Bem: como funciona e como participar',
                'slug' => 'rifa-bem-como-participar',
                'category' => 'Guias',
                'eyebrow' => 'Guias',
                'excerpt' => 'Entenda como participar das nossas rifas e contribuir...',
                'content' => '<h2>Como participar</h2><p>Entenda o passo a passo...</p>',
                'featured_image' => 'https://cdn.exemplo.com/blog-2.jpg',
                'image_alt' => 'Vista de vegetação da caatinga ao entardecer',
                'tags' => ['Rifa', 'Participação'],
                'meta_description' => 'Aprenda como participar da Rifa do Bem',
                'meta_keywords' => ['Rifa', 'Participação'],
                'published_at' => now()->subDays(8),
                'views' => 89,
            ],
            [
                'title' => 'Caatinga viva: natureza que inspira resistência',
                'slug' => 'caatinga-viva-resistencia',
                'category' => 'Natureza',
                'eyebrow' => 'Natureza',
                'excerpt' => 'Descubra a beleza única e resilência da Caatinga...',
                'content' => '<h2>Caatinga</h2><p>Um bioma de resistência e beleza...</p>',
                'featured_image' => 'https://cdn.exemplo.com/blog-3.jpg',
                'image_alt' => 'Cenário natural da caatinga com vegetação nativa',
                'tags' => ['Caatinga', 'Natureza'],
                'meta_description' => 'Caatinga viva: natureza que inspira resistência',
                'meta_keywords' => ['Caatinga', 'Natureza'],
                'published_at' => now()->subDays(11),
                'views' => 72,
            ],
            [
                'title' => 'Educação transformadora no interior nordestino',
                'slug' => 'educacao-transformadora',
                'category' => 'Histórias',
                'eyebrow' => 'Histórias',
                'excerpt' => 'Histórias de alunos e professores que mudam a realidade local...',
                'content' => '<h2>Educação</h2><p>Conheça a mudança que começa na escola...</p>',
                'featured_image' => 'https://cdn.exemplo.com/blog-4.jpg',
                'image_alt' => 'Crianças em sala de aula no interior',
                'tags' => ['Educação', 'Interior'],
                'meta_description' => 'Educação transformadora no interior nordestino',
                'meta_keywords' => ['Educação', 'Interior'],
                'published_at' => now()->subDays(15),
                'views' => 55,
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::query()->updateOrCreate(
                ['slug' => $post['slug']],
                $post + ['author_id' => $author->id]
            );
        }

        $firstPost = BlogPost::query()->where('slug', 'como-solidariedade-transformou')->first();

        if ($firstPost) {
            Comment::query()->updateOrCreate(
                ['blog_post_id' => $firstPost->id, 'email' => 'ana@exemplo.com'],
                [
                    'author' => 'Ana Costa',
                    'content' => 'Excelente matéria! Muito inspirador.',
                    'replies' => [],
                ]
            );
        }
    }
}