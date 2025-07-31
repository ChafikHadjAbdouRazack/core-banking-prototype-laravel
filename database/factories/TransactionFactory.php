<?php

namespace Database\Factories;

use App\Domain\Account\Models\Account;
use App\Domain\Account\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Account\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['deposit', 'withdrawal', 'transfer_in', 'transfer_out'];
        $type = fake()->randomElement($types);

        // Generate amount based on type (deposits and transfers in are positive, withdrawals and transfers out are negative)
        $amount = match($type) {
            'deposit', 'transfer_in' => fake()->numberBetween(100, 100000), // $1 to $1000
            'withdrawal', 'transfer_out' => -fake()->numberBetween(100, 50000), // -$1 to -$500
        };

        $accountUuid = Account::factory()->create()->uuid;

        return [
            'aggregate_uuid'    => $accountUuid,
            'aggregate_version' => fake()->numberBetween(1, 100),
            'event_version'     => 1,
            'event_class'       => 'App\\Domain\\Account\\Events\\MoneyAdded',
            'event_properties'  => [
                'amount'    => $amount,
                'assetCode' => 'USD',
                'metadata'  => [],
            ],
            'meta_data' => [
                'type'        => $type,
                'reference'   => fake()->optional()->bothify('REF-####-????'),
                'description' => fake()->optional()->sentence(),
            ],
            'created_at' => now(),
        ];
    }

    /**
     * Indicate that the transaction is a deposit.
     */
    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'meta_data' => array_merge($attributes['meta_data'] ?? [], [
                'type' => 'deposit',
            ]),
            'event_properties' => array_merge($attributes['event_properties'] ?? [], [
                'amount' => fake()->numberBetween(100, 100000),
            ]),
        ]);
    }

    /**
     * Indicate that the transaction is a withdrawal.
     */
    public function withdrawal(): static
    {
        return $this->state(fn (array $attributes) => [
            'meta_data' => array_merge($attributes['meta_data'] ?? [], [
                'type' => 'withdrawal',
            ]),
            'event_properties' => array_merge($attributes['event_properties'] ?? [], [
                'amount' => -fake()->numberBetween(100, 50000),
            ]),
        ]);
    }

    /**
     * Indicate that the transaction is a transfer in.
     */
    public function transferIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'meta_data' => array_merge($attributes['meta_data'] ?? [], [
                'type' => 'transfer_in',
            ]),
            'event_properties' => array_merge($attributes['event_properties'] ?? [], [
                'amount' => fake()->numberBetween(100, 100000),
            ]),
        ]);
    }

    /**
     * Indicate that the transaction is a transfer out.
     */
    public function transferOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'meta_data' => array_merge($attributes['meta_data'] ?? [], [
                'type' => 'transfer_out',
            ]),
            'event_properties' => array_merge($attributes['event_properties'] ?? [], [
                'amount' => -fake()->numberBetween(100, 50000),
            ]),
        ]);
    }

    /**
     * Set a specific account for the transaction.
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'aggregate_uuid' => $account->uuid,
        ]);
    }
}
