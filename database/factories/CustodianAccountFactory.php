<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\CustodianAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustodianAccountFactory extends Factory
{
    protected $model = CustodianAccount::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'account_uuid' => Account::factory(),
            'custodian_name' => $this->faker->randomElement(['mock', 'paysera', 'santander']),
            'custodian_account_id' => $this->faker->uuid(),
            'custodian_account_name' => $this->faker->company() . ' Account',
            'status' => $this->faker->randomElement(['active', 'suspended', 'closed', 'pending']),
            'is_primary' => false,
            'metadata' => [
                'iban' => $this->faker->iban(),
                'bic' => strtoupper($this->faker->lexify('????????')),
                'created_via' => 'factory',
            ],
        ];
    }

    /**
     * Indicate that the custodian account is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the custodian account is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the custodian account is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }

    /**
     * Indicate that the custodian account is primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    /**
     * Indicate that the custodian account is for Paysera.
     */
    public function paysera(): static
    {
        return $this->state(fn (array $attributes) => [
            'custodian_name' => 'paysera',
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'currency' => 'EUR',
                'country' => 'LT',
            ]),
        ]);
    }

    /**
     * Indicate that the custodian account is for Mock Bank.
     */
    public function mock(): static
    {
        return $this->state(fn (array $attributes) => [
            'custodian_name' => 'mock',
            'custodian_account_id' => 'mock-account-' . $this->faker->numberBetween(1, 99),
        ]);
    }

    /**
     * Attach to a specific account.
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_uuid' => $account->uuid,
        ]);
    }
}