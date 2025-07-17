<?php

declare(strict_types=1);

use App\Domain\Governance\Enums\PollStatus;
use App\Domain\Governance\Enums\PollType;
use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\Models\Vote;
use App\Domain\Governance\ValueObjects\PollOption;
use App\Models\User;

describe('Poll Model', function () {
    it('has correct fillable attributes', function () {
        $poll = new Poll();

        expect($poll->getFillable())->toContain(
            'uuid',
            'title',
            'description',
            'type',
            'options',
            'start_date',
            'end_date',
            'status',
            'required_participation',
            'voting_power_strategy',
            'execution_workflow',
            'created_by',
            'metadata'
        );
    });

    it('casts attributes correctly', function () {
        $poll = Poll::factory()->create([
            'type'     => 'yes_no',
            'status'   => 'draft',
            'options'  => [['id' => 'yes', 'label' => 'Yes']],
            'metadata' => ['test' => 'value'],
        ]);

        expect($poll->type)->toBeInstanceOf(PollType::class);
        expect($poll->status)->toBeInstanceOf(PollStatus::class);
        expect($poll->options)->toBeArray();
        expect($poll->metadata)->toBeArray();
        expect($poll->start_date)->toBeInstanceOf(Carbon\Carbon::class);
        expect($poll->end_date)->toBeInstanceOf(Carbon\Carbon::class);
    });

    it('generates UUID on creation', function () {
        $poll = Poll::factory()->create(['uuid' => null]);

        expect($poll->uuid)->toBeString();
        expect($poll->uuid)->toHaveLength(36); // UUID v4 length
    });

    it('has creator relationship', function () {
        $user = User::factory()->create();
        $poll = Poll::factory()->forUser($user)->create();

        expect($poll->creator)->toBeInstanceOf(User::class);
        expect($poll->creator->uuid)->toBe($user->uuid);
    });

    it('has votes relationship', function () {
        $poll = Poll::factory()->create();
        Vote::factory()->forPoll($poll)->count(3)->create();

        expect($poll->votes)->toHaveCount(3);
        $poll->votes->each(fn ($vote) => expect($vote)->toBeInstanceOf(Vote::class));
    });
});

describe('Poll Options Handling', function () {
    it('can get options as objects', function () {
        $poll = Poll::factory()->create([
            'options' => [
                ['id' => 'opt1', 'label' => 'Option 1', 'description' => 'First option'],
                ['id' => 'opt2', 'label' => 'Option 2'],
            ],
        ]);

        $options = $poll->getOptionsAsObjects();

        expect($options)->toHaveCount(2);
        expect($options[0])->toBeInstanceOf(PollOption::class);
        expect($options[0]->id)->toBe('opt1');
        expect($options[0]->label)->toBe('Option 1');
        expect($options[0]->description)->toBe('First option');
        expect($options[1]->description)->toBeNull();
    });

    it('can set options from objects', function () {
        $poll = Poll::factory()->create();

        $options = [
            new PollOption('opt1', 'Option 1', 'Description 1'),
            new PollOption('opt2', 'Option 2'),
        ];

        $poll->setOptionsFromObjects($options);

        expect($poll->options)->toHaveCount(2);
        expect($poll->options[0]['id'])->toBe('opt1');
        expect($poll->options[0]['label'])->toBe('Option 1');
        expect($poll->options[0]['description'])->toBe('Description 1');
        expect($poll->options[1]['description'])->toBeNull();
    });
});

describe('Poll Status Checks', function () {
    it('identifies active polls correctly', function () {
        $activePoll = Poll::factory()->active()->create([
            'start_date' => now()->subHour(),
            'end_date'   => now()->addHour(),
        ]);

        $draftPoll = Poll::factory()->draft()->create();

        expect($activePoll->isActive())->toBeTrue();
        expect($draftPoll->isActive())->toBeFalse();
    });

    it('identifies expired polls correctly', function () {
        $expiredPoll = Poll::factory()->create([
            'end_date' => now()->subHour(),
        ]);

        $futurePoll = Poll::factory()->create([
            'end_date' => now()->addHour(),
        ]);

        expect($expiredPoll->isExpired())->toBeTrue();
        expect($futurePoll->isExpired())->toBeFalse();
    });

    it('determines if poll can accept votes', function () {
        $activePoll = Poll::factory()->active()->create([
            'start_date' => now()->subHour(),
            'end_date'   => now()->addHour(),
        ]);

        $expiredPoll = Poll::factory()->active()->create([
            'end_date' => now()->subHour(),
        ]);

        $draftPoll = Poll::factory()->draft()->create();

        expect($activePoll->canVote())->toBeTrue();
        expect($expiredPoll->canVote())->toBeFalse();
        expect($draftPoll->canVote())->toBeFalse();
    });
});

describe('Poll Time Calculations', function () {
    it('calculates duration correctly', function () {
        $poll = Poll::factory()->create([
            'start_date' => now(),
            'end_date'   => now()->addHours(48),
        ]);

        expect($poll->getDurationInHours())->toBe(48);
    });

    it('calculates remaining time correctly', function () {
        $poll = Poll::factory()->active()->create([
            'start_date' => now()->subHour(),
            'end_date'   => now()->addHours(23),
        ]);

        $remainingHours = $poll->getTimeRemainingInHours();
        expect($remainingHours)->toBeGreaterThanOrEqual(22);
        expect($remainingHours)->toBeLessThanOrEqual(23);
    });

    it('returns zero remaining time for inactive polls', function () {
        $poll = Poll::factory()->draft()->create([
            'end_date' => now()->addHours(24),
        ]);

        expect($poll->getTimeRemainingInHours())->toBe(0);
    });
});

describe('Poll Voting Tracking', function () {
    it('tracks if user has voted', function () {
        $poll = Poll::factory()->create();
        $user = User::factory()->create();

        expect($poll->hasUserVoted($user->uuid))->toBeFalse();

        Vote::factory()->forPoll($poll)->forUser($user)->create();

        expect($poll->hasUserVoted($user->uuid))->toBeTrue();
    });

    it('gets user vote', function () {
        $poll = Poll::factory()->create();
        $user = User::factory()->create();

        expect($poll->getUserVote($user->uuid))->toBeNull();

        $vote = Vote::factory()->forPoll($poll)->forUser($user)->create();

        $userVote = $poll->getUserVote($user->uuid);
        expect($userVote)->toBeInstanceOf(Vote::class);
        expect($userVote->id)->toBe($vote->id);
    });

    it('counts votes correctly', function () {
        $poll = Poll::factory()->create();
        Vote::factory()->forPoll($poll)->count(5)->create();

        expect($poll->getVoteCount())->toBe(5);
    });

    it('calculates total voting power', function () {
        $poll = Poll::factory()->create();
        Vote::factory()->forPoll($poll)->create(['voting_power' => 10]);
        Vote::factory()->forPoll($poll)->create(['voting_power' => 25]);
        Vote::factory()->forPoll($poll)->create(['voting_power' => 15]);

        expect($poll->getTotalVotingPower())->toBe(50);
    });
});

describe('Poll Results Calculation', function () {
    it('calculates results with votes', function () {
        $poll = Poll::factory()->yesNo()->create();

        Vote::factory()->forPoll($poll)->yesVote()->create(['voting_power' => 10]);
        Vote::factory()->forPoll($poll)->yesVote()->create(['voting_power' => 15]);
        Vote::factory()->forPoll($poll)->noVote()->create(['voting_power' => 5]);

        $results = $poll->calculateResults();

        expect($results->totalVotes)->toBe(3);
        expect($results->totalVotingPower)->toBe(30);
        expect($results->winningOption)->toBe('yes');
        expect($results->optionResults['yes']['power'])->toBe(25);
        expect($results->optionResults['no']['power'])->toBe(5);
    });

    it('calculates results with no votes', function () {
        $poll = Poll::factory()->yesNo()->create();

        $results = $poll->calculateResults();

        expect($results->totalVotes)->toBe(0);
        expect($results->totalVotingPower)->toBe(0);
        expect($results->winningOption)->toBeNull();
    });
});

describe('Poll Query Scopes', function () {
    it('filters active polls', function () {
        Poll::factory()->active()->count(2)->create([
            'start_date' => now()->subHour(),
            'end_date'   => now()->addHour(),
        ]);
        Poll::factory()->draft()->count(3)->create();

        $activePolls = Poll::active()->get();

        expect($activePolls)->toHaveCount(2);
    });

    it('filters by poll type', function () {
        Poll::factory()->yesNo()->count(2)->create();
        Poll::factory()->singleChoice()->count(3)->create();

        $yesNoPolls = Poll::byType(PollType::YES_NO)->get();

        expect($yesNoPolls)->toHaveCount(2);
    });

    it('filters by creator', function () {
        $user = User::factory()->create();
        Poll::factory()->forUser($user)->count(2)->create();
        Poll::factory()->count(3)->create();

        $userPolls = Poll::byCreator($user->uuid)->get();

        expect($userPolls)->toHaveCount(2);
    });

    it('filters expired polls', function () {
        Poll::factory()->count(2)->create(['end_date' => now()->subHour()]);
        Poll::factory()->count(3)->create(['end_date' => now()->addHour()]);

        $expiredPolls = Poll::expired()->get();

        expect($expiredPolls)->toHaveCount(2);
    });

    it('filters upcoming polls', function () {
        Poll::factory()->count(2)->create(['start_date' => now()->addHour()]);
        Poll::factory()->count(3)->create(['start_date' => now()->subHour()]);

        $upcomingPolls = Poll::upcoming()->get();

        expect($upcomingPolls)->toHaveCount(2);
    });
});

describe('Poll Route Key', function () {
    it('uses uuid as route key', function () {
        $poll = new Poll();

        expect($poll->getRouteKeyName())->toBe('uuid');
    });
});
