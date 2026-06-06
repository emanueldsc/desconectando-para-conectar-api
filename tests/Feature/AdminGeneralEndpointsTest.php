<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\CmsSetting;
use App\Models\Raffle;
use App\Models\RaffleReservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminGeneralEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_read_admin_overview(): void
    {
        $manager = User::query()->create([
            'name' => 'Gestor Geral',
            'email' => 'gestor.geral@exemplo.com',
            'password' => 'senha1234',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $member = User::query()->create([
            'name' => 'Membro Teste',
            'email' => 'membro.teste@exemplo.com',
            'password' => 'senha1234',
            'role' => 'buyer',
            'status' => 'active',
        ]);

        User::query()->create([
            'name' => 'Publicador Teste',
            'email' => 'publicador.teste@exemplo.com',
            'password' => 'senha1234',
            'role' => 'publisher',
            'status' => 'active',
        ]);

        $oldUser = User::query()->create([
            'name' => 'Usuario Antigo',
            'email' => 'usuario.antigo@exemplo.com',
            'password' => 'senha1234',
            'role' => 'buyer',
            'status' => 'active',
        ]);

        User::query()->whereKey($oldUser->id)->update([
            'created_at' => now()->subDays(20),
        ]);

        CmsSetting::query()->create([
            'banners' => [],
            'phrases' => ['Frase teste'],
            'contact' => ['email' => 'cms@exemplo.com', 'whatsapp' => '(81) 90000-0000', 'phone' => '(81) 3000-0000'],
            'socials' => ['instagram' => '', 'facebook' => '', 'youtube' => ''],
            'hero_button' => [
                'label' => 'Doar Agora',
                'link' => '/public/raffles',
                'icon' => 'favorite',
                'backgroundColor' => '#d35400',
                'textColor' => '#ffffff',
            ],
            'home_reality' => [
                'title' => 'Nossa Realidade',
                'subtitle' => 'Ultimas publicacoes',
                'displayMode' => 'latest',
                'publicationIds' => [],
            ],
            'monthly_goal' => 500,
        ]);

        $activeRaffle = Raffle::query()->create([
            'title' => 'Rifa Solidaria A',
            'slug' => 'rifa-solidaria-a',
            'description' => 'Descricao curta',
            'full_description' => 'Descricao longa',
            'image' => 'https://cdn.exemplo.com/rifa-a.png',
            'goal' => 1000,
            'current' => 450,
            'status' => 'active',
            'draw_date' => now()->addDays(4),
            'category' => 'Geral',
            'ticket_price' => 10,
            'tickets_available' => 100,
            'tickets_sold' => 45,
        ]);

        Raffle::query()->create([
            'title' => 'Rifa Solidaria B',
            'slug' => 'rifa-solidaria-b',
            'description' => 'Descricao curta',
            'full_description' => 'Descricao longa',
            'image' => 'https://cdn.exemplo.com/rifa-b.png',
            'goal' => 2000,
            'current' => 300,
            'status' => 'finished',
            'draw_date' => now()->addDays(12),
            'category' => 'Geral',
            'ticket_price' => 10,
            'tickets_available' => 200,
            'tickets_sold' => 30,
        ]);

        Donation::query()->forceCreate([
            'user_id' => $member->id,
            'donor_name' => 'Maria Silva',
            'amount' => 120,
            'date' => now()->toDateString(),
            'payment_method' => 'pix',
            'status' => 'confirmed',
            'created_by' => $manager->id,
        ]);

        Donation::query()->forceCreate([
            'user_id' => $member->id,
            'donor_name' => 'Joao Souza',
            'amount' => 40,
            'date' => now()->subMonth()->toDateString(),
            'payment_method' => 'card',
            'status' => 'confirmed',
            'created_by' => $manager->id,
        ]);

        RaffleReservation::query()->forceCreate([
            'user_id' => $member->id,
            'raffle_id' => $activeRaffle->id,
            'number' => 7,
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        RaffleReservation::query()->forceCreate([
            'user_id' => $member->id,
            'raffle_id' => $activeRaffle->id,
            'number' => 8,
            'status' => 'paid',
            'created_at' => now()->subMonth(),
            'updated_at' => now()->subMonth(),
        ]);

        Sanctum::actingAs($manager);

        $response = $this->getJson('/api/admin/overview');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.metrics.totalDonationsCurrentMonth', 120)
            ->assertJsonPath('data.metrics.totalRafflePointsCurrentMonth', 10)
            ->assertJsonPath('data.metrics.totalRaisedCurrentMonth', 130)
            ->assertJsonPath('data.metrics.activeRaffles', 1)
            ->assertJsonPath('data.metrics.finishedRaffles', 1)
            ->assertJsonPath('data.metrics.usersTotal', 2)
            ->assertJsonPath('data.metrics.membersTotal', 2)
            ->assertJsonPath('data.metrics.monthlyTarget', 500)
            ->assertJsonPath('data.metrics.goalProgress', 26)
            ->assertJsonPath('data.metrics.historyLastSixMonths.5.month', now()->format('Y-m'))
            ->assertJsonPath('data.metrics.historyLastSixMonths.5.total', 130)
            ->assertJsonPath('data.cards.0.title', 'Total Arrecadado')
            ->assertJsonPath('data.cards.1.subtitle', 'Rifas finalizadas: 1')
            ->assertJsonPath('data.cards.2.subtitle', 'Membros cadastrados: 2')
            ->assertJsonPath('data.cards.3.value', '26%')
            ->assertJsonPath('data.cards.3.target', 'R$ 500,00');
    }

    public function test_member_cannot_read_admin_overview(): void
    {
        $member = User::query()->create([
            'name' => 'Membro Sem Acesso',
            'email' => 'membro.sem.acesso@exemplo.com',
            'password' => 'senha1234',
            'role' => 'buyer',
            'status' => 'active',
        ]);

        Sanctum::actingAs($member);

        $response = $this->getJson('/api/admin/overview');

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Acesso negado');
    }

    public function test_guest_cannot_read_admin_overview(): void
    {
        $response = $this->getJson('/api/admin/overview');

        $response->assertStatus(401);
    }
}
