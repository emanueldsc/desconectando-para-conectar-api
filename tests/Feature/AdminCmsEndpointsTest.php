<?php

namespace Tests\Feature;

use App\Models\CmsSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCmsEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_user_can_read_cms_settings(): void
    {
        $user = User::query()->create([
            'name' => 'Gestor Interno',
            'email' => 'gestor@exemplo.com',
            'password' => 'senha1234',
            'role' => 'manager',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/cms');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'banners',
                    'phrases',
                    'contact' => ['email', 'whatsapp', 'phone'],
                    'socials' => ['instagram', 'facebook', 'youtube'],
                    'heroButton' => ['title', 'label', 'link', 'icon', 'backgroundColor', 'textColor'],
                    'realitySection' => ['title', 'subtitle', 'displayMode', 'publicationIds'],
                    'monthlyGoal',
                    'updatedAt',
                ],
                'availablePublications',
            ]);
    }

    public function test_internal_user_can_update_cms_settings(): void
    {
        $user = User::query()->create([
            'name' => 'Publicador',
            'email' => 'publicador@exemplo.com',
            'password' => 'senha1234',
            'role' => 'publisher',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'banners' => [
                [
                    'url' => 'https://cdn.exemplo.com/banner-novo.jpg',
                    'alt' => 'Novo banner',
                    'label' => 'Campanha Atual',
                ],
            ],
            'phrases' => [
                'Nova frase de impacto',
            ],
            'contact' => [
                'email' => 'cms@desconectando.com.br',
                'whatsapp' => '(81) 98888-1111',
                'phone' => '(81) 3000-1234',
            ],
            'socials' => [
                'instagram' => 'https://instagram.com/novo-perfil',
                'facebook' => 'https://facebook.com/novo-perfil',
                'youtube' => 'https://youtube.com/@novocanal',
            ],
            'heroButton' => [
                'title' => 'Desconectando para Conectar',
                'label' => 'Doar Agora',
                'link' => '/public/raffles',
                'icon' => 'favorite',
                'backgroundColor' => '#d35400',
                'textColor' => '#ffffff',
            ],
            'realitySection' => [
                'title' => 'Nossa Realidade',
                'subtitle' => 'Últimas publicações em destaque',
                'displayMode' => 'latest',
                'publicationIds' => [],
            ],
            'monthlyGoal' => 35000,
        ];

        $response = $this->putJson('/api/admin/cms', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'CMS atualizado com sucesso')
            ->assertJsonPath('data.contact.email', 'cms@desconectando.com.br')
            ->assertJsonPath('data.banners.0.label', 'Campanha Atual')
            ->assertJsonPath('data.phrases.0', 'Nova frase de impacto')
            ->assertJsonPath('data.heroButton.label', 'Doar Agora')
            ->assertJsonPath('data.realitySection.title', 'Nossa Realidade')
            ->assertJsonPath('data.monthlyGoal', 35000);
    }

    public function test_member_user_cannot_access_cms_endpoints(): void
    {
        $user = User::query()->create([
            'name' => 'Membro',
            'email' => 'membro@exemplo.com',
            'password' => 'senha1234',
            'role' => 'buyer',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/cms');

        $response->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_internal_user_can_upload_banner_image(): void
    {
        Storage::fake('public');

        $user = User::query()->create([
            'name' => 'Gestor Upload',
            'email' => 'upload@exemplo.com',
            'password' => 'senha1234',
            'role' => 'manager',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/admin/cms/banner-image', [
            'banner' => $this->fakePngUpload('banner-novo.png'),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Imagem enviada com sucesso');

        $url = $response->json('url');
        $path = str_replace('/storage/', '', parse_url((string) $url, PHP_URL_PATH) ?? '');

        $this->assertNotSame('', $path);
        $this->assertTrue(Storage::disk('public')->exists($path));
    }

    public function test_upload_replaces_previous_local_banner_image(): void
    {
        Storage::fake('public');

        $oldPath = $this->fakePngUpload('banner-antigo.png')->store('cms-banners', 'public');
        $this->assertTrue(Storage::disk('public')->exists($oldPath));

        $user = User::query()->create([
            'name' => 'Publicador Upload',
            'email' => 'upload.publisher@exemplo.com',
            'password' => 'senha1234',
            'role' => 'publisher',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $previousUrl = url('/storage/'.$oldPath);

        $response = $this->postJson('/api/admin/cms/banner-image', [
            'banner' => $this->fakePngUpload('banner-substituto.png'),
            'previous_url' => $previousUrl,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertFalse(Storage::disk('public')->exists($oldPath));
    }

    public function test_update_can_remove_banner_image_and_delete_previous_local_file(): void
    {
        Storage::fake('public');

        $oldPath = $this->fakePngUpload('banner-removivel.png')->store('cms-banners', 'public');
        $oldUrl = url('/storage/'.$oldPath);

        CmsSetting::query()->create([
            'banners' => [
                [
                    'url' => $oldUrl,
                    'alt' => 'Banner antigo',
                    'label' => 'Banner Principal',
                ],
            ],
            'phrases' => ['Frase teste'],
            'contact' => [
                'email' => 'cms@desconectando.com.br',
                'whatsapp' => '(81) 98888-1111',
                'phone' => '(81) 3000-1234',
            ],
            'socials' => [
                'instagram' => '',
                'facebook' => '',
                'youtube' => '',
            ],
            'hero_button' => [
                'title' => 'Desconectando para Conectar',
                'label' => 'Doar Agora',
                'link' => '/public/raffles',
                'icon' => 'favorite',
                'backgroundColor' => '#d35400',
                'textColor' => '#ffffff',
            ],
            'home_reality' => [
                'title' => 'Nossa Realidade',
                'subtitle' => 'Últimas publicações em destaque',
                'displayMode' => 'latest',
                'publicationIds' => [],
            ],
            'monthly_goal' => 20000,
        ]);

        $user = User::query()->create([
            'name' => 'Gestor Remove Banner',
            'email' => 'remove.banner@exemplo.com',
            'password' => 'senha1234',
            'role' => 'manager',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/admin/cms', [
            'banners' => [
                [
                    'url' => '',
                    'alt' => 'Banner sem imagem',
                    'label' => 'Banner Principal',
                ],
            ],
            'phrases' => ['Frase teste'],
            'contact' => [
                'email' => 'cms@desconectando.com.br',
                'whatsapp' => '(81) 98888-1111',
                'phone' => '(81) 3000-1234',
            ],
            'socials' => [
                'instagram' => '',
                'facebook' => '',
                'youtube' => '',
            ],
                'heroButton' => [
                    'title' => 'Desconectando para Conectar',
                    'label' => 'Doar Agora',
                    'link' => '/public/raffles',
                    'icon' => 'favorite',
                    'backgroundColor' => '#d35400',
                    'textColor' => '#ffffff',
                ],
                'realitySection' => [
                    'title' => 'Nossa Realidade',
                    'subtitle' => 'Últimas publicações em destaque',
                    'displayMode' => 'latest',
                    'publicationIds' => [],
                ],
                'monthlyGoal' => 25000,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.banners.0.url', '');

        $this->assertFalse(Storage::disk('public')->exists($oldPath));
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5TQ2kAAAAASUVORK5CYII=';

        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode($pngBase64, true) ?: ''
        );
    }
}
