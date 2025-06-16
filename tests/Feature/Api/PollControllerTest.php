<?php

declare(strict_types=1);

use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\Models\Vote;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'sanctum');
});

describe('Poll Listing', function () {
    it('can list all polls', function () {
        Poll::factory()->count(5)->create();

        $response = $this->getJson('/api/polls');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'title',
                        'type',
                        'status',
                        'start_date',
                        'end_date',
                    ]
                ]
            ]);
    });

    it('can filter polls by status', function () {
        Poll::factory()->active()->count(2)->create();
        Poll::factory()->draft()->count(3)->create();

        $response = $this->getJson('/api/polls?status=active');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        expect($data)->toHaveCount(2);
        
        foreach ($data as $poll) {
            expect($poll['status'])->toBe('active');
        }
    });

    it('can filter polls by type', function () {
        Poll::factory()->yesNo()->count(2)->create();
        Poll::factory()->singleChoice()->count(3)->create();

        $response = $this->getJson('/api/polls?type=yes_no');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        expect($data)->toHaveCount(2);
        
        foreach ($data as $poll) {
            expect($poll['type'])->toBe('yes_no');
        }
    });

    it('can get active polls', function () {
        Poll::factory()->active()->count(3)->create([
            'start_date' => now()->subHour(),
            'end_date' => now()->addHour(),
        ]);
        Poll::factory()->draft()->count(2)->create();

        $response = $this->getJson('/api/polls/active');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'title',
                        'status',
                    ]
                ],
                'count'
            ]);

        expect($response->json('count'))->toBe(3);
    });
});

describe('Poll Creation', function () {
    it('can create a poll', function () {
        $pollData = [
            'title' => 'Should we add JPY support?',
            'description' => 'Adding Japanese Yen currency support',
            'type' => 'yes_no',
            'options' => [
                ['id' => 'yes', 'label' => 'Yes', 'description' => 'Add JPY support'],
                ['id' => 'no', 'label' => 'No', 'description' => 'Keep current currencies'],
            ],
            'start_date' => now()->addHour()->toISOString(),
            'end_date' => now()->addWeek()->toISOString(),
            'voting_power_strategy' => 'one_user_one_vote',
        ];

        $response = $this->postJson('/api/polls', $pollData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'title',
                    'type',
                    'status',
                    'created_by',
                ],
                'message'
            ]);

        expect($response->json('data.title'))->toBe($pollData['title']);
        expect($response->json('data.created_by'))->toBe($this->user->uuid);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/polls', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'type', 'options', 'start_date', 'end_date']);
    });

    it('validates poll options', function () {
        $pollData = [
            'title' => 'Test Poll',
            'type' => 'yes_no',
            'options' => [
                ['id' => 'yes'], // Missing label
            ],
            'start_date' => now()->addHour()->toISOString(),
            'end_date' => now()->addWeek()->toISOString(),
        ];

        $response = $this->postJson('/api/polls', $pollData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['options.0.label']);
    });

    it('validates date constraints', function () {
        $pollData = [
            'title' => 'Test Poll',
            'type' => 'yes_no',
            'options' => [
                ['id' => 'yes', 'label' => 'Yes'],
                ['id' => 'no', 'label' => 'No'],
            ],
            'start_date' => now()->addWeek()->toISOString(),
            'end_date' => now()->addDay()->toISOString(), // End before start
        ];

        $response = $this->postJson('/api/polls', $pollData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    });
});

describe('Poll Details', function () {
    it('can show poll details', function () {
        $poll = Poll::factory()->create();

        $response = $this->getJson("/api/polls/{$poll->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'title',
                    'description',
                    'type',
                    'options',
                    'status',
                    'start_date',
                    'end_date',
                    'creator',
                    'votes',
                ]
            ]);

        expect($response->json('data.uuid'))->toBe($poll->uuid);
    });

    it('returns 404 for non-existent poll', function () {
        $response = $this->getJson('/api/polls/non-existent-uuid');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Poll not found']);
    });
});

describe('Poll Activation', function () {
    it('can activate a draft poll', function () {
        $poll = Poll::factory()->draft()->create([
            'start_date' => now()->subMinute(),
            'end_date' => now()->addWeek(),
        ]);

        $response = $this->postJson("/api/polls/{$poll->uuid}/activate");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['status'],
                'message'
            ]);

        expect($response->json('data.status'))->toBe('active');
    });

    it('cannot activate poll before start date', function () {
        $poll = Poll::factory()->draft()->create([
            'start_date' => now()->addHour(),
            'end_date' => now()->addWeek(),
        ]);

        $response = $this->postJson("/api/polls/{$poll->uuid}/activate");

        $response->assertStatus(400)
            ->assertJsonStructure(['message']);
    });

    it('returns 404 for non-existent poll', function () {
        $response = $this->postJson('/api/polls/non-existent-uuid/activate');

        $response->assertStatus(404);
    });
});

describe('Voting', function () {
    it('can cast vote in active poll', function () {
        $poll = Poll::factory()->active()->yesNo()->create();

        $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
            'selected_options' => ['yes']
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'poll_id',
                    'user_uuid',
                    'selected_options',
                    'voting_power',
                    'voted_at',
                ],
                'message'
            ]);

        expect($response->json('data.user_uuid'))->toBe($this->user->uuid);
        expect($response->json('data.selected_options'))->toBe(['yes']);
    });

    it('validates selected options', function () {
        $poll = Poll::factory()->active()->yesNo()->create();

        $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
            'selected_options' => []
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['selected_options']);
    });

    it('cannot vote twice in same poll', function () {
        $poll = Poll::factory()->active()->yesNo()->create();

        // First vote
        $this->postJson("/api/polls/{$poll->uuid}/vote", [
            'selected_options' => ['yes']
        ])->assertStatus(201);

        // Second vote attempt
        $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
            'selected_options' => ['no']
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure(['message']);
    });

    it('cannot vote in inactive poll', function () {
        $poll = Poll::factory()->draft()->yesNo()->create();

        $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
            'selected_options' => ['yes']
        ]);

        $response->assertStatus(400);
    });

    it('returns 404 for non-existent poll', function () {
        $response = $this->postJson('/api/polls/non-existent-uuid/vote', [
            'selected_options' => ['yes']
        ]);

        $response->assertStatus(404);
    });
});

describe('Poll Results', function () {
    it('can get poll results', function () {
        $poll = Poll::factory()->yesNo()->create();
        
        // Add some votes
        Vote::factory()->forPoll($poll)->yesVote()->create(['voting_power' => 10]);
        Vote::factory()->forPoll($poll)->noVote()->create(['voting_power' => 5]);

        $response = $this->getJson("/api/polls/{$poll->uuid}/results");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'poll_uuid',
                    'total_votes',
                    'total_voting_power',
                    'option_results',
                    'participation_rate',
                    'winning_option',
                ]
            ]);

        expect($response->json('data.total_votes'))->toBe(2);
        expect($response->json('data.total_voting_power'))->toBe(15);
        expect($response->json('data.winning_option'))->toBe('yes');
    });

    it('returns 404 for non-existent poll', function () {
        $response = $this->getJson('/api/polls/non-existent-uuid/results');

        $response->assertStatus(404);
    });
});

describe('Voting Power Check', function () {
    it('can check user voting power', function () {
        $poll = Poll::factory()->oneUserOneVote()->create();

        $response = $this->getJson("/api/polls/{$poll->uuid}/voting-power");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'voting_power',
                'can_vote',
                'has_voted',
                'strategy'
            ]);

        expect($response->json('voting_power'))->toBe(1);
        expect($response->json('can_vote'))->toBeTrue();
        expect($response->json('has_voted'))->toBeFalse();
        expect($response->json('strategy'))->toBe('one_user_one_vote');
    });

    it('reflects voting status after voting', function () {
        $poll = Poll::factory()->active()->yesNo()->create();

        // Check before voting
        $response = $this->getJson("/api/polls/{$poll->uuid}/voting-power");
        expect($response->json('has_voted'))->toBeFalse();

        // Cast vote
        $this->postJson("/api/polls/{$poll->uuid}/vote", [
            'selected_options' => ['yes']
        ]);

        // Check after voting
        $response = $this->getJson("/api/polls/{$poll->uuid}/voting-power");
        expect($response->json('has_voted'))->toBeTrue();
        expect($response->json('can_vote'))->toBeFalse();
    });

    it('returns 404 for non-existent poll', function () {
        $response = $this->getJson('/api/polls/non-existent-uuid/voting-power');

        $response->assertStatus(404);
    });
});

describe('Authentication Required', function () {
    it('requires authentication for protected endpoints', function () {
        // Test without authentication
        $this->withoutAuthenticatedUser();
        
        $response = $this->postJson('/api/polls', []);
        $response->assertStatus(401);

        $response = $this->postJson('/api/polls/uuid/vote', []);
        $response->assertStatus(401);

        $response = $this->getJson('/api/polls/uuid/voting-power');
        $response->assertStatus(401);
    });
});