<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\Models\Vote;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VoteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    public function test_can_get_user_voting_history()
    {
        Sanctum::actingAs($this->user);

        // Create polls and votes for the authenticated user
        $poll1 = Poll::factory()->create();
        $poll2 = Poll::factory()->create();
        
        $vote1 = Vote::factory()->create([
            'user_uuid' => $this->user->uuid,
            'poll_id' => $poll1->id,
            'voting_power' => 100,
        ]);
        
        $vote2 = Vote::factory()->create([
            'user_uuid' => $this->user->uuid,
            'poll_id' => $poll2->id,
            'voting_power' => 150,
        ]);

        // Create vote for other user (should not appear)
        Vote::factory()->create([
            'user_uuid' => $this->otherUser->uuid,
            'poll_id' => $poll1->id,
        ]);

        $response = $this->getJson('/api/votes');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_uuid',
                        'poll_id',
                        'voting_power',
                        'voted_at',
                        'poll',
                        'user',
                    ]
                ],
                'meta',
                'links',
            ])
            ->assertJsonCount(2, 'data');

        // Verify only user's own votes are returned
        $responseData = $response->json('data');
        $this->assertTrue(collect($responseData)->every(fn($vote) => $vote['user_uuid'] === $this->user->uuid));
    }

    public function test_can_filter_votes_by_poll_id()
    {
        Sanctum::actingAs($this->user);

        $poll1 = Poll::factory()->create();
        $poll2 = Poll::factory()->create();
        
        Vote::factory()->create([
            'user_uuid' => $this->user->uuid,
            'poll_id' => $poll1->id,
        ]);
        
        Vote::factory()->create([
            'user_uuid' => $this->user->uuid,
            'poll_id' => $poll2->id,
        ]);

        $response = $this->getJson("/api/votes?poll_id={$poll1->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.poll_id', $poll1->id);
    }

    public function test_vote_index_validates_parameters()
    {
        Sanctum::actingAs($this->user);

        // Test invalid poll_id
        $response = $this->getJson('/api/votes?poll_id=999999');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['poll_id']);

        // Test invalid per_page
        $response = $this->getJson('/api/votes?per_page=0');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);

        $response = $this->getJson('/api/votes?per_page=101');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_can_get_vote_details()
    {
        Sanctum::actingAs($this->user);

        $poll = Poll::factory()->create();
        $vote = Vote::factory()->create([
            'user_uuid' => $this->user->uuid,
            'poll_id' => $poll->id,
            'voting_power' => 200,
        ]);

        $response = $this->getJson("/api/votes/{$vote->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_uuid',
                    'poll_id',
                    'voting_power',
                    'voted_at',
                    'poll',
                    'user',
                ]
            ])
            ->assertJsonPath('data.id', $vote->id)
            ->assertJsonPath('data.user_uuid', $this->user->uuid)
            ->assertJsonPath('data.voting_power', 200);
    }

    public function test_cannot_view_other_users_votes()
    {
        Sanctum::actingAs($this->user);

        $poll = Poll::factory()->create();
        $otherUserVote = Vote::factory()->create([
            'user_uuid' => $this->otherUser->uuid,
            'poll_id' => $poll->id,
        ]);

        $response = $this->getJson("/api/votes/{$otherUserVote->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Access denied',
            ]);
    }

    public function test_returns_404_for_nonexistent_vote()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/votes/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Vote not found',
            ]);
    }

    public function test_can_verify_vote_signature()
    {
        Sanctum::actingAs($this->user);

        $poll = Poll::factory()->create();
        $vote = Vote::factory()->create([
            'user_uuid' => $this->user->uuid,
            'poll_id' => $poll->id,
        ]);

        // Mock the verifySignature method
        $this->mock(Vote::class, function ($mock) use ($vote) {
            $mock->shouldReceive('find')
                ->with($vote->id)
                ->andReturn($vote);
            
            $mock->shouldReceive('getAttribute')
                ->with('user_uuid')
                ->andReturn($this->user->uuid);
                
            $mock->shouldReceive('verifySignature')
                ->once()
                ->andReturn(true);
        });

        $response = $this->postJson("/api/votes/{$vote->id}/verify");

        $response->assertOk()
            ->assertJsonStructure([
                'verified',
                'message',
            ])
            ->assertJsonPath('verified', true)
            ->assertJsonFragment([
                'message' => 'Vote signature is valid and vote has not been tampered with'
            ]);
    }

    public function test_verification_fails_for_invalid_signature()
    {
        Sanctum::actingAs($this->user);

        $poll = Poll::factory()->create();
        $vote = Vote::factory()->create([
            'user_uuid' => $this->user->uuid,
            'poll_id' => $poll->id,
        ]);

        // Mock the verifySignature method to return false
        $this->mock(Vote::class, function ($mock) use ($vote) {
            $mock->shouldReceive('find')
                ->with($vote->id)
                ->andReturn($vote);
            
            $mock->shouldReceive('getAttribute')
                ->with('user_uuid')
                ->andReturn($this->user->uuid);
                
            $mock->shouldReceive('verifySignature')
                ->once()
                ->andReturn(false);
        });

        $response = $this->postJson("/api/votes/{$vote->id}/verify");

        $response->assertOk()
            ->assertJsonPath('verified', false)
            ->assertJsonFragment([
                'message' => 'Vote signature is invalid or vote has been tampered with'
            ]);
    }

    public function test_cannot_verify_other_users_votes()
    {
        Sanctum::actingAs($this->user);

        $poll = Poll::factory()->create();
        $otherUserVote = Vote::factory()->create([
            'user_uuid' => $this->otherUser->uuid,
            'poll_id' => $poll->id,
        ]);

        $response = $this->postJson("/api/votes/{$otherUserVote->id}/verify");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Access denied',
            ]);
    }

    public function test_verification_returns_404_for_nonexistent_vote()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/votes/99999/verify');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Vote not found',
            ]);
    }

    public function test_can_get_voting_statistics()
    {
        Sanctum::actingAs($this->user);

        // Create polls and votes for statistics
        $poll1 = Poll::factory()->create();
        $poll2 = Poll::factory()->create();
        $poll3 = Poll::factory()->create();
        
        // Create votes with different voting powers
        Vote::factory()->create([
            'user_uuid' => $this->user->uuid,
            'poll_id' => $poll1->id,
            'voting_power' => 100,
            'voted_at' => now()->subDays(5), // Recent vote
        ]);
        
        Vote::factory()->create([
            'user_uuid' => $this->user->uuid,
            'poll_id' => $poll2->id,
            'voting_power' => 200,
            'voted_at' => now()->subDays(10), // Recent vote
        ]);
        
        Vote::factory()->create([
            'user_uuid' => $this->user->uuid,
            'poll_id' => $poll3->id,
            'voting_power' => 300,
            'voted_at' => now()->subDays(40), // Old vote (outside 30 days)
        ]);

        // Create vote for other user (should not affect stats)
        Vote::factory()->create([
            'user_uuid' => $this->otherUser->uuid,
            'poll_id' => $poll1->id,
            'voting_power' => 1000,
        ]);

        $response = $this->getJson('/api/votes/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'total_votes',
                'total_voting_power',
                'recent_votes',
                'avg_voting_power',
                'participation_rate',
            ]);

        $stats = $response->json();
        
        $this->assertEquals(3, $stats['total_votes']);
        $this->assertEquals(600, $stats['total_voting_power']); // 100 + 200 + 300
        $this->assertEquals(2, $stats['recent_votes']); // Only votes from last 30 days
        $this->assertEquals(200.0, $stats['avg_voting_power']); // 600 / 3
        $this->assertEquals(100.0, $stats['participation_rate']); // 3 votes / 3 polls * 100
    }

    public function test_stats_handles_no_votes()
    {
        Sanctum::actingAs($this->user);

        // Create some polls but no votes
        Poll::factory()->count(2)->create();

        $response = $this->getJson('/api/votes/stats');

        $response->assertOk()
            ->assertJson([
                'total_votes' => 0,
                'total_voting_power' => 0,
                'recent_votes' => 0,
                'avg_voting_power' => 0,
                'participation_rate' => 0,
            ]);
    }

    public function test_all_vote_endpoints_require_authentication()
    {
        $poll = Poll::factory()->create();
        $vote = Vote::factory()->create([
            'poll_id' => $poll->id,
        ]);

        $endpoints = [
            ['GET', '/api/votes'],
            ['GET', "/api/votes/{$vote->id}"],
            ['POST', "/api/votes/{$vote->id}/verify"],
            ['GET', '/api/votes/stats'],
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint);
            $response->assertStatus(401);
        }
    }

    public function test_vote_pagination_works()
    {
        Sanctum::actingAs($this->user);

        $poll = Poll::factory()->create();
        
        // Create more votes than the default per_page
        Vote::factory()->count(20)->create([
            'user_uuid' => $this->user->uuid,
            'poll_id' => $poll->id,
        ]);

        $response = $this->getJson('/api/votes?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'total',
                    'per_page',
                ],
                'links',
            ]);
            
        $this->assertEquals(20, $response->json('meta.total'));
        $this->assertEquals(5, $response->json('meta.per_page'));
    }
}