<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class RaffleController extends Controller
{
    public function reserveNumber(Request $request, Raffle $raffle, int $number): JsonResponse
    {
        if ((string) $raffle->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'A reserva só está disponível para rifas ativas.',
            ], 422);
        }

        if ($number < 1 || $number > (int) $raffle->tickets_available) {
            return response()->json([
                'success' => false,
                'message' => 'Número de ponto inválido para esta rifa.',
            ], 422);
        }

        $validated = $request->validate([
            'buyerName' => ['nullable', 'string', 'max:120'],
            'buyerPhone' => ['nullable', 'string', 'max:40'],
        ]);

        $numbers = $this->sanitizeNumbers($raffle, true);
        $index = collect($numbers)->search(fn (array $item): bool => (int) ($item['number'] ?? 0) === $number);

        if ($index === false) {
            return response()->json([
                'success' => false,
                'message' => 'Número não encontrado para esta rifa.',
            ], 422);
        }

        $current = $numbers[$index];

        if (($current['status'] ?? 'available') !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Este ponto já está reservado ou comprado.',
            ], 422);
        }

        $reservationCode = Str::uuid()->toString();
        $reservedUntil = now()->addMinutes($this->reservationTimeout($raffle));

        $numbers[$index] = [
            ...$current,
            'status' => 'reserved',
            'reservedAt' => now()->toISOString(),
            'reservedUntil' => $reservedUntil->toISOString(),
            'reservationCode' => $reservationCode,
            'reservationReceiptUrl' => null,
            'reservationPaymentStatus' => 'awaiting_receipt',
            'buyerName' => is_string($validated['buyerName'] ?? null) ? (string) $validated['buyerName'] : null,
            'buyerPhone' => is_string($validated['buyerPhone'] ?? null) ? (string) $validated['buyerPhone'] : null,
        ];

        $raffle->numbers = array_values($numbers);
        $raffle->save();

        return response()->json([
            'success' => true,
            'message' => 'Ponto reservado com sucesso. Envie o comprovante para finalizar a reserva.',
            'data' => [
                'number' => $number,
                'reservationCode' => $reservationCode,
                'reservedUntil' => $reservedUntil->toISOString(),
            ],
        ]);
    }

    public function uploadReservationReceipt(Request $request, Raffle $raffle, int $number): JsonResponse
    {
        $validated = $request->validate([
            'reservationCode' => ['required', 'string', 'max:80'],
            'receipt' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ]);

        $numbers = $this->sanitizeNumbers($raffle, true);
        $index = collect($numbers)->search(fn (array $item): bool => (int) ($item['number'] ?? 0) === $number);

        if ($index === false) {
            return response()->json([
                'success' => false,
                'message' => 'Número não encontrado para esta rifa.',
            ], 422);
        }

        $current = $numbers[$index];

        if (($current['status'] ?? '') !== 'reserved') {
            return response()->json([
                'success' => false,
                'message' => 'Este ponto não está reservado no momento.',
            ], 422);
        }

        if ((string) ($current['reservationCode'] ?? '') !== (string) $validated['reservationCode']) {
            return response()->json([
                'success' => false,
                'message' => 'Código de reserva inválido para este ponto.',
            ], 422);
        }

        $reservedUntil = $this->safeDate($current['reservedUntil'] ?? null);

        if (! $reservedUntil || $reservedUntil->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'O tempo de reserva deste ponto expirou.',
            ], 422);
        }

        if (is_string($current['reservationReceiptUrl'] ?? null)) {
            $this->deleteReceiptFile($current['reservationReceiptUrl']);
        }

        $path = $request->file('receipt')->store('raffle-receipts', 'public');
        $url = rtrim($request->getSchemeAndHttpHost(), '/').Storage::url($path);

        $numbers[$index] = [
            ...$current,
            'reservationReceiptUrl' => $url,
            'reservationPaymentStatus' => 'pending_review',
            'receiptSentAt' => now()->toISOString(),
        ];

        $raffle->numbers = array_values($numbers);
        $raffle->save();

        return response()->json([
            'success' => true,
            'message' => 'Comprovante enviado. Aguarde a confirmação do administrador.',
            'data' => [
                'number' => $number,
                'receiptUrl' => $url,
                'paymentStatus' => 'pending_review',
            ],
        ]);
    }

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
        $extractionNumber = trim((string) $request->string('extractionNumber', ''));
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

        if ($extractionNumber !== '' && ctype_digit($extractionNumber)) {
            $query->where('status', 'finished')
            ->where('extraction_number', (int) $extractionNumber);
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
        $raffle = Raffle::query()->with('organization')->findOrFail($id);
        $this->sanitizeNumbers($raffle, true);

        return $this->respondWithRaffle($raffle->fresh('organization'));
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
        $raffle = Raffle::query()->with('organization')->where('slug', $slug)->firstOrFail();
        $this->sanitizeNumbers($raffle, true);

        return $this->respondWithRaffle($raffle->fresh('organization'));
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
            'extractionNumber' => isset($raffle->extraction_number) ? (int) $raffle->extraction_number : null,
            'category' => $raffle->category,
            'ticketPrice' => (float) $raffle->ticket_price,
            'ticketsAvailable' => (int) $raffle->tickets_available,
            'ticketsSold' => (int) $raffle->tickets_sold,
            'reservationTimeoutMinutes' => $this->reservationTimeout($raffle),
            'numbers' => $this->sanitizeNumbers($raffle, false),
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
        $this->sanitizeNumbers($raffle, true);
        $winnerInfo = is_array($raffle->winner_info) ? $raffle->winner_info : [];

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
            'extractionNumber' => isset($raffle->extraction_number) ? (int) $raffle->extraction_number : null,
            'category' => $raffle->category,
            'ticketPrice' => (float) $raffle->ticket_price,
            'ticketsAvailable' => (int) $raffle->tickets_available,
            'ticketsSold' => (int) $raffle->tickets_sold,
            'reservationTimeoutMinutes' => $this->reservationTimeout($raffle),
            'winnerName' => is_string($winnerInfo['name'] ?? null) ? $winnerInfo['name'] : null,
            'winnerNumber' => isset($winnerInfo['winnerNumber']) ? (int) $winnerInfo['winnerNumber'] : null,
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

        return collect(range(1, $available))
            ->map(fn (int $number): array => [
                'number' => $number,
                'status' => $number <= $sold ? 'occupied' : 'available',
            ])
            ->all();
    }

    private function sanitizeNumbers(Raffle $raffle, bool $persist): array
    {
        $baseNumbers = is_array($raffle->numbers) ? $raffle->numbers : $this->generatedNumbers($raffle);
        $changed = ! is_array($raffle->numbers);

        $numbers = collect($baseNumbers)
            ->filter(fn ($item): bool => is_array($item) && isset($item['number']))
            ->map(function (array $item) use (&$changed): array {
                $normalizedStatus = $this->normalizeStatus((string) ($item['status'] ?? 'available'));

                if ($normalizedStatus !== (string) ($item['status'] ?? 'available')) {
                    $changed = true;
                }

                return [
                    ...$item,
                    'number' => (int) ($item['number'] ?? 0),
                    'status' => $normalizedStatus,
                ];
            })
            ->filter(fn (array $item): bool => $item['number'] > 0)
            ->sortBy('number')
            ->values()
            ->all();

        $released = false;
        $now = now();

        foreach ($numbers as $index => $numberData) {
            if (($numberData['status'] ?? '') !== 'reserved') {
                continue;
            }

            $reservedUntil = $this->safeDate($numberData['reservedUntil'] ?? null);

            if (! $reservedUntil || $reservedUntil->isFuture()) {
                continue;
            }

            $released = true;
            $numbers[$index] = [
                'number' => (int) ($numberData['number'] ?? 0),
                'status' => 'available',
                'releasedAt' => $now->toISOString(),
            ];
        }

        if ($persist && ($changed || $released)) {
            $raffle->numbers = array_values($numbers);
            $raffle->save();
        }

        return array_values($numbers);
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'occupied' => 'occupied',
            'reserved' => 'reserved',
            default => 'available',
        };
    }

    private function reservationTimeout(Raffle $raffle): int
    {
        return max(1, (int) ($raffle->reservation_timeout_minutes ?? 30));
    }

    private function safeDate(mixed $date): ?Carbon
    {
        if (! is_string($date) || trim($date) === '') {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }

    private function deleteReceiptFile(string $previousUrl): void
    {
        $parsedPath = parse_url($previousUrl, PHP_URL_PATH);

        if (! is_string($parsedPath) || ! str_starts_with($parsedPath, '/storage/raffle-receipts/')) {
            return;
        }

        $storagePath = ltrim(str_replace('/storage/', '', $parsedPath), '/');

        if ($storagePath !== '' && Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }
    }

    private function progress(float|int $current, float|int $goal): int
    {
        if ((float) $goal <= 0) {
            return 0;
        }

        return (int) min(100, round(((float) $current / (float) $goal) * 100));
    }
}