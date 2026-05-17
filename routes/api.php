<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\RaffleController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function (): void {
    Route::get('/home', [PublicController::class, 'getHome']);

    Route::get('/blog', [BlogController::class, 'list']);
    Route::get('/blog/{id}', [BlogController::class, 'show'])->whereNumber('id');
    Route::get('/blog/{slug}', [BlogController::class, 'showBySlug'])->where('slug', '[A-Za-z0-9\-]+');

    Route::get('/raffles', [RaffleController::class, 'list']);
    Route::get('/raffles/{id}', [RaffleController::class, 'show'])->whereNumber('id');
    Route::get('/raffles/{slug}', [RaffleController::class, 'showBySlug'])->where('slug', '[A-Za-z0-9\-]+');
});

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/verify', [AuthController::class, 'verify'])->middleware('auth:sanctum');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::prefix('member')->group(function (): void {
        Route::get('/profile', fn () => response()->json(['message' => 'Not implemented yet'], 501));
        Route::get('/raffles', fn () => response()->json(['message' => 'Not implemented yet'], 501));
        Route::get('/donations', fn () => response()->json(['message' => 'Not implemented yet'], 501));
        Route::put('/profile', fn () => response()->json(['message' => 'Not implemented yet'], 501));
    });

    Route::prefix('admin')->group(function (): void {
        Route::get('/health', fn () => response()->json(['message' => 'Not implemented yet'], 501));
    });
});
