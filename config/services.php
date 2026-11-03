<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        // Dicocokkan dengan header X-Telegram-Bot-Api-Secret-Token yang
        // dikirim Telegram ke webhook kita (diset lewat setWebhook).
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    // Webhook untuk menerima transaksi dari Google Apps Script (pembaca
    // email notifikasi bank). Endpoint publik, diautentikasi pakai
    // shared secret di header X-Webhook-Secret -- bukan sesi/login.
    'email_webhook' => [
        'secret' => env('EMAIL_WEBHOOK_SECRET'),
        // ID user DanaTrack pemilik transaksi dari kanal email. Aplikasi
        // ini single-user untuk saat ini, jadi dikonfigurasi manual
        // alih-alih ditebak dari isi email (lihat CLAUDE.md: jangan
        // percaya isi email sebagai data tepercaya).
        'user_id' => env('EMAIL_WEBHOOK_USER_ID'),
    ],

];
