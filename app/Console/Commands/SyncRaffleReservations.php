<?php

namespace App\Console\Commands;

use App\Models\Raffle;
use App\Models\RaffleReservation;
use App\Models\User;
use Illuminate\Console\Command;

class SyncRaffleReservations extends Command
{
    protected $signature = 'raffle:sync-reservations {--assign-to= : User ID to assign reservations when no match is found}';

    protected $description = 'Sync raffle numbers (reserved/occupied) into raffle_reservations table when missing';

    public function handle(): int
    {
        $assignTo = $this->option('assign-to');
        $assignUser = null;

        if ($assignTo !== null) {
            $assignUser = User::find((int) $assignTo);
            if (! $assignUser) {
                $this->error("assign-to user id {$assignTo} not found");
                return 1;
            }
        }

        $created = 0;
        $skipped = 0;

        $this->info('Scanning raffles...');

        Raffle::chunk(50, function ($raffles) use (&$created, &$skipped, $assignUser) {
            foreach ($raffles as $raffle) {
                $numbers = is_array($raffle->numbers) ? $raffle->numbers : [];

                foreach ($numbers as $num) {
                    $number = isset($num['number']) ? (int) $num['number'] : 0;
                    if ($number <= 0) {
                        continue;
                    }

                    $status = isset($num['status']) ? (string) $num['status'] : 'available';
                    if (! in_array($status, ['reserved', 'occupied'], true)) {
                        continue;
                    }

                    $exists = RaffleReservation::where('raffle_id', $raffle->id)
                        ->where('number', $number)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    // try to find a user by phone or name
                    $user = null;
                    $phone = trim((string) ($num['buyerPhone'] ?? ''));
                    $name = trim((string) ($num['buyerName'] ?? ''));

                    if ($phone !== '') {
                        $user = User::where('phone', $phone)->first();
                    }

                    if (! $user && $name !== '') {
                        $user = User::where('name', $name)->first();
                    }

                    if (! $user && $assignUser !== null) {
                        $user = $assignUser;
                    }

                    if (! $user) {
                        $this->line("Skipping raffle {$raffle->id} number {$number}: no matching user (buyerName/buyerPhone not found)");
                        $skipped++;
                        continue;
                    }

                    $reservationStatus = ($status === 'occupied' || (isset($num['reservationPaymentStatus']) && in_array($num['reservationPaymentStatus'], ['approved','paid'], true))) ? 'paid' : 'reserved';

                    try {
                        RaffleReservation::create([
                            'user_id' => $user->id,
                            'raffle_id' => $raffle->id,
                            'number' => $number,
                            'status' => $reservationStatus,
                        ]);

                        $this->line("Created reservation: raffle {$raffle->id} number {$number} -> user {$user->id} ({$user->email}) status {$reservationStatus}");
                        $created++;
                    } catch (\Throwable $e) {
                        $this->error("Failed to create reservation for raffle {$raffle->id} number {$number}: " . $e->getMessage());
                        $skipped++;
                    }
                }
            }
        });

        $this->info("Done. Created: {$created}. Skipped: {$skipped}.");

        return 0;
    }
}
