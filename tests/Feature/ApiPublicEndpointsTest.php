<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\CmsSetting;
use App\Models\Institution;
use App\Models\Raffle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPublicEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_endpoint_returns_expected_contract(): void
    {
        $this->seedPublicData();

        $response = $this->getJson('/api/public/home');

        $response->assertOk()
            ->assertJsonPath('hero.title', 'Desconectando para Conectar')
            ->assertJsonCount(0, 'impactPhrases')
            ->assertJsonPath('realitySection.title', 'Nossa Realidade')
            ->assertJsonCount(4, 'realitySection.publications')
            ->assertJsonCount(3, 'featuredRaffles')
            ->assertJsonCount(4, 'institutions')
            ->assertJsonCount(3, 'blogPreview');
    }

    public function test_home_endpoint_uses_cms_banner_and_phrase_when_available(): void
    {
        $this->seedPublicData();

        CmsSetting::query()->create([
            'banners' => [
                [
                    'url' => 'https://cdn.exemplo.com/cms/banner-home.jpg',
                    'alt' => 'Banner da home',
                    'label' => 'Principal',
                ],
            ],
            'phrases' => [
                'Frase vinda do CMS para a home pública.',
            ],
            'contact' => [
                'email' => 'contato@exemplo.com',
                'whatsapp' => '(81) 99999-9999',
                'phone' => '(81) 3000-0000',
            ],
            'socials' => [
                'instagram' => '',
                'facebook' => '',
                'youtube' => '',
            ],
            'hero_button' => [
                'label' => 'Conhecer Agora',
                'link' => '/public/blog',
                'icon' => 'campaign',
                'backgroundColor' => '#0058bd',
                'textColor' => '#ffffff',
            ],
            'home_reality' => [
                'title' => 'Nossa Realidade',
                'subtitle' => 'Publicações em destaque selecionadas pelo CMS.',
                'displayMode' => 'selected',
                'publicationIds' => [1, 3],
            ],
        ]);

        $response = $this->getJson('/api/public/home');

        $response->assertOk()
            ->assertJsonPath('hero.backgroundImage', 'https://cdn.exemplo.com/cms/banner-home.jpg')
            ->assertJsonPath('hero.subtitle', 'Frase vinda do CMS para a home pública.')
            ->assertJsonPath('hero.ctaLabel', 'Conhecer Agora')
            ->assertJsonPath('hero.ctaIcon', 'campaign')
            ->assertJsonPath('impactPhrases.0', 'Frase vinda do CMS para a home pública.')
            ->assertJsonPath('realitySection.displayMode', 'selected')
            ->assertJsonPath('realitySection.publications.0.id', 1)
            ->assertJsonPath('realitySection.publications.1.id', 3);
    }

    public function test_blog_list_returns_pagination_and_categories(): void
    {
        $this->seedPublicData();

        $response = $this->getJson('/api/public/blog?page=1&limit=10&sort=newest');

        $response->assertOk()
            ->assertJsonPath('pagination.total', 4)
            ->assertJsonPath('pagination.page', 1)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'title',
                    'excerpt',
                    'image',
                    'imageAlt',
                    'eyebrow',
                    'description',
                    'category',
                    'slug',
                    'publishedAt',
                    'readTime',
                    'views',
                    'author' => ['id', 'name'],
                ]],
                'pagination' => ['total', 'page', 'limit', 'pages'],
                'categories',
            ]);
    }

    public function test_blog_single_can_be_loaded_by_slug(): void
    {
        $this->seedPublicData();

        $response = $this->getJson('/api/public/blog/como-solidariedade-transformou');

        $response->assertOk()
            ->assertJsonPath('slug', 'como-solidariedade-transformou')
            ->assertJsonPath('author.name', 'João Silva Santos')
            ->assertJsonStructure([
                'id',
                'title',
                'content',
                'image',
                'author' => ['id', 'name', 'avatar', 'bio', 'socialLinks'],
                'category',
                'tags',
                'publishedAt',
                'updatedAt',
                'readTime',
                'views',
                'slug',
                'relatedPosts',
                'comments',
                'seo' => ['metaDescription', 'keywords'],
            ]);
    }

    public function test_raffles_list_returns_filters(): void
    {
        $this->seedPublicData();

        $response = $this->getJson('/api/public/raffles?status=active&sort=progress');

        $response->assertOk()
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'title',
                    'description',
                    'image',
                    'goal',
                    'current',
                    'progress',
                    'status',
                    'drawDate',
                    'category',
                    'ticketPrice',
                    'ticketsAvailable',
                    'ticketsSold',
                    'slug',
                    'createdAt',
                ]],
                'pagination' => ['total', 'page', 'limit', 'pages'],
                'filters' => ['statuses', 'categories'],
            ]);
    }

    public function test_raffle_single_can_be_loaded_by_slug(): void
    {
        $this->seedPublicData();

        $response = $this->getJson('/api/public/raffles/cesta-regional-nordestina');

        $response->assertOk()
            ->assertJsonPath('slug', 'cesta-regional-nordestina')
            ->assertJsonPath('organization.name', 'Instituto Raízes')
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'fullDescription',
                'image',
                'gallery',
                'goal',
                'current',
                'progress',
                'status',
                'drawDate',
                'category',
                'ticketPrice',
                'ticketsAvailable',
                'ticketsSold',
                'numbers',
                'slug',
                'createdAt',
                'organization' => ['id', 'name', 'logo', 'description', 'contact'],
                'rules',
                'seo' => ['metaDescription', 'keywords'],
                'winnerInfo',
            ]);
    }

    private function seedPublicData(): void
    {
        $user = User::query()->create([
            'name' => 'João Silva Santos',
            'email' => 'joao@exemplo.com',
            'password' => 'senha123',
            'phone' => '(87) 99999-0000',
            'avatar' => 'https://cdn.exemplo.com/avatar-joao.jpg',
            'address' => 'Rua das Flores, 123 - Sertânia, PE 56500-000',
            'role' => 'buyer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $institutions = collect([
            ['name' => 'Associação Sertaneja', 'image_position' => 'left center'],
            ['name' => 'Instituto Raízes', 'image_position' => 'center center'],
            ['name' => 'Rede Caatinga', 'image_position' => 'right center'],
            ['name' => 'Projeto Mandacaru', 'image_position' => 'center top'],
        ])->map(fn (array $data) => Institution::query()->create([
            'name' => $data['name'],
            'description' => "Descrição de {$data['name']}",
            'image' => "https://cdn.exemplo.com/{$data['name']}.jpg",
            'image_position' => $data['image_position'],
            'status' => 'active',
        ]));

        BlogPost::query()->create([
            'title' => 'Como a solidariedade transformou o Sertão',
            'slug' => 'como-solidariedade-transformou',
            'content' => '<h2>Introdução</h2><p>Uma história de transformação social...</p>',
            'excerpt' => 'Uma história de transformação social e impacto comunitário...',
            'featured_image' => 'https://cdn.exemplo.com/blog-1.jpg',
            'image_alt' => 'Paisagem do sertão com árvores e céu aberto',
            'eyebrow' => 'Histórias',
            'category' => 'Histórias',
            'tags' => ['Sertão', 'Solidariedade', 'Transformação'],
            'author_id' => $user->id,
            'meta_description' => 'Como a solidariedade transformou o Sertão',
            'meta_keywords' => ['Sertão', 'Solidariedade', 'Transformação Social'],
            'views' => 156,
            'published_at' => now()->subDays(5),
        ]);

        BlogPost::query()->create([
            'title' => 'Rifa do Bem: como funciona e como participar',
            'slug' => 'rifa-bem-como-participar',
            'content' => '<h2>Como participar</h2><p>Passo a passo...</p>',
            'excerpt' => 'Entenda como participar das nossas rifas e contribuir...',
            'featured_image' => 'https://cdn.exemplo.com/blog-2.jpg',
            'image_alt' => 'Vista de vegetação da caatinga',
            'eyebrow' => 'Guias',
            'category' => 'Guias',
            'tags' => ['Rifa', 'Participação'],
            'author_id' => $user->id,
            'meta_description' => 'Aprenda como participar da Rifa do Bem',
            'meta_keywords' => ['Rifa', 'Participação'],
            'views' => 89,
            'published_at' => now()->subDays(8),
        ]);

        BlogPost::query()->create([
            'title' => 'Caatinga viva: natureza que inspira resistência',
            'slug' => 'caatinga-viva-resistencia',
            'content' => '<h2>Caatinga</h2><p>Resistência e beleza...</p>',
            'excerpt' => 'Descubra a beleza única e resilência da Caatinga...',
            'featured_image' => 'https://cdn.exemplo.com/blog-3.jpg',
            'image_alt' => 'Cenário natural da caatinga com vegetação nativa',
            'eyebrow' => 'Natureza',
            'category' => 'Natureza',
            'tags' => ['Caatinga', 'Natureza'],
            'author_id' => $user->id,
            'meta_description' => 'Caatinga viva: natureza que inspira resistência',
            'meta_keywords' => ['Caatinga', 'Natureza'],
            'views' => 72,
            'published_at' => now()->subDays(11),
        ]);

        BlogPost::query()->create([
            'title' => 'Educação transformadora no interior nordestino',
            'slug' => 'educacao-transformadora',
            'content' => '<h2>Educação</h2><p>Conheça a mudança...</p>',
            'excerpt' => 'Histórias de alunos e professores que mudam a realidade local...',
            'featured_image' => 'https://cdn.exemplo.com/blog-4.jpg',
            'image_alt' => 'Crianças em sala de aula no interior',
            'eyebrow' => 'Histórias',
            'category' => 'Histórias',
            'tags' => ['Educação', 'Interior'],
            'author_id' => $user->id,
            'meta_description' => 'Educação transformadora no interior nordestino',
            'meta_keywords' => ['Educação', 'Interior'],
            'views' => 55,
            'published_at' => now()->subDays(15),
        ]);

        Raffle::query()->create([
            'title' => 'Cesta Regional Nordestina',
            'slug' => 'cesta-regional-nordestina',
            'description' => 'Arrecadação de cestas básicas para famílias em vulnerabilidade',
            'full_description' => '<h3>Objetivo</h3><p>Arrecadar recursos para...</p>',
            'image' => 'https://cdn.exemplo.com/rifa-1.jpg',
            'gallery' => [],
            'goal' => 5000,
            'current' => 3200,
            'status' => 'active',
            'draw_date' => now()->addMonths(7),
            'category' => 'Alimentação',
            'ticket_price' => 10,
            'tickets_available' => 5000,
            'tickets_sold' => 3200,
            'organization_id' => $institutions[1]->id,
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
        ]);

        Raffle::query()->create([
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
            'organization_id' => $institutions[1]->id,
            'rules' => null,
            'numbers' => null,
            'winner_info' => null,
            'featured' => true,
            'meta_description' => 'Material escolar para crianças da zona rural',
            'meta_keywords' => ['educação', 'escola', 'rifa'],
        ]);

        Raffle::query()->create([
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
            'organization_id' => $institutions[0]->id,
            'rules' => null,
            'numbers' => [['number' => 1, 'status' => 'occupied']],
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
        ]);
    }
}