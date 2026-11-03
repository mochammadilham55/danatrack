<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_message_returns_false_when_not_configured(): void
    {
        config([
            'services.telegram.bot_token' => null,
            'services.telegram.chat_id' => null,
        ]);

        Http::fake();

        $result = (new TelegramService)->sendMessage('halo');

        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    public function test_send_message_returns_true_on_success(): void
    {
        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => '12345',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $result = (new TelegramService)->sendMessage('halo');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
                && $request['chat_id'] === '12345'
                && $request['text'] === 'halo';
        });
    }

    public function test_send_message_returns_false_when_telegram_responds_with_an_error(): void
    {
        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => '12345',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request'], 400),
        ]);

        $result = (new TelegramService)->sendMessage('halo');

        $this->assertFalse($result);
    }

    public function test_send_message_returns_false_on_connection_exception_without_leaking_the_token(): void
    {
        config([
            'services.telegram.bot_token' => 'super-secret-token',
            'services.telegram.chat_id' => '12345',
        ]);

        Http::fake(function () {
            throw new ConnectionException('Connection timed out for https://api.telegram.org/botsuper-secret-token/sendMessage');
        });

        $result = (new TelegramService)->sendMessage('halo');

        $this->assertFalse($result);
    }

    public function test_format_transaction_message_contains_expected_fields(): void
    {
        $transaction = Transaction::factory()->make([
            'type' => 'expense',
            'status' => 'approved',
            'amount' => 25000,
            'description' => 'Makan siang',
            'transaction_date' => now()->setDate(2026, 11, 3),
            'category_id' => null,
        ]);

        $message = (new TelegramService)->formatTransactionMessage($transaction);

        $this->assertStringContainsString('DanaTrack — Transaksi Baru', $message);
        $this->assertStringContainsString('Jenis: Pengeluaran', $message);
        $this->assertStringContainsString('Rp25.000', $message);
        $this->assertStringContainsString('Deskripsi: Makan siang', $message);
        $this->assertStringContainsString('Status: Disetujui', $message);
        $this->assertStringContainsString('Tanpa kategori', $message);
    }
}
