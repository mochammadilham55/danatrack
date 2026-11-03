<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TransactionTelegramNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => '12345',
        ]);
    }

    public function test_creating_a_transaction_sends_a_telegram_notification(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'transaction_date' => now()->format('Y-m-d'),
            'type' => 'expense',
            'amount' => 25000,
            'description' => 'Makan siang',
        ]);

        $response->assertRedirect(route('transactions.index'));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
                && str_contains($request['text'], 'Makan siang');
        });
    }

    public function test_transaction_is_still_saved_even_if_telegram_notification_fails(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false], 500),
        ]);

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'transaction_date' => now()->format('Y-m-d'),
            'type' => 'income',
            'amount' => 100000,
            'description' => 'Gaji',
        ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'description' => 'Gaji',
            'status' => 'approved',
        ]);
    }

    public function test_transaction_is_still_saved_even_if_telegram_connection_fails(): void
    {
        $user = User::factory()->create();

        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'transaction_date' => now()->format('Y-m-d'),
            'type' => 'expense',
            'amount' => 15000,
            'description' => 'Parkir',
        ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'description' => 'Parkir',
            'status' => 'approved',
        ]);
    }
}
