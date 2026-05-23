<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/api/admin/raffles/1', 'PUT', [], [], [], ['HTTP_ACCEPT' => 'application/json'], json_encode([
  'title' => 'Cesta Regional Nordestina',
  'description' => 'Arrecadação de cestas básicas para famílias em vulnerabilidade',
  'rangeStart' => 1,
  'rangeEnd' => 100,
  'ticketPrice' => 10,
  'reservationTimeoutMinutes' => 30,
  'drawDate' => '2026-12-23',
  'drawDateUndefined' => false,
  'imageUrl' => 'http://localhost:8000/storage/raffle-images/9eeRLLQiWHMURtbPsvO0XjN7wvisn9MjEa9sFQJf.jpg'
]));
$request->headers->set('Content-Type', 'application/json');

try {
    $controller = app(\App\Http\Controllers\Api\AdminRaffleController::class);
    $user = \App\Models\User::first() ?? \App\Models\User::factory()->make(['role' => 'manager']);
    $request->setUserResolver(function() use ($user) { return $user; });
    
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('validatedPayload');
    $method->setAccessible(true);
    $method->invoke($controller, $request);
    
    echo "Validation Passed\n";
    
    // Test the specific ticket count validation
    $raffle = \App\Models\Raffle::find(1);
    if ($raffle) {
        $ticketCount = 100 - 1 + 1;
        if ($ticketCount < (int) $raffle->tickets_sold) {
            echo "Failed: rangeEnd < tickets_sold\n";
        } else {
            echo "Ticket count logic passed\n";
        }
    }
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "Validation Failed: " . json_encode($e->errors()) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
