<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_totals_for_the_current_month_by_default(): void
    {
        $user = User::factory()->create();

        Transaction::factory()->for($user)->income()->create([
            'amount' => 1000000,
            'transaction_date' => now(),
            'status' => 'approved',
        ]);

        Transaction::factory()->for($user)->expense()->create([
            'amount' => 250000,
            'transaction_date' => now(),
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('750.000', escape: false);
    }

    public function test_pending_transactions_are_excluded_from_dashboard_totals(): void
    {
        $user = User::factory()->create();

        Transaction::factory()->for($user)->income()->pending()->create([
            'amount' => 5000000,
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('5.000.000', escape: false);
    }

    /**
     * Regression test: the 6-month chart used to build its data with
     * selectRaw('YEAR(transaction_date)...MONTH(transaction_date)...'),
     * which only exists on MySQL. The test suite runs on SQLite, so this
     * would throw a QueryException before the query was made portable.
     */
    public function test_six_month_chart_query_works_on_the_test_database_driver(): void
    {
        $user = User::factory()->create();

        Transaction::factory()->for($user)->income()->create([
            'amount' => 500000,
            'transaction_date' => now()->subMonths(2),
            'status' => 'approved',
        ]);

        Transaction::factory()->for($user)->expense()->create([
            'amount' => 150000,
            'transaction_date' => now()->subMonths(4),
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }
}
