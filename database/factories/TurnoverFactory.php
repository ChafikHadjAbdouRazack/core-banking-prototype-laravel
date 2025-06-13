<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Turnover;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Turnover>
 */
class TurnoverFactory extends Factory
{
    protected $model = Turnover::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_uuid' => Account::factory(),
            'date' => $this->faker->date(),
            'count' => $this->faker->numberBetween(1, 100),
            'amount' => $this->faker->randomFloat(2, -10000, 10000),
        ];
    }
}