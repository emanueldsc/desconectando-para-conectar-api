<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    private const INTERNAL_ROLES = ['manager', 'publisher'];

    private const MEMBER_ROLE = 'buyer';

    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Register a new user',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                    new OA\Property(property: 'password_confirmation', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'user', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        return $this->registerMember($request);
    }

    #[OA\Post(
        path: '/api/auth/register/internal',
        summary: 'Register an internal admin user (manager or publisher)',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                    new OA\Property(property: 'password_confirmation', type: 'string'),
                    new OA\Property(property: 'role', type: 'string', enum: ['manager', 'publisher']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Internal user registered successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function registerInternal(Request $request): JsonResponse
    {
        return $this->registerUser($request, true);
    }

    #[OA\Post(
        path: '/api/auth/register/member',
        summary: 'Register a member user (donor and raffle buyer)',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                    new OA\Property(property: 'password_confirmation', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Member registered successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function registerMember(Request $request): JsonResponse
    {
        return $this->registerUser($request, false);
    }

    private function registerUser(Request $request, bool $isInternal): JsonResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if ($isInternal) {
            $rules['role'] = ['required', 'string', 'in:'.implode(',', self::INTERNAL_ROLES)];
        }

        try {
            $validated = $request->validate($rules);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validação falhou',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }

        try {
            $role = $isInternal
                ? $validated['role']
                : self::MEMBER_ROLE;

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $role,
                'status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => $isInternal
                    ? 'Usuário administrativo registrado com sucesso'
                    : 'Membro registrado com sucesso',
                'user' => $this->formatUser($user),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar usuário',
                'code' => 'REGISTRATION_ERROR',
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Login user',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                    new OA\Property(property: 'rememberMe', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful login',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'user', type: 'object'),
                        new OA\Property(property: 'expiresIn', type: 'integer'),
                        new OA\Property(property: 'refreshToken', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:6'],
                'rememberMe' => ['sometimes', 'boolean'],
            ]);
        } catch (ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Email ou senha não fornecidos',
                'code' => 'INVALID_EMAIL',
                'timestamp' => now()->toISOString(),
            ], 400);
        }

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email ou senha inválidos',
                'code' => 'INVALID_CREDENTIALS',
                'timestamp' => now()->toISOString(),
            ], 401);
        }

        if (($user->status ?? 'active') !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Conta desativada',
                'code' => 'ACCOUNT_DISABLED',
                'timestamp' => now()->toISOString(),
            ], 401);
        }

        $tokenResult = $user->createToken('auth-token');
        $expiresAt = now()->addDay();
        $tokenResult->accessToken->expires_at = $expiresAt;
        $tokenResult->accessToken->save();

        return response()->json([
            'success' => true,
            'token' => $tokenResult->plainTextToken,
            'user' => $this->formatUser($user),
            'expiresIn' => 86400,
            'refreshToken' => null,
        ]);
    }

    #[OA\Get(
        path: '/api/auth/verify',
        summary: 'Verify token validity',
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token is valid',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'valid', type: 'boolean'),
                        new OA\Property(property: 'user', type: 'object'),
                        new OA\Property(property: 'expiresAt', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid or expired token',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'valid', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'code', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function verify(Request $request): JsonResponse
    {
        $tokenValue = $request->bearerToken();
        $token = $tokenValue ? PersonalAccessToken::findToken($tokenValue) : null;
        $user = $request->user();

        if (! $user || ! $token) {
            return response()->json([
                'valid' => false,
                'message' => 'Token inválido',
                'code' => 'TOKEN_INVALID',
            ], 401);
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            return response()->json([
                'valid' => false,
                'message' => 'Token expirado',
                'code' => 'TOKEN_EXPIRED',
            ], 401);
        }

        return response()->json([
            'valid' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'expiresAt' => $token->expires_at?->toISOString(),
        ]);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout user',
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful logout',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'timestamp', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso',
            'timestamp' => now()->toISOString(),
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'fullName' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'role' => $user->role,
            'address' => $user->address,
            'createdAt' => $user->created_at?->toISOString(),
        ];
    }
}