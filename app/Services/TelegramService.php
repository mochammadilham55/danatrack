<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramService
{
    /**
     * Kirim notifikasi Telegram untuk transaksi manual yang baru dibuat.
     *
     * Transaksi dari website selalu berstatus 'approved' saat dibuat,
     * jadi notifikasi ini murni informatif (tanpa tombol Setujui/Tolak).
     */
    public function sendTransactionCreated(Transaction $transaction): bool
    {
        return $this->sendMessage($this->formatTransactionMessage($transaction));
    }

    /**
     * Kirim pesan teks bebas ke chat Telegram yang dikonfigurasi.
     *
     * Mengembalikan false (tanpa melempar exception) jika konfigurasi
     * belum lengkap atau pengiriman gagal, supaya pemanggil (misalnya
     * TransactionController::store()) tidak perlu membatalkan transaksi
     * hanya karena Telegram tidak tersedia.
     */
    public function sendMessage(string $text): bool
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $botToken || ! $chatId) {
            Log::warning('Notifikasi Telegram dilewati: TELEGRAM_BOT_TOKEN atau TELEGRAM_CHAT_ID belum dikonfigurasi di .env.');

            return false;
        }

        try {
            $response = Http::timeout(5)
                ->asForm()
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                ]);

            if ($response->failed()) {
                Log::warning('Notifikasi Telegram gagal terkirim.', [
                    'status' => $response->status(),
                    'description' => $response->json('description'),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            // Sengaja tidak mencatat $e->getMessage(): untuk error koneksi,
            // pesan exception dari Guzzle/HTTP client bisa memuat URL
            // request lengkap (termasuk bot token).
            Log::warning('Notifikasi Telegram gagal terkirim karena exception: '.class_basename($e));

            return false;
        }
    }

    /**
     * Kirim transaksi pending (sumber email/kanal otomatis lain) sebagai
     * pesan dengan tombol inline "Setujui"/"Tolak". callback_data berisi
     * "approve:{id}" / "reject:{id}" yang ditangani TelegramWebhookController.
     */
    public function sendApprovalRequest(Transaction $transaction): bool
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $botToken || ! $chatId) {
            Log::warning('Permintaan approval Telegram dilewati: bot_token/chat_id belum dikonfigurasi.');

            return false;
        }

        $keyboard = [
            'inline_keyboard' => [[
                ['text' => '✅ Setujui', 'callback_data' => "approve:{$transaction->id}"],
                ['text' => '❌ Tolak', 'callback_data' => "reject:{$transaction->id}"],
            ]],
        ];

        try {
            $response = Http::timeout(5)
                ->asForm()
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $this->formatTransactionMessage($transaction),
                    'reply_markup' => json_encode($keyboard),
                ]);

            if ($response->failed()) {
                Log::warning('Permintaan approval Telegram gagal terkirim.', [
                    'status' => $response->status(),
                    'description' => $response->json('description'),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('Permintaan approval Telegram gagal terkirim karena exception: '.class_basename($e));

            return false;
        }
    }

    /**
     * Balas callback_query supaya loading spinner tombol Telegram hilang.
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text): bool
    {
        $botToken = config('services.telegram.bot_token');

        if (! $botToken) {
            return false;
        }

        try {
            return Http::timeout(5)
                ->asForm()
                ->post("https://api.telegram.org/bot{$botToken}/answerCallbackQuery", [
                    'callback_query_id' => $callbackQueryId,
                    'text' => $text,
                ])
                ->successful();
        } catch (Throwable $e) {
            Log::warning('answerCallbackQuery Telegram gagal karena exception: '.class_basename($e));

            return false;
        }
    }

    /**
     * Hapus tombol inline pada pesan yang sudah diproses, supaya klik
     * berulang pada tombol lama tidak memicu apa-apa di sisi Telegram.
     */
    public function removeInlineKeyboard(string $chatId, int $messageId): bool
    {
        $botToken = config('services.telegram.bot_token');

        if (! $botToken) {
            return false;
        }

        try {
            return Http::timeout(5)
                ->asForm()
                ->post("https://api.telegram.org/bot{$botToken}/editMessageReplyMarkup", [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode(['inline_keyboard' => []]),
                ])
                ->successful();
        } catch (Throwable $e) {
            Log::warning('editMessageReplyMarkup Telegram gagal karena exception: '.class_basename($e));

            return false;
        }
    }

    /**
     * Format pesan notifikasi transaksi sesuai contoh di CLAUDE.md.
     */
    public function formatTransactionMessage(Transaction $transaction): string
    {
        $jenis = $transaction->type === 'expense' ? 'Pengeluaran' : 'Pemasukan';
        $status = $transaction->status === 'approved' ? 'Disetujui' : 'Menunggu persetujuan';
        $kategori = $transaction->category?->name ?? 'Tanpa kategori';
        $jumlah = 'Rp'.number_format((float) $transaction->amount, 0, ',', '.');
        $tanggal = $transaction->transaction_date->translatedFormat('d F Y');

        return implode("\n", [
            'DanaTrack — Transaksi Baru',
            '',
            "Jenis: {$jenis}",
            "Jumlah: {$jumlah}",
            "Kategori: {$kategori}",
            "Tanggal: {$tanggal}",
            "Deskripsi: {$transaction->description}",
            "Status: {$status}",
        ]);
    }
}
