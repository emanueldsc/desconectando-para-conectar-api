<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Raffle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AdminRaffleController extends Controller
{
    private const DEFAULT_IMAGE = 'https://placehold.co/1200x675/png?text=Rifa+Solidaria';

    public function index(Request $request): JsonResponse
    {
        if (! $this->canManageRaffles($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar rifas',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $raffles = Raffle::query()
            ->latest('draw_date')
            ->get()
            ->map(function (Raffle $raffle): array {
                $this->sanitizeNumbers($raffle, true);

                return $this->formatRaffle($raffle->fresh());
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $raffles,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->canManageRaffles($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar rifas',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $payload = $this->validatedPayload($request);
        $organizationId = $this->resolveOrganizationId();
        $ticketCount = ($payload['rangeEnd'] - $payload['rangeStart']) + 1;

        $raffle = Raffle::query()->create([
            'title' => $payload['title'],
            'slug' => $this->uniqueSlug($payload['title']),
            'description' => $payload['description'],
            'full_description' => $payload['description'],
            'image' => $payload['imageUrl'] ?? self::DEFAULT_IMAGE,
            'goal' => $ticketCount * $payload['ticketPrice'],
            'current' => 0,
            'status' => 'coming',
            'draw_date' => $payload['drawDate'],
            'category' => 'Geral',
            'ticket_price' => $payload['ticketPrice'],
            'tickets_available' => $ticketCount,
            'tickets_sold' => 0,
            'reservation_timeout_minutes' => $payload['reservationTimeoutMinutes'],
            'organization_id' => $organizationId,
            'featured' => false,
            'meta_description' => Str::limit($payload['description'], 155, ''),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rifa criada com sucesso',
            'data' => $this->formatRaffle($raffle->fresh()),
        ], 201);
    }

    public function update(Request $request, Raffle $raffle): JsonResponse
    {
        if (! $this->canManageRaffles($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar rifas',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $payload = $this->validatedPayload($request);
        $ticketCount = ($payload['rangeEnd'] - $payload['rangeStart']) + 1;

        if ($ticketCount < (int) $raffle->tickets_sold) {
            throw ValidationException::withMessages([
                'rangeEnd' => 'O intervalo não pode ser menor que a quantidade de bilhetes já vendidos.',
            ]);
        }

        $raffle->fill([
            'title' => $payload['title'],
            'slug' => $this->uniqueSlug($payload['title'], $raffle->id),
            'description' => $payload['description'],
            'full_description' => $payload['description'],
            'image' => $payload['imageUrl'] ?? $raffle->image ?? self::DEFAULT_IMAGE,
            'goal' => $ticketCount * $payload['ticketPrice'],
            'current' => ((int) $raffle->tickets_sold) * $payload['ticketPrice'],
            'ticket_price' => $payload['ticketPrice'],
            'tickets_available' => $ticketCount,
            'reservation_timeout_minutes' => $payload['reservationTimeoutMinutes'],
            'draw_date' => $payload['drawDate'],
            'meta_description' => Str::limit($payload['description'], 155, ''),
        ]);

        $raffle->save();

        return response()->json([
            'success' => true,
            'message' => 'Rifa atualizada com sucesso',
            'data' => $this->formatRaffle($raffle->fresh()),
        ]);
    }

    public function activate(Request $request, Raffle $raffle): JsonResponse
    {
        if (! $this->canManageRaffles($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar rifas',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        if ((string) $raffle->status === 'finished') {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível ativar uma rifa já finalizada.',
                'code' => 'RAFFLE_ALREADY_FINISHED',
            ], 422);
        }

        $raffle->status = 'active';
        $raffle->save();

        return response()->json([
            'success' => true,
            'message' => 'Rifa ativada com sucesso',
            'data' => $this->formatRaffle($raffle->fresh()),
        ]);
    }

    public function confirmReservedNumber(Request $request, Raffle $raffle, int $number): JsonResponse
    {
        if (! $this->canManageRaffles($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar rifas',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        if ($number < 1 || $number > (int) $raffle->tickets_available) {
            return response()->json([
                'success' => false,
                'message' => 'Número de ponto inválido para esta rifa.',
            ], 422);
        }

        $validated = $request->validate([
            'reservationCode' => ['nullable', 'string', 'max:80'],
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
                'message' => 'Este ponto não está reservado para confirmação.',
            ], 422);
        }

        $reservationCode = is_string($validated['reservationCode'] ?? null) ? (string) $validated['reservationCode'] : null;

        if ($reservationCode !== null && $reservationCode !== '' && $reservationCode !== (string) ($current['reservationCode'] ?? '')) {
            return response()->json([
                'success' => false,
                'message' => 'Código de reserva não confere para este ponto.',
            ], 422);
        }

        $numbers[$index] = [
            ...$current,
            'status' => 'occupied',
            'reservationPaymentStatus' => 'approved',
            'approvedAt' => now()->toISOString(),
            'approvedBy' => (int) $request->user()->id,
        ];

        $raffle->numbers = array_values($numbers);
        $raffle->tickets_sold = min((int) $raffle->tickets_available, (int) $raffle->tickets_sold + 1);
        $raffle->current = ((int) $raffle->tickets_sold) * (float) $raffle->ticket_price;
        $raffle->save();

        return response()->json([
            'success' => true,
            'message' => 'Ponto confirmado como comprado com sucesso.',
            'data' => $this->formatRaffle($raffle->fresh()),
        ]);
    }

    public function updateReservationTimeout(Request $request, Raffle $raffle): JsonResponse
    {
        if (! $this->canManageRaffles($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar rifas',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $validated = $request->validate([
            'reservationTimeoutMinutes' => ['required', 'integer', 'min:1', 'max:10080'],
        ]);

        $raffle->reservation_timeout_minutes = (int) $validated['reservationTimeoutMinutes'];
        $raffle->save();

        return response()->json([
            'success' => true,
            'message' => 'Tempo de reserva atualizado com sucesso.',
            'data' => $this->formatRaffle($raffle->fresh()),
        ]);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        if (! $this->canManageRaffles($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar rifas',
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

        $path = $request->file('image')->store('raffle-images', 'public');
        $url = rtrim($request->getSchemeAndHttpHost(), '/').Storage::url($path);

        return response()->json([
            'success' => true,
            'message' => 'Imagem enviada com sucesso',
            'url' => $url,
        ]);
    }

    public function destroy(Request $request, Raffle $raffle): JsonResponse
    {
        if (! $this->canManageRaffles($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar rifas',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $raffle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rifa excluída com sucesso',
        ]);
    }

    public function draw(Request $request, Raffle $raffle): JsonResponse
    {
        if (! $this->canManageRaffles($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem permissão para gerenciar rifas',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        if ((string) $raffle->status === 'finished') {
            return response()->json([
                'success' => false,
                'message' => 'Esta rifa já foi finalizada.',
                'code' => 'RAFFLE_ALREADY_DRAWN',
            ], 422);
        }

        $validated = $request->validate([
            'winnerNumber' => [
                'required',
                'integer',
                'min:1',
                'max:'.$raffle->tickets_available,
            ],
            'extractionNumber' => ['required', 'integer', 'min:1', 'unique:raffles,extraction_number'],
            'winnerName' => ['nullable', 'string', 'max:120'],
        ]);

        $winnerNumber = (int) $validated['winnerNumber'];
        $extractionNumber = (int) $validated['extractionNumber'];
        $winnerName = is_string($validated['winnerName'] ?? null)
            ? trim((string) $validated['winnerName'])
            : null;

        if (is_array($raffle->numbers)) {
            $raffle->numbers = collect($raffle->numbers)
                ->map(function ($numberData) use ($winnerNumber): array {
                    if (! is_array($numberData)) {
                        return [];
                    }

                    $number = (int) ($numberData['number'] ?? 0);

                    if ($number === $winnerNumber) {
                        return [
                            ...$numberData,
                            'status' => 'occupied',
                        ];
                    }

                    return $numberData;
                })
                ->values()
                ->all();
        }

        $raffle->winner_info = [
            'id' => (int) $request->user()->id,
            'name' => $winnerName !== '' ? $winnerName : null,
            'winnerNumber' => $winnerNumber,
            'extractionNumber' => $extractionNumber,
            'drawDate' => now()->toISOString(),
            'prize' => $raffle->title,
        ];
        $raffle->extraction_number = $extractionNumber;
        $raffle->status = 'finished';
        $raffle->save();

        return response()->json([
            'success' => true,
            'message' => 'Sorteio registrado com sucesso',
            'data' => $this->formatRaffle($raffle->fresh()),
        ]);
    }

    private function canManageRaffles(Request $request): bool
    {
        $role = (string) ($request->user()?->role ?? '');

        return in_array($role, ['manager', 'publisher'], true);
    }

    /**
     * @return array{title: string, description: string, rangeStart: int, rangeEnd: int, ticketPrice: float, imageUrl?: string, reservationTimeoutMinutes: int, drawDate: ?Carbon}
     */
    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'description' => ['required', 'string', 'min:12', 'max:1000'],
            'rangeStart' => ['required', 'integer', 'min:1'],
            'rangeEnd' => ['required', 'integer', 'gte:rangeStart'],
            'ticketPrice' => ['required', 'numeric', 'min:0.01'],
            'imageUrl' => ['nullable', 'url', 'max:2048'],
            'reservationTimeoutMinutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'drawDate' => ['nullable', 'date'],
        ]);

        return [
            'title' => (string) $validated['title'],
            'description' => (string) $validated['description'],
            'rangeStart' => (int) $validated['rangeStart'],
            'rangeEnd' => (int) $validated['rangeEnd'],
            'ticketPrice' => (float) $validated['ticketPrice'],
            'imageUrl' => is_string($validated['imageUrl'] ?? null) ? (string) $validated['imageUrl'] : null,
            'reservationTimeoutMinutes' => isset($validated['reservationTimeoutMinutes'])
                ? (int) $validated['reservationTimeoutMinutes']
                : 30,
            'drawDate' => is_string($validated['drawDate'] ?? null)
                ? Carbon::parse((string) $validated['drawDate'])
                : null,
        ];
    }

    private function resolveOrganizationId(): int
    {
        $organization = Institution::query()->active()->first() ?? Institution::query()->first();

        if (! $organization) {
            throw ValidationException::withMessages([
                'organization' => 'Cadastre ao menos uma instituição ativa antes de criar rifas.',
            ]);
        }

        return (int) $organization->id;
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'rifa';
        $slug = $baseSlug;
        $suffix = 1;

        while (
            Raffle::query()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $suffix++;
            $slug = sprintf('%s-%d', $baseSlug, $suffix);
        }

        return $slug;
    }

    private function deletePreviousImage(string $previousUrl): void
    {
        $parsedPath = parse_url($previousUrl, PHP_URL_PATH);

        if (! is_string($parsedPath) || ! str_starts_with($parsedPath, '/storage/raffle-images/')) {
            return;
        }

        $storagePath = ltrim(str_replace('/storage/', '', $parsedPath), '/');

        if ($storagePath !== '' && Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }
    }

    private function formatRaffle(Raffle $raffle): array
    {
        $winnerInfo = is_array($raffle->winner_info) ? $raffle->winner_info : [];

        return [
            'id' => $raffle->id,
            'title' => $raffle->title,
            'description' => $raffle->description,
            'rangeStart' => 1,
            'rangeEnd' => (int) $raffle->tickets_available,
            'soldTickets' => (int) $raffle->tickets_sold,
            'ticketPrice' => (float) $raffle->ticket_price,
            'reservationTimeoutMinutes' => max(1, (int) ($raffle->reservation_timeout_minutes ?? 30)),
            'drawDate' => $raffle->draw_date?->toISOString(),
            'status' => match ((string) $raffle->status) {
                'coming' => 'draft',
                'finished' => 'closed',
                default => 'active',
            },
            'imageUrl' => $raffle->image,
            'numbers' => $this->sanitizeNumbers($raffle, false),
            'winnerName' => is_string($winnerInfo['name'] ?? null) ? $winnerInfo['name'] : null,
            'winnerNumber' => isset($winnerInfo['winnerNumber']) ? (int) $winnerInfo['winnerNumber'] : null,
            'extractionNumber' => isset($raffle->extraction_number) ? (int) $raffle->extraction_number : null,
        ];
    }

    private function sanitizeNumbers(Raffle $raffle, bool $persist): array
    {
        $available = max(0, (int) $raffle->tickets_available);
        $fallback = collect(range(1, $available))
            ->map(fn (int $n): array => [
                'number' => $n,
                'status' => $n <= (int) $raffle->tickets_sold ? 'occupied' : 'available',
            ])
            ->all();

        $baseNumbers = is_array($raffle->numbers) ? $raffle->numbers : $fallback;
        $changed = ! is_array($raffle->numbers);

        $numbers = collect($baseNumbers)
            ->filter(fn ($item): bool => is_array($item) && isset($item['number']))
            ->map(function (array $item) use (&$changed): array {
                $status = match ((string) ($item['status'] ?? 'available')) {
                    'occupied' => 'occupied',
                    'reserved' => 'reserved',
                    default => 'available',
                };

                if ($status !== (string) ($item['status'] ?? 'available')) {
                    $changed = true;
                }

                return [
                    ...$item,
                    'number' => (int) ($item['number'] ?? 0),
                    'status' => $status,
                ];
            })
            ->filter(fn (array $item): bool => $item['number'] > 0)
            ->sortBy('number')
            ->values()
            ->all();

        $released = false;

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
                'releasedAt' => now()->toISOString(),
            ];
        }

        if ($persist && ($changed || $released)) {
            $raffle->numbers = array_values($numbers);
            $raffle->save();
        }

        return array_values($numbers);
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
}