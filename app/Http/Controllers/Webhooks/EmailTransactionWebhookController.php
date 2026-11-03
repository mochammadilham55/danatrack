<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\Transaction;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Endpoint publik yang dipanggil Google Apps Script setelah membaca
 * email notifikasi transaksi dari bank. Diautentikasi dengan shared
 * secret (header X-Webhook-Secret), BUKAN sesi/login -- rute ini
 * dikecualikan dari middleware 'auth' dan verifikasi CSRF
 * (lihat bootstrap/app.php).
 *
 * Idempotency: email_message_id unik di tabel email_logs. Pemanggilan
 * berulang untuk email yang sama (misal GAS retry) tidak akan membuat
 * transaksi duplikat.
 */
class EmailTransactionWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramService $telegramService)
    {
        $secret = config('services.email_webhook.secret');

        if (! $secret || ! hash_equals($secret, (string) $request->header('X-Webhook-Secret'))) {
            abort(401, 'Webhook secret tidak valid.');
        }

        $userId = config('services.email_webhook.user_id');

        if (! $userId) {
            Log::error('Email webhook dipanggil tapi EMAIL_WEBHOOK_USER_ID belum dikonfigurasi di .env.');
            abort(500, 'Webhook belum dikonfigurasi dengan benar.');
        }

        // Jangan percaya isi email sebagai data tepercaya -- validasi
        // ketat semua field yang akan masuk ke tabel transactions.
        $validated = $request->validate([
            'email_message_id' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'sender' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['expense', 'income'])],
            'amount' => ['required', 'numeric', 'min:1', 'max:9999999999999'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'merchant' => ['nullable', 'string', 'max:150'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $emailLog = EmailLog::where('email_message_id', $validated['email_message_id'])->first();

        if ($emailLog && $emailLog->status === 'processed') {
            return response()->json([
                'message' => 'Email ini sudah diproses sebelumnya.',
                'transaction_id' => $emailLog->transaction_id,
            ], 200);
        }

        if (! $emailLog) {
            $emailLog = EmailLog::create([
                'user_id' => $userId,
                'email_message_id' => $validated['email_message_id'],
                'subject' => $validated['subject'] ?? null,
                'sender' => $validated['sender'] ?? null,
                'status' => 'received',
            ]);
        }

        try {
            $transaction = DB::transaction(function () use ($validated, $userId, $emailLog) {
                $transaction = Transaction::create([
                    'user_id' => $userId,
                    'category_id' => null,
                    'transaction_date' => $validated['transaction_date'],
                    'type' => $validated['type'],
                    'amount' => $validated['amount'],
                    'description' => $validated['description'],
                    'merchant' => $validated['merchant'] ?? null,
                    'source' => 'email',
                    'status' => 'pending',
                    'reference_number' => $validated['reference_number'] ?? null,
                ]);

                $emailLog->update([
                    'status' => 'processed',
                    'transaction_id' => $transaction->id,
                    'processed_at' => now(),
                ]);

                return $transaction;
            });
        } catch (Throwable $e) {
            $emailLog->update([
                'status' => 'failed',
                'error_message' => class_basename($e),
            ]);

            Log::error('Gagal memproses transaksi dari email webhook.', [
                'email_log_id' => $emailLog->id,
            ]);

            return response()->json(['message' => 'Gagal memproses transaksi.'], 500);
        }

        // Transaksi pending tetap tersimpan meskipun pengiriman
        // permintaan approval Telegram gagal -- lihat TelegramService.
        $telegramService->sendApprovalRequest($transaction);

        return response()->json([
            'message' => 'Transaksi pending berhasil dibuat, menunggu persetujuan via Telegram.',
            'transaction_id' => $transaction->id,
        ], 201);
    }
}
