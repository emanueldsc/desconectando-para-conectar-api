<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    private const INTERNAL_CONTACT_DOMAIN = '@internal.local';

    public function index(Request $request): JsonResponse
    {
        if (! $this->canManageUsers($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $users = User::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (User $user): array => $this->formatUser($user))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->canManageUsers($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $validated = $request->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:none,manager,publisher'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validated['role'] !== 'none' && (empty($validated['email']) || empty($validated['password']))) {
            return response()->json([
                'success' => false,
                'message' => 'E-mail e senha são obrigatórios para Gestor ou Publicador.',
            ], 422);
        }

        $isInternalContact = $validated['role'] === 'none';
        $storedRole = $isInternalContact ? 'buyer' : $validated['role'];
        $email = $isInternalContact
            ? sprintf('contato-%s%s', strtolower((string) Str::ulid()), self::INTERNAL_CONTACT_DOMAIN)
            : (string) $validated['email'];
        $password = $isInternalContact
            ? Str::password(24)
            : (string) $validated['password'];

        $user = User::create([
            'name' => $validated['fullName'],
            'email' => $email,
            'password' => Hash::make($password),
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'role' => $storedRole,
            'status' => $isInternalContact ? 'inactive' : 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuário criado com sucesso',
            'data' => $this->formatUser($user),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if (! $this->canManageUsers($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado',
            ], 403);
        }

        $validated = $request->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:none,buyer,manager,publisher'],
        ]);

        $isInternalContact = $validated['role'] === 'none';
        $storedRole = $isInternalContact ? 'buyer' : $validated['role'];

        $email = $user->email;
        if ($isInternalContact && !str_ends_with((string) $user->email, self::INTERNAL_CONTACT_DOMAIN)) {
            $email = sprintf('contato-%s%s', strtolower((string) Str::ulid()), self::INTERNAL_CONTACT_DOMAIN);
        }

        $user->update([
            'name' => $validated['fullName'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'role' => $storedRole,
            'status' => $isInternalContact ? 'inactive' : 'active',
            'email' => $email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuário atualizado com sucesso',
            'data' => $this->formatUser($user),
        ]);
    }

    private function canManageUsers(Request $request): bool
    {
        $role = (string) ($request->user()?->role ?? '');

        return in_array($role, ['manager', 'publisher'], true);
    }

    private function formatUser(User $user): array
    {
        $isInternalContact =
            (string) ($user->role ?? '') === 'buyer'
            && (string) ($user->status ?? '') === 'inactive'
            && str_ends_with((string) $user->email, self::INTERNAL_CONTACT_DOMAIN);

        return [
            'id' => $user->id,
            'fullName' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'role' => $isInternalContact ? 'none' : (string) ($user->role ?? 'buyer'),
            'status' => (string) ($user->status ?? 'active'),
            'createdAt' => $user->created_at?->toISOString(),
        ];
    }
}
