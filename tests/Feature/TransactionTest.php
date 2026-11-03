<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_transactions(): void
    {
        $response = $this->get(route('transactions.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_transaction_index_only_lists_own_transactions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Transaction::factory()->for($user)->create(['description' => 'Punya Saya']);
        Transaction::factory()->for($otherUser)->create(['description' => 'Punya Orang Lain']);

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('Punya Saya');
        $response->assertDontSee('Punya Orang Lain');
    }

    public function test_transaction_can_be_created_as_approved_website_source(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->expense()->create();

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'transaction_date' => now()->format('Y-m-d'),
            'type' => 'expense',
            'amount' => 50000,
            'category_id' => $category->id,
            'description' => 'Makan siang',
            'merchant' => 'Warteg',
        ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'description' => 'Makan siang',
            'source' => 'website',
            'status' => 'approved',
            'approved_by' => $user->id,
        ]);
    }

    public function test_transaction_can_be_created_without_a_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'transaction_date' => now()->format('Y-m-d'),
            'type' => 'income',
            'amount' => 100000,
            'description' => 'Hadiah',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('transactions', [
            'description' => 'Hadiah',
            'category_id' => null,
        ]);
    }

    public function test_transaction_rejects_a_category_belonging_to_another_user(): void
    {
        $user = User::factory()->create();
        $otherCategory = Category::factory()->for(User::factory())->expense()->create();

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'transaction_date' => now()->format('Y-m-d'),
            'type' => 'expense',
            'amount' => 10000,
            'category_id' => $otherCategory->id,
            'description' => 'Belanja',
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertDatabaseMissing('transactions', ['description' => 'Belanja']);
    }

    public function test_transaction_rejects_a_category_with_mismatched_type(): void
    {
        $user = User::factory()->create();
        $incomeCategory = Category::factory()->for($user)->income()->create();

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'transaction_date' => now()->format('Y-m-d'),
            'type' => 'expense',
            'amount' => 10000,
            'category_id' => $incomeCategory->id,
            'description' => 'Belanja',
        ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertDatabaseMissing('transactions', ['description' => 'Belanja']);
    }

    public function test_transaction_amount_must_be_at_least_one(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'transaction_date' => now()->format('Y-m-d'),
            'type' => 'expense',
            'amount' => 0,
            'description' => 'Tidak valid',
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_transaction_can_be_updated_by_owner(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create(['description' => 'Lama']);

        $response = $this->actingAs($user)->put(route('transactions.update', $transaction), [
            'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
            'type' => $transaction->type,
            'amount' => 75000,
            'description' => 'Baru',
        ]);

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'description' => 'Baru',
            'amount' => 75000,
        ]);
    }

    public function test_transaction_can_be_deleted_by_owner(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('transactions.destroy', $transaction));

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_user_cannot_edit_another_users_transaction(): void
    {
        $user = User::factory()->create();
        $otherTransaction = Transaction::factory()->for(User::factory())->create();

        $response = $this->actingAs($user)->get(route('transactions.edit', $otherTransaction));

        $response->assertForbidden();
    }

    public function test_user_cannot_update_another_users_transaction(): void
    {
        $user = User::factory()->create();
        $otherTransaction = Transaction::factory()->for(User::factory())->create(['description' => 'Tetap']);

        $response = $this->actingAs($user)->put(route('transactions.update', $otherTransaction), [
            'transaction_date' => now()->format('Y-m-d'),
            'type' => $otherTransaction->type,
            'amount' => 1000,
            'description' => 'Diubah',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('transactions', ['id' => $otherTransaction->id, 'description' => 'Tetap']);
    }

    public function test_user_cannot_delete_another_users_transaction(): void
    {
        $user = User::factory()->create();
        $otherTransaction = Transaction::factory()->for(User::factory())->create();

        $response = $this->actingAs($user)->delete(route('transactions.destroy', $otherTransaction));

        $response->assertForbidden();
        $this->assertDatabaseHas('transactions', ['id' => $otherTransaction->id]);
    }
}
