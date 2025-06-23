<?php

namespace App\Domain\Compliance\Workflows;

use Workflow\Activity;
use Workflow\Workflow;
use App\Domain\Compliance\Activities\ValidateKycDocumentsActivity;
use App\Domain\Compliance\Activities\StoreKycDocumentsActivity;
use App\Domain\Compliance\Activities\NotifyComplianceTeamActivity;
use App\Domain\Compliance\Activities\UpdateUserKycStatusActivity;

class KycSubmissionWorkflow extends Workflow
{
    public function execute(array $input): array
    {
        $userUuid = $input['user_uuid'];
        $documents = $input['documents'];

        // Step 1: Validate documents
        $validationResult = yield Activity::run(
            ValidateKycDocumentsActivity::class,
            ['documents' => $documents]
        );

        if (!$validationResult['valid']) {
            yield Activity::run(
                UpdateUserKycStatusActivity::class,
                [
                    'user_uuid' => $userUuid,
                    'status' => 'rejected',
                    'reason' => $validationResult['errors']
                ]
            );

            return ['status' => 'rejected', 'errors' => $validationResult['errors']];
        }

        // Step 2: Store documents securely
        $storageResult = yield Activity::run(
            StoreKycDocumentsActivity::class,
            [
                'user_uuid' => $userUuid,
                'documents' => $documents
            ]
        );

        // Step 3: Update status to pending review
        yield Activity::run(
            UpdateUserKycStatusActivity::class,
            [
                'user_uuid' => $userUuid,
                'status' => 'pending_review'
            ]
        );

        // Step 4: Notify compliance team
        yield Activity::run(
            NotifyComplianceTeamActivity::class,
            [
                'user_uuid' => $userUuid,
                'submission_id' => $storageResult['submission_id']
            ]
        );

        return [
            'status' => 'submitted',
            'submission_id' => $storageResult['submission_id']
        ];
    }

    public function compensate(array $input): void
    {
        $userUuid = $input['user_uuid'];

        // Rollback status change
        yield Activity::run(
            UpdateUserKycStatusActivity::class,
            [
                'user_uuid' => $userUuid,
                'status' => 'not_submitted'
            ]
        );
    }
}