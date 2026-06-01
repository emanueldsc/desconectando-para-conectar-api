<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CmsSetting;
use App\Models\Donation;
use App\Models\Raffle;
use App\Models\RaffleReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminGeneralController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        if (! $this->canManage($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $totalDonationsCurrentMonth = (float) Donation::query()
            ->where('status', 'confirmed')
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->sum('amount');

        $totalRafflePointsCurrentMonth = (float) RaffleReservation::query()
            ->join('raffles', 'raffle_reservations.raffle_id', '=', 'raffles.id')
            ->where('raffle_reservations.status', 'paid')
            ->whereBetween('raffle_reservations.updated_at', [$startOfMonth, $endOfMonth])
            ->sum('raffles.ticket_price');

        $totalRaisedCurrentMonth = $totalDonationsCurrentMonth + $totalRafflePointsCurrentMonth;

        $activeRaffles = Raffle::query()
            ->where('status', 'active')
            ->count();

        $finishedRaffles = Raffle::query()
            ->where('status', 'finished')
            ->count();

        $usersTotal = User::query()
            ->whereIn('role', ['manager', 'publisher'])
            ->count();

        $membersTotal = User::query()
            ->where('role', 'buyer')
            ->where('status', 'active')
            ->count();

        $monthlyGoal = $this->resolveMonthlyGoal();
        $goalProgress = $monthlyGoal > 0
            ? min(100, (int) round(($totalRaisedCurrentMonth / $monthlyGoal) * 100))
            : 0;
        $historyLastSixMonths = $this->historyLastSixMonths();

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => [
                    'totalDonationsCurrentMonth' => $totalDonationsCurrentMonth,
                    'totalRafflePointsCurrentMonth' => $totalRafflePointsCurrentMonth,
                    'totalRaisedCurrentMonth' => $totalRaisedCurrentMonth,
                    'activeRaffles' => $activeRaffles,
                    'finishedRaffles' => $finishedRaffles,
                    'usersTotal' => $usersTotal,
                    'membersTotal' => $membersTotal,
                    'monthlyTarget' => $monthlyGoal,
                    'goalProgress' => $goalProgress,
                    'historyLastSixMonths' => $historyLastSixMonths,
                ],
                'cards' => [
                    [
                        'title' => 'Total Arrecadado',
                        'icon' => 'payments',
                        'value' => $this->toCurrency($totalRaisedCurrentMonth),
                        'subtitle' => 'Doacoes confirmadas + pontos pagos no mes',
                        'accent' => 'green',
                        'trendIcon' => 'trending_up',
                    ],
                    [
                        'title' => 'Rifas Ativas',
                        'icon' => 'local_activity',
                        'value' => str_pad((string) $activeRaffles, 2, '0', STR_PAD_LEFT),
                        'subtitle' => sprintf('Rifas finalizadas: %d', $finishedRaffles),
                        'accent' => 'orange',
                    ],
                    [
                        'title' => 'Usuarios',
                        'icon' => 'group',
                        'value' => number_format($usersTotal, 0, ',', '.'),
                        'subtitle' => sprintf('Membros cadastrados: %s', number_format($membersTotal, 0, ',', '.')),
                        'accent' => 'green',
                        'trendIcon' => 'person_add',
                    ],
                    [
                        'title' => 'Meta Mensal',
                        'icon' => 'flag',
                        'value' => sprintf('%d%%', $goalProgress),
                        'subtitle' => '',
                        'accent' => 'orange',
                        'progress' => $goalProgress,
                        'target' => $this->toCurrency($monthlyGoal),
                    ],
                ],
            ],
        ]);
    }

    private function canManage(Request $request): bool
    {
        $role = (string) ($request->user()?->role ?? '');

        return in_array($role, ['manager', 'publisher'], true);
    }

    private function toCurrency(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }

    private function resolveMonthlyGoal(): float
    {
        $value = CmsSetting::query()
            ->whereKey(1)
            ->value('monthly_goal');

        if ($value === null) {
            return 20000.0;
        }

        $parsed = (float) $value;

        return $parsed >= 0 ? $parsed : 0.0;
    }

    private function historyLastSixMonths(): array
    {
        $current = CarbonImmutable::now()->startOfMonth();
        $history = [];

        for ($offset = 5; $offset >= 0; $offset--) {
            $monthStart = $current->subMonths($offset);
            $monthEnd = $monthStart->endOfMonth();

            $donations = (float) Donation::query()
                ->where('status', 'confirmed')
                ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount');

            $raffles = (float) RaffleReservation::query()
                ->join('raffles', 'raffle_reservations.raffle_id', '=', 'raffles.id')
                ->where('raffle_reservations.status', 'paid')
                ->whereBetween('raffle_reservations.updated_at', [$monthStart, $monthEnd])
                ->sum('raffles.ticket_price');

            $total = $donations + $raffles;

            $history[] = [
                'month' => $monthStart->format('Y-m'),
                'label' => $monthStart->translatedFormat('M/Y'),
                'donations' => $donations,
                'raffles' => $raffles,
                'total' => $total,
                'totalFormatted' => $this->toCurrency($total),
            ];
        }

        return $history;
    }
}
