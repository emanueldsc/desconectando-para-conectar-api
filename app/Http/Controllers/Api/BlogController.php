<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BlogController extends Controller
{
    #[OA\Get(
        path: '/api/blog',
        summary: 'List blog posts',
        tags: ['Blog'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Number of items per page', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'pagination', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->integer('page', 1));
        $limit = max(1, min(50, (int) $request->integer('limit', 10)));
        $search = trim((string) $request->string('search', ''));
        $category = trim((string) $request->string('category', ''));
        $sort = (string) $request->string('sort', 'newest');

        $query = BlogPost::query()->published()->with('author');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        match ($sort) {
            'oldest' => $query->orderBy('published_at'),
            'popular' => $query->orderByDesc('views'),
            default => $query->orderByDesc('published_at'),
        };

        $total = (clone $query)->count();
        $posts = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'data' => $posts->getCollection()->map(fn (BlogPost $post): array => $this->formatPreview($post))->all(),
            'pagination' => [
                'total' => $total,
                'page' => $posts->currentPage(),
                'limit' => $posts->perPage(),
                'pages' => $posts->lastPage(),
            ],
            'categories' => $this->categories(),
        ]);
    }

    #[OA\Get(
        path: '/api/blog/{id}',
        summary: 'Get blog post by ID',
        tags: ['Blog'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Blog post ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'content', type: 'string'),
                        new OA\Property(property: 'author', type: 'object'),
                        new OA\Property(property: 'publishedAt', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        return $this->respondWithPost(BlogPost::query()->with(['author', 'comments'])->findOrFail($id));
    }

    #[OA\Get(
        path: '/api/blog/slug/{slug}',
        summary: 'Get blog post by slug',
        tags: ['Blog'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', description: 'Blog post slug', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'content', type: 'string'),
                        new OA\Property(property: 'author', type: 'object'),
                        new OA\Property(property: 'publishedAt', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function showBySlug(string $slug): JsonResponse
    {
        return $this->respondWithPost(BlogPost::query()->with(['author', 'comments'])->where('slug', $slug)->firstOrFail());
    }

    private function respondWithPost(BlogPost $post): JsonResponse
    {
        $post->increment('views');
        $post->refresh()->load(['author', 'comments']);

        return response()->json([
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'image' => $post->featured_image,
            'imageAlt' => $post->image_alt,
            'author' => [
                'id' => $post->author->id,
                'name' => $post->author->name,
                'avatar' => $post->author->avatar,
                'bio' => null,
                'socialLinks' => null,
            ],
            'category' => $post->category,
            'tags' => $post->tags ?? [],
            'publishedAt' => $post->published_at?->toISOString(),
            'updatedAt' => $post->updated_at?->toISOString(),
            'readTime' => $this->estimateReadTime($post->content),
            'views' => $post->views,
            'slug' => $post->slug,
            'relatedPosts' => $this->relatedPosts($post),
            'comments' => $post->comments->map(fn ($comment): array => [
                'id' => $comment->id,
                'author' => $comment->author,
                'email' => $comment->email,
                'content' => $comment->content,
                'createdAt' => $comment->created_at?->toISOString(),
                'replies' => $comment->replies ?? [],
            ])->all(),
            'seo' => [
                'metaDescription' => $post->meta_description,
                'keywords' => $post->meta_keywords ?? [],
            ],
        ]);
    }

    private function formatPreview(BlogPost $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'excerpt' => $post->excerpt ?? str($post->content)->stripTags()->limit(220)->toString(),
            'image' => $post->featured_image,
            'imageAlt' => $post->image_alt,
            'eyebrow' => $post->eyebrow ?? $post->category,
            'description' => $post->title,
            'category' => $post->category,
            'slug' => $post->slug,
            'publishedAt' => $post->published_at?->toISOString(),
            'readTime' => $this->estimateReadTime($post->content),
            'views' => $post->views,
            'author' => [
                'id' => $post->author->id,
                'name' => $post->author->name,
                'avatar' => $post->author->avatar,
            ],
        ];
    }

    private function relatedPosts(BlogPost $post): array
    {
        return BlogPost::query()
            ->published()
            ->whereKeyNot($post->id)
            ->where('category', $post->category)
            ->latest('published_at')
            ->limit(3)
            ->get()
            ->map(fn (BlogPost $relatedPost): array => [
                'id' => $relatedPost->id,
                'title' => $relatedPost->title,
                'excerpt' => $relatedPost->excerpt ?? str($relatedPost->content)->stripTags()->limit(160)->toString(),
                'image' => $relatedPost->featured_image,
                'imageAlt' => $relatedPost->image_alt,
                'eyebrow' => $relatedPost->eyebrow ?? $relatedPost->category,
                'description' => $relatedPost->title,
                'slug' => $relatedPost->slug,
                'publishedAt' => $relatedPost->published_at?->toISOString(),
                'readTime' => $this->estimateReadTime($relatedPost->content),
            ])
            ->all();
    }

    private function categories(): array
    {
        return BlogPost::query()
            ->published()
            ->selectRaw('category as label, lower(category) as value, count(*) as count')
            ->groupBy('category')
            ->orderBy('category')
            ->get()
            ->map(fn ($category): array => [
                'label' => $category->label,
                'value' => $category->value,
                'count' => (int) $category->count,
            ])
            ->all();
    }

    private function estimateReadTime(string $content): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($content)) / 200));
    }
}