<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Webhook GAS/Telegram diautentikasi dengan shared secret di
        // header (lihat EmailTransactionWebhookController &
        // TelegramWebhookController), bukan sesi browser -- caller-nya
        // tidak punya CSRF token.
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        // Hosting (Railway/Render) menerima HTTPS di edge lalu meneruskan
        // ke container lewat HTTP biasa. Tanpa ini, Laravel mengira semua
        // request HTTP sehingga asset/Vite dibuat dengan URL http:// yang
        // diblokir browser sebagai mixed-content di halaman https://.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
