<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\CmsSetting;
use App\Models\Institution;
use App\Models\Raffle;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PublicController extends Controller
{
    private const DEFAULT_HERO_SUBTITLE = 'Uma iniciativa solidária para o Sertão Nordestino';
    private const DEFAULT_HERO_BACKGROUND = 'https://placehold.co/1200x675/png?text=Banner+Principal';

    #[OA\Get(
        path: '/api/public/home',
        summary: 'Get public home data',
        tags: ['Public'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'hero', type: 'object'),
                        new OA\Property(property: 'realitySection', type: 'object'),
                        new OA\Property(property: 'featuredRaffles', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'institutions', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'statistics', type: 'object'),
                        new OA\Property(property: 'blogPreview', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
        ]
    )]
    public function getHome(): JsonResponse
    {
        $cms = CmsSetting::query()->first();

        return response()->json([
            'hero' => $this->hero($cms),
            'impactPhrases' => $this->impactPhrases($cms),
            'realitySection' => $this->realitySection($cms),
            'socials' => $this->socials($cms),
            'featuredRaffles' => $this->featuredRaffles(),
            'institutions' => $this->institutions(),
            'statistics' => $this->statistics(),
            'blogPreview' => $this->blogPreview(),
        ]);
    }

    private function hero(?CmsSetting $cms): array
    {
        $banners = is_array($cms?->banners) ? $cms->banners : [];
        $phrases = is_array($cms?->phrases) ? $cms->phrases : [];
        $heroButton = is_array($cms?->hero_button) ? $cms->hero_button : [];

        $firstBannerUrl = collect($banners)
            ->map(fn ($banner) => is_array($banner) ? (string) ($banner['url'] ?? '') : '')
            ->first(fn (string $url): bool => $url !== '');

        $firstPhrase = collect($phrases)
            ->map(fn ($phrase) => is_string($phrase) ? trim($phrase) : '')
            ->first(fn (string $phrase): bool => $phrase !== '');

        return [
            'title' => 'Desconectando para Conectar',
            'subtitle' => $firstPhrase !== null ? $firstPhrase : self::DEFAULT_HERO_SUBTITLE,
            'backgroundImage' => $firstBannerUrl !== null ? $firstBannerUrl : self::DEFAULT_HERO_BACKGROUND,
            'ctaLabel' => (string) ($heroButton['label'] ?? 'Participar Agora'),
            'ctaLink' => (string) ($heroButton['link'] ?? '/public/raffles'),
            'ctaIcon' => (string) ($heroButton['icon'] ?? 'favorite_border'),
            'ctaBackgroundColor' => (string) ($heroButton['backgroundColor'] ?? '#d35400'),
            'ctaTextColor' => (string) ($heroButton['textColor'] ?? '#ffffff'),
        ];
    }

    private function impactPhrases(?CmsSetting $cms): array
    {
        $phrases = is_array($cms?->phrases) ? $cms->phrases : [];

        return collect($phrases)
            ->map(fn ($phrase) => is_string($phrase) ? trim($phrase) : '')
            ->filter(fn (string $phrase): bool => $phrase !== '')
            ->values()
            ->all();
    }

    private function realitySection(?CmsSetting $cms): array
    {
        $settings = is_array($cms?->home_reality) ? $cms->home_reality : [];
        $displayMode = (string) ($settings['displayMode'] ?? 'latest');
        $publicationIds = array_values(array_filter(array_map(
            static fn ($publicationId): int => (int) $publicationId,
            is_array($settings['publicationIds'] ?? null) ? $settings['publicationIds'] : []
        ), static fn (int $publicationId): bool => $publicationId > 0));

        return [
            'title' => (string) ($settings['title'] ?? 'Nossa Realidade'),
            'subtitle' => (string) ($settings['subtitle'] ?? 'Publicações em destaque sobre a transformação da nossa comunidade.'),
            'displayMode' => in_array($displayMode, ['latest', 'selected'], true) ? $displayMode : 'latest',
            'publications' => $displayMode === 'selected' && $publicationIds !== []
                ? $this->selectedPublications($publicationIds)
                : $this->latestPublications(4),
        ];
    }

    private function socials(?CmsSetting $cms): array
    {
        $socials = is_array($cms?->socials) ? $cms->socials : [];

        return [
            'instagram' => (string) ($socials['instagram'] ?? ''),
            'facebook' => (string) ($socials['facebook'] ?? ''),
            'youtube' => (string) ($socials['youtube'] ?? ''),
        ];
    }

    private function featuredRaffles(): array
    {
        return Raffle::query()
            ->with('organization')
            ->where(function ($query): void {
                $query->where('status', 'active')->orWhere('featured', true);
            })
            ->latest('draw_date')
            ->limit(3)
            ->get()
            ->map(fn (Raffle $raffle): array => $this->formatFeaturedRaffle($raffle))
            ->all();
    }

    private function institutions(): array
    {
        return Institution::query()
            ->active()
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (Institution $institution): array => [
                'id' => $institution->id,
                'name' => $institution->name,
                'description' => $institution->description,
                'image' => $institution->image,
                'imagePosition' => $institution->image_position,
            ])
            ->all();
    }

    private function statistics(): array
    {
        return [
            'totalDonated' => (float) Raffle::query()->sum('current'),
            'livesImpacted' => (int) Raffle::query()->sum('tickets_sold'),
            'communitiesReached' => Institution::query()->active()->count(),
        ];
    }

    private function blogPreview(): array
    {
        return BlogPost::query()
            ->published()
            ->with('author')
            ->latest('published_at')
            ->limit(3)
            ->get()
            ->map(fn (BlogPost $post): array => $this->formatBlogPreview($post))
            ->all();
    }

    private function latestPublications(int $limit): array
    {
        return BlogPost::query()
            ->published()
            ->with('author')
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->map(fn (BlogPost $post): array => $this->formatBlogPreview($post))
            ->all();
    }

    private function selectedPublications(array $publicationIds): array
    {
        if ($publicationIds === []) {
            return [];
        }

        $posts = BlogPost::query()
            ->published()
            ->with('author')
            ->whereIn('id', $publicationIds)
            ->get()
            ->keyBy('id');

        return collect($publicationIds)
            ->map(fn (int $publicationId) => $posts->get($publicationId))
            ->filter()
            ->map(fn (BlogPost $post): array => $this->formatBlogPreview($post))
            ->values()
            ->all();
    }

    private function formatFeaturedRaffle(Raffle $raffle): array
    {
        return [
            'id' => $raffle->id,
            'title' => $raffle->title,
            'description' => $raffle->description,
            'image' => $raffle->image,
            'progress' => $this->progress($raffle->current, $raffle->goal),
            'goal' => (float) $raffle->goal,
            'current' => (float) $raffle->current,
            'status' => $raffle->status,
            'drawDate' => $raffle->draw_date?->toISOString(),
            'category' => $raffle->category,
        ];
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

    private function progress(float|int $current, float|int $goal): int
    {
        if ((float) $goal <= 0) {
            return 0;
        }

        return (int) min(100, round(((float) $current / (float) $goal) * 100));
    }

    private function estimateReadTime(string $content): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($content)) / 200));
    }
}