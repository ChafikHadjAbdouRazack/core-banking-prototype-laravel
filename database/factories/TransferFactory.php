<?php

namespace Database\Factories;

use App\Models\Transfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transfer>
 */
class TransferFactory extends Factory
{
    protected $model = Transfer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'aggregate_uuid' => $this->faker->uuid(),
            'aggregate_version' => $this->faker->numberBetween(1, 100),
            'event_version' => 1,
            'event_class' => 'App\\Domain\\Account\\Events\\MoneyTransferred',
            'event_properties' => json_encode([
                'fromAccount' => ['uuid' => $this->faker->uuid()],
                'toAccount' => ['uuid' => $this->faker->uuid()],
                'money' => ['amount' => $this->faker->numberBetween(100, 100000)],
                'hash' => ['hash' => hash('sha3-512', $this->faker->text())]
            ]),
            'meta_data' => json_encode([
                'user_uuid' => $this->faker->uuid(),
                'ip_address' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
            ]),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Create a transfer with specific amount
     */
    public function withAmount(int $amount): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            $eventProperties = json_decode($attributes['event_properties'], true);
            $eventProperties['money']['amount'] = $amount;
            
            return [
                'event_properties' => json_encode($eventProperties),
            ];
        });
    }

    /**
     * Create a transfer between specific accounts
     */
    public function betweenAccounts(string $fromUuid, string $toUuid): static
    {
        return $this->state(function (array $attributes) use ($fromUuid, $toUuid) {
            $eventProperties = json_decode($attributes['event_properties'], true);
            $eventProperties['fromAccount']['uuid'] = $fromUuid;
            $eventProperties['toAccount']['uuid'] = $toUuid;
            
            return [
                'aggregate_uuid' => $fromUuid, // Transfer aggregate is associated with source account
                'event_properties' => json_encode($eventProperties),
            ];
        });
    }

    /**
     * Create a multi-asset transfer
     */
    public function multiAsset(string $fromAsset, string $toAsset, float $exchangeRate): static
    {
        return $this->state(function (array $attributes) use ($fromAsset, $toAsset, $exchangeRate) {
            $eventProperties = json_decode($attributes['event_properties'], true);
            $fromAmount = $eventProperties['money']['amount'];
            
            return [
                'event_class' => 'App\\Domain\\Account\\Events\\AssetTransferred',
                'event_properties' => json_encode([
                    'fromAccount' => $eventProperties['fromAccount'],
                    'toAccount' => $eventProperties['toAccount'],
                    'fromAsset' => $fromAsset,
                    'fromAmount' => $fromAmount,
                    'toAsset' => $toAsset,
                    'toAmount' => (int) round($fromAmount * $exchangeRate),
                    'exchangeRate' => $exchangeRate,
                    'hash' => $eventProperties['hash'],
                ]),
            ];
        });
    }

    /**
     * Create a large transfer (over $1000)
     */
    public function large(): static
    {
        return $this->withAmount($this->faker->numberBetween(100000, 1000000));
    }

    /**
     * Create a small transfer (under $100)
     */
    public function small(): static
    {
        return $this->withAmount($this->faker->numberBetween(100, 10000));
    }
}