<?php

use Tests\TestCase;
use App\Domain\Basket\Workflows\ComposeBasketWorkflow;
use App\Domain\Asset\Aggregates\BasketAggregate;
use App\Models\User;
use App\Models\Account;
use App\Models\BasketAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Workflow\WorkflowStub;

class ComposeBasketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $account;
    private BasketAsset $basket;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->account = Account::factory()->create(['user_id' => $this->user->id]);
        
        $this->basket = BasketAsset::factory()->create([
            'code' => 'GCU',
            'name' => 'Global Currency Unit',
            'is_active' => true
        ]);

        // Create basket components
        $this->basket->components()->create([
            'asset_code' => 'USD',
            'weight' => 40.0,
            'is_active' => true
        ]);
        
        $this->basket->components()->create([
            'asset_code' => 'EUR', 
            'weight' => 35.0,
            'is_active' => true
        ]);
        
        $this->basket->components()->create([
            'asset_code' => 'GBP',
            'weight' => 25.0,
            'is_active' => true
        ]);
    }

    public function test_compose_basket_workflow_executes_successfully()
    {
        // Set up component balances
        $this->account->balances()->create(['asset_code' => 'USD', 'balance' => 50000]);
        $this->account->balances()->create(['asset_code' => 'EUR', 'balance' => 40000]);
        $this->account->balances()->create(['asset_code' => 'GBP', 'balance' => 30000]);

        $input = [
            'account_uuid' => $this->account->uuid,
            'basket_code' => 'GCU',
            'amount' => 10000
        ];

        $workflow = WorkflowStub::make(ComposeBasketWorkflow::class);
        $result = $workflow->start($input);

        $this->assertEquals('GCU', $result['basket_code']);
        $this->assertEquals(10000, $result['basket_amount']);
        $this->assertArrayHasKey('components_used', $result);
        $this->assertArrayHasKey('composed_at', $result);
    }

    public function test_compose_basket_workflow_validates_sufficient_balances()
    {
        // Set up insufficient component balances
        $this->account->balances()->create(['asset_code' => 'USD', 'balance' => 1000]);
        $this->account->balances()->create(['asset_code' => 'EUR', 'balance' => 1000]);
        $this->account->balances()->create(['asset_code' => 'GBP', 'balance' => 1000]);

        $input = [
            'account_uuid' => $this->account->uuid,
            'basket_code' => 'GCU',
            'amount' => 10000
        ];

        $workflow = WorkflowStub::make(ComposeBasketWorkflow::class);
        
        $this->expectException(\Exception::class);
        $workflow->start($input);
    }

    public function test_compose_basket_workflow_validates_basket_composition()
    {
        $input = [
            'account_uuid' => $this->account->uuid,
            'basket_code' => 'INVALID_BASKET',
            'amount' => 10000
        ];

        $workflow = WorkflowStub::make(ComposeBasketWorkflow::class);
        
        $this->expectException(\Exception::class);
        $workflow->start($input);
    }

    public function test_compose_basket_workflow_calculates_correct_component_amounts()
    {
        // Set up sufficient component balances
        $this->account->balances()->create(['asset_code' => 'USD', 'balance' => 50000]);
        $this->account->balances()->create(['asset_code' => 'EUR', 'balance' => 40000]);
        $this->account->balances()->create(['asset_code' => 'GBP', 'balance' => 30000]);

        $input = [
            'account_uuid' => $this->account->uuid,
            'basket_code' => 'GCU',
            'amount' => 10000
        ];

        $workflow = WorkflowStub::make(ComposeBasketWorkflow::class);
        $result = $workflow->start($input);

        $expectedAmounts = [
            'USD' => 4000, // 40% of 10000
            'EUR' => 3500, // 35% of 10000
            'GBP' => 2500  // 25% of 10000
        ];

        $this->assertEquals($expectedAmounts, $result['components_used']);
    }

    public function test_compose_basket_workflow_emits_correct_events()
    {
        $aggregate = BasketAggregate::retrieve('GCU');
        $aggregate->composeBasket(
            $this->account->uuid,
            'GCU',
            10000,
            ['USD' => 1.0, 'EUR' => 0.85, 'GBP' => 0.75]
        );
        $aggregate->persist();

        $events = $aggregate->getRecordedEvents();
        $this->assertCount(1, $events);
        $this->assertEquals('BasketComposed', class_basename($events[0]));
    }

    public function test_compose_basket_workflow_compensation_works()
    {
        // Set up scenario that might require compensation
        $this->account->balances()->create(['asset_code' => 'USD', 'balance' => 4000]);
        $this->account->balances()->create(['asset_code' => 'EUR', 'balance' => 3500]);
        $this->account->balances()->create(['asset_code' => 'GBP', 'balance' => 2500]);

        $input = [
            'account_uuid' => $this->account->uuid,
            'basket_code' => 'GCU',
            'amount' => 10000,
            'component_amounts' => [
                'USD' => 4000,
                'EUR' => 3500,
                'GBP' => 2500
            ]
        ];

        $workflow = WorkflowStub::make(ComposeBasketWorkflow::class);
        
        try {
            $result = $workflow->start($input);
            // If successful, verify composition
            $this->assertEquals('GCU', $result['basket_code']);
        } catch (\Exception $e) {
            // If failed, compensation should restore original balances
            $this->account->refresh();
            $usdBalance = $this->account->balances()->where('asset_code', 'USD')->first();
            $this->assertEquals(4000, $usdBalance->balance);
        }
    }

    public function test_compose_basket_workflow_validates_positive_amount()
    {
        $inputs = [
            [
                'account_uuid' => $this->account->uuid,
                'basket_code' => 'GCU',
                'amount' => 0
            ],
            [
                'account_uuid' => $this->account->uuid,
                'basket_code' => 'GCU',
                'amount' => -1000
            ]
        ];

        foreach ($inputs as $input) {
            $workflow = WorkflowStub::make(ComposeBasketWorkflow::class);
            
            $this->expectException(\Exception::class);
            $workflow->start($input);
        }
    }

    public function test_compose_basket_workflow_handles_inactive_basket()
    {
        $this->basket->update(['is_active' => false]);

        $input = [
            'account_uuid' => $this->account->uuid,
            'basket_code' => 'GCU',
            'amount' => 10000
        ];

        $workflow = WorkflowStub::make(ComposeBasketWorkflow::class);
        
        $this->expectException(\Exception::class);
        $workflow->start($input);
    }
}