<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_member_creates_buyer_user(): void
    {
        $response = $this->postJson('/api/auth/register/member', [
            'name' => 'Maria doadora',
            'email' => 'maria.member@exemplo.com',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'maria.member@exemplo.com')
            ->assertJsonPath('user.role', 'buyer');

        $this->assertDatabaseHas('users', [
            'email' => 'maria.member@exemplo.com',
            'role' => 'buyer',
            'status' => 'active',
        ]);
    }

    public function test_register_internal_creates_admin_user_with_manager_or_publisher_role(): void
    {
        $response = $this->postJson('/api/auth/register/internal', [
            'name' => 'Carlos gestor',
            'email' => 'carlos.manager@exemplo.com',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
            'role' => 'manager',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'carlos.manager@exemplo.com')
            ->assertJsonPath('user.role', 'manager');

        $this->assertDatabaseHas('users', [
            'email' => 'carlos.manager@exemplo.com',
            'role' => 'manager',
            'status' => 'active',
        ]);
    }

    public function test_register_internal_rejects_non_admin_role(): void
    {
        $response = $this->postJson('/api/auth/register/internal', [
            'name' => 'Joana invalida',
            'email' => 'joana.invalid@exemplo.com',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
            'role' => 'buyer',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['role']]);
    }

    public function test_login_returns_token_and_user_payload(): void
    {
        $user = User::query()->create([
            'name' => 'João Silva Santos',
            'email' => 'joao@exemplo.com',
            'password' => Hash::make('senha123'),
            'role' => 'buyer',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'senha123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.fullName', 'João Silva Santos')
            ->assertJsonStructure([
                'success',
                'token',
                'user' => ['id', 'fullName', 'email', 'role', 'createdAt'],
                'expiresIn',
                'refreshToken',
            ]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::query()->create([
            'name' => 'João Silva Santos',
            'email' => 'joao@exemplo.com',
            'password' => Hash::make('senha123'),
            'role' => 'buyer',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'joao@exemplo.com',
            'password' => 'errada',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_verify_and_logout_work_with_token(): void
    {
        $user = User::query()->create([
            'name' => 'João Silva Santos',
            'email' => 'joao@exemplo.com',
            'password' => Hash::make('senha123'),
            'role' => 'buyer',
            'status' => 'active',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'senha123',
        ]);

        $token = $loginResponse->json('token');

        $verifyResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/verify');

        $verifyResponse->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('user.email', 'joao@exemplo.com');

        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout', []);

        $logoutResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/verify')
            ->assertStatus(401)
            ->assertJsonPath('code', 'TOKEN_INVALID');
    }
}