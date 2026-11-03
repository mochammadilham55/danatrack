<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'transaction_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'type' => fake()->randomElement(['expense', 'income']),
            'amount' => fake()->randomFloat(2, 10000, 5000000),
            'description' => fake()->sentence(3),
            'merchant' => fake()->company(),
            'source' => 'website',
            'status' => 'approved',
            'reference_number' => null,
            'approved_by' => null,
            'approved_at' => now(),
        ];
    }

    /**
     * Indicate that the transaction is an expense.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
        ]);
    }

    /**
     * Indicate that the transaction is income.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
        ]);
    }

    /**
     * Indicate that the transaction is still awaiting approval
     * (e.g. incoming from the email/Telegram channels).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }
}
