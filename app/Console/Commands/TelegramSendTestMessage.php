<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSendTestMessage extends Command
{
    protected $signature = 'telegram:test';

    protected $description = 'Mengirim pesan uji ke Telegram untuk memastikan TELEGRAM_BOT_TOKEN dan TELEGRAM_CHAT_ID sudah benar';

    public function handle(TelegramService $telegram): int
    {
        $this->info('Mengirim pesan uji ke Telegram...');

        $terkirim = $telegram->sendMessage('✅ DanaTrack: pesan uji berhasil terkirim.');

        if ($terkirim) {
            $this->info('Berhasil. Cek chat Telegram Anda.');

            return self::SUCCESS;
        }

        $this->error('Gagal mengirim pesan uji. Periksa storage/logs/laravel.log untuk detail, dan pastikan TELEGRAM_BOT_TOKEN & TELEGRAM_CHAT_ID di .env sudah benar.');

        return self::FAILURE;
    }
}
