<?php

namespace Database\Factories;

use App\Domain\Asset\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Asset\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Asset::class;
    
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(Asset::getTypes());
        
        return [
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'name' => $this->faker->company() . ' ' . $this->getTypeLabel($type),
            'type' => $type,
            'precision' => $this->getPrecisionForType($type),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'metadata' => $this->getMetadataForType($type),
        ];
    }
    
    /**
     * Get type label
     */
    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            Asset::TYPE_FIAT => 'Currency',
            Asset::TYPE_CRYPTO => 'Cryptocurrency',
            Asset::TYPE_COMMODITY => 'Commodity',
            Asset::TYPE_CUSTOM => 'Asset',
        };
    }
    
    /**
     * Get appropriate precision for asset type
     */
    private function getPrecisionForType(string $type): int
    {
        return match ($type) {
            Asset::TYPE_FIAT => $this->faker->numberBetween(0, 2),
            Asset::TYPE_CRYPTO => $this->faker->numberBetween(6, 18),
            Asset::TYPE_COMMODITY => $this->faker->numberBetween(2, 4),
            Asset::TYPE_CUSTOM => $this->faker->numberBetween(0, 8),
        };
    }
    
    /**
     * Get metadata for asset type
     */
    private function getMetadataForType(string $type): array
    {
        $metadata = ['symbol' => $this->faker->currencyCode()];
        
        return match ($type) {
            Asset::TYPE_FIAT => array_merge($metadata, [
                'iso_code' => $this->faker->currencyCode(),
                'country' => $this->faker->country(),
            ]),
            Asset::TYPE_CRYPTO => array_merge($metadata, [
                'network' => $this->faker->randomElement(['ethereum', 'bitcoin', 'solana', 'polygon']),
                'contract_address' => $this->faker->optional()->sha256(),
            ]),
            Asset::TYPE_COMMODITY => array_merge($metadata, [
                'unit' => $this->faker->randomElement(['troy_ounce', 'kilogram', 'barrel', 'bushel']),
                'exchange' => $this->faker->randomElement(['COMEX', 'LME', 'NYMEX', 'ICE']),
            ]),
            Asset::TYPE_CUSTOM => $metadata,
        };
    }
    
    /**
     * Indicate that the asset is a fiat currency.
     */
    public function fiat(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Asset::TYPE_FIAT,
            'precision' => 2,
            'metadata' => [
                'symbol' => $this->faker->currencyCode(),
                'iso_code' => $this->faker->currencyCode(),
            ],
        ]);
    }
    
    /**
     * Indicate that the asset is a cryptocurrency.
     */
    public function crypto(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Asset::TYPE_CRYPTO,
            'precision' => $this->faker->numberBetween(8, 18),
            'metadata' => [
                'symbol' => '₿',
                'network' => $this->faker->randomElement(['ethereum', 'bitcoin', 'solana']),
            ],
        ]);
    }
    
    /**
     * Indicate that the asset is a commodity.
     */
    public function commodity(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Asset::TYPE_COMMODITY,
            'precision' => 3,
            'metadata' => [
                'symbol' => $this->faker->randomElement(['Au', 'Ag', 'Pt', 'Cu']),
                'unit' => 'troy_ounce',
            ],
        ]);
    }
    
    /**
     * Indicate that the asset is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
    
    /**
     * Indicate that the asset is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}