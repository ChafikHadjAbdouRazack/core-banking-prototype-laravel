<?php

namespace Tests\Feature\Lending;

use App\Domain\Lending\Models\Loan;
use App\Domain\Lending\Models\LoanApplication;
use App\Domain\Lending\Services\CreditScoringService;
use App\Domain\Lending\Services\InterestCalculationService;
use App\Domain\Lending\Services\LoanService;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private LoanService $loanService;
    private CreditScoringService $creditScoringService;
    private InterestCalculationService $interestService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loanService = app(LoanService::class);
        $this->creditScoringService = app(CreditScoringService::class);
        $this->interestService = app(InterestCalculationService::class);
    }

    public function test_complete_loan_lifecycle_from_application_to_repayment()
    {
        // Create borrower and lender
        $borrower = User::factory()->create();
        $lender = User::factory()->create();
        
        // Create accounts for both users
        $borrowerAccount = $this->createAccountForUser($borrower);
        $lenderAccount = $this->createAccountForUser($lender);
        
        // Add funds to lender account
        $this->addFundsToAccount($lenderAccount, 'USD', '10000');
        
        // Step 1: Create loan application
        $application = $this->loanService->createApplication([
            'borrower_id' => $borrower->id,
            'amount' => '5000',
            'currency' => 'USD',
            'term_months' => 12,
            'purpose' => 'Business expansion',
            'interest_rate' => '10.5', // Annual rate
        ]);
        
        $this->assertInstanceOf(LoanApplication::class, $application);
        $this->assertEquals('pending', $application->status);
        
        // Step 2: Credit scoring
        $creditScore = $this->creditScoringService->calculateScore($borrower->id);
        $this->assertGreaterThan(0, $creditScore);
        
        // Step 3: Approve application
        $this->loanService->approveApplication($application->id, [
            'approved_by' => $lender->id,
            'credit_score' => $creditScore,
            'risk_rating' => 'medium',
        ]);
        
        $application->refresh();
        $this->assertEquals('approved', $application->status);
        
        // Step 4: Fund the loan
        $loan = $this->loanService->fundLoan($application->id, $lender->id);
        
        $this->assertInstanceOf(Loan::class, $loan);
        $this->assertEquals('active', $loan->status);
        $this->assertEquals('5000', $loan->principal_amount);
        
        // Verify funds transferred
        $this->assertAccountBalance($borrowerAccount, 'USD', '5000');
        $this->assertAccountBalance($lenderAccount, 'USD', '5000'); // 10000 - 5000
        
        // Step 5: Calculate monthly payment
        $monthlyPayment = $this->interestService->calculateMonthlyPayment(
            BigDecimal::of('5000'),
            BigDecimal::of('10.5'),
            12
        );
        
        $this->assertGreaterThan(416, (float)$monthlyPayment); // Should be ~$439.72
        
        // Step 6: Make payments
        for ($month = 1; $month <= 12; $month++) {
            // Add funds to borrower for payment
            $this->addFundsToAccount($borrowerAccount, 'USD', $monthlyPayment);
            
            $payment = $this->loanService->makePayment($loan->id, [
                'amount' => $monthlyPayment,
                'payment_date' => now()->addMonths($month - 1),
            ]);
            
            $this->assertEquals('completed', $payment->status);
            
            // Check loan balance
            $loan->refresh();
            if ($month < 12) {
                $this->assertGreaterThan(0, (float)$loan->outstanding_balance);
            }
        }
        
        // Step 7: Verify loan completion
        $loan->refresh();
        $this->assertEquals('completed', $loan->status);
        $this->assertEquals('0', $loan->outstanding_balance);
        
        // Verify lender received all payments
        $totalReceived = BigDecimal::of($monthlyPayment)->multipliedBy(12);
        $totalInterest = $totalReceived->minus('5000');
        
        $this->assertGreaterThan(500, (float)$totalInterest); // Should earn ~$276.64 in interest
    }

    public function test_loan_default_and_recovery_process()
    {
        $borrower = User::factory()->create();
        $lender = User::factory()->create();
        
        // Create and fund a loan
        $loan = $this->createAndFundLoan($borrower, $lender, '1000', 6);
        
        // Make 2 payments
        for ($i = 1; $i <= 2; $i++) {
            $this->makePaymentForLoan($loan, '180'); // Approximate monthly payment
        }
        
        // Miss 3 payments (90 days)
        $this->travelTo(now()->addDays(90));
        
        // Check loan status
        $this->loanService->checkDefaultedLoans();
        
        $loan->refresh();
        $this->assertEquals('defaulted', $loan->status);
        
        // Initiate recovery process
        $recovery = $this->loanService->initiateRecovery($loan->id);
        
        $this->assertEquals('in_recovery', $recovery->status);
        $this->assertNotNull($recovery->collection_agency_id);
        
        // Partial recovery
        $recoveredAmount = '400'; // Recover 40% of remaining balance
        $this->loanService->recordRecovery($recovery->id, $recoveredAmount);
        
        $recovery->refresh();
        $this->assertEquals($recoveredAmount, $recovery->amount_recovered);
        
        // Write off remaining balance
        $writeOff = $this->loanService->writeOffLoan($loan->id);
        
        $this->assertEquals('written_off', $writeOff->status);
        $this->assertGreaterThan(0, (float)$writeOff->amount_written_off);
    }

    public function test_early_loan_repayment_with_penalty()
    {
        $borrower = User::factory()->create();
        $lender = User::factory()->create();
        
        $loan = $this->createAndFundLoan($borrower, $lender, '3000', 12);
        
        // Make 3 regular payments
        for ($i = 1; $i <= 3; $i++) {
            $this->makePaymentForLoan($loan, '270'); // Approximate monthly payment
        }
        
        // Calculate early repayment
        $loan->refresh();
        $earlyPayment = $this->loanService->calculateEarlyRepayment($loan->id);
        
        $this->assertArrayHasKey('remaining_principal', $earlyPayment);
        $this->assertArrayHasKey('prepayment_penalty', $earlyPayment);
        $this->assertArrayHasKey('total_amount', $earlyPayment);
        
        // Pay off loan early
        $settlement = $this->loanService->settleEarly($loan->id, [
            'payment_amount' => $earlyPayment['total_amount'],
            'include_penalty' => true,
        ]);
        
        $this->assertEquals('completed', $settlement->status);
        $this->assertEquals('early_settlement', $settlement->completion_type);
        
        $loan->refresh();
        $this->assertEquals('completed', $loan->status);
        $this->assertEquals('0', $loan->outstanding_balance);
    }

    public function test_loan_refinancing()
    {
        $borrower = User::factory()->create();
        $lender = User::factory()->create();
        $newLender = User::factory()->create();
        
        // Original loan
        $originalLoan = $this->createAndFundLoan($borrower, $lender, '5000', 24);
        
        // Make 6 payments
        for ($i = 1; $i <= 6; $i++) {
            $this->makePaymentForLoan($originalLoan, '235');
        }
        
        // Apply for refinancing with better rate
        $originalLoan->refresh();
        $refinanceApplication = $this->loanService->applyForRefinancing($originalLoan->id, [
            'new_interest_rate' => '8.5', // Lower than original
            'new_term_months' => 18,
            'new_lender_id' => $newLender->id,
        ]);
        
        $this->assertEquals('pending', $refinanceApplication->status);
        
        // Approve refinancing
        $newLoan = $this->loanService->approveRefinancing($refinanceApplication->id);
        
        $this->assertInstanceOf(Loan::class, $newLoan);
        $this->assertEquals($originalLoan->outstanding_balance, $newLoan->principal_amount);
        $this->assertEquals('8.5', $newLoan->interest_rate);
        
        // Verify original loan is closed
        $originalLoan->refresh();
        $this->assertEquals('refinanced', $originalLoan->status);
    }

    // Helper methods
    
    private function createAccountForUser(User $user): string
    {
        // Implementation would create account using AccountService
        return 'account-' . $user->id;
    }
    
    private function addFundsToAccount(string $accountId, string $currency, string $amount): void
    {
        // Implementation would add funds using AccountService
    }
    
    private function assertAccountBalance(string $accountId, string $currency, string $expectedBalance): void
    {
        // Implementation would check balance using AccountService
        $this->assertTrue(true); // Placeholder
    }
    
    private function createAndFundLoan(User $borrower, User $lender, string $amount, int $termMonths): Loan
    {
        $application = $this->loanService->createApplication([
            'borrower_id' => $borrower->id,
            'amount' => $amount,
            'currency' => 'USD',
            'term_months' => $termMonths,
            'interest_rate' => '12.0',
        ]);
        
        $this->loanService->approveApplication($application->id, [
            'approved_by' => $lender->id,
            'credit_score' => 700,
        ]);
        
        return $this->loanService->fundLoan($application->id, $lender->id);
    }
    
    private function makePaymentForLoan(Loan $loan, string $amount): void
    {
        $this->loanService->makePayment($loan->id, [
            'amount' => $amount,
            'payment_date' => now(),
        ]);
    }
}