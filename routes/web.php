<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Webhooks\EmailTransactionWebhookController;
use App\Http\Controllers\Webhooks\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Webhook publik (machine-to-machine): otentikasi pakai shared secret
// di header, BUKAN sesi/login. Dikecualikan dari CSRF di bootstrap/app.php.
Route::post('/webhooks/email-transactions', EmailTransactionWebhookController::class)
    ->name('webhooks.email-transactions');

Route::post('/webhooks/telegram', TelegramWebhookController::class)
    ->name('webhooks.telegram');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('transactions', TransactionController::class);
    Route::post('/transactions/{transaction}/approve', [TransactionController::class, 'approve'])
        ->name('transactions.approve');
    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index');
    Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])
        ->name('reports.exportExcel');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

require __DIR__.'/auth.php';
