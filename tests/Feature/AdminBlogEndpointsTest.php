<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminBlogEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_user_can_manage_blog_posts(): void
    {
        $user = User::query()->create([
            'name' => 'Gestor Conteudo',
            'email' => 'conteudo@exemplo.com',
            'password' => 'senha1234',
            'role' => 'manager',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $createResponse = $this->postJson('/api/admin/content/posts', [
            'title' => 'Nova história da comunidade',
            'content' => '<p>Texto da nova publicação.</p>',
            'status' => 'draft',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Nova história da comunidade')
            ->assertJsonPath('data.status', 'draft');

        $postId = (int) $createResponse->json('data.id');

        $updateResponse = $this->putJson("/api/admin/content/posts/{$postId}", [
            'title' => 'Nova história da comunidade',
            'content' => '<p>Texto da nova publicação atualizado.</p>',
            'status' => 'published',
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.status', 'published');

        $listResponse = $this->getJson('/api/admin/content/posts');

        $listResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $postId);

        $deleteResponse = $this->deleteJson("/api/admin/content/posts/{$postId}");

        $deleteResponse->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_internal_user_can_upload_featured_image(): void
    {
        Storage::fake('public');

        $user = User::query()->create([
            'name' => 'Gestor Imagem',
            'email' => 'imagem@exemplo.com',
            'password' => 'senha1234',
            'role' => 'manager',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/admin/content/posts/featured-image', [
            'image' => UploadedFile::fake()->createWithContent(
                'capa.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO2m9mUAAAAASUVORK5CYII=')
            ),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Imagem enviada com sucesso');

        $url = (string) $response->json('url');
        $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH) ?? '');

        $this->assertNotSame('', $path);
        $this->assertTrue(Storage::disk('public')->exists($path));
    }

    public function test_member_user_cannot_access_blog_management(): void
    {
        $user = User::query()->create([
            'name' => 'Membro Conteudo',
            'email' => 'membro.conteudo@exemplo.com',
            'password' => 'senha1234',
            'role' => 'buyer',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/content/posts');

        $response->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_delete_non_existing_post_returns_json_not_found(): void
    {
        $user = User::query()->create([
            'name' => 'Gestor Conteudo',
            'email' => 'gestor.notfound@exemplo.com',
            'password' => 'senha1234',
            'role' => 'manager',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/admin/content/posts/999999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'POST_NOT_FOUND');
    }

    public function test_internal_user_can_delete_post_using_post_fallback_endpoint(): void
    {
        $user = User::query()->create([
            'name' => 'Gestor Conteudo',
            'email' => 'gestor.postdelete@exemplo.com',
            'password' => 'senha1234',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $post = BlogPost::query()->create([
            'title' => 'Publicação para excluir via POST',
            'slug' => 'publicacao-excluir-via-post',
            'content' => '<p>Conteúdo teste.</p>',
            'excerpt' => 'Conteúdo teste.',
            'featured_image' => 'https://cdn.exemplo.com/blog-delete.jpg',
            'image_alt' => 'Imagem de teste',
            'eyebrow' => 'Histórias',
            'category' => 'Histórias',
            'author_id' => $user->id,
            'views' => 0,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/admin/content/posts/{$post->id}/delete");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('blog_posts', [
            'id' => $post->id,
        ]);
    }

    public function test_published_admin_post_appears_in_public_blog_and_home(): void
    {
        $user = User::query()->create([
            'name' => 'Autor Publicacao',
            'email' => 'autor@exemplo.com',
            'password' => 'senha1234',
            'role' => 'manager',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $createResponse = $this->postJson('/api/admin/content/posts', [
            'title' => 'Publicação de destaque da home',
            'content' => '<p>Conteúdo da publicação destacada.</p>',
            'status' => 'published',
        ]);

        $postId = (int) $createResponse->json('data.id');

        $publicBlogResponse = $this->getJson('/api/public/blog');

        $publicBlogResponse->assertOk()
            ->assertJsonFragment(['id' => $postId])
            ->assertJsonPath('data.0.title', 'Publicação de destaque da home');

        $homeResponse = $this->getJson('/api/public/home');

        $homeResponse->assertOk()
            ->assertJsonFragment(['id' => $postId]);
    }
}