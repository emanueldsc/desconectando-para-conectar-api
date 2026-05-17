<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $throwable, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            if ($throwable instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de validação',
                    'code' => 'VALIDATION_ERROR',
                    'errors' => $throwable->errors(),
                ], 422);
            }

            if ($throwable instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autenticado',
                    'code' => 'UNAUTHENTICATED',
                ], 401);
            }

            if ($throwable instanceof ModelNotFoundException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recurso não encontrado',
                    'code' => 'NOT_FOUND',
                ], 404);
            }

            return null;
        });
    })->create();
