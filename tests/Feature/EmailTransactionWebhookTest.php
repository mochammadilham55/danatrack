<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmailTransactionWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        config([
            'services.email_webhook.secret' => 'webhook-secret',
            'services.email_webhook.user_id' => $this->user->id,
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => '12345',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'email_message_id' => 'gmail-msg-1',
            'subject' => 'Notifikasi Transaksi',
            'sender' => 'bri_notification@example.co.id',
            'type' => 'expense',
            'amount' => 25000,
            'transaction_date' => '2026-11-03 14:22:10',
            'description' => 'QRIS MERCHANT WARTEG XYZ',
            'merchant' => null,
            'reference_number' => '2026110312345678',
        ], $overrides);
    }

    public function test_webhook_rejects_request_without_a_valid_secret(): void
    {
        $response = $this->postJson(route('webhooks.email-transactions'), $this->payload(), [
            'X-Webhook-Secret' => 'salah',
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_webhook_rejects_request_without_any_secret_header(): void
    {
        $response = $this->postJson(route('webhooks.email-transactions'), $this->payload());

        $response->assertStatus(401);
    }

    public function test_webhook_creates_a_pending_transaction_and_email_log(): void
    {
        $response = $this->postJson(route('webhooks.email-transactions'), $this->payload(), [
            'X-Webhook-Secret' => 'webhook-secret',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'source' => 'email',
            'status' => 'pending',
            'description' => 'QRIS MERCHANT WARTEG XYZ',
            'reference_number' => '2026110312345678',
        ]);

        $this->assertDatabaseHas('email_logs', [
            'email_message_id' => 'gmail-msg-1',
            'status' => 'processed',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['reply_markup'] ?? '', 'approve:');
        });
    }

    public function test_webhook_is_idempotent_for_the_same_email_message_id(): void
    {
        $this->postJson(route('webhooks.email-transactions'), $this->payload(), [
            'X-Webhook-Secret' => 'webhook-secret',
        ])->assertStatus(201);

        $response = $this->postJson(route('webhooks.email-transactions'), $this->payload(), [
            'X-Webhook-Secret' => 'webhook-secret',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(1, EmailLog::where('email_message_id', 'gmail-msg-1')->count());
    }

    public function test_webhook_validates_required_fields(): void
    {
        $response = $this->postJson(route('webhooks.email-transactions'), [
            'email_message_id' => 'gmail-msg-2',
        ], [
            'X-Webhook-Secret' => 'webhook-secret',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type', 'amount', 'transaction_date', 'description']);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_webhook_rejects_an_invalid_type_value(): void
    {
        $response = $this->postJson(
            route('webhooks.email-transactions'),
            $this->payload(['type' => 'debit']),
            ['X-Webhook-Secret' => 'webhook-secret']
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }
}
