<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Raffle;
use App\Models\Donation;
use App\Models\RaffleReservation;

class MemberController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'address' => $user->address,
                'role' => $user->role,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update([
            'name' => trim((string) $validated['name']),
            'phone' => isset($validated['phone']) ? trim((string) $validated['phone']) : null,
            'address' => isset($validated['address']) ? trim((string) $validated['address']) : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Perfil atualizado com sucesso.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'address' => $user->address,
                'role' => $user->role,
            ],
        ]);
    }

    // GET /api/member/raffles
    public function raffles(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $reservations = RaffleReservation::with('raffle')
            ->where('user_id', $user->id)
            ->get();

        $existingKeys = $reservations->mapWithKeys(function ($reservation) {
            return [sprintf('%s:%s', $reservation->raffle_id, $reservation->number) => true];
        });

        $userPhone = preg_replace('/\D+/', '', (string) ($user->phone ?? ''));
        $userName = mb_strtolower(trim((string) ($user->name ?? '')));

        $candidateRaffles = Raffle::query()
            ->whereIn('status', ['active', 'coming', 'finished'])
            ->get();

        foreach ($candidateRaffles as $raffle) {
            foreach (is_array($raffle->numbers) ? $raffle->numbers : [] as $number) {
                $num = isset($number['number']) ? (int) $number['number'] : null;
                if ($num === null || ($number['status'] ?? '') !== 'reserved') {
                    continue;
                }

                $key = sprintf('%s:%s', $raffle->id, $num);
                if (isset($existingKeys[$key])) {
                    continue;
                }

                $buyerPhone = preg_replace('/\D+/', '', (string) ($number['buyerPhone'] ?? ''));
                $buyerName = mb_strtolower(trim((string) ($number['buyerName'] ?? '')));

                if ($buyerPhone !== '' && $userPhone !== '' && $buyerPhone === $userPhone) {
                    $reservations->push((object) [
                        'raffle' => $raffle,
                        'raffle_id' => $raffle->id,
                        'number' => $num,
                        'status' => 'reserved',
                    ]);
                    $existingKeys[$key] = true;
                    continue;
                }

                if ($buyerName !== '' && $userName !== '' && $buyerName === $userName) {
                    $reservations->push((object) [
                        'raffle' => $raffle,
                        'raffle_id' => $raffle->id,
                        'number' => $num,
                        'status' => 'reserved',
                    ]);
                    $existingKeys[$key] = true;
                }
            }
        }

        $raffles = $reservations->groupBy('raffle_id')->map(function ($items) {
            $raffle = $items->first()->raffle;

            // build a map of raffle numbers info from raffle->numbers (if present)
            $numbersInfo = [];
            if (is_array($raffle->numbers)) {
                foreach ($raffle->numbers as $n) {
                    $num = isset($n['number']) ? (int) $n['number'] : null;
                    if ($num === null) continue;
                    $numbersInfo[$num] = [
                        'status' => $n['status'] ?? 'available',
                        'reservationCode' => $n['reservationCode'] ?? null,
                        'reservationPaymentStatus' => $n['reservationPaymentStatus'] ?? null,
                        'reservationReceiptUrl' => $n['reservationReceiptUrl'] ?? null,
                    ];
                }
            }

            $numbers = $items->map(function ($reservation) use ($numbersInfo) {
                $num = (int) $reservation->number;
                return [
                    'number' => $num,
                    'status' => $reservation->status ?? ($numbersInfo[$num]['status'] ?? 'reserved'),
                    'reservationCode' => $numbersInfo[$num]['reservationCode'] ?? null,
                    'reservationPaymentStatus' => $numbersInfo[$num]['reservationPaymentStatus'] ?? null,
                    'reservationReceiptUrl' => $numbersInfo[$num]['reservationReceiptUrl'] ?? null,
                ];
            })->values();

            return [
                'id' => $raffle->id,
                'title' => $raffle->title,
                'drawDate' => $raffle->draw_date,
                'status' => $raffle->status,
                'numbers' => $numbers,
                'imageUrl' => $raffle->image,
                'summary' => $raffle->summary ?? null,
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $raffles]);
    }

    // GET /api/member/donations
    public function donations(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $donations = Donation::where('user_id', $user->id)->get();

        $result = $donations->map(function ($donation) {
            return [
                'id' => $donation->id,
                'amount' => $donation->amount,
                'date' => $donation->date,
                'status' => $donation->status,
                'paymentMethod' => $donation->payment_method,
            ];
        });

        return response()->json(['success' => true, 'data' => $result]);
    }
}
