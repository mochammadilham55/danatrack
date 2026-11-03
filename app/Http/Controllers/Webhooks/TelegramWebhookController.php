<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Services\TelegramService;
use App\Services\TransactionApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint publik yang didaftarkan ke Telegram lewat setWebhook(),
 * menerima Update setiap kali pengguna menekan tombol inline
 * Setujui/Tolak pada notifikasi transaksi pending.
 *
 * Diautentikasi dengan secret_token bawaan Telegram (header
 * X-Telegram-Bot-Api-Secret-Token), bukan sesi/login. Rute ini
 * dikecualikan dari middleware 'auth' dan verifikasi CSRF
 * (lihat bootstrap/app.php).
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TelegramService $telegramService,
        TransactionApprovalService $approvalService
    ) {
        $secret = config('services.telegram.webhook_secret');

        if ($secret && ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            abort(401);
        }

        $callback = $request->input('callback_query');

        // Update jenis lain (pesan teks biasa, dll) sengaja diabaikan --
        // bot ini hanya bereaksi pada tombol inline.
        if (! $callback) {
            return response()->json(['ok' => true]);
        }

        $callbackId = $callback['id'] ?? null;
        $telegramId = (string) ($callback['from']['id'] ?? '');
        $chatId = $callback['message']['chat']['id'] ?? null;
        $messageId = $callback['message']['message_id'] ?? null;

        [$aksi, $transactionId] = array_pad(explode(':', $callback['data'] ?? '', 2), 2, null);

        $telegramUser = TelegramUser::where('telegram_id', $telegramId)->first();
        $transaction = $transactionId ? Transaction::find($transactionId) : null;

        $balasan = 'Permintaan tidak valid.';
        $sudahDiproses = false;

        if (! $telegramUser || ! $transaction || $transaction->user_id !== $telegramUser->user_id) {
            Log::warning('Callback Telegram ditolak: telegram_id/transaksi tidak cocok.', [
                'telegram_id' => $telegramId,
                'transaction_id' => $transactionId,
            ]);
        } elseif ($transaction->status !== 'pending') {
            $balasan = 'Transaksi ini sudah diproses sebelumnya.';
            $sudahDiproses = true;
        } elseif ($aksi === 'approve') {
            $approvalService->approve($transaction, $telegramUser->user);
            $balasan = 'Transaksi disetujui. ✅';
            $sudahDiproses = true;
        } elseif ($aksi === 'reject') {
            $approvalService->reject($transaction);
            $balasan = 'Transaksi ditolak dan dihapus. ❌';
            $sudahDiproses = true;
        }

        if ($callbackId) {
            $telegramService->answerCallbackQuery($callbackId, $balasan);
        }

        if ($sudahDiproses && $chatId && $messageId) {
            $telegramService->removeInlineKeyboard((string) $chatId, (int) $messageId);
        }

        return response()->json(['ok' => true]);
    }
}
