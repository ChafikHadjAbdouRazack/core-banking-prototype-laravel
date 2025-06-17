<?php

use App\Domain\Governance\Models\Poll;
use App\Models\User;
use App\Domain\Governance\Models\Vote;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('can cast a vote on an active poll', function () {
    $poll = Poll::factory()->active()->create([
        'type' => 'single_choice',
        'options' => [
            ['id' => 'yes', 'label' => 'Yes'],
            ['id' => 'no', 'label' => 'No'],
        ],
    ]);

    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
        'selected_options' => ['yes'],
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Vote cast successfully',
        ]);

    $this->assertDatabaseHas('votes', [
        'poll_id' => $poll->id,
        'user_uuid' => $this->user->uuid,
        'selected_options' => json_encode(['yes']),
        'voting_power' => 1,
    ]);
});

it('can cast multiple votes on multiple choice poll', function () {
    $poll = Poll::factory()->active()->create([
        'type' => 'multiple_choice',
        'options' => [
            ['id' => 'option1', 'label' => 'Option 1'],
            ['id' => 'option2', 'label' => 'Option 2'],
            ['id' => 'option3', 'label' => 'Option 3'],
        ],
    ]);

    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
        'selected_options' => ['option1', 'option3'],
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Vote cast successfully',
        ]);

    $this->assertDatabaseHas('votes', [
        'poll_id' => $poll->id,
        'user_uuid' => $this->user->uuid,
        'selected_options' => json_encode(['option1', 'option3']),
    ]);
});

it('cannot vote on inactive poll', function () {
    $poll = Poll::factory()->create([
        'status' => 'draft',
        'type' => 'single_choice',
        'options' => [
            ['id' => 'yes', 'label' => 'Yes'],
            ['id' => 'no', 'label' => 'No'],
        ],
    ]);

    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
        'selected_options' => ['yes'],
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Poll is not active for voting',
        ]);

    $this->assertDatabaseMissing('votes', [
        'poll_id' => $poll->id,
        'user_uuid' => $this->user->uuid,
    ]);
});

it('cannot vote on expired poll', function () {
    $poll = Poll::factory()->create([
        'status' => 'active',
        'start_date' => now()->subDays(7),
        'end_date' => now()->subDay(),
        'type' => 'single_choice',
        'options' => [
            ['id' => 'yes', 'label' => 'Yes'],
            ['id' => 'no', 'label' => 'No'],
        ],
    ]);

    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
        'selected_options' => ['yes'],
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Poll voting period has ended',
        ]);
});

it('cannot vote twice on same poll', function () {
    $poll = Poll::factory()->active()->create([
        'type' => 'single_choice',
        'options' => [
            ['id' => 'yes', 'label' => 'Yes'],
            ['id' => 'no', 'label' => 'No'],
        ],
    ]);

    // First vote
    Vote::create([
        'poll_id' => $poll->id,
        'user_uuid' => $this->user->uuid,
        'selected_options' => ['yes'],
        'voting_power' => 1,
        'voted_at' => now(),
    ]);

    // Attempt second vote
    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
        'selected_options' => ['no'],
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'You have already voted on this poll',
        ]);
});

it('validates option exists for single choice poll', function () {
    $poll = Poll::factory()->active()->create([
        'type' => 'single_choice',
        'options' => [
            ['id' => 'yes', 'label' => 'Yes'],
            ['id' => 'no', 'label' => 'No'],
        ],
    ]);

    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
        'selected_options' => ['invalid_option'],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['selected_options']);
});

it('validates options exist for multiple choice poll', function () {
    $poll = Poll::factory()->active()->create([
        'type' => 'multiple_choice',
        'options' => [
            ['id' => 'option1', 'label' => 'Option 1'],
            ['id' => 'option2', 'label' => 'Option 2'],
        ],
    ]);

    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
        'selected_options' => ['option1', 'invalid_option'],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['selected_options']);
});

it('requires option_id for single choice poll', function () {
    $poll = Poll::factory()->active()->create([
        'type' => 'single_choice',
        'options' => [
            ['id' => 'yes', 'label' => 'Yes'],
            ['id' => 'no', 'label' => 'No'],
        ],
    ]);

    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['selected_options']);
});

it('requires option_ids for multiple choice poll', function () {
    $poll = Poll::factory()->active()->create([
        'type' => 'multiple_choice',
        'options' => [
            ['id' => 'option1', 'label' => 'Option 1'],
            ['id' => 'option2', 'label' => 'Option 2'],
        ],
    ]);

    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['selected_options']);
});

it('calculates voting power correctly for asset weighted voting', function () {
    // Create user with account balance
    $account = $this->user->accounts()->create([
        'uuid' => fake()->uuid(),
        'name' => 'Test Account',
        'balance' => 500000, // $5000
    ]);

    $poll = Poll::factory()->active()->create([
        'type' => 'single_choice',
        'voting_power_strategy' => 'asset_weighted',
        'options' => [
            ['id' => 'yes', 'label' => 'Yes'],
            ['id' => 'no', 'label' => 'No'],
        ],
    ]);

    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
        'selected_options' => ['yes'],
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('votes', [
        'poll_id' => $poll->id,
        'user_uuid' => $this->user->uuid,
        'voting_power' => 500000, // Balance-based voting power
    ]);
});

it('applies square root weighting when configured', function () {
    // Create user with account balance
    $account = $this->user->accounts()->create([
        'uuid' => fake()->uuid(),
        'name' => 'Test Account',
        'balance' => 1000000, // $10000
    ]);

    $poll = Poll::factory()->active()->create([
        'type' => 'single_choice',
        'voting_power_strategy' => 'asset_weighted',
        'options' => [
            ['id' => 'yes', 'label' => 'Yes'],
            ['id' => 'no', 'label' => 'No'],
        ],
    ]);

    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
        'selected_options' => ['yes'],
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('votes', [
        'poll_id' => $poll->id,
        'user_uuid' => $this->user->uuid,
        'voting_power' => 1000, // sqrt(1000000) = 1000
    ]);
});

it('defaults to voting power of 1 for one-user-one-vote strategy', function () {
    $poll = Poll::factory()->active()->create([
        'type' => 'single_choice',
        'voting_power_strategy' => 'one_user_one_vote',
        'options' => [
            ['id' => 'yes', 'label' => 'Yes'],
            ['id' => 'no', 'label' => 'No'],
        ],
    ]);

    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
        'selected_options' => ['yes'],
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('votes', [
        'poll_id' => $poll->id,
        'user_uuid' => $this->user->uuid,
        'voting_power' => 1,
    ]);
});

it('returns 404 for non-existent poll', function () {
    $response = $this->postJson('/api/polls/non-existent-uuid/vote', [
        'selected_options' => ['yes'],
    ]);

    $response->assertStatus(404);
});

it('requires authentication', function () {
    $poll = Poll::factory()->active()->create();

    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
        'selected_options' => ['yes'],
    ], ['Authorization' => '']);

    $response->assertStatus(401);
})->skip('Authentication test needs refactoring');

it('includes vote signature when provided', function () {
    $poll = Poll::factory()->active()->create([
        'type' => 'single_choice',
        'options' => [
            ['id' => 'yes', 'label' => 'Yes'],
            ['id' => 'no', 'label' => 'No'],
        ],
    ]);

    $signature = Hash::make('vote-signature-' . $this->user->uuid . '-' . $poll->uuid);

    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
        'selected_options' => ['yes'],
        'signature' => $signature,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('votes', [
        'poll_id' => $poll->id,
        'user_uuid' => $this->user->uuid,
        'signature' => $signature,
    ]);
});

it('records vote timestamp correctly', function () {
    $poll = Poll::factory()->active()->create([
        'type' => 'single_choice',
        'options' => [
            ['id' => 'yes', 'label' => 'Yes'],
            ['id' => 'no', 'label' => 'No'],
        ],
    ]);

    $beforeVote = now();
    
    $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
        'selected_options' => ['yes'],
    ]);

    $afterVote = now();

    $response->assertStatus(201);

    $vote = Vote::where('poll_id', $poll->id)
        ->where('user_uuid', $this->user->uuid)
        ->first();

    expect($vote->voted_at)
        ->toBeGreaterThanOrEqual($beforeVote)
        ->toBeLessThanOrEqual($afterVote);
});