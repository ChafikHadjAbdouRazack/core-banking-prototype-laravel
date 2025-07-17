<?php

declare(strict_types=1);

use App\Domain\Governance\Enums\PollStatus;
use App\Domain\Governance\Enums\PollType;
use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\Models\Vote;
use App\Domain\Governance\Services\GovernanceService;
use App\Models\User;

beforeEach(function () {
    $this->governanceService = app(GovernanceService::class);
    $this->user = User::factory()->create();
});

describe('Poll Creation', function () {
    it('can create a basic poll', function () {
        $pollData = [
            'title' => 'Should we add JPY support?',
            'description' => 'Adding Japanese Yen as a supported currency',
            'type' => 'yes_no',
            'options' => [
                ['id' => 'yes', 'label' => 'Yes', 'description' => 'Add JPY support'],
                ['id' => 'no', 'label' => 'No', 'description' => 'Keep current currencies'],
            ],
            'start_date' => now()->addHour(),
            'end_date' => now()->addWeek(),
            'created_by' => $this->user->uuid,
        ];

        $poll = $this->governanceService->createPoll($pollData);

        expect($poll)->toBeInstanceOf(Poll::class);
        expect($poll->title)->toBe($pollData['title']);
        expect($poll->type)->toBe(PollType::YES_NO);
        expect($poll->status)->toBe(PollStatus::DRAFT);
        expect($poll->created_by)->toBe($this->user->uuid);
    });

    it('can create poll with execution workflow', function () {
        $pollData = [
            'title' => 'Enable two-factor authentication',
            'type' => 'yes_no',
            'options' => [
                ['id' => 'yes', 'label' => 'Yes'],
                ['id' => 'no', 'label' => 'No'],
            ],
            'start_date' => now()->addHour(),
            'end_date' => now()->addWeek(),
            'execution_workflow' => 'FeatureToggleWorkflow',
            'created_by' => $this->user->uuid,
        ];

        $poll = $this->governanceService->createPoll($pollData);

        expect($poll->execution_workflow)->toBe('FeatureToggleWorkflow');
    });
});

describe('Poll Activation', function () {
    it('can activate a draft poll', function () {
        $poll = Poll::factory()->draft()->create([
            'start_date' => now()->subMinute(),
            'end_date' => now()->addWeek(),
        ]);

        $result = $this->governanceService->activatePoll($poll);

        expect($result)->toBeTrue();
        expect($poll->fresh()->status)->toBe(PollStatus::ACTIVE);
    });

    it('cannot activate poll before start date', function () {
        $poll = Poll::factory()->draft()->create([
            'start_date' => now()->addHour(),
            'end_date' => now()->addWeek(),
        ]);

        expect(fn () => $this->governanceService->activatePoll($poll))
            ->toThrow(InvalidArgumentException::class, 'Poll cannot be activated before its start date');
    });

    it('cannot activate poll after end date', function () {
        $poll = Poll::factory()->draft()->create([
            'start_date' => now()->subWeek(),
            'end_date' => now()->subDay(),
        ]);

        expect(fn () => $this->governanceService->activatePoll($poll))
            ->toThrow(InvalidArgumentException::class, 'Poll cannot be activated after its end date');
    });

    it('cannot activate non-draft poll', function () {
        $poll = Poll::factory()->active()->create();

        expect(fn () => $this->governanceService->activatePoll($poll))
            ->toThrow(InvalidArgumentException::class, 'Only draft polls can be activated');
    });
});

describe('Voting', function () {
    it('can cast vote in active poll', function () {
        $poll = Poll::factory()->active()->yesNo()->oneUserOneVote()->create();

        $vote = $this->governanceService->castVote($poll, $this->user, ['yes']);

        expect($vote)->toBeInstanceOf(Vote::class);
        expect($vote->poll_id)->toBe($poll->id);
        expect($vote->user_uuid)->toBe($this->user->uuid);
        expect($vote->selected_options)->toBe(['yes']);
        expect($vote->voting_power)->toBe(1); // OneUserOneVote strategy
    });

    it('cannot vote twice in same poll', function () {
        $poll = Poll::factory()->active()->yesNo()->oneUserOneVote()->create();

        // First vote
        $this->governanceService->castVote($poll, $this->user, ['yes']);

        // Second vote attempt
        expect(fn () => $this->governanceService->castVote($poll, $this->user, ['no']))
            ->toThrow(InvalidArgumentException::class, 'User has already voted in this poll');
    });

    it('cannot vote in inactive poll', function () {
        $poll = Poll::factory()->draft()->yesNo()->oneUserOneVote()->create();

        expect(fn () => $this->governanceService->castVote($poll, $this->user, ['yes']))
            ->toThrow(InvalidArgumentException::class, 'Poll is not available for voting');
    });

    it('validates selected options', function () {
        $poll = Poll::factory()->active()->yesNo()->oneUserOneVote()->create();

        expect(fn () => $this->governanceService->castVote($poll, $this->user, ['invalid_option']))
            ->toThrow(InvalidArgumentException::class, 'Invalid option ID: invalid_option');
    });

    it('validates single choice constraint', function () {
        $poll = Poll::factory()->active()->singleChoice()->oneUserOneVote()->create();

        // Get actual option IDs from the poll
        $optionIds = array_column($poll->options, 'id');
        $twoOptions = array_slice($optionIds, 0, 2);

        expect(fn () => $this->governanceService->castVote($poll, $this->user, $twoOptions))
            ->toThrow(InvalidArgumentException::class, 'Exactly one option must be selected for single choice polls');
    });
});

describe('Voting Power', function () {
    it('calculates voting power with OneUserOneVote strategy', function () {
        $poll = Poll::factory()->oneUserOneVote()->create();

        $votingPower = $this->governanceService->getUserVotingPower($this->user, $poll);

        expect($votingPower)->toBe(1);
    });

    it('calculates voting power with AssetWeighted strategy', function () {
        $poll = Poll::factory()->assetWeighted()->create();

        // Create account with balance for user
        $account = App\Models\Account::factory()
            ->withBalance(1000000) // $10,000 = 100 voting power
            ->forUser($this->user)
            ->create();

        $votingPower = $this->governanceService->getUserVotingPower($this->user, $poll);

        expect($votingPower)->toBe(100); // $10,000 / $100 = 100 voting power
    });

    it('checks if user can vote', function () {
        $poll = Poll::factory()->active()->oneUserOneVote()->create();

        $canVote = $this->governanceService->canUserVote($this->user, $poll);

        expect($canVote)->toBeTrue();
    });

    it('prevents voting if user already voted', function () {
        $poll = Poll::factory()->active()->yesNo()->oneUserOneVote()->create();

        // Cast vote
        $this->governanceService->castVote($poll, $this->user, ['yes']);

        $canVote = $this->governanceService->canUserVote($this->user, $poll);

        expect($canVote)->toBeFalse();
    });
});

describe('Poll Completion', function () {
    it('can complete expired active poll', function () {
        $poll = Poll::factory()->active()->yesNo()->create([
            'end_date' => now()->subMinute(),
        ]);

        // Add some votes
        Vote::factory()->forPoll($poll)->yesVote()->create();
        Vote::factory()->forPoll($poll)->noVote()->create();

        $result = $this->governanceService->completePoll($poll);

        expect($poll->fresh()->status)->toBe(PollStatus::CLOSED);
        expect($result->totalVotes)->toBe(2);
        expect($result->hasWinner())->toBeTrue();
    });

    it('cannot complete non-expired poll', function () {
        $poll = Poll::factory()->active()->create([
            'end_date' => now()->addWeek(),
        ]);

        expect(fn () => $this->governanceService->completePoll($poll))
            ->toThrow(InvalidArgumentException::class, 'Poll has not expired yet');
    });

    it('cannot complete non-active poll', function () {
        $poll = Poll::factory()->draft()->create([
            'end_date' => now()->subMinute(),
        ]);

        expect(fn () => $this->governanceService->completePoll($poll))
            ->toThrow(InvalidArgumentException::class, 'Only active polls can be completed');
    });
});

describe('Poll Cancellation', function () {
    it('can cancel draft poll', function () {
        $poll = Poll::factory()->draft()->create();

        $result = $this->governanceService->cancelPoll($poll, 'No longer needed');

        expect($result)->toBeTrue();
        expect($poll->fresh()->status)->toBe(PollStatus::CANCELLED);
        expect($poll->fresh()->metadata['cancellation_reason'])->toBe('No longer needed');
    });

    it('can cancel active poll', function () {
        $poll = Poll::factory()->active()->create();

        $result = $this->governanceService->cancelPoll($poll);

        expect($result)->toBeTrue();
        expect($poll->fresh()->status)->toBe(PollStatus::CANCELLED);
    });

    it('cannot cancel completed poll', function () {
        $poll = Poll::factory()->completed()->create();

        expect(fn () => $this->governanceService->cancelPoll($poll))
            ->toThrow(InvalidArgumentException::class, 'Cannot cancel a completed poll');
    });
});

describe('Utility Methods', function () {
    it('can get active polls', function () {
        Poll::factory()->active()->count(3)->create();
        Poll::factory()->draft()->count(2)->create();

        $activePolls = $this->governanceService->getActivePolls();

        expect($activePolls)->toHaveCount(3);
        $activePolls->each(fn ($poll) => expect($poll->status)->toBe(PollStatus::ACTIVE));
    });

    it('can get poll results', function () {
        $poll = Poll::factory()->yesNo()->create();

        // Add votes
        Vote::factory()->forPoll($poll)->yesVote()->withHighVotingPower()->create();
        Vote::factory()->forPoll($poll)->noVote()->create();

        $results = $this->governanceService->getPollResults($poll);

        expect($results->totalVotes)->toBe(2);
        expect($results->winningOption)->toBe('yes');
    });

    it('can get available voting strategies', function () {
        $strategies = $this->governanceService->getAvailableVotingStrategies();

        expect($strategies)->toHaveCount(2);
        expect($strategies[0]['name'])->toBe('one_user_one_vote');
        expect($strategies[1]['name'])->toBe('asset_weighted_vote');
    });
});
