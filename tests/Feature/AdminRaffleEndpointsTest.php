<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Raffle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminRaffleEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_user_can_create_update_and_delete_raffle(): void
    {
        Institution::query()->create([
            'name' => 'Instituto Nordeste Solidário',
            'description' => 'Apoio comunitário',
            'image' => 'https://cdn.exemplo.com/org-2.jpg',
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'name' => 'Gestor CRUD Rifa',
            'email' => 'gestor.crud@exemplo.com',
            'password' => 'senha1234',
            'role' => 'manager',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $createResponse = $this->postJson('/api/admin/raffles', [
            'title' => 'Rifa Cesta Especial',
            'description' => 'Campanha para apoiar famílias em vulnerabilidade da região.',
            'rangeStart' => 1,
            'rangeEnd' => 150,
            'ticketPrice' => 12.5,
            'drawDate' => null,
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Rifa Cesta Especial')
            ->assertJsonPath('data.rangeEnd', 150)
            ->assertJsonPath('data.drawDate', null)
            ->assertJsonPath('data.status', 'draft');

        $raffleId = (int) $createResponse->json('data.id');

        $updateResponse = $this->putJson("/api/admin/raffles/{$raffleId}", [
            'title' => 'Rifa Cesta Especial Atualizada',
            'description' => 'Descrição atualizada para a campanha solidária.',
            'rangeStart' => 1,
            'rangeEnd' => 200,
            'ticketPrice' => 15,
            'drawDate' => '2026-12-20',
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Rifa Cesta Especial Atualizada')
            ->assertJsonPath('data.rangeEnd', 200)
            ->assertJsonPath('data.ticketPrice', 15)
            ->assertJsonPath('data.drawDate', '2026-12-20T00:00:00.000000Z');

        $deleteResponse = $this->deleteJson("/api/admin/raffles/{$raffleId}");

        $deleteResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('raffles', [
            'id' => $raffleId,
        ]);
    }

    public function test_internal_user_can_list_and_draw_raffle_with_optional_winner_name(): void
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
            'extractionNumber' => 15234,
            'winnerName' => 'Maria da Silva',
        ]);

        $drawResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.extractionNumber', 15234)
            ->assertJsonPath('data.winnerNumber', 98)
                ->assertJsonPath('data.winnerName', 'Maria da Silva');
    }

    public function test_internal_user_can_activate_raffle_and_upload_image(): void
    {
        Storage::fake('public');

        $organization = Institution::query()->create([
            'name' => 'Instituto Sertão Vivo',
            'description' => 'Apoio comunitário',
            'image' => 'https://cdn.exemplo.com/org.jpg',
            'status' => 'active',
        ]);

        $raffle = Raffle::query()->create([
            'title' => 'Rifa em Rascunho',
            'slug' => 'rifa-em-rascunho',
            'description' => 'Descrição da rifa em rascunho',
            'full_description' => 'Detalhes completos',
            'image' => 'https://cdn.exemplo.com/rifa.jpg',
            'goal' => 1000,
            'current' => 0,
            'status' => 'coming',
            'draw_date' => now()->addDay(),
            'category' => 'Social',
            'ticket_price' => 10,
            'tickets_available' => 100,
            'tickets_sold' => 0,
            'organization_id' => $organization->id,
            'winner_info' => null,
            'featured' => false,
        ]);

        $user = User::query()->create([
            'name' => 'Gestor Rifa',
            'email' => 'gestor.ativacao@exemplo.com',
            'password' => 'senha1234',
            'role' => 'manager',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $activateResponse = $this->postJson("/api/admin/raffles/{$raffle->id}/activate");

        $activateResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active');

        $file = UploadedFile::fake()->create('raffle-banner.jpg', 256, 'image/jpeg');
        $uploadResponse = $this->postJson('/api/admin/raffles/image', [
            'image' => $file,
        ]);

        $uploadResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Imagem enviada com sucesso');

        $this->assertTrue(Storage::disk('public')->exists('raffle-images/'.$file->hashName()));
    }

    public function test_internal_user_can_confirm_reserved_number_and_update_timeout(): void
    {
        $organization = Institution::query()->create([
            'name' => 'Instituto Sertão Vivo',
            'description' => 'Apoio comunitário',
            'image' => 'https://cdn.exemplo.com/org.jpg',
            'status' => 'active',
        ]);

        $raffle = Raffle::query()->create([
            'title' => 'Rifa com Reserva',
            'slug' => 'rifa-com-reserva',
            'description' => 'Descrição da rifa',
            'full_description' => 'Detalhes completos',
            'image' => 'https://cdn.exemplo.com/rifa.jpg',
            'goal' => 1000,
            'current' => 0,
            'status' => 'active',
            'draw_date' => now()->addDay(),
            'category' => 'Social',
            'ticket_price' => 10,
            'tickets_available' => 100,
            'tickets_sold' => 0,
            'reservation_timeout_minutes' => 30,
            'organization_id' => $organization->id,
            'numbers' => [
                [
                    'number' => 1,
                    'status' => 'reserved',
                    'reservationCode' => 'res-123',
                    'reservedAt' => now()->subMinutes(5)->toISOString(),
                    'reservedUntil' => now()->addMinutes(25)->toISOString(),
                    'reservationPaymentStatus' => 'pending_review',
                ],
            ],
            'winner_info' => null,
            'featured' => false,
        ]);

        $user = User::query()->create([
            'name' => 'Gestor Rifa',
            'email' => 'gestor.confirmacao@exemplo.com',
            'password' => 'senha1234',
            'role' => 'manager',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $confirmResponse = $this->postJson("/api/admin/raffles/{$raffle->id}/numbers/1/confirm-payment", [
            'reservationCode' => 'res-123',
        ]);

        $confirmResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.soldTickets', 1);

        $timeoutResponse = $this->putJson("/api/admin/raffles/{$raffle->id}/reservation-timeout", [
            'reservationTimeoutMinutes' => 90,
        ]);

        $timeoutResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reservationTimeoutMinutes', 90);
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