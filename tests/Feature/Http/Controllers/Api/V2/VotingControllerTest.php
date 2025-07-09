<?php

namespace Tests\Feature\Http\Controllers\Api\V2;

use App\Models\Account;
use App\Models\GcuVote;
use App\Models\GcuVotingProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VotingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->account = Account::factory()->create([
            'user_uuid' => $this->user->uuid,
            'balance'   => 100000000, // 1000 units for voting power
        ]);
    }

    public function test_get_proposals_returns_list(): void
    {
        // Create test proposals
        GcuVotingProposal::factory()->create([
            'title'            => 'Q1 2025 GCU Rebalancing',
            'status'           => 'active',
            'voting_starts_at' => now()->subDay(),
            'voting_ends_at'   => now()->addDays(6),
        ]);

        GcuVotingProposal::factory()->create([
            'title'            => 'Add JPY to GCU Basket',
            'status'           => 'upcoming',
            'voting_starts_at' => now()->addDays(7),
            'voting_ends_at'   => now()->addDays(14),
        ]);

        GcuVotingProposal::factory()->create([
            'title'            => 'Q4 2024 GCU Rebalancing',
            'status'           => 'completed',
            'voting_starts_at' => now()->subMonth(),
            'voting_ends_at'   => now()->subDays(20),
        ]);

        $response = $this->getJson('/api/v2/gcu/voting/proposals');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'voting_starts_at',
                        'voting_ends_at',
                        'participation_rate',
                        'approval_rate',
                    ],
                ],
            ])
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_get_proposals_filters_by_status(): void
    {
        GcuVotingProposal::factory()->create(['status' => 'active']);
        GcuVotingProposal::factory()->create(['status' => 'active']);
        GcuVotingProposal::factory()->create(['status' => 'upcoming']);
        GcuVotingProposal::factory()->create(['status' => 'completed']);

        $response = $this->getJson('/api/v2/gcu/voting/proposals?status=active');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status', 'active')
            ->assertJsonPath('data.1.status', 'active');
    }

    public function test_get_proposal_details(): void
    {
        $proposal = GcuVotingProposal::factory()->create([
            'title'            => 'Q1 2025 GCU Rebalancing',
            'status'           => 'active',
            'proposed_weights' => [
                'USD' => 0.38,
                'EUR' => 0.37,
                'GBP' => 0.25,
            ],
        ]);

        $response = $this->getJson("/api/v2/gcu/voting/proposals/{$proposal->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'voting_starts_at',
                    'voting_ends_at',
                    'proposed_weights',
                    'current_weights',
                    'weight_changes',
                    'voting_stats' => [
                        'total_votes',
                        'unique_voters',
                        'total_voting_power',
                        'participation_rate',
                        'current_results',
                    ],
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'data'   => [
                    'id'    => $proposal->id,
                    'title' => 'Q1 2025 GCU Rebalancing',
                ],
            ]);
    }

    public function test_get_proposal_details_returns_404_for_invalid_id(): void
    {
        $response = $this->getJson('/api/v2/gcu/voting/proposals/999999');

        $response->assertStatus(404);
    }

    public function test_cast_vote_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $proposal = GcuVotingProposal::factory()->create([
            'status'           => 'active',
            'voting_starts_at' => now()->subDay(),
            'voting_ends_at'   => now()->addDays(6),
        ]);

        $response = $this->postJson("/api/v2/gcu/voting/proposals/{$proposal->id}/vote", [
            'vote'    => 'approve',
            'comment' => 'I support this rebalancing proposal',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'vote_id',
                    'proposal_id',
                    'vote',
                    'voting_power',
                    'timestamp',
                    'message',
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'data'   => [
                    'vote'    => 'approve',
                    'message' => 'Vote cast successfully',
                ],
            ]);

        $this->assertDatabaseHas('gcu_votes', [
            'proposal_id' => $proposal->id,
            'user_uuid'   => $this->user->uuid,
            'vote'        => 'approve',
        ]);
    }

    public function test_cast_vote_requires_authentication(): void
    {
        $proposal = GcuVotingProposal::factory()->create(['status' => 'active']);

        $response = $this->postJson("/api/v2/gcu/voting/proposals/{$proposal->id}/vote", [
            'vote' => 'approve',
        ]);

        $response->assertStatus(401);
    }

    public function test_cast_vote_validates_vote_option(): void
    {
        Sanctum::actingAs($this->user);

        $proposal = GcuVotingProposal::factory()->create(['status' => 'active']);

        $response = $this->postJson("/api/v2/gcu/voting/proposals/{$proposal->id}/vote", [
            'vote' => 'invalid_option',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vote']);
    }

    public function test_cast_vote_prevents_voting_on_inactive_proposal(): void
    {
        Sanctum::actingAs($this->user);

        $proposal = GcuVotingProposal::factory()->create([
            'status'         => 'completed',
            'voting_ends_at' => now()->subDay(),
        ]);

        $response = $this->postJson("/api/v2/gcu/voting/proposals/{$proposal->id}/vote", [
            'vote' => 'approve',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Voting is not active for this proposal',
            ]);
    }

    public function test_cast_vote_prevents_duplicate_voting(): void
    {
        Sanctum::actingAs($this->user);

        $proposal = GcuVotingProposal::factory()->create(['status' => 'active']);

        // First vote
        GcuVote::create([
            'proposal_id'  => $proposal->id,
            'user_uuid'    => $this->user->uuid,
            'vote'         => 'approve',
            'voting_power' => 1000,
        ]);

        // Attempt second vote
        $response = $this->postJson("/api/v2/gcu/voting/proposals/{$proposal->id}/vote", [
            'vote' => 'reject',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'You have already voted on this proposal',
            ]);
    }

    public function test_get_voting_history(): void
    {
        Sanctum::actingAs($this->user);

        // Create past votes
        $proposal1 = GcuVotingProposal::factory()->create(['title' => 'Proposal 1']);
        $proposal2 = GcuVotingProposal::factory()->create(['title' => 'Proposal 2']);

        GcuVote::create([
            'proposal_id'  => $proposal1->id,
            'user_uuid'    => $this->user->uuid,
            'vote'         => 'approve',
            'voting_power' => 1000,
            'created_at'   => now()->subMonth(),
        ]);

        GcuVote::create([
            'proposal_id'  => $proposal2->id,
            'user_uuid'    => $this->user->uuid,
            'vote'         => 'reject',
            'voting_power' => 1500,
            'created_at'   => now()->subWeek(),
        ]);

        $response = $this->getJson('/api/v2/gcu/voting/my-votes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'vote_id',
                        'proposal' => [
                            'id',
                            'title',
                            'status',
                        ],
                        'vote',
                        'voting_power',
                        'voted_at',
                    ],
                ],
            ])
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_get_voting_history_requires_authentication(): void
    {
        $response = $this->getJson('/api/v2/gcu/voting/my-votes');

        $response->assertStatus(401);
    }

    public function test_get_voting_power(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/gcu/voting/my-voting-power');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'current_voting_power',
                    'calculation_method',
                    'factors' => [
                        'gcu_balance',
                        'holding_duration_multiplier',
                        'participation_bonus',
                    ],
                    'next_calculation_at',
                ],
            ])
            ->assertJson([
                'status' => 'success',
            ]);
    }

    public function test_get_voting_power_requires_authentication(): void
    {
        $response = $this->getJson('/api/v2/gcu/voting/my-voting-power');

        $response->assertStatus(401);
    }
}
