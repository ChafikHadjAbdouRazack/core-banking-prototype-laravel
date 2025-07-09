<?php

namespace Tests\Unit\Http\Resources;

use App\Domain\Governance\Strategies\AssetWeightedVotingStrategy;
use App\Http\Resources\UserVotingPollResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class UserVotingPollResourceTest extends TestCase
{
    use RefreshDatabase;

    protected $votingStrategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->votingStrategy = Mockery::mock(AssetWeightedVotingStrategy::class);
        $this->app->instance(AssetWeightedVotingStrategy::class, $this->votingStrategy);

        // Set the primary basket code to GCU for testing
        config(['baskets.primary_code' => 'GCU']);
    }

    private function createPoll(array $attributes = []): object
    {
        $defaults = [
            'uuid'                   => 'poll-123',
            'title'                  => 'Test Poll',
            'description'            => 'Test Description',
            'type'                   => (object) ['value' => 'single_choice'],
            'status'                 => (object) ['value' => 'active'],
            'options'                => ['Yes', 'No', 'Abstain'],
            'start_date'             => now()->subDay(),
            'end_date'               => now()->addDays(5),
            'required_participation' => 50.0,
            'metadata'               => [
                'basket_code'  => 'GCU',
                'voting_month' => '2025-01',
                'template'     => 'standard',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data = array_merge($defaults, $attributes);

        return new class ($data) {
            private array $attributes;

            private array $relations = [];

            public function __construct(array $attributes)
            {
                $this->attributes = $attributes;
            }

            public function __get($name)
            {
                if ($name === 'resource') {
                    return $this; // Return self as resource
                }

                return $this->attributes[$name] ?? null;
            }

            public function votes()
            {
                return new class ($this) {
                    private $poll;

                    public function __construct($poll)
                    {
                        $this->poll = $poll;
                    }

                    public function where($field, $value)
                    {
                        return $this;
                    }

                    public function first()
                    {
                        return $this->poll->getUserVote();
                    }

                    public function sum($field)
                    {
                        return $this->poll->getTotalVotingPower();
                    }
                };
            }

            public function calculateParticipation(): float
            {
                $totalVotingPower = $this->getTotalVotingPower();
                $potentialVotingPower = $this->estimatePotentialVotingPower();

                if ($potentialVotingPower === 0) {
                    return 0;
                }

                return round(($totalVotingPower / $potentialVotingPower) * 100, 2);
            }

            private function estimatePotentialVotingPower(): int
            {
                return 1000000;
            }

            public function setUserVote($vote)
            {
                $this->relations['userVote'] = $vote;

                return $this;
            }

            public function getUserVote()
            {
                return $this->relations['userVote'] ?? null;
            }

            public function setTotalVotingPower($power)
            {
                $this->relations['totalVotingPower'] = $power;

                return $this;
            }

            public function getTotalVotingPower()
            {
                return $this->relations['totalVotingPower'] ?? 0;
            }
        };
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'uuid'  => 'user-123',
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    private function createVote(array $attributes = []): object
    {
        $defaults = [
            'poll_uuid'        => 'poll-123',
            'user_uuid'        => 'user-123',
            'selected_options' => ['Yes'],
            'voting_power'     => 1000,
            'created_at'       => now(),
        ];

        $data = array_merge($defaults, $attributes);

        return (object) $data;
    }

    public function test_transforms_poll_without_user_context(): void
    {
        $poll = $this->createPoll();

        $resource = new UserVotingPollResource($poll);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $this->assertEquals('poll-123', $array['uuid']);
        $this->assertEquals('Test Poll', $array['title']);
        $this->assertEquals('Test Description', $array['description']);
        $this->assertEquals('single_choice', $array['type']);
        $this->assertEquals('active', $array['status']);
        $this->assertEquals(['Yes', 'No', 'Abstain'], $array['options']);
        $this->assertEquals(50.0, $array['required_participation']);
        $this->assertEquals(0.0, $array['current_participation']);
        $this->assertEquals([
            'has_voted'    => false,
            'voting_power' => 0,
            'can_vote'     => false,
            'vote'         => null,
        ], $array['user_context']);
        // Check that metadata is processed correctly
        $this->assertArrayHasKey('is_gcu_poll', $array['metadata']);
        $this->assertArrayHasKey('voting_month', $array['metadata']);
        $this->assertArrayHasKey('template', $array['metadata']);
        $this->assertTrue($array['metadata']['is_gcu_poll']);
        $this->assertEquals('2025-01', $array['metadata']['voting_month']);
        $this->assertEquals('standard', $array['metadata']['template']);
        $this->assertFalse($array['results_visible']);
        $this->assertIsArray($array['time_remaining']);
    }

    public function test_transforms_poll_with_authenticated_user(): void
    {
        $user = $this->createUser();
        $poll = $this->createPoll();

        $this->votingStrategy->shouldReceive('calculatePower')
            ->once()
            ->with(Mockery::on(function ($arg) use ($user) {
                return $arg->uuid === $user->uuid;
            }), $poll)
            ->andReturn(1000);

        $resource = new UserVotingPollResource($poll);
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        $array = $resource->toArray($request);

        $this->assertEquals([
            'has_voted'    => false,
            'voting_power' => 1000,
            'can_vote'     => true,
            'vote'         => null,
        ], $array['user_context']);
    }

    public function test_shows_user_vote_when_exists(): void
    {
        $user = $this->createUser();
        $poll = $this->createPoll();
        $vote = $this->createVote([
            'poll_uuid'        => $poll->uuid,
            'user_uuid'        => $user->uuid,
            'selected_options' => ['Yes'],
            'voting_power'     => 1000,
        ]);

        $poll->setUserVote($vote);

        $this->votingStrategy->shouldReceive('calculatePower')
            ->once()
            ->andReturn(1000);

        $resource = new UserVotingPollResource($poll);
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        $array = $resource->toArray($request);

        $this->assertTrue($array['user_context']['has_voted']);
        $this->assertFalse($array['user_context']['can_vote']);
        $this->assertEquals([
            'selected_options' => ['Yes'],
            'voted_at'         => $vote->created_at->toISOString(),
        ], $array['user_context']['vote']);
    }

    public function test_closed_poll_shows_results_visible(): void
    {
        $poll = $this->createPoll(['status' => (object) ['value' => 'closed']]);

        $resource = new UserVotingPollResource($poll);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $this->assertTrue($array['results_visible']);
        $this->assertNull($array['time_remaining']);
    }

    public function test_calculates_participation_correctly(): void
    {
        $poll = $this->createPoll();
        $poll->setTotalVotingPower(15000);

        $resource = new UserVotingPollResource($poll);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        // Total voting power: 15000
        // Estimated potential: 1000000 (from method)
        // Participation: (15000 / 1000000) * 100 = 1.5%
        $this->assertEquals(1.5, $array['current_participation']);
    }

    public function test_handles_non_gcu_poll(): void
    {
        $poll = $this->createPoll([
            'metadata' => ['basket_code' => 'OTHER'],
        ]);

        $resource = new UserVotingPollResource($poll);
        $request = Request::create('/');
        $array = $resource->toArray($request);

        $this->assertFalse($array['metadata']['is_gcu_poll']);
    }

    public function test_user_cannot_vote_when_no_voting_power(): void
    {
        $user = $this->createUser();
        $poll = $this->createPoll();

        $this->votingStrategy->shouldReceive('calculatePower')
            ->once()
            ->andReturn(0);

        $resource = new UserVotingPollResource($poll);
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        $array = $resource->toArray($request);

        $this->assertFalse($array['user_context']['can_vote']);
    }

    public function test_user_cannot_vote_on_inactive_poll(): void
    {
        $user = $this->createUser();
        $poll = $this->createPoll(['status' => (object) ['value' => 'pending']]);

        $this->votingStrategy->shouldReceive('calculatePower')
            ->once()
            ->andReturn(1000);

        $resource = new UserVotingPollResource($poll);
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        $array = $resource->toArray($request);

        $this->assertFalse($array['user_context']['can_vote']);
    }

    public function test_resource_collection(): void
    {
        $polls = [
            $this->createPoll(['uuid' => 'poll-1', 'title' => 'Poll 1']),
            $this->createPoll(['uuid' => 'poll-2', 'title' => 'Poll 2']),
            $this->createPoll(['uuid' => 'poll-3', 'title' => 'Poll 3']),
        ];

        $collection = UserVotingPollResource::collection($polls);
        $request = Request::create('/');
        $array = $collection->toArray($request);

        $this->assertCount(3, $array);
        $this->assertEquals('poll-1', $array[0]['uuid']);
        $this->assertEquals('Poll 1', $array[0]['title']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
