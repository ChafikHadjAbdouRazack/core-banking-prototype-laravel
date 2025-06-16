<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Governance\Enums\PollStatus;
use App\Domain\Governance\Enums\PollType;
use App\Domain\Governance\Models\Poll;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PollFactory extends Factory
{
    protected $model = Poll::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 week', '+1 week');
        $endDate = $this->faker->dateTimeBetween($startDate, '+2 weeks');

        return [
            'uuid' => (string) Str::uuid(),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(3),
            'type' => $this->faker->randomElement(PollType::cases()),
            'options' => $this->generateOptions(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $this->faker->randomElement(PollStatus::cases()),
            'required_participation' => $this->faker->optional(0.3)->numberBetween(10, 80),
            'voting_power_strategy' => $this->faker->randomElement([
                'one_user_one_vote',
                'asset_weighted_vote'
            ]),
            'execution_workflow' => $this->faker->optional(0.5)->randomElement([
                'AddAssetWorkflow',
                'UpdateConfigurationWorkflow',
                'FeatureToggleWorkflow'
            ]),
            'created_by' => User::factory()->create()->uuid,
            'metadata' => [
                'category' => $this->faker->randomElement(['governance', 'features', 'assets', 'policy']),
                'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
                'tags' => $this->faker->words(3),
            ],
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PollStatus::ACTIVE,
            'start_date' => now()->subHour(),
            'end_date' => now()->addDays(7),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PollStatus::DRAFT,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(8),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PollStatus::CLOSED,
            'start_date' => now()->subWeeks(2),
            'end_date' => now()->subWeek(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PollStatus::CANCELLED,
        ]);
    }

    public function yesNo(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PollType::YES_NO,
            'options' => [
                ['id' => 'yes', 'label' => 'Yes', 'description' => 'I support this proposal'],
                ['id' => 'no', 'label' => 'No', 'description' => 'I do not support this proposal'],
            ],
        ]);
    }

    public function singleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PollType::SINGLE_CHOICE,
            'options' => $this->generateOptions(3, 5),
        ]);
    }

    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PollType::MULTIPLE_CHOICE,
            'options' => $this->generateOptions(4, 6),
        ]);
    }

    public function assetWeighted(): static
    {
        return $this->state(fn (array $attributes) => [
            'voting_power_strategy' => 'asset_weighted_vote',
        ]);
    }

    public function oneUserOneVote(): static
    {
        return $this->state(fn (array $attributes) => [
            'voting_power_strategy' => 'one_user_one_vote',
        ]);
    }

    public function withExecutionWorkflow(string $workflow): static
    {
        return $this->state(fn (array $attributes) => [
            'execution_workflow' => $workflow,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->uuid,
        ]);
    }

    private function generateOptions(int $min = 2, int $max = 4): array
    {
        $count = $this->faker->numberBetween($min, $max);
        $options = [];

        for ($i = 0; $i < $count; $i++) {
            $options[] = [
                'id' => Str::slug($this->faker->unique()->words(2, true)),
                'label' => $this->faker->sentence(3),
                'description' => $this->faker->optional(0.7)->sentence(8),
                'metadata' => $this->faker->optional(0.3)->randomElement([
                    ['impact' => 'high'],
                    ['cost' => 'low'],
                    ['complexity' => 'medium'],
                ]),
            ];
        }

        return $options;
    }
}