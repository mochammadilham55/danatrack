<?php

namespace Database\Factories;

use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelegramUser>
 */
class TelegramUserFactory extends Factory
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
            'telegram_id' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            'username' => fake()->userName(),
            'first_name' => fake()->firstName(),
        ];
    }
}
