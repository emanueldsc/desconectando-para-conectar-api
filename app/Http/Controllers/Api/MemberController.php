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
    // GET /api/member/raffles
    public function raffles(Request $request)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Não autenticado'], 401);
        }

        $reservations = RaffleReservation::with('raffle')
            ->where('user_id', $user->id)
            ->get();

        $raffles = $reservations->groupBy('raffle_id')->map(function ($items) {
            $raffle = $items->first()->raffle;
            return [
                'id' => $raffle->id,
                'title' => $raffle->title,
                'drawDate' => $raffle->draw_date,
                'status' => $raffle->status,
                'numbers' => $items->pluck('number')->values(),
                'imageUrl' => $raffle->image,
                'summary' => $raffle->summary ?? null,
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $raffles]);
    }

    // GET /api/member/donations
    public function donations(Request $request)
    {
        $user = Auth::guard('api')->user();
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
