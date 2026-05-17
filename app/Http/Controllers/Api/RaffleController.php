<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class RaffleController extends Controller
{
    #[OA\Get(
        path: '/api/raffles',
        summary: 'List raffles',
        tags: ['Raffles'],
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
        $limit = max(1, min(50, (int) $request->integer('limit', 12)));
        $search = trim((string) $request->string('search', ''));
        $status = trim((string) $request->string('status', ''));
        $sort = (string) $request->string('sort', 'newest');
        $includeOld = $request->boolean('includeOld', false);

        $query = Raffle::query()->with('organization');

        if (! $includeOld) {
            $query->whereIn('status', ['active', 'coming']);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        match ($sort) {
            'popular' => $query->orderByDesc('tickets_sold'),
            'progress' => $query->orderByDesc('current'),
            default => $query->orderByDesc('created_at'),
        };

        $total = (clone $query)->count();
        $raffles = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'data' => $raffles->getCollection()->map(fn (Raffle $raffle): array => $this->formatListItem($raffle))->all(),
            'pagination' => [
                'total' => $total,
                'page' => $raffles->currentPage(),
                'limit' => $raffles->perPage(),
                'pages' => $raffles->lastPage(),
            ],
            'filters' => [
                'statuses' => $this->statusFilters(),
                'categories' => $this->categoryFilters(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/raffles/{id}',
        summary: 'Get raffle by ID',
        tags: ['Raffles'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'Raffle ID', required: true, schema: new OA\Schema(type: 'integer')),
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
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'drawDate', type: 'string'),
                        new OA\Property(property: 'organization', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        return $this->respondWithRaffle(Raffle::query()->with('organization')->findOrFail($id));
    }

    #[OA\Get(
        path: '/api/raffles/slug/{slug}',
        summary: 'Get raffle by slug',
        tags: ['Raffles'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', description: 'Raffle slug', required: true, schema: new OA\Schema(type: 'string')),
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
                        new OA\Property(property: 'description', type: 'string'),
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'drawDate', type: 'string'),
                        new OA\Property(property: 'organization', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function showBySlug(string $slug): JsonResponse
    {
        return $this->respondWithRaffle(Raffle::query()->with('organization')->where('slug', $slug)->firstOrFail());
    }

    private function respondWithRaffle(Raffle $raffle): JsonResponse
    {
        return response()->json([
            'id' => $raffle->id,
            'title' => $raffle->title,
            'description' => $raffle->description,
            'fullDescription' => $raffle->full_description,
            'image' => $raffle->image,
            'gallery' => $raffle->gallery ?? [],
            'goal' => (float) $raffle->goal,
            'current' => (float) $raffle->current,
            'progress' => $this->progress($raffle->current, $raffle->goal),
            'status' => $raffle->status,
            'drawDate' => $raffle->draw_date?->toISOString(),
            'category' => $raffle->category,
            'ticketPrice' => (float) $raffle->ticket_price,
            'ticketsAvailable' => (int) $raffle->tickets_available,
            'ticketsSold' => (int) $raffle->tickets_sold,
            'numbers' => $raffle->numbers ?? $this->generatedNumbers($raffle),
            'slug' => $raffle->slug,
            'createdAt' => $raffle->created_at?->toISOString(),
            'organization' => [
                'id' => $raffle->organization->id,
                'name' => $raffle->organization->name,
                'logo' => $raffle->organization->logo,
                'description' => $raffle->organization->description,
                'contact' => $raffle->organization->contact,
            ],
            'rules' => $raffle->rules,
            'seo' => [
                'metaDescription' => $raffle->meta_description,
                'keywords' => $raffle->meta_keywords ?? [],
            ],
            'winnerInfo' => $raffle->winner_info,
        ]);
    }

    private function formatListItem(Raffle $raffle): array
    {
        return [
            'id' => $raffle->id,
            'title' => $raffle->title,
            'description' => $raffle->description,
            'image' => $raffle->image,
            'goal' => (float) $raffle->goal,
            'current' => (float) $raffle->current,
            'progress' => $this->progress($raffle->current, $raffle->goal),
            'status' => $raffle->status,
            'drawDate' => $raffle->draw_date?->toISOString(),
            'category' => $raffle->category,
            'ticketPrice' => (float) $raffle->ticket_price,
            'ticketsAvailable' => (int) $raffle->tickets_available,
            'ticketsSold' => (int) $raffle->tickets_sold,
            'slug' => $raffle->slug,
            'createdAt' => $raffle->created_at?->toISOString(),
        ];
    }

    private function statusFilters(): array
    {
        return collect(['active', 'coming', 'finished'])
            ->map(fn (string $status): array => [
                'label' => match ($status) {
                    'active' => 'Em andamento',
                    'coming' => 'Em breve',
                    default => 'Concluída',
                },
                'value' => $status,
                'count' => Raffle::query()->where('status', $status)->count(),
            ])
            ->all();
    }

    private function categoryFilters(): array
    {
        return Raffle::query()
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

    private function generatedNumbers(Raffle $raffle): array
    {
        $available = max(0, (int) $raffle->tickets_available);
        $sold = min($available, (int) $raffle->tickets_sold);

        return collect(range(1, min($available, 50)))
            ->map(fn (int $number): array => [
                'number' => $number,
                'status' => $number <= $sold ? 'occupied' : 'available',
            ])
            ->all();
    }

    private function progress(float|int $current, float|int $goal): int
    {
        if ((float) $goal <= 0) {
            return 0;
        }

        return (int) min(100, round(((float) $current / (float) $goal) * 100));
    }
}