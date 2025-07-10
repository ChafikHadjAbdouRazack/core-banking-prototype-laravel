<?php

namespace Tests\Unit\Domain\Lending\Aggregates;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\DomainTestCase;

class LoanApplicationTest extends DomainTestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_submit_loan_application_successfully(): void
    {
        $aggregate = LoanApplication::fake();

        $applicationId = 'loan-app-123';
        $borrowerId = 'borrower-456';
        $requestedAmount = '25000.00';
        $termMonths = 36;
        $purpose = 'debt_consolidation';
        $borrowerInfo = [
            'annual_income'     => '60000',
            'employment_status' => 'employed',
        ];

        LoanApplication::submit(
            $applicationId,
            $borrowerId,
            $requestedAmount,
            $termMonths,
            $purpose,
            $borrowerInfo
        );

        $aggregate->assertRecorded([
            new LoanApplicationSubmitted(
                $applicationId,
                $borrowerId,
                $requestedAmount,
                $termMonths,
                $purpose,
                $borrowerInfo,
                new \DateTimeImmutable()
            ),
        ]);
    }

    #[Test]
    public function test_submit_fails_with_zero_amount(): void
    {
        $this->expectException(LoanApplicationException::class);
        $this->expectExceptionMessage('Requested amount must be greater than zero');

        LoanApplication::submit(
            'app-123',
            'borrower-123',
            '0',
            24,
            'personal',
            []
        );
    }

    #[Test]
    public function test_submit_fails_with_negative_amount(): void
    {
        $this->expectException(LoanApplicationException::class);
        $this->expectExceptionMessage('Requested amount must be greater than zero');

        LoanApplication::submit(
            'app-123',
            'borrower-123',
            '-1000',
            24,
            'personal',
            []
        );
    }

    #[Test]
    public function test_submit_fails_with_invalid_term_too_short(): void
    {
        $this->expectException(LoanApplicationException::class);
        $this->expectExceptionMessage('Term must be between 1 and 360 months');

        LoanApplication::submit(
            'app-123',
            'borrower-123',
            '10000',
            0,
            'personal',
            []
        );
    }

    #[Test]
    public function test_submit_fails_with_invalid_term_too_long(): void
    {
        $this->expectException(LoanApplicationException::class);
        $this->expectExceptionMessage('Term must be between 1 and 360 months');

        LoanApplication::submit(
            'app-123',
            'borrower-123',
            '10000',
            361,
            'personal',
            []
        );
    }

    #[Test]
    public function test_complete_credit_check(): void
    {
        $aggregate = LoanApplication::fake();
        $applicationId = 'app-credit-check';

        // First submit the application
        $loanApp = LoanApplication::submit(
            $applicationId,
            'borrower-123',
            '50000',
            48,
            'business',
            []
        );

        // Complete credit check
        $loanApp->completeCreditCheck(
            score: 750,
            bureau: 'Experian',
            creditReport: ['accounts' => 5, 'delinquencies' => 0],
            checkedBy: 'system-auto'
        );

        $aggregate->assertRecorded(
            new LoanApplicationCreditCheckCompleted(
                applicationId: $applicationId,
                creditScore: 750,
                creditBureau: 'Experian',
                creditReport: ['accounts' => 5, 'delinquencies' => 0],
                checkedBy: 'system-auto',
                checkedAt: new \DateTimeImmutable()
            )
        );
    }

    #[Test]
    public function test_complete_risk_assessment(): void
    {
        $aggregate = LoanApplication::fake();
        $applicationId = 'app-risk-assessment';

        $loanApp = LoanApplication::submit(
            $applicationId,
            'borrower-456',
            '30000',
            24,
            'auto',
            []
        );

        $loanApp->completeRiskAssessment(
            riskScore: 85,
            riskLevel: 'low',
            factors: ['stable_income', 'good_credit', 'low_dti'],
            assessedBy: 'risk-engine-v2'
        );

        $aggregate->assertRecorded(
            new LoanApplicationRiskAssessmentCompleted(
                applicationId: $applicationId,
                riskScore: 85,
                riskLevel: 'low',
                riskFactors: ['stable_income', 'good_credit', 'low_dti'],
                assessedBy: 'risk-engine-v2',
                assessedAt: new \DateTimeImmutable()
            )
        );
    }

    #[Test]
    public function test_approve_application(): void
    {
        $aggregate = LoanApplication::fake();
        $applicationId = 'app-approval';

        $loanApp = LoanApplication::submit(
            $applicationId,
            'borrower-789',
            '40000',
            36,
            'home_improvement',
            []
        );

        // Complete required checks first
        $loanApp->completeCreditCheck(720, 'Equifax', [], 'system');
        $loanApp->completeRiskAssessment(80, 'medium', [], 'system');

        // Approve application
        $loanApp->approve(
            approvedAmount: '40000',
            interestRate: 7.5,
            monthlyPayment: '1244.56',
            approvedBy: 'loan-officer-123',
            conditions: ['employment_verification']
        );

        $aggregate->assertRecorded(
            new LoanApplicationApproved(
                applicationId: $applicationId,
                approvedAmount: '40000',
                interestRate: 7.5,
                termMonths: 36,
                monthlyPayment: '1244.56',
                conditions: ['employment_verification'],
                approvedBy: 'loan-officer-123',
                approvedAt: new \DateTimeImmutable()
            )
        );
    }

    #[Test]
    public function test_reject_application(): void
    {
        $aggregate = LoanApplication::fake();
        $applicationId = 'app-rejection';

        $loanApp = LoanApplication::submit(
            $applicationId,
            'borrower-321',
            '100000',
            60,
            'business',
            []
        );

        $loanApp->reject(
            reasons: ['credit_score_too_low', 'insufficient_income'],
            rejectedBy: 'auto-decisioning'
        );

        $aggregate->assertRecorded(
            new LoanApplicationRejected(
                applicationId: $applicationId,
                reasons: ['credit_score_too_low', 'insufficient_income'],
                rejectedBy: 'auto-decisioning',
                rejectedAt: new \DateTimeImmutable()
            )
        );
    }

    #[Test]
    public function test_withdraw_application(): void
    {
        $aggregate = LoanApplication::fake();
        $applicationId = 'app-withdrawal';

        $loanApp = LoanApplication::submit(
            $applicationId,
            'borrower-654',
            '20000',
            24,
            'personal',
            []
        );

        $loanApp->withdraw('Changed my mind');

        $aggregate->assertRecorded(
            new LoanApplicationWithdrawn(
                applicationId: $applicationId,
                reason: 'Changed my mind',
                withdrawnAt: new \DateTimeImmutable()
            )
        );
    }

    #[Test]
    public function test_cannot_approve_without_credit_check(): void
    {
        $loanApp = LoanApplication::submit(
            'app-no-credit',
            'borrower-111',
            '15000',
            12,
            'personal',
            []
        );

        $this->expectException(LoanApplicationException::class);
        $this->expectExceptionMessage('Cannot approve application without credit check');

        $loanApp->approve('15000', 8.0, '1328.25', 'officer-123');
    }

    #[Test]
    public function test_cannot_approve_without_risk_assessment(): void
    {
        $loanApp = LoanApplication::submit(
            'app-no-risk',
            'borrower-222',
            '25000',
            24,
            'auto',
            []
        );

        // Complete credit check but not risk assessment
        $loanApp->completeCreditCheck(700, 'TransUnion', [], 'system');

        $this->expectException(LoanApplicationException::class);
        $this->expectExceptionMessage('Cannot approve application without risk assessment');

        $loanApp->approve('25000', 9.0, '1142.22', 'officer-456');
    }

    #[Test]
    public function test_cannot_process_already_decided_application(): void
    {
        $loanApp = LoanApplication::submit(
            'app-decided',
            'borrower-333',
            '30000',
            36,
            'business',
            []
        );

        // Reject the application
        $loanApp->reject(['high_risk'], 'system');

        // Try to approve after rejection
        $this->expectException(LoanApplicationException::class);
        $this->expectExceptionMessage('Application has already been decided');

        $loanApp->completeCreditCheck(800, 'Experian', [], 'system');
    }

    #[Test]
    public function test_apply_events_updates_state(): void
    {
        $loanApp = new LoanApplication();

        // Apply submitted event
        $submittedEvent = new LoanApplicationSubmitted(
            'app-state-test',
            'borrower-state',
            '35000',
            48,
            'education',
            [],
            new \DateTimeImmutable()
        );

        $loanApp->applyLoanApplicationSubmitted($submittedEvent);

        // Use reflection to check private properties
        $reflection = new \ReflectionClass($loanApp);

        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setAccessible(true);
        $this->assertEquals('pending', $statusProperty->getValue($loanApp));

        $amountProperty = $reflection->getProperty('requestedAmount');
        $amountProperty->setAccessible(true);
        $this->assertEquals('35000', $amountProperty->getValue($loanApp));
    }
}
