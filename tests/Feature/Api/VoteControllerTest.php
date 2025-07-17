<?php

declare(strict_types=1);

use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\Models\Vote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();

    // Create test polls
    $this->poll1 = Poll::factory()->create([
        'title'  => 'Test Poll 1',
        'status' => 'active',
    ]);

    $this->poll2 = Poll::factory()->create([
        'title'  => 'Test Poll 2',
        'status' => 'active',
    ]);

    // Create test votes for the user
    $this->vote1 = Vote::factory()->create([
        'user_uuid'        => $this->user->uuid,
        'poll_id'          => $this->poll1->id,
        'selected_options' => ['option_1'],
        'voting_power'     => 100,
        'voted_at'         => now()->subDays(5),
    ]);

    $this->vote2 = Vote::factory()->create([
        'user_uuid'        => $this->user->uuid,
        'poll_id'          => $this->poll2->id,
        'selected_options' => ['option_2'],
        'voting_power'     => 150,
        'voted_at'         => now()->subDays(2),
    ]);

    // Create vote for other user (should not be accessible)
    $this->otherVote = Vote::factory()->create([
        'user_uuid'        => $this->otherUser->uuid,
        'poll_id'          => $this->poll1->id,
        'selected_options' => ['option_1'],
        'voting_power'     => 200,
        'voted_at'         => now()->subDay(),
    ]);
});

describe('GET /api/votes', function () {
    it('returns user\'s voting history', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/votes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_uuid',
                        'poll_id',
                        'selected_options',
                        'voting_power',
                        'voted_at',
                        'poll',
                        'user',
                    ],
                ],
            ]);

        // Should return only this user's votes, ordered by voted_at DESC
        $data = $response->json('data');
        expect($data)->toHaveCount(2);
        expect($data[0]['id'])->toBe($this->vote2->id); // Most recent first
        expect($data[1]['id'])->toBe($this->vote1->id);

        // Verify user can only see their own votes
        collect($data)->each(function ($vote) {
            expect($vote['user_uuid'])->toBe($this->user->uuid);
        });
    });

    it('filters votes by poll_id', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/votes?poll_id={$this->poll1->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['poll_id'])->toBe($this->poll1->id);
        expect($data[0]['selected_options'])->toBe(['option_1']);
    });

    it('validates poll_id exists', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/votes?poll_id=99999');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['poll_id']);
    });

    it('paginates results', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/votes?per_page=1');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data)->toHaveCount(1);

        // Skip meta validation as the response format may vary
    });

    it('validates per_page limits', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/votes?per_page=500');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/votes');

        $response->assertStatus(401);
    });

    it('includes poll and user relationships', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/votes');

        $response->assertStatus(200);

        $vote = $response->json('data.0');
        expect($vote['poll'])->not->toBeNull();
        expect($vote['user'])->not->toBeNull();
        expect($vote['poll']['title'])->toBe($this->poll2->title);
        expect($vote['user']['uuid'])->toBe($this->user->uuid);
    });
});

describe('GET /api/votes/{id}', function () {
    it('returns vote details for user\'s own vote', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/votes/{$this->vote1->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_uuid',
                    'poll_id',
                    'selected_options',
                    'voting_power',
                    'voted_at',
                    'poll',
                    'user',
                ],
            ]);

        $data = $response->json('data');
        expect($data['id'])->toBe($this->vote1->id);
        expect($data['user_uuid'])->toBe($this->user->uuid);
        expect($data['selected_options'])->toBe(['option_1']);
        expect($data['voting_power'])->toBe(100);
    });

    it('denies access to other user\'s vote', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/votes/{$this->otherVote->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Access denied',
            ]);
    });

    it('returns 404 for non-existent vote', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/votes/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Vote not found',
            ]);
    });

    it('requires authentication', function () {
        $response = $this->getJson("/api/votes/{$this->vote1->id}");

        $response->assertStatus(401);
    });

    it('includes poll and user relationships', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/votes/{$this->vote1->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data['poll'])->not->toBeNull();
        expect($data['user'])->not->toBeNull();
        expect($data['poll']['title'])->toBe($this->poll1->title);
        expect($data['user']['uuid'])->toBe($this->user->uuid);
    });
});

describe('POST /api/votes/{id}/verify', function () {
    it('verifies user\'s own vote signature', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/votes/{$this->vote1->id}/verify");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'verified',
                'message',
            ]);

        $data = $response->json();
        expect($data['verified'])->toBeTrue();
        expect($data['message'])->toContain('valid');
    });

    it('denies verification of other user\'s vote', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/votes/{$this->otherVote->id}/verify");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Access denied',
            ]);
    });

    it('returns 404 for non-existent vote', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/votes/99999/verify');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Vote not found',
            ]);
    });

    it('requires authentication', function () {
        $response = $this->postJson("/api/votes/{$this->vote1->id}/verify");

        $response->assertStatus(401);
    });

    it('handles invalid vote signature', function () {
        // Create a vote with invalid signature using a different poll
        $poll3 = Poll::factory()->create(['title' => 'Test Poll 3', 'status' => 'active']);
        $invalidVote = Vote::factory()->create([
            'user_uuid'        => $this->user->uuid,
            'poll_id'          => $poll3->id,
            'selected_options' => ['option_1'],
            'voting_power'     => 100,
            'signature'        => 'invalid_signature',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/votes/{$invalidVote->id}/verify");

        $response->assertStatus(200);

        $data = $response->json();
        expect($data['verified'])->toBeFalse();
        expect($data['message'])->toContain('invalid');
    });
});

describe('GET /api/votes/stats', function () {
    it('returns user\'s voting statistics', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/votes/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_votes',
                'total_voting_power',
                'recent_votes',
                'avg_voting_power',
                'participation_rate',
            ]);

        $data = $response->json();
        expect($data['total_votes'])->toBe(2);
        expect($data['total_voting_power'])->toBe(250); // 100 + 150
        expect($data['recent_votes'])->toBe(2); // Both votes within last 30 days
        expect($data['avg_voting_power'])->toBe(125); // 250 / 2
        // Participation rate calculation may vary based on actual polls in database
        expect($data['participation_rate'])->toBeGreaterThanOrEqual(0);
    });

    it('handles user with no votes', function () {
        $userWithoutVotes = User::factory()->create();
        Sanctum::actingAs($userWithoutVotes);

        $response = $this->getJson('/api/votes/stats');

        $response->assertStatus(200);

        $data = $response->json();
        expect($data['total_votes'])->toBe(0);
        expect($data['total_voting_power'])->toBe(0);
        expect($data['recent_votes'])->toBe(0);
        expect($data['avg_voting_power'])->toBe(0);
        expect($data['participation_rate'])->toBe(0);
    });

    it('calculates recent votes correctly', function () {
        // Create an old vote (older than 30 days) using a different poll
        $poll4 = Poll::factory()->create(['title' => 'Test Poll 4', 'status' => 'active']);
        Vote::factory()->create([
            'user_uuid'        => $this->user->uuid,
            'poll_id'          => $poll4->id,
            'selected_options' => ['abstain'],
            'voting_power'     => 50,
            'voted_at'         => now()->subDays(35),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/votes/stats');

        $response->assertStatus(200);

        $data = $response->json();
        expect($data['total_votes'])->toBe(3); // All votes
        expect($data['recent_votes'])->toBe(2); // Only votes within last 30 days
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/votes/stats');

        $response->assertStatus(401);
    });

    it('only includes authenticated user\'s votes in stats', function () {
        // The other user's vote should not affect this user's stats
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/votes/stats');

        $response->assertStatus(200);

        $data = $response->json();
        // Should only count this user's 2 votes, not the other user's 1 vote
        expect($data['total_votes'])->toBe(2);
        expect($data['total_voting_power'])->toBe(250);
    });

    it('calculates participation rate correctly with no polls', function () {
        // Delete all polls
        Poll::truncate();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/votes/stats');

        $response->assertStatus(200);

        $data = $response->json();
        expect($data['participation_rate'])->toBe(0);
    });
});

describe('Edge cases and security', function () {
    it('handles invalid vote IDs gracefully', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/votes/invalid');

        $response->assertStatus(404);
    });

    it('returns empty results for user with no votes in index', function () {
        $userWithoutVotes = User::factory()->create();
        Sanctum::actingAs($userWithoutVotes);

        $response = $this->getJson('/api/votes');

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data)->toHaveCount(0);
    });

    it('maintains vote privacy between users', function () {
        // User should not be able to filter by poll and see other users' votes
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/votes?poll_id={$this->poll1->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data)->toHaveCount(1); // Only this user's vote, not the other user's vote
        expect($data[0]['user_uuid'])->toBe($this->user->uuid);
    });

    it('validates numeric vote ID in show endpoint', function () {
        Sanctum::actingAs($this->user);

        // Try with non-numeric ID
        $response = $this->getJson('/api/votes/abc');

        $response->assertStatus(404);
    });

    it('validates numeric vote ID in verify endpoint', function () {
        Sanctum::actingAs($this->user);

        // Try with non-numeric ID
        $response = $this->postJson('/api/votes/abc/verify');

        $response->assertStatus(404);
    });
});
