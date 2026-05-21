<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Raffle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminRaffleEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_user_can_list_and_draw_raffle_with_source_comment(): void
    {
        $organization = Institution::query()->create([
            'name' => 'Instituto Sertão Vivo',
            'description' => 'Apoio comunitário',
            'image' => 'https://cdn.exemplo.com/org.jpg',
            'status' => 'active',
        ]);

        $raffle = Raffle::query()->create([
            'title' => 'Rifa Solidária',
            'slug' => 'rifa-solidaria',
            'description' => 'Descrição da rifa',
            'full_description' => 'Detalhes completos',
            'image' => 'https://cdn.exemplo.com/rifa.jpg',
            'goal' => 1000,
            'current' => 1000,
            'status' => 'active',
            'draw_date' => now()->addDay(),
            'category' => 'Social',
            'ticket_price' => 10,
            'tickets_available' => 100,
            'tickets_sold' => 100,
            'organization_id' => $organization->id,
            'winner_info' => null,
            'featured' => false,
        ]);

        $user = User::query()->create([
            'name' => 'Gestor Rifa',
            'email' => 'gestor.rifa@exemplo.com',
            'password' => 'senha1234',
            'role' => 'manager',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $listResponse = $this->getJson('/api/admin/raffles');

        $listResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $raffle->id);

        $drawResponse = $this->postJson("/api/admin/raffles/{$raffle->id}/draw", [
            'winnerNumber' => 98,
            'sourceComment' => 'Sorteio realizado com a última bola da extração número X - 1º prêmio',
        ]);

        $drawResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.winnerNumber', 98)
            ->assertJsonPath('data.winnerSourceComment', 'Sorteio realizado com a última bola da extração número X - 1º prêmio');
    }

    public function test_member_user_cannot_access_admin_raffles(): void
    {
        $user = User::query()->create([
            'name' => 'Membro Rifa',
            'email' => 'membro.rifa@exemplo.com',
            'password' => 'senha1234',
            'role' => 'buyer',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/raffles');

        $response->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');
    }
}