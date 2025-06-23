<?php

namespace App\Domain\Compliance\Workflows;

use Workflow\Activity;
use Workflow\Workflow;
use App\Domain\Compliance\Activities\VerifyIdentityDocumentsActivity;
use App\Domain\Compliance\Activities\CheckSanctionsListActivity;
use App\Domain\Compliance\Activities\UpdateUserKycStatusActivity;
use App\Domain\Compliance\Activities\NotifyUserActivity;
use App\Domain\Compliance\Activities\UpdateAccountLimitsActivity;

class KycVerificationWorkflow extends Workflow
{
    public function execute(array $input): array
    {
        $userUuid = $input['user_uuid'];
        $level = $input['level'] ?? 'standard';
        
        // Step 1: Verify identity documents
        $verificationResult = yield Activity::run(
            VerifyIdentityDocumentsActivity::class,
            [
                'user_uuid' => $userUuid,
                'level' => $level
            ]
        );

        if (!$verificationResult['passed']) {
            yield Activity::run(
                UpdateUserKycStatusActivity::class,
                [
                    'user_uuid' => $userUuid,
                    'status' => 'rejected',
                    'reason' => $verificationResult['reason']
                ]
            );

            yield Activity::run(
                NotifyUserActivity::class,
                [
                    'user_uuid' => $userUuid,
                    'type' => 'kyc_rejected',
                    'reason' => $verificationResult['reason']
                ]
            );

            return ['status' => 'rejected', 'reason' => $verificationResult['reason']];
        }

        // Step 2: Check sanctions lists
        $sanctionsResult = yield Activity::run(
            CheckSanctionsListActivity::class,
            ['user_uuid' => $userUuid]
        );

        if ($sanctionsResult['flagged']) {
            yield Activity::run(
                UpdateUserKycStatusActivity::class,
                [
                    'user_uuid' => $userUuid,
                    'status' => 'rejected',
                    'reason' => 'sanctions_list'
                ]
            );

            return ['status' => 'rejected', 'reason' => 'sanctions_list'];
        }

        // Step 3: Approve KYC
        yield Activity::run(
            UpdateUserKycStatusActivity::class,
            [
                'user_uuid' => $userUuid,
                'status' => 'approved',
                'level' => $level
            ]
        );

        // Step 4: Update account limits
        yield Activity::run(
            UpdateAccountLimitsActivity::class,
            [
                'user_uuid' => $userUuid,
                'kyc_level' => $level
            ]
        );

        // Step 5: Notify user
        yield Activity::run(
            NotifyUserActivity::class,
            [
                'user_uuid' => $userUuid,
                'type' => 'kyc_approved',
                'level' => $level
            ]
        );

        return [
            'status' => 'approved',
            'level' => $level
        ];
    }

    public function compensate(array $input): void
    {
        $userUuid = $input['user_uuid'];

        // Rollback to pending status
        yield Activity::run(
            UpdateUserKycStatusActivity::class,
            [
                'user_uuid' => $userUuid,
                'status' => 'pending_review'
            ]
        );
    }
}