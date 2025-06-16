<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\TransactionReadModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionReadModel>
 */
class TransactionReadModelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = TransactionReadModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(TransactionReadModel::getTypes());
        $status = $this->faker->randomElement(TransactionReadModel::getStatuses());
        
        return [
            'uuid' => $this->faker->uuid(),
            'account_uuid' => Account::factory(),
            'type' => $type,
            'amount' => $this->faker->numberBetween(100, 100000), // 1.00 to 1000.00
            'asset_code' => 'USD',
            'exchange_rate' => null,
            'reference_currency' => null,
            'reference_amount' => null,
            'description' => $this->generateDescription($type),
            'related_transaction_uuid' => null,
            'related_account_uuid' => null,
            'initiated_by' => User::factory(),
            'status' => $status,
            'metadata' => [],
            'hash' => hash('sha3-512', Str::random(64)),
            'processed_at' => $status === TransactionReadModel::STATUS_COMPLETED 
                ? $this->faker->dateTimeBetween('-30 days', 'now')
                : null,
        ];
    }

    /**
     * Generate description based on transaction type
     */
    private function generateDescription(string $type): string
    {
        return match ($type) {
            TransactionReadModel::TYPE_DEPOSIT => $this->faker->randomElement([
                'ATM Deposit',
                'Bank Transfer Deposit',
                'Check Deposit',
                'Wire Transfer Deposit',
                'Mobile Deposit',
            ]),
            TransactionReadModel::TYPE_WITHDRAWAL => $this->faker->randomElement([
                'ATM Withdrawal',
                'Bank Transfer Withdrawal',
                'Cash Withdrawal',
                'Wire Transfer Withdrawal',
            ]),
            TransactionReadModel::TYPE_TRANSFER_IN => 'Transfer from ' . $this->faker->name(),
            TransactionReadModel::TYPE_TRANSFER_OUT => 'Transfer to ' . $this->faker->name(),
            default => 'Transaction',
        };
    }

    /**
     * Indicate that the transaction is a deposit
     */
    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionReadModel::TYPE_DEPOSIT,
            'description' => $this->generateDescription(TransactionReadModel::TYPE_DEPOSIT),
        ]);
    }

    /**
     * Indicate that the transaction is a withdrawal
     */
    public function withdrawal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionReadModel::TYPE_WITHDRAWAL,
            'description' => $this->generateDescription(TransactionReadModel::TYPE_WITHDRAWAL),
        ]);
    }

    /**
     * Indicate that the transaction is a transfer in
     */
    public function transferIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionReadModel::TYPE_TRANSFER_IN,
            'description' => $this->generateDescription(TransactionReadModel::TYPE_TRANSFER_IN),
        ]);
    }

    /**
     * Indicate that the transaction is a transfer out
     */
    public function transferOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionReadModel::TYPE_TRANSFER_OUT,
            'description' => $this->generateDescription(TransactionReadModel::TYPE_TRANSFER_OUT),
        ]);
    }

    /**
     * Indicate that the transaction is completed
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionReadModel::STATUS_COMPLETED,
            'processed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the transaction is pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionReadModel::STATUS_PENDING,
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction failed
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionReadModel::STATUS_FAILED,
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'failure_reason' => $this->faker->sentence(),
            ]),
        ]);
    }

    /**
     * Indicate that the transaction was reversed
     */
    public function reversed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TransactionReadModel::STATUS_REVERSED,
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'reversal_reason' => $this->faker->sentence(),
                'reversed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            ]),
        ]);
    }

    /**
     * Create a transfer pair (out and in transactions)
     */
    public function transferPair(Account $fromAccount, Account $toAccount): array
    {
        $transferUuid = $this->faker->uuid();
        $amount = $this->faker->numberBetween(100, 50000);
        $initiator = User::factory()->create();
        $processedAt = $this->faker->dateTimeBetween('-30 days', 'now');
        
        $outTransaction = $this->create([
            'account_uuid' => $fromAccount->uuid,
            'type' => TransactionReadModel::TYPE_TRANSFER_OUT,
            'amount' => $amount,
            'description' => 'Transfer to ' . $toAccount->name,
            'initiated_by' => $initiator->uuid,
            'status' => TransactionReadModel::STATUS_COMPLETED,
            'metadata' => [
                'transfer_uuid' => $transferUuid,
                'to_account' => $toAccount->uuid,
            ],
            'processed_at' => $processedAt,
        ]);
        
        $inTransaction = $this->create([
            'account_uuid' => $toAccount->uuid,
            'type' => TransactionReadModel::TYPE_TRANSFER_IN,
            'amount' => $amount,
            'description' => 'Transfer from ' . $fromAccount->name,
            'related_transaction_uuid' => $outTransaction->uuid,
            'initiated_by' => $initiator->uuid,
            'status' => TransactionReadModel::STATUS_COMPLETED,
            'metadata' => [
                'transfer_uuid' => $transferUuid,
                'from_account' => $fromAccount->uuid,
            ],
            'processed_at' => $processedAt,
        ]);
        
        // Update outgoing transaction with related transaction UUID
        $outTransaction->update(['related_transaction_uuid' => $inTransaction->uuid]);
        
        return [$outTransaction->fresh(), $inTransaction];
    }

    /**
     * Create a multi-asset transaction
     */
    public function multiAsset(string $assetCode): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_code' => $assetCode,
        ]);
    }

    /**
     * Create a cross-asset transaction with exchange rate
     */
    public function crossAsset(string $fromAsset, string $toAsset, float $exchangeRate): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_code' => $fromAsset,
            'exchange_rate' => $exchangeRate,
            'reference_currency' => $toAsset,
            'reference_amount' => (int) round($attributes['amount'] * $exchangeRate),
        ]);
    }
}