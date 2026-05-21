<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRaffleController extends Controller
{
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
            ->map(fn (Raffle $raffle): array => $this->formatRaffle($raffle))
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $raffles,
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
            'sourceComment' => ['required', 'string', 'max:500'],
        ]);

        $winnerNumber = (int) $validated['winnerNumber'];
        $sourceComment = (string) $validated['sourceComment'];

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
            'name' => 'Resultado registrado manualmente',
            'winnerNumber' => $winnerNumber,
            'drawDate' => now()->toISOString(),
            'prize' => $raffle->title,
            'sourceComment' => $sourceComment,
        ];
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
            'status' => match ((string) $raffle->status) {
                'coming' => 'draft',
                'finished' => 'closed',
                default => 'active',
            },
            'imageUrl' => $raffle->image,
            'winnerName' => is_string($winnerInfo['name'] ?? null) ? $winnerInfo['name'] : null,
            'winnerNumber' => isset($winnerInfo['winnerNumber']) ? (int) $winnerInfo['winnerNumber'] : null,
            'winnerSourceComment' => is_string($winnerInfo['sourceComment'] ?? null) ? $winnerInfo['sourceComment'] : null,
        ];
    }
}