<?php

namespace Tests\Feature\Lending;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\ControllerTestCase;

class LendingControllerTest extends ControllerTestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $accountId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user
        $this->user = User::factory()->create();

        // Create account for the user
        $this->accountId = (string) Str::uuid();
        Account::create(
            $this->accountId,
            new AccountData(
                userId: $this->user->id,
                name: 'Test Account',
                type: AccountType::PERSONAL,
                status: AccountStatus::ACTIVE,
                metadata: []
            )
        )->deposit('50000.00', 'USD', 'Initial deposit')->persist();
    }

    #[Test]
    public function test_can_access_lending_dashboard(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('lending.index'));

        $response->assertStatus(200);
        $response->assertViewIs('lending.index');
    }

    #[Test]
    public function test_can_access_loan_application_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('lending.apply'));

        $response->assertStatus(200);
        $response->assertViewIs('lending.apply');
    }

    #[Test]
    public function test_can_submit_loan_application(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('lending.apply.submit'), [
                'amount'              => '10000',
                'term_months'         => '12',
                'purpose'             => LoanPurpose::PERSONAL->value,
                'purpose_description' => 'Personal expenses',
                'employment_status'   => EmploymentStatus::EMPLOYED->value,
                'monthly_income'      => '5000',
                'monthly_expenses'    => '2000',
                'collateral'          => [],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify application was created
        $this->assertDatabaseHas('loan_applications', [
            'amount'            => '10000.00',
            'term_months'       => 12,
            'purpose'           => LoanPurpose::PERSONAL->value,
            'employment_status' => EmploymentStatus::EMPLOYED->value,
        ]);
    }

    #[Test]
    public function test_cannot_submit_invalid_loan_application(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('lending.apply.submit'), [
                'amount'            => '-1000', // Invalid negative amount
                'term_months'       => '999', // Too long term
                'purpose'           => 'invalid_purpose',
                'employment_status' => 'invalid_status',
                'monthly_income'    => '0',
                'monthly_expenses'  => '10000',
            ]);

        $response->assertSessionHasErrors(['amount', 'term_months', 'purpose', 'employment_status']);
    }

    #[Test]
    public function test_guest_cannot_access_lending(): void
    {
        $response = $this->get(route('lending.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('lending.apply'));
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function test_can_view_loan_details(): void
    {
        // Create a test loan
        $loanId = (string) Str::uuid();
        \App\Models\Loan::create([
            'id'                    => $loanId,
            'application_id'        => Str::uuid(),
            'borrower_account_uuid' => $this->accountId,
            'lender_account_uuid'   => null,
            'amount'                => '10000.00',
            'interest_rate'         => 8.5,
            'term_months'           => 12,
            'status'                => 'active',
            'disbursed_at'          => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('lending.loan', $loanId));

        $response->assertStatus(200);
        $response->assertViewIs('lending.loan');
        $response->assertViewHas('loan');
    }

    #[Test]
    public function test_can_access_repayment_form(): void
    {
        // Create a test loan
        $loanId = (string) Str::uuid();
        \App\Models\Loan::create([
            'id'                    => $loanId,
            'application_id'        => Str::uuid(),
            'borrower_account_uuid' => $this->accountId,
            'lender_account_uuid'   => null,
            'amount'                => '10000.00',
            'interest_rate'         => 8.5,
            'term_months'           => 12,
            'status'                => 'active',
            'disbursed_at'          => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('lending.repay', $loanId));

        $response->assertStatus(200);
        $response->assertViewIs('lending.repay');
    }

    #[Test]
    public function test_subproduct_page_has_correct_route(): void
    {
        $response = $this->get('/subproducts/lending');

        $response->assertStatus(200);
        $response->assertSee('route(\'lending.index\')', false);
        $response->assertDontSee('route(\'loans.index\')', false);
    }
}
