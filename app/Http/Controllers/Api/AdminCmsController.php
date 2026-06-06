<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\CmsSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminCmsController extends Controller
{
    private const DEFAULT_BANNER_URL = 'https://placehold.co/1200x675/png?text=Banner+Principal';

    public function show(Request $request): JsonResponse
    {
        if (! $this->canManageCms($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar CMS',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatSettings($this->resolveSettings()),
            'availablePublications' => $this->availablePublications(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        if (! $this->canManageCms($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar CMS',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $validated = $request->validate([
            'banners' => ['required', 'array', 'min:1'],
            'banners.*.url' => ['nullable', 'string', 'max:2048'],
            'banners.*.alt' => ['required', 'string', 'max:120'],
            'banners.*.label' => ['required', 'string', 'max:120'],
            'phrases' => ['required', 'array', 'min:1'],
            'phrases.*' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'array'],
            'contact.email' => ['required', 'email', 'max:255'],
            'contact.whatsapp' => ['required', 'string', 'max:40'],
            'contact.phone' => ['required', 'string', 'max:40'],
            'socials' => ['required', 'array'],
            'socials.instagram' => ['nullable', 'url', 'max:2048'],
            'socials.facebook' => ['nullable', 'url', 'max:2048'],
            'socials.youtube' => ['nullable', 'url', 'max:2048'],
            'heroButton' => ['required', 'array'],
            'heroButton.title' => ['required', 'string', 'max:120'],
            'heroButton.label' => ['required', 'string', 'max:80'],
            'heroButton.link' => ['required', 'string', 'max:255'],
            'heroButton.icon' => ['required', 'string', 'max:40'],
            'heroButton.backgroundColor' => ['required', 'string', 'max:20'],
            'heroButton.textColor' => ['required', 'string', 'max:20'],
            'realitySection' => ['required', 'array'],
            'realitySection.title' => ['required', 'string', 'max:120'],
            'realitySection.subtitle' => ['required', 'string', 'max:255'],
            'realitySection.displayMode' => ['required', 'in:latest,selected'],
            'realitySection.publicationIds' => ['nullable', 'array', 'max:4'],
            'realitySection.publicationIds.*' => ['integer', 'distinct', 'exists:blog_posts,id'],
            'monthlyGoal' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        $settings = $this->resolveSettings();
        $currentBannerPaths = $this->resolveLocalBannerPaths($settings->banners ?? []);
        $nextBannerPaths = $this->resolveLocalBannerPaths($validated['banners'] ?? []);

        foreach (array_diff($currentBannerPaths, $nextBannerPaths) as $storagePath) {
            $this->deleteBannerByStoragePath($storagePath);
        }

        $settings->update([
            'banners' => $validated['banners'],
            'phrases' => $validated['phrases'],
            'contact' => $validated['contact'],
            'socials' => $validated['socials'],
            'hero_button' => $validated['heroButton'],
            'home_reality' => $validated['realitySection'],
            'monthly_goal' => $validated['monthlyGoal'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'CMS atualizado com sucesso',
            'data' => $this->formatSettings($settings->fresh()),
            'availablePublications' => $this->availablePublications(),
        ]);
    }

    public function uploadBannerImage(Request $request): JsonResponse
    {
        if (! $this->canManageCms($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar CMS',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $validated = $request->validate([
            'banner' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'previous_url' => ['nullable', 'string'],
        ]);

        if (is_string($validated['previous_url'] ?? null)) {
            $this->deletePreviousBanner($validated['previous_url']);
        }

        $path = $request->file('banner')->store('cms-banners', 'public');
        $url = rtrim($request->getSchemeAndHttpHost(), '/').Storage::url($path);

        return response()->json([
            'success' => true,
            'message' => 'Imagem enviada com sucesso',
            'url' => $url,
        ]);
    }

    private function canManageCms(Request $request): bool
    {
        $role = (string) ($request->user()?->role ?? '');

        return in_array($role, ['manager', 'publisher'], true);
    }

    private function resolveSettings(): CmsSetting
    {
        return CmsSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'banners' => [
                    [
                        'url' => self::DEFAULT_BANNER_URL,
                        'alt' => 'Banner principal da campanha',
                        'label' => 'Banner Principal',
                    ],
                ],
                'phrases' => [
                    'A união faz a força do nosso povo.',
                ],
                'contact' => [
                    'email' => 'contato@desconectando.com.br',
                    'whatsapp' => '(81) 99999-0000',
                    'phone' => '(81) 3333-4444',
                ],
                'socials' => [
                    'instagram' => '',
                    'facebook' => '',
                    'youtube' => '',
                ],
                'hero_button' => [
                    'title' => 'Desconectando para Conectar',
                    'label' => 'Participar Agora',
                    'link' => '/public/raffles',
                    'icon' => 'favorite_border',
                    'backgroundColor' => '#d35400',
                    'textColor' => '#ffffff',
                ],
                'home_reality' => [
                    'title' => 'Nossa Realidade',
                    'subtitle' => 'Publicações em destaque sobre a transformação da nossa comunidade.',
                    'displayMode' => 'latest',
                    'publicationIds' => [],
                ],
                'monthly_goal' => 20000,
            ]
        );
    }

    private function formatSettings(CmsSetting $settings): array
    {
        $banners = collect($settings->banners ?? [])
            ->map(function ($banner): array {
                $url = is_array($banner) ? (string) ($banner['url'] ?? '') : '';

                // Some seed/demo URLs can become forbidden over time; keep preview stable.
                if ($url !== '' && (str_contains($url, 'aida-public') || str_contains($url, 'cdn.exemplo.com'))) {
                    $url = self::DEFAULT_BANNER_URL;
                }

                return [
                    'url' => $url,
                    'alt' => is_array($banner) ? (string) ($banner['alt'] ?? '') : '',
                    'label' => is_array($banner) ? (string) ($banner['label'] ?? '') : '',
                ];
            })
            ->values()
            ->all();

        return [
            'banners' => $banners,
            'phrases' => $settings->phrases ?? [],
            'contact' => $settings->contact ?? [
                'email' => '',
                'whatsapp' => '',
                'phone' => '',
            ],
            'socials' => $settings->socials ?? [
                'instagram' => '',
                'facebook' => '',
                'youtube' => '',
            ],
            'heroButton' => $settings->hero_button ?? [
                'title' => 'Desconectando para Conectar',
                'label' => 'Participar Agora',
                'link' => '/public/raffles',
                'icon' => 'favorite_border',
                'backgroundColor' => '#d35400',
                'textColor' => '#ffffff',
            ],
            'realitySection' => $settings->home_reality ?? [
                'title' => 'Nossa Realidade',
                'subtitle' => 'Publicações em destaque sobre a transformação da nossa comunidade.',
                'displayMode' => 'latest',
                'publicationIds' => [],
            ],
            'monthlyGoal' => (float) ($settings->monthly_goal ?? 20000),
            'updatedAt' => $settings->updated_at?->toISOString(),
        ];
    }

    private function deletePreviousBanner(string $previousUrl): void
    {
        $parsedPath = parse_url($previousUrl, PHP_URL_PATH);

        if (! is_string($parsedPath) || $parsedPath === '') {
            return;
        }

        if (! str_starts_with($parsedPath, '/storage/cms-banners/')) {
            return;
        }

        $storagePath = ltrim(str_replace('/storage/', '', $parsedPath), '/');

        if ($storagePath !== '' && Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }
    }

    private function resolveLocalBannerPaths(array $banners): array
    {
        $paths = [];

        foreach ($banners as $banner) {
            $url = is_array($banner) ? (string) ($banner['url'] ?? '') : '';
            $parsedPath = parse_url($url, PHP_URL_PATH);

            if (! is_string($parsedPath) || ! str_starts_with($parsedPath, '/storage/cms-banners/')) {
                continue;
            }

            $storagePath = ltrim(str_replace('/storage/', '', $parsedPath), '/');

            if ($storagePath !== '') {
                $paths[] = $storagePath;
            }
        }

        return array_values(array_unique($paths));
    }

    private function deleteBannerByStoragePath(string $storagePath): void
    {
        if ($storagePath !== '' && Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }
    }

    private function availablePublications(): array
    {
        return BlogPost::query()
            ->published()
            ->latest('published_at')
            ->limit(12)
            ->get()
            ->map(fn (BlogPost $post): array => $this->formatBlogPreview($post))
            ->all();
    }

    private function formatBlogPreview(BlogPost $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'excerpt' => $post->excerpt ?? str($post->content)->stripTags()->limit(220)->toString(),
            'image' => $post->featured_image,
            'imageAlt' => $post->image_alt,
            'eyebrow' => $post->eyebrow ?? $post->category,
            'description' => $post->title,
            'slug' => $post->slug,
            'publishedAt' => $post->published_at?->toISOString(),
            'readTime' => $this->estimateReadTime($post->content),
        ];
    }

    private function estimateReadTime(string $content): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($content)) / 200));
    }
}
