<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AdminBlogController extends Controller
{
    private const DEFAULT_CATEGORY = 'Histórias';
    private const DEFAULT_EYEBROW = 'Histórias';
    private const DEFAULT_THUMBNAIL = 'https://placehold.co/1200x675/png?text=Publicacao';

    public function index(Request $request): JsonResponse
    {
        if (! $this->canManageContent($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar conteúdo',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $posts = BlogPost::query()
            ->with('author')
            ->latest('updated_at')
            ->get()
            ->map(fn (BlogPost $post): array => $this->formatPublication($post))
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->canManageContent($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar conteúdo',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $validated = $this->validatePayload($request);
        $status = (string) $validated['status'];

        $post = BlogPost::query()->create([
            'title' => $validated['title'],
            'slug' => $this->resolveSlug((string) $validated['title']),
            'content' => $validated['content'],
            'excerpt' => $validated['excerpt'] ?? $this->buildExcerpt((string) $validated['content']),
            'featured_image' => $validated['featuredImage'] ?? self::DEFAULT_THUMBNAIL,
            'image_alt' => $validated['imageAlt'] ?? (string) $validated['title'],
            'eyebrow' => $validated['eyebrow'] ?? self::DEFAULT_EYEBROW,
            'category' => $validated['category'] ?? self::DEFAULT_CATEGORY,
            'tags' => $validated['tags'] ?? [],
            'author_id' => (int) $request->user()->id,
            'meta_description' => $validated['metaDescription'] ?? $this->buildExcerpt((string) $validated['content'], 155),
            'meta_keywords' => $validated['metaKeywords'] ?? [],
            'views' => 0,
            'published_at' => $status === 'published' ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Publicação criada com sucesso',
            'data' => $this->formatPublication($post->fresh(['author'])),
        ], 201);
    }

    public function update(Request $request, BlogPost $post): JsonResponse
    {
        if (! $this->canManageContent($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar conteúdo',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $validated = $this->validatePayload($request);
        $status = (string) $validated['status'];

        $post->update([
            'title' => $validated['title'],
            'slug' => $post->slug ?: $this->resolveSlug((string) $validated['title'], $post),
            'content' => $validated['content'],
            'excerpt' => $validated['excerpt'] ?? $this->buildExcerpt((string) $validated['content']),
            'featured_image' => $validated['featuredImage'] ?? $post->featured_image ?? self::DEFAULT_THUMBNAIL,
            'image_alt' => $validated['imageAlt'] ?? (string) $validated['title'],
            'eyebrow' => $validated['eyebrow'] ?? $post->eyebrow ?? self::DEFAULT_EYEBROW,
            'category' => $validated['category'] ?? $post->category ?? self::DEFAULT_CATEGORY,
            'tags' => $validated['tags'] ?? $post->tags ?? [],
            'meta_description' => $validated['metaDescription'] ?? $this->buildExcerpt((string) $validated['content'], 155),
            'meta_keywords' => $validated['metaKeywords'] ?? $post->meta_keywords ?? [],
            'published_at' => $status === 'published'
                ? ($post->published_at ?? now())
                : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Publicação atualizada com sucesso',
            'data' => $this->formatPublication($post->refresh()->load('author')),
        ]);
    }

    public function destroy(Request $request, int $post): JsonResponse
    {
        if (! $this->canManageContent($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar conteúdo',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $postModel = BlogPost::query()->find($post);

        if (! $postModel) {
            return response()->json([
                'success' => false,
                'message' => 'Publicação não encontrada.',
                'code' => 'POST_NOT_FOUND',
            ], 404);
        }

        try {
            $postModel->delete();
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível excluir a publicação agora.',
                'code' => 'DELETE_FAILED',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Publicação excluída com sucesso',
        ]);
    }

    public function destroyByPost(Request $request, int $post): JsonResponse
    {
        return $this->destroy($request, $post);
    }

    public function uploadFeaturedImage(Request $request): JsonResponse
    {
        if (! $this->canManageContent($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar conteúdo',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'previous_url' => ['nullable', 'string'],
        ]);

        if (is_string($validated['previous_url'] ?? null)) {
            $this->deletePreviousImage($validated['previous_url']);
        }

        $path = $request->file('image')->store('blog-images', 'public');
        $url = rtrim($request->getSchemeAndHttpHost(), '/').Storage::url($path);

        return response()->json([
            'success' => true,
            'message' => 'Imagem enviada com sucesso',
            'url' => $url,
        ]);
    }

    private function canManageContent(Request $request): bool
    {
        $role = (string) ($request->user()?->role ?? '');

        return in_array($role, ['manager', 'publisher'], true);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'featuredImage' => ['nullable', 'string', 'max:2048'],
            'imageAlt' => ['nullable', 'string', 'max:120'],
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:80'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:40'],
            'metaDescription' => ['nullable', 'string', 'max:255'],
            'metaKeywords' => ['nullable', 'array'],
            'metaKeywords.*' => ['string', 'max:60'],
        ]);
    }

    private function resolveSlug(string $title, ?BlogPost $current = null): string
    {
        $baseSlug = Str::slug($title) ?: 'publicacao';

        if ($current !== null && $current->exists && $current->slug !== '') {
            return $current->slug;
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($slug, $current)) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?BlogPost $current = null): bool
    {
        $query = BlogPost::query()->where('slug', $slug);

        if ($current !== null && $current->exists) {
            $query->whereKeyNot($current->getKey());
        }

        return $query->exists();
    }

    private function buildExcerpt(string $content, int $limit = 220): string
    {
        return str($content)->stripTags()->limit($limit)->toString();
    }

    private function deletePreviousImage(string $previousUrl): void
    {
        $parsedPath = parse_url($previousUrl, PHP_URL_PATH);

        if (! is_string($parsedPath) || ! str_starts_with($parsedPath, '/storage/blog-images/')) {
            return;
        }

        $storagePath = ltrim(str_replace('/storage/', '', $parsedPath), '/');

        if ($storagePath !== '' && Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }
    }

    private function formatPublication(BlogPost $post): array
    {
        $post->loadMissing('author');

        return [
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'author' => $post->author?->name ?? 'Equipe DPC',
            'date' => $post->published_at?->toISOString() ?? $post->updated_at?->toISOString() ?? now()->toISOString(),
            'status' => $post->published_at !== null ? 'published' : 'draft',
            'thumbnail' => $post->featured_image ?? '',
            'excerpt' => $post->excerpt,
            'imageAlt' => $post->image_alt,
            'eyebrow' => $post->eyebrow ?? $post->category,
            'category' => $post->category,
            'slug' => $post->slug,
            'publishedAt' => $post->published_at?->toISOString(),
            'updatedAt' => $post->updated_at?->toISOString(),
            'tags' => $post->tags ?? [],
            'metaDescription' => $post->meta_description,
            'metaKeywords' => $post->meta_keywords ?? [],
        ];
    }
}