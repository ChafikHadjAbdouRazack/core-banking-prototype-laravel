<?php

namespace Tests\Unit\Domain\Account\Listeners;

use App\Domain\Account\Events\AccountCreated;
use App\Domain\Account\Events\AccountDestroyed;
use App\Domain\Account\Events\AccountFrozen;
use App\Domain\Account\Events\AccountUnfrozen;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Events\MoneySubtracted;
use App\Domain\Account\Events\MoneyTransferred;
use App\Domain\Account\Listeners\WebhookEventListener;
use App\Models\Account;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class WebhookEventListenerTest extends TestCase
{
    use RefreshDatabase;

    private WebhookService $webhookService;

    private WebhookEventListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhookService = Mockery::mock(WebhookService::class);
        $this->listener = new WebhookEventListener($this->webhookService);

        // Enable webhook testing
        app()->instance('testing.webhooks', true);
    }

    private function setEventProperties($event, array $properties): void
    {
        $reflection = new \ReflectionClass($event);

        foreach ($properties as $name => $value) {
            $property = $reflection->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($event, $value);
        }
    }

    public function test_skips_webhook_in_testing_environment_when_not_explicitly_enabled(): void
    {
        // Remove the webhook testing flag
        app()->forgetInstance('testing.webhooks');

        $event = Mockery::mock(AccountCreated::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn('account-123');

        $this->webhookService->shouldNotReceive('dispatchAccountEvent');

        $this->listener->onAccountCreated($event);
    }

    public function test_handles_account_created_event(): void
    {
        $accountUuid = Uuid::uuid4()->toString();
        $user = \App\Models\User::factory()->create(['uuid' => 'user-123']);
        $account = Account::factory()->create([
            'uuid'      => $accountUuid,
            'name'      => 'Test Account',
            'user_uuid' => $user->uuid,
            'balance'   => 0,
        ]);

        $event = Mockery::mock(AccountCreated::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn($accountUuid);

        $this->webhookService->shouldReceive('dispatchAccountEvent')
            ->once()
            ->with('account.created', $accountUuid, [
                'name'      => 'Test Account',
                'user_uuid' => 'user-123',
                'balance'   => 0,
            ]);

        $this->listener->onAccountCreated($event);
    }

    public function test_handles_account_frozen_event(): void
    {
        $accountUuid = 'account-456';
        $event = Mockery::mock(AccountFrozen::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn($accountUuid);
        
        $this->setEventProperties($event, [
            'reason' => 'Suspicious activity detected'
        ]);

        $this->webhookService->shouldReceive('dispatchAccountEvent')
            ->once()
            ->with('account.frozen', $accountUuid, [
                'reason' => 'Suspicious activity detected',
            ]);

        $this->listener->onAccountFrozen($event);
    }

    public function test_handles_account_unfrozen_event(): void
    {
        $accountUuid = 'account-789';
        $event = Mockery::mock(AccountUnfrozen::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn($accountUuid);

        $this->webhookService->shouldReceive('dispatchAccountEvent')
            ->once()
            ->with('account.unfrozen', $accountUuid);

        $this->listener->onAccountUnfrozen($event);
    }

    public function test_handles_account_destroyed_event(): void
    {
        $accountUuid = 'account-999';
        $event = Mockery::mock(AccountDestroyed::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn($accountUuid);

        $this->webhookService->shouldReceive('dispatchAccountEvent')
            ->once()
            ->with('account.closed', $accountUuid);

        $this->listener->onAccountDestroyed($event);
    }

    public function test_handles_money_added_event(): void
    {
        $accountUuid = Uuid::uuid4()->toString();
        $account = Account::factory()->create([
            'uuid'    => $accountUuid,
            'balance' => 5000, // $50.00
        ]);

        $event = Mockery::mock(MoneyAdded::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn($accountUuid);

        $money = Mockery::mock(\App\Domain\Account\DataObjects\Money::class);
        $money->shouldReceive('getAmount')->andReturn('2500'); // $25.00

        $hash = Mockery::mock(\App\Domain\Account\DataObjects\Hash::class);
        $hash->shouldReceive('getHash')->andReturn('hash123');

        $this->setEventProperties($event, [
            'money' => $money,
            'hash'  => $hash,
        ]);

        $this->webhookService->shouldReceive('dispatchTransactionEvent')
            ->once()
            ->with('transaction.created', [
                'account_uuid'  => $accountUuid,
                'type'          => 'deposit',
                'amount'        => '2500',
                'currency'      => 'USD',
                'balance_after' => 5000,
                'hash'          => 'hash123',
            ]);

        $this->webhookService->shouldNotReceive('dispatchAccountEvent')
            ->with('balance.low', Mockery::any(), Mockery::any());

        $this->listener->onMoneyAdded($event);
    }

    public function test_triggers_low_balance_alert(): void
    {
        $accountUuid = Uuid::uuid4()->toString();
        $account = Account::factory()->create([
            'uuid'    => $accountUuid,
            'balance' => 500, // $5.00 - below threshold
        ]);

        $event = Mockery::mock(MoneyAdded::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn($accountUuid);

        $money = Mockery::mock(\App\Domain\Account\DataObjects\Money::class);
        $money->shouldReceive('getAmount')->andReturn('100');

        $hash = Mockery::mock(\App\Domain\Account\DataObjects\Hash::class);
        $hash->shouldReceive('getHash')->andReturn('hash456');

        $this->setEventProperties($event, [
            'money' => $money,
            'hash'  => $hash,
        ]);

        $this->webhookService->shouldReceive('dispatchTransactionEvent')->once();

        $this->webhookService->shouldReceive('dispatchAccountEvent')
            ->once()
            ->with('balance.low', $accountUuid, [
                'balance'   => 500,
                'threshold' => 1000,
            ]);

        $this->listener->onMoneyAdded($event);
    }

    public function test_handles_money_subtracted_event(): void
    {
        $accountUuid = Uuid::uuid4()->toString();
        $account = Account::factory()->create([
            'uuid'    => $accountUuid,
            'balance' => 3000, // $30.00
        ]);

        $event = Mockery::mock(MoneySubtracted::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn($accountUuid);

        $money = Mockery::mock(\App\Domain\Account\DataObjects\Money::class);
        $money->shouldReceive('getAmount')->andReturn('1000');

        $hash = Mockery::mock(\App\Domain\Account\DataObjects\Hash::class);
        $hash->shouldReceive('getHash')->andReturn('hash789');

        $this->setEventProperties($event, [
            'money' => $money,
            'hash'  => $hash,
        ]);

        $this->webhookService->shouldReceive('dispatchTransactionEvent')
            ->once()
            ->with('transaction.created', [
                'account_uuid'  => $accountUuid,
                'type'          => 'withdrawal',
                'amount'        => '1000',
                'currency'      => 'USD',
                'balance_after' => 3000,
                'hash'          => 'hash789',
            ]);

        $this->webhookService->shouldNotReceive('dispatchAccountEvent')
            ->with('balance.negative', Mockery::any(), Mockery::any());

        $this->listener->onMoneySubtracted($event);
    }

    public function test_triggers_negative_balance_alert(): void
    {
        $accountUuid = Uuid::uuid4()->toString();
        $account = Account::factory()->create([
            'uuid'    => $accountUuid,
            'balance' => -500, // Negative balance
        ]);

        $event = Mockery::mock(MoneySubtracted::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn($accountUuid);

        $money = Mockery::mock(\App\Domain\Account\DataObjects\Money::class);
        $money->shouldReceive('getAmount')->andReturn('1000');

        $hash = Mockery::mock(\App\Domain\Account\DataObjects\Hash::class);
        $hash->shouldReceive('getHash')->andReturn('hash999');

        $this->setEventProperties($event, [
            'money' => $money,
            'hash'  => $hash,
        ]);

        $this->webhookService->shouldReceive('dispatchTransactionEvent')->once();

        $this->webhookService->shouldReceive('dispatchAccountEvent')
            ->once()
            ->with('balance.negative', $accountUuid, [
                'balance' => -500,
            ]);

        $this->listener->onMoneySubtracted($event);
    }

    public function test_handles_money_transferred_event(): void
    {
        $fromUuid = Uuid::uuid4()->toString();
        $toUuid = Uuid::uuid4()->toString();

        $fromAccount = Account::factory()->create([
            'uuid'    => $fromUuid,
            'balance' => 5000,
        ]);

        $toAccount = Account::factory()->create([
            'uuid'    => $toUuid,
            'balance' => 10000,
        ]);

        $event = Mockery::mock(MoneyTransferred::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn($fromUuid);

        $toAccountUuid = Mockery::mock(\App\Domain\Account\DataObjects\AccountUuid::class);
        $toAccountUuid->shouldReceive('toString')->andReturn($toUuid);

        $money = Mockery::mock(\App\Domain\Account\DataObjects\Money::class);
        $money->shouldReceive('getAmount')->andReturn('2000');

        $hash = Mockery::mock(\App\Domain\Account\DataObjects\Hash::class);
        $hash->shouldReceive('getHash')->andReturn('transfer123');

        $this->setEventProperties($event, [
            'to'    => $toAccountUuid,
            'money' => $money,
            'hash'  => $hash,
        ]);

        $expectedData = [
            'from_account_uuid'  => $fromUuid,
            'to_account_uuid'    => $toUuid,
            'amount'             => '2000',
            'currency'           => 'USD',
            'from_balance_after' => 5000,
            'to_balance_after'   => 10000,
            'hash'               => 'transfer123',
        ];

        $this->webhookService->shouldReceive('dispatchTransferEvent')
            ->once()
            ->with('transfer.created', $expectedData);

        $this->webhookService->shouldReceive('dispatchTransferEvent')
            ->once()
            ->with('transfer.completed', $expectedData);

        $this->listener->onMoneyTransferred($event);
    }

    public function test_skips_events_when_account_not_found(): void
    {
        $event = Mockery::mock(AccountCreated::class);
        $event->shouldReceive('aggregateRootUuid')->andReturn('non-existent-uuid');

        $this->webhookService->shouldNotReceive('dispatchAccountEvent');

        $this->listener->onAccountCreated($event);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
