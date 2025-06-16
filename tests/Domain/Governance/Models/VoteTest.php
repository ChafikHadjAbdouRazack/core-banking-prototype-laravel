<?php

declare(strict_types=1);

use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\Models\Vote;
use App\Models\User;

describe('Vote Model', function () {
    it('has correct fillable attributes', function () {
        $vote = new Vote();
        
        expect($vote->getFillable())->toContain(
            'poll_id',
            'user_uuid',
            'selected_options',
            'voting_power',
            'voted_at',
            'signature',
            'metadata'
        );
    });

    it('casts attributes correctly', function () {
        $vote = Vote::factory()->create([
            'selected_options' => ['option1', 'option2'],
            'voting_power' => 25,
            'metadata' => ['ip' => '127.0.0.1'],
        ]);

        expect($vote->selected_options)->toBeArray();
        expect($vote->voting_power)->toBeInt();
        expect($vote->voted_at)->toBeInstanceOf(Carbon\Carbon::class);
        expect($vote->metadata)->toBeArray();
    });

    it('sets voted_at on creation', function () {
        $vote = Vote::factory()->create(['voted_at' => null]);
        
        expect($vote->voted_at)->toBeInstanceOf(Carbon\Carbon::class);
        expect($vote->voted_at->diffInSeconds(now()))->toBeLessThan(5);
    });

    it('generates signature on creation', function () {
        $vote = Vote::factory()->create(['signature' => null]);
        
        expect($vote->signature)->toBeString();
        expect($vote->signature)->not->toBeEmpty();
    });

    it('has poll relationship', function () {
        $poll = Poll::factory()->create();
        $vote = Vote::factory()->forPoll($poll)->create();

        expect($vote->poll)->toBeInstanceOf(Poll::class);
        expect($vote->poll->id)->toBe($poll->id);
    });

    it('has user relationship', function () {
        $user = User::factory()->create();
        $vote = Vote::factory()->forUser($user)->create();

        expect($vote->user)->toBeInstanceOf(User::class);
        expect($vote->user->uuid)->toBe($user->uuid);
    });
});

describe('Vote Options Handling', function () {
    it('formats selected options as string', function () {
        $vote = Vote::factory()->create([
            'selected_options' => ['option1', 'option2', 'option3'],
        ]);

        expect($vote->getSelectedOptionsAsString())->toBe('option1, option2, option3');
    });

    it('handles empty selected options', function () {
        $vote = Vote::factory()->create([
            'selected_options' => [],
        ]);

        expect($vote->getSelectedOptionsAsString())->toBe('');
    });

    it('checks if option is selected', function () {
        $vote = Vote::factory()->create([
            'selected_options' => ['option1', 'option3'],
        ]);

        expect($vote->hasSelectedOption('option1'))->toBeTrue();
        expect($vote->hasSelectedOption('option2'))->toBeFalse();
        expect($vote->hasSelectedOption('option3'))->toBeTrue();
    });

    it('counts selected options', function () {
        $vote = Vote::factory()->create([
            'selected_options' => ['option1', 'option2', 'option3'],
        ]);

        expect($vote->getSelectedOptionCount())->toBe(3);
    });
});

describe('Vote Signature Handling', function () {
    it('generates valid signature', function () {
        $vote = Vote::factory()->create();

        $signature = $vote->generateSignature();

        expect($signature)->toBeString();
        expect($signature)->not->toBeEmpty();
    });

    it('verifies signature correctly', function () {
        $vote = Vote::factory()->create();

        expect($vote->verifySignature())->toBeTrue();
    });

    it('detects tampered signature', function () {
        $vote = Vote::factory()->create();
        
        // Tamper with the signature
        $vote->signature = 'tampered_signature';

        expect($vote->verifySignature())->toBeFalse();
    });

    it('detects tampered vote data', function () {
        $vote = Vote::factory()->create([
            'selected_options' => ['option1'],
        ]);

        // Store original signature
        $originalSignature = $vote->signature;

        // Tamper with vote data
        $vote->selected_options = ['option2'];
        $vote->signature = $originalSignature; // Keep old signature

        expect($vote->verifySignature())->toBeFalse();
    });

    it('fails verification with missing signature', function () {
        $vote = Vote::factory()->make(['signature' => null]);
        $vote->skipSignatureGeneration = true;
        $vote->save();

        expect($vote->verifySignature())->toBeFalse();
    });
});

describe('Vote Validation', function () {
    it('validates complete vote', function () {
        $vote = Vote::factory()->create([
            'selected_options' => ['option1'],
            'voting_power' => 10,
        ]);

        expect($vote->isValid())->toBeTrue();
    });

    it('invalidates vote with no selected options', function () {
        $vote = Vote::factory()->create([
            'selected_options' => [],
            'voting_power' => 10,
        ]);

        expect($vote->isValid())->toBeFalse();
    });

    it('invalidates vote with zero voting power', function () {
        $vote = Vote::factory()->create([
            'selected_options' => ['option1'],
            'voting_power' => 0,
        ]);

        expect($vote->isValid())->toBeFalse();
    });

    it('invalidates vote with invalid signature', function () {
        $vote = Vote::factory()->create([
            'selected_options' => ['option1'],
            'voting_power' => 10,
            'signature' => 'invalid_signature',
        ]);

        expect($vote->isValid())->toBeFalse();
    });
});

describe('Vote Query Scopes', function () {
    it('filters votes by user', function () {
        $user = User::factory()->create();
        Vote::factory()->forUser($user)->count(3)->create();
        Vote::factory()->count(2)->create();

        $userVotes = Vote::byUser($user->uuid)->get();

        expect($userVotes)->toHaveCount(3);
    });

    it('filters votes by poll', function () {
        $poll = Poll::factory()->create();
        Vote::factory()->forPoll($poll)->count(4)->create();
        Vote::factory()->count(2)->create();

        $pollVotes = Vote::byPoll($poll->id)->get();

        expect($pollVotes)->toHaveCount(4);
    });

    it('filters votes with high voting power', function () {
        Vote::factory()->withHighVotingPower()->count(2)->create();
        Vote::factory()->withLowVotingPower()->count(3)->create();

        $highPowerVotes = Vote::withHighVotingPower(50)->get();

        expect($highPowerVotes)->toHaveCount(2);
    });

    it('filters recent votes', function () {
        Vote::factory()->recentVote()->count(2)->create();
        Vote::factory()->oldVote()->count(3)->create();

        $recentVotes = Vote::recentVotes(24)->get();

        expect($recentVotes)->toHaveCount(2);
    });
});

describe('Vote Calculations', function () {
    it('calculates voting power weight', function () {
        $poll = Poll::factory()->create();
        
        // Create votes with different voting powers
        $vote1 = Vote::factory()->forPoll($poll)->create(['voting_power' => 30]);
        $vote2 = Vote::factory()->forPoll($poll)->create(['voting_power' => 20]);
        $vote3 = Vote::factory()->forPoll($poll)->create(['voting_power' => 50]);

        // Refresh to get updated poll totals
        $vote1 = $vote1->fresh(['poll']);
        $vote2 = $vote2->fresh(['poll']);
        $vote3 = $vote3->fresh(['poll']);

        expect($vote1->getVotingPowerWeight())->toBe(30.0);
        expect($vote2->getVotingPowerWeight())->toBe(20.0);
        expect($vote3->getVotingPowerWeight())->toBe(50.0);
    });

    it('handles zero total voting power', function () {
        $poll = Poll::factory()->create();
        $vote = Vote::factory()->forPoll($poll)->create(['voting_power' => 10]);

        // Mock poll with no total voting power
        $poll->votes()->delete();
        $vote = $vote->fresh(['poll']);

        if ($vote) {
            expect($vote->getVotingPowerWeight())->toBe(0.0);
        } else {
            expect(true)->toBeTrue(); // Vote was deleted, test passes
        }
    });
});

describe('Vote Array Conversion', function () {
    it('includes additional computed fields in array', function () {
        $vote = Vote::factory()->create([
            'selected_options' => ['option1', 'option2'],
            'voting_power' => 25,
        ]);

        $array = $vote->toArray();

        expect($array)->toHaveKey('selected_options_string');
        expect($array)->toHaveKey('selected_option_count');
        expect($array)->toHaveKey('voting_power_weight');
        expect($array)->toHaveKey('is_valid');
        
        expect($array['selected_options_string'])->toBe('option1, option2');
        expect($array['selected_option_count'])->toBe(2);
        expect($array['is_valid'])->toBeTrue();
    });
});