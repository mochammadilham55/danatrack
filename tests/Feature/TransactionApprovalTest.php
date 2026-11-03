<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_approve_a_transaction(): void
    {
        $transaction = Transaction::factory()->pending()->create();

        $response = $this->post(route('transactions.approve', $transaction));

        $response->assertRedirect(route('login'));
    }

    public function test_owner_can_approve_a_pending_transaction(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->pending()->create();

        $response = $this->actingAs($user)->post(route('transactions.approve', $transaction));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success');

        $transaction->refresh();
        $this->assertSame('approved', $transaction->status);
        $this->assertSame($user->id, $transaction->approved_by);
        $this->assertNotNull($transaction->approved_at);
    }

    public function test_user_cannot_approve_another_users_transaction(): void
    {
        $user = User::factory()->create();
        $otherTransaction = Transaction::factory()->for(User::factory())->pending()->create();

        $response = $this->actingAs($user)->post(route('transactions.approve', $otherTransaction));

        $response->assertForbidden();
        $this->assertSame('pending', $otherTransaction->refresh()->status);
    }

    public function test_approving_an_already_approved_transaction_is_rejected_with_an_error(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now()->subDay(),
        ]);
        $originalApprovedAt = $transaction->approved_at;

        $response = $this->actingAs($user)->post(route('transactions.approve', $transaction));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('error');

        $transaction->refresh();
        $this->assertTrue($transaction->approved_at->equalTo($originalApprovedAt));
    }

    public function test_pending_transactions_show_a_setujui_button_on_the_index_page(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->pending()->create();

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('Menunggu');
        $response->assertSee(route('transactions.approve', $transaction), escape: false);
    }

    public function test_index_shows_a_banner_with_the_number_of_pending_transactions(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->pending()->count(2)->create();

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('2 transaksi yang menunggu persetujuan');
    }

    public function test_rejecting_a_pending_transaction_deletes_it(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->pending()->create();

        $response = $this->actingAs($user)->delete(route('transactions.destroy', $transaction));

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_pending_transactions_are_excluded_from_dashboard_and_report_totals(): void
    {
        $user = User::factory()->create();

        Transaction::factory()->for($user)->income()->pending()->create([
            'amount' => 9999999,
            'transaction_date' => now(),
        ]);

        $dashboard = $this->actingAs($user)->get(route('dashboard'));
        $report = $this->actingAs($user)->get(route('reports.index'));

        $dashboard->assertOk()->assertDontSee('9.999.999', escape: false);
        $report->assertOk()->assertDontSee('9.999.999', escape: false);
    }
}
