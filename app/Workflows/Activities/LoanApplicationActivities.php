<?php

namespace App\Workflows\Activities;

use App\Domain\Lending\Services\CreditScoringService;
use App\Domain\Lending\Services\RiskAssessmentService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LoanApplicationActivities
{
    public function __construct(
        private CreditScoringService $creditScoring,
        private RiskAssessmentService $riskAssessment
    ) {
    }

    public function fetchApplicantData(string $userId): array
    {
        $user = User::find($userId);
        if (! $user) {
            throw new \Exception('User not found');
        }

        // Fetch KYC data, account history, etc.
        return [
            'user_id'          => $userId,
            'kyc_verified'     => $user->kyc_verified_at !== null,
            'account_age_days' => $user->created_at->diffInDays(now()),
            'total_deposits'   => DB::table('transactions')
                ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
                ->where('accounts.user_id', $userId)
                ->where('transactions.type', 'credit')
                ->sum('transactions.amount'),
        ];
    }

    public function verifyCreditEligibility(array $applicantData, string $amount): array
    {
        if (! $applicantData['kyc_verified']) {
            return ['eligible' => false, 'reason' => 'KYC not verified'];
        }

        if ($applicantData['account_age_days'] < 30) {
            return ['eligible' => false, 'reason' => 'Account too new'];
        }

        $requestedAmount = (float) $amount;
        $maxLoanAmount = $applicantData['total_deposits'] * 0.5; // 50% of total deposits

        if ($requestedAmount > $maxLoanAmount) {
            return ['eligible' => false, 'reason' => 'Amount exceeds limit'];
        }

        return ['eligible' => true, 'max_amount' => $maxLoanAmount];
    }

    public function performCreditCheck(string $userId): array
    {
        // Integrate with credit scoring service
        $score = $this->creditScoring->getScore($userId);

        return [
            'credit_score' => $score,
            'rating'       => $this->getCreditRating($score),
        ];
    }

    private function getCreditRating(int $score): string
    {
        return match (true) {
            $score >= 750 => 'excellent',
            $score >= 700 => 'good',
            $score >= 650 => 'fair',
            $score >= 600 => 'poor',
            default       => 'very_poor'
        };
    }

    public function assessRisk(
        array $applicantData,
        array $creditData,
        string $amount,
        int $termMonths
    ): array {
        $riskScore = $this->riskAssessment->calculateRiskScore(
            $applicantData,
            $creditData,
            $amount,
            $termMonths
        );

        return [
            'risk_score' => $riskScore,
            'risk_level' => $this->getRiskLevel($riskScore),
            'approved'   => $riskScore < 70, // Approve if risk score < 70
        ];
    }

    private function getRiskLevel(int $score): string
    {
        return match (true) {
            $score < 30 => 'low',
            $score < 50 => 'medium',
            $score < 70 => 'high',
            default     => 'very_high'
        };
    }

    public function calculateTerms(
        array $creditData,
        array $riskData,
        string $amount,
        int $termMonths
    ): array {
        // Base interest rate
        $baseRate = 0.05; // 5%

        // Adjust based on credit score
        $creditAdjustment = match ($creditData['rating']) {
            'excellent' => 0,
            'good'      => 0.01,
            'fair'      => 0.02,
            'poor'      => 0.03,
            default     => 0.05
        };

        // Adjust based on risk
        $riskAdjustment = match ($riskData['risk_level']) {
            'low'    => 0,
            'medium' => 0.01,
            'high'   => 0.02,
            default  => 0.03
        };

        $interestRate = $baseRate + $creditAdjustment + $riskAdjustment;

        // Calculate monthly payment
        $principal = (float) $amount;
        $monthlyRate = $interestRate / 12;
        $monthlyPayment = ($principal * $monthlyRate) / (1 - pow(1 + $monthlyRate, -$termMonths));

        return [
            'interest_rate'   => $interestRate,
            'monthly_payment' => round($monthlyPayment, 2),
            'total_payment'   => round($monthlyPayment * $termMonths, 2),
            'total_interest'  => round(($monthlyPayment * $termMonths) - $principal, 2),
        ];
    }

    public function requiresManualReview(array $riskData): bool
    {
        return $riskData['risk_level'] === 'high' || $riskData['risk_level'] === 'very_high';
    }

    public function waitForManualReview(string $applicationId): array
    {
        // In production, this would wait for actual manual review
        // For now, simulate with a delay
        sleep(5);

        return [
            'approved'       => true,
            'reviewer_notes' => 'Approved after manual review',
            'reviewed_at'    => now()->toIso8601String(),
        ];
    }

    public function createLoanApplication(
        string $applicationId,
        string $userId,
        string $amount,
        int $termMonths,
        array $terms,
        array $creditData,
        array $riskData
    ): void {
        DB::table('loan_applications')->insert([
            'application_id'  => $applicationId,
            'user_id'         => $userId,
            'amount'          => $amount,
            'term_months'     => $termMonths,
            'interest_rate'   => $terms['interest_rate'],
            'monthly_payment' => $terms['monthly_payment'],
            'credit_score'    => $creditData['credit_score'],
            'credit_rating'   => $creditData['rating'],
            'risk_score'      => $riskData['risk_score'],
            'risk_level'      => $riskData['risk_level'],
            'status'          => 'pending',
            'created_at'      => now(),
        ]);
    }

    public function updateApplicationStatus(
        string $applicationId,
        string $status,
        ?string $reason = null
    ): void {
        $update = [
            'status'     => $status,
            'updated_at' => now(),
        ];

        if ($reason) {
            $update['rejection_reason'] = $reason;
        }

        if ($status === 'approved') {
            $update['approved_at'] = now();
        } elseif ($status === 'rejected') {
            $update['rejected_at'] = now();
        }

        DB::table('loan_applications')
            ->where('application_id', $applicationId)
            ->update($update);
    }

    public function createLoanAccount(string $applicationId): string
    {
        $application = DB::table('loan_applications')
            ->where('application_id', $applicationId)
            ->first();

        if (! $application) {
            throw new \Exception('Application not found');
        }

        // Create loan account
        $loanId = \Str::uuid()->toString();

        DB::table('loans')->insert([
            'loan_id'           => $loanId,
            'application_id'    => $applicationId,
            'user_id'           => $application->user_id,
            'principal_amount'  => $application->amount,
            'interest_rate'     => $application->interest_rate,
            'term_months'       => $application->term_months,
            'monthly_payment'   => $application->monthly_payment,
            'remaining_balance' => $application->amount,
            'status'            => 'active',
            'disbursed_at'      => now(),
            'next_payment_date' => now()->addMonth(),
            'created_at'        => now(),
        ]);

        return $loanId;
    }

    public function disburseFunds(string $userId, string $amount): void
    {
        // Get user's primary account
        $account = DB::table('accounts')
            ->where('user_id', $userId)
            ->where('currency', 'USD')
            ->where('status', 'active')
            ->first();

        if (! $account) {
            throw new \Exception('No active account found');
        }

        // Credit the loan amount
        DB::table('transactions')->insert([
            'account_id'  => $account->id,
            'type'        => 'credit',
            'amount'      => $amount,
            'description' => 'Loan disbursement',
            'created_at'  => now(),
        ]);

        // Update account balance
        DB::table('accounts')
            ->where('id', $account->id)
            ->increment('balance', $amount);
    }

    public function notifyApplicant(string $userId, string $applicationId, string $status): void
    {
        DB::table('notifications')->insert([
            'user_id' => $userId,
            'type'    => 'loan_application',
            'data'    => json_encode([
                'application_id' => $applicationId,
                'status'         => $status,
            ]),
            'created_at' => now(),
        ]);
    }

    public function compensateFailedApplication(string $applicationId): void
    {
        // Update application status
        DB::table('loan_applications')
            ->where('application_id', $applicationId)
            ->update([
                'status'     => 'failed',
                'updated_at' => now(),
            ]);

        // If funds were disbursed, reverse them
        $loan = DB::table('loans')
            ->where('application_id', $applicationId)
            ->first();

        if ($loan) {
            // Mark loan as cancelled
            DB::table('loans')
                ->where('loan_id', $loan->loan_id)
                ->update([
                    'status'       => 'cancelled',
                    'cancelled_at' => now(),
                ]);

            // Reverse any disbursed funds
            $transactions = DB::table('transactions')
                ->where('description', 'Loan disbursement')
                ->where('created_at', '>=', $loan->created_at)
                ->get();

            foreach ($transactions as $transaction) {
                DB::table('transactions')->insert([
                    'account_id'  => $transaction->account_id,
                    'type'        => 'debit',
                    'amount'      => $transaction->amount,
                    'description' => 'Loan disbursement reversal',
                    'reference'   => $transaction->id,
                    'created_at'  => now(),
                ]);

                DB::table('accounts')
                    ->where('id', $transaction->account_id)
                    ->decrement('balance', $transaction->amount);
            }
        }
    }
}
