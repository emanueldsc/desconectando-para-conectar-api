<?php

$u = App\Models\User::find(3);
if (! $u) {
    echo json_encode(['error' => 'user_not_found']);
    return;
}

$reservations = App\Models\RaffleReservation::with('raffle')
    ->where('user_id', $u->id)
    ->get();

$raffles = $reservations->groupBy('raffle_id')->map(function ($items) {
    $raffle = $items->first()->raffle;

    return [
        'id' => $raffle->id,
        'title' => $raffle->title,
        'drawDate' => $raffle->draw_date?->toISOString(),
        'status' => $raffle->status,
        'numbers' => $items->pluck('number')->values(),
        'imageUrl' => $raffle->image,
        'summary' => $raffle->summary ?? null,
    ];
})->values()->toArray();

echo json_encode($raffles);
