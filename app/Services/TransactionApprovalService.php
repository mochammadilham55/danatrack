<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;

/**
 * Logika approve/reject transaksi pending, dipakai bersama oleh
 * TransactionController (approval manual lewat web) dan
 * TelegramWebhookController (approval lewat tombol inline keyboard).
 */
class TransactionApprovalService
{
    public function approve(Transaction $transaction, ?User $approver = null): void
    {
        $transaction->update([
            'status' => 'approved',
            'approved_by' => $approver?->id ?? $transaction->user_id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Menolak transaksi pending. Belum ada enum status 'rejected' di
     * migration (lihat CLAUDE.md), jadi menolak = menghapus transaksi
     * pending -- konsisten dengan pola "Tolak" di transactions/index.
     */
    public function reject(Transaction $transaction): void
    {
        $transaction->delete();
    }
}
