<?php

namespace Tests\Feature;

use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => '12345',
            'services.telegram.webhook_secret' => 'telegram-secret',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
    }

    protected function callbackPayload(string $data, string $telegramId = '999'): array
    {
        return [
            'callback_query' => [
                'id' => 'cb-1',
                'from' => ['id' => $telegramId],
                'message' => [
                    'chat' => ['id' => 777],
                    'message_id' => 42,
                ],
                'data' => $data,
            ],
        ];
    }

    public function test_rejects_request_without_a_valid_secret_token(): void
    {
        $response = $this->postJson(route('webhooks.telegram'), $this->callbackPayload('approve:1'), [
            'X-Telegram-Bot-Api-Secret-Token' => 'salah',
        ]);

        $response->assertStatus(401);
    }

    public function test_approve_callback_approves_the_transaction_for_the_linked_user(): void
    {
        $user = User::factory()->create();
        TelegramUser::factory()->for($user)->create(['telegram_id' => '999']);
        $transaction = Transaction::factory()->for($user)->pending()->create();

        $response = $this->postJson(
            route('webhooks.telegram'),
            $this->callbackPayload("approve:{$transaction->id}", '999'),
            ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret']
        );

        $response->assertOk();

        $transaction->refresh();
        $this->assertSame('approved', $transaction->status);
        $this->assertSame($user->id, $transaction->approved_by);
        $this->assertNotNull($transaction->approved_at);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'answerCallbackQuery'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'editMessageReplyMarkup'));
    }

    public function test_reject_callback_deletes_the_pending_transaction(): void
    {
        $user = User::factory()->create();
        TelegramUser::factory()->for($user)->create(['telegram_id' => '999']);
        $transaction = Transaction::factory()->for($user)->pending()->create();

        $response = $this->postJson(
            route('webhooks.telegram'),
            $this->callbackPayload("reject:{$transaction->id}", '999'),
            ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret']
        );

        $response->assertOk();
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_callback_from_an_unlinked_telegram_account_is_ignored(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->pending()->create();

        // Tidak ada TelegramUser dengan telegram_id '999' sama sekali.
        $response = $this->postJson(
            route('webhooks.telegram'),
            $this->callbackPayload("approve:{$transaction->id}", '999'),
            ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret']
        );

        $response->assertOk();
        $this->assertSame('pending', $transaction->refresh()->status);
    }

    public function test_callback_for_another_users_transaction_is_ignored(): void
    {
        $linkedUser = User::factory()->create();
        TelegramUser::factory()->for($linkedUser)->create(['telegram_id' => '999']);

        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->for($otherUser)->pending()->create();

        $response = $this->postJson(
            route('webhooks.telegram'),
            $this->callbackPayload("approve:{$transaction->id}", '999'),
            ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret']
        );

        $response->assertOk();
        $this->assertSame('pending', $transaction->refresh()->status);
    }

    public function test_callback_for_an_already_processed_transaction_does_not_change_it_again(): void
    {
        $user = User::factory()->create();
        TelegramUser::factory()->for($user)->create(['telegram_id' => '999']);
        $transaction = Transaction::factory()->for($user)->create([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now()->subHour(),
        ]);
        $originalApprovedAt = $transaction->approved_at;

        $response = $this->postJson(
            route('webhooks.telegram'),
            $this->callbackPayload("approve:{$transaction->id}", '999'),
            ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret']
        );

        $response->assertOk();
        $this->assertTrue($transaction->refresh()->approved_at->equalTo($originalApprovedAt));
    }

    public function test_non_callback_updates_are_acknowledged_without_side_effects(): void
    {
        $response = $this->postJson(route('webhooks.telegram'), [
            'message' => ['text' => 'halo'],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret']);

        $response->assertOk();
        Http::assertNothingSent();
    }
}
