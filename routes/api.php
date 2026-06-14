<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminBlogController;
use App\Http\Controllers\Api\AdminCmsController;
use App\Http\Controllers\Api\AdminGeneralController;
use App\Http\Controllers\Api\AdminRaffleController;
use App\Http\Controllers\Api\AdminDonationController;
use App\Http\Controllers\Api\AdminUserController;
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
    Route::post('/raffles/{raffle}/numbers/{number}/reserve', [RaffleController::class, 'reserveNumber'])->whereNumber('raffle')->whereNumber('number');
    Route::post('/raffles/{raffle}/numbers/{number}/receipt', [RaffleController::class, 'uploadReservationReceipt'])->whereNumber('raffle')->whereNumber('number');
    Route::get('/raffles/{id}', [RaffleController::class, 'show'])->whereNumber('id');
    Route::get('/raffles/{slug}', [RaffleController::class, 'showBySlug'])->where('slug', '[A-Za-z0-9\-]+');
});

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register/internal', [AuthController::class, 'registerInternal']);
    Route::post('/register/member', [AuthController::class, 'registerMember']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/verify', [AuthController::class, 'verify'])->middleware('auth:sanctum');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::prefix('member')->group(function (): void {
        Route::get('/profile', [\App\Http\Controllers\Api\MemberController::class, 'profile']);
        Route::get('/raffles', [\App\Http\Controllers\Api\MemberController::class, 'raffles']);
        Route::get('/donations', [\App\Http\Controllers\Api\MemberController::class, 'donations']);
        Route::put('/profile', [\App\Http\Controllers\Api\MemberController::class, 'updateProfile']);
    });

    Route::prefix('admin')->group(function (): void {
        Route::get('/health', fn () => response()->json(['message' => 'Not implemented yet'], 501));
        Route::get('/overview', [AdminGeneralController::class, 'overview']);
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->whereNumber('user');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->whereNumber('user');
        Route::get('/content/posts', [AdminBlogController::class, 'index']);
        Route::post('/content/posts', [AdminBlogController::class, 'store']);
        Route::post('/content/posts/featured-image', [AdminBlogController::class, 'uploadFeaturedImage']);
        Route::delete('/content/posts/image', [AdminBlogController::class, 'deleteImage']);
        Route::put('/content/posts/{post}', [AdminBlogController::class, 'update'])->whereNumber('post');
        Route::delete('/content/posts/{post}', [AdminBlogController::class, 'destroy'])->whereNumber('post');
        Route::post('/content/posts/{post}/delete', [AdminBlogController::class, 'destroyByPost'])->whereNumber('post');
        Route::get('/raffles', [AdminRaffleController::class, 'index']);
        Route::post('/raffles', [AdminRaffleController::class, 'store']);
        Route::post('/raffles/image', [AdminRaffleController::class, 'uploadImage']);
        Route::put('/raffles/{raffle}', [AdminRaffleController::class, 'update'])->whereNumber('raffle');
        Route::post('/raffles/{raffle}/activate', [AdminRaffleController::class, 'activate'])->whereNumber('raffle');
        Route::post('/raffles/{raffle}/numbers/{number}/confirm-payment', [AdminRaffleController::class, 'confirmReservedNumber'])->whereNumber('raffle')->whereNumber('number');
        Route::post('/raffles/{raffle}/numbers/{number}/mark-sold', [AdminRaffleController::class, 'markNumberAsSold'])->whereNumber('raffle')->whereNumber('number');
        Route::put('/raffles/{raffle}/reservation-timeout', [AdminRaffleController::class, 'updateReservationTimeout'])->whereNumber('raffle');
        Route::delete('/raffles/{raffle}', [AdminRaffleController::class, 'destroy'])->whereNumber('raffle');
        Route::post('/raffles/{raffle}/draw', [AdminRaffleController::class, 'draw'])->whereNumber('raffle');
        Route::get('/cms', [AdminCmsController::class, 'show']);
        Route::put('/cms', [AdminCmsController::class, 'update']);
        Route::post('/cms/banner-image', [AdminCmsController::class, 'uploadBannerImage']);
        Route::get('/donations', [AdminDonationController::class, 'index']);
        Route::post('/donations', [AdminDonationController::class, 'store']);
        Route::put('/donations/{donation}', [AdminDonationController::class, 'update'])->whereNumber('donation');
        Route::delete('/donations/{donation}', [AdminDonationController::class, 'destroy'])->whereNumber('donation');
    });
});
