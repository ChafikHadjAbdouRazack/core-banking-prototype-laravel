<?php

namespace Tests\Feature;

use App\Domain\Account\Models\Account;
use App\Domain\Account\Models\TransactionProjection;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\ControllerTestCase;

class FundFlowControllerTest extends ControllerTestCase
{
    // RefreshDatabase is already used in parent TestCase

    protected User $user;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->account = Account::factory()->create([
            'user_uuid' => $this->user->uuid,
            'name'      => 'Test Account',
        ]);
    }

    #[Test]
    public function test_user_can_view_fund_flow_visualization()
    {
        $this->actingAs($this->user);

        $response = $this->get('/fund-flow');

        $response->assertStatus(200);
        $response->assertViewIs('fund-flow.index');
        $response->assertViewHas('accounts');
        $response->assertViewHas('flowData');
        $response->assertViewHas('statistics');
        $response->assertViewHas('networkData');
        $response->assertViewHas('chartData');
        $response->assertViewHas('filters');
    }

    #[Test]
    public function test_user_can_filter_fund_flow_by_period()
    {
        $this->actingAs($this->user);

        $response = $this->get('/fund-flow?period=30days');

        $response->assertStatus(200);
        $response->assertViewIs('fund-flow.index');
        $response->assertViewHas('filters', function ($filters) {
            return $filters['period'] === '30days';
        });
    }

    #[Test]
    public function test_user_can_filter_fund_flow_by_account()
    {
        $this->actingAs($this->user);

        $response = $this->get('/fund-flow?account=' . $this->account->uuid);

        $response->assertStatus(200);
        $response->assertViewIs('fund-flow.index');
        $response->assertViewHas('filters', [
            'period'    => '7days',
            'account'   => $this->account->uuid,
            'flow_type' => 'all',
        ]);
    }

    #[Test]
    public function test_user_can_filter_fund_flow_by_type()
    {
        $this->actingAs($this->user);

        $response = $this->get('/fund-flow?flow_type=deposit');

        $response->assertStatus(200);
        $response->assertViewIs('fund-flow.index');
        $response->assertViewHas('filters', function ($filters) {
            return $filters['flow_type'] === 'deposit';
        });
    }

    #[Test]
    public function test_user_can_view_account_fund_flow_details()
    {
        $this->actingAs($this->user);

        $response = $this->get('/fund-flow/account/' . $this->account->uuid);

        $response->assertStatus(200);
        $response->assertJson([
            'account'        => [],
            'inflows'        => [],
            'outflows'       => [],
            'flowBalance'    => [],
            'counterparties' => [],
        ]);
    }

    #[Test]
    public function test_user_cannot_view_other_users_account_flow()
    {
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherAccount = Account::factory()->create([
            'user_uuid' => $otherUser->uuid,
        ]);

        $this->actingAs($this->user);

        $response = $this->get('/fund-flow/account/' . $otherAccount->uuid);

        $response->assertStatus(404);
    }

    #[Test]
    public function test_user_can_export_fund_flow_data()
    {
        $this->actingAs($this->user);

        $response = $this->get('/fund-flow/data');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'flows',
            'period',
            'generated_at',
        ]);
    }

    #[Test]
    public function test_fund_flow_statistics_calculate_correctly()
    {
        $this->actingAs($this->user);

        // Create some transactions
        TransactionProjection::create([
            'account_uuid' => $this->account->uuid,
            'type'         => 'deposit',
            'amount'       => 10000, // $100
            'asset_code'   => 'USD',
            'status'       => 'completed',
            'hash'         => md5(uniqid()),
            'created_at'   => now()->subDays(2),
        ]);

        TransactionProjection::create([
            'account_uuid' => $this->account->uuid,
            'type'         => 'withdrawal',
            'amount'       => 5000, // $50
            'asset_code'   => 'USD',
            'status'       => 'completed',
            'hash'         => md5(uniqid()),
            'created_at'   => now()->subDay(),
        ]);

        $response = $this->get('/fund-flow');

        $response->assertStatus(200);
        $response->assertViewIs('fund-flow.index');
        $response->assertViewHas('statistics', function ($statistics) {
            return $statistics->total_inflow === 10000
                && $statistics->total_outflow === 5000
                && $statistics->net_flow === 5000;
        });
    }

    #[Test]
    public function test_fund_flow_respects_date_range_filter()
    {
        $this->actingAs($this->user);

        // Create transactions at different times
        TransactionProjection::create([
            'account_uuid' => $this->account->uuid,
            'type'         => 'deposit',
            'amount'       => 10000,
            'asset_code'   => 'USD',
            'status'       => 'completed',
            'hash'         => md5(uniqid()),
            'created_at'   => now()->subDays(10), // Outside 7-day range
        ]);

        TransactionProjection::create([
            'account_uuid' => $this->account->uuid,
            'type'         => 'deposit',
            'amount'       => 5000,
            'asset_code'   => 'USD',
            'status'       => 'completed',
            'hash'         => md5(uniqid()),
            'created_at'   => now()->subDays(3), // Within 7-day range
        ]);

        $response = $this->get('/fund-flow?period=7days');

        $response->assertStatus(200);
        $response->assertViewIs('fund-flow.index');
        $response->assertViewHas('statistics', function ($statistics) {
            return $statistics->total_inflow === 5000; // Only recent transaction
        });
    }

    #[Test]
    public function test_fund_flow_network_data_includes_accounts_and_external_entities()
    {
        $this->actingAs($this->user);

        // Create a second account
        $account2 = Account::factory()->create([
            'user_uuid' => $this->user->uuid,
            'name'      => 'Second Account',
        ]);

        $response = $this->get('/fund-flow');

        $response->assertStatus(200);
        $response->assertViewIs('fund-flow.index');
        $response->assertViewHas('networkData', function ($networkData) {
            return count($networkData['nodes']) === 2 // Both accounts as nodes
                && isset($networkData['edges']);
        });
    }

    #[Test]
    public function test_fund_flow_chart_data_aggregates_by_day()
    {
        $this->actingAs($this->user);

        // Create transactions on different days within 24 hours
        TransactionProjection::create([
            'account_uuid' => $this->account->uuid,
            'type'         => 'deposit',
            'amount'       => 5000,
            'asset_code'   => 'USD',
            'status'       => 'completed',
            'hash'         => md5(uniqid()),
            'created_at'   => now()->subHours(20), // Yesterday
        ]);

        TransactionProjection::create([
            'account_uuid' => $this->account->uuid,
            'type'         => 'deposit',
            'amount'       => 3000,
            'asset_code'   => 'USD',
            'status'       => 'completed',
            'hash'         => md5(uniqid()),
            'created_at'   => now()->subHours(2), // Today
        ]);

        $response = $this->get('/fund-flow?period=24hours');

        $response->assertStatus(200);
        $response->assertViewIs('fund-flow.index');
        $response->assertViewHas('chartData');
        $chartData = $response->viewData('chartData');

        // Chart should have data for 24 hours period
        $this->assertGreaterThan(0, count($chartData));

        // Find data entries
        $yesterdayData = collect($chartData)->firstWhere('date', now()->subDay()->format('Y-m-d'));
        $todayData = collect($chartData)->firstWhere('date', now()->format('Y-m-d'));

        // Verify data aggregation
        if ($yesterdayData) {
            $this->assertEquals(5000, $yesterdayData['inflow']);
        }
        if ($todayData) {
            $this->assertEquals(3000, $todayData['inflow']);
        }

        // At least one of them should exist
        $this->assertTrue($yesterdayData !== null || $todayData !== null, 'Should have data for at least one day');
    }

    #[Test]
    public function test_unauthorized_user_cannot_access_fund_flow()
    {
        $response = $this->get('/fund-flow');

        $response->assertRedirect('/login');
    }
}
