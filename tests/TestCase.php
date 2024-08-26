<?php

namespace Tests;

use App\Domain\Account\AccountAggregateRoot;
use App\Domain\Account\Repositories\AccountRepository;
use App\Models\Account;
use App\Models\Role;
use App\Models\User;
use App\Values\DefaultAccountNames;
use App\Values\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected User $user;

    protected Account $account;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createRoles();

        $this->user = User::factory()->create();

        $this->account = $this->createAccount();
    }

    /**
     * @throws \Throwable
     */
    protected function assertExceptionThrown( callable $callable, string $expectedExceptionClass): void
    {
        try {
            $callable();

            $this->fail(
                "Expected exception `{$expectedExceptionClass}` was not thrown."
            );
        } catch (Throwable $exception) {
            if (! $exception instanceof $expectedExceptionClass) {
                throw $exception;
            }
            $this->assertInstanceOf($expectedExceptionClass, $exception);
        }
    }

    /**
     * @return \App\Models\Account
     */
    protected function createAccount(): Account
    {
        $uuid = Str::uuid();

        $aggregate = AccountAggregateRoot::retrieve($uuid)
            ->createAccount(
                account: hydrate(
                    class: \App\Domain\Account\DataObjects\Account::class,
                    properties: [
                        'name' => DefaultAccountNames::default(),
                        'user_id' => $this->user->id,
                    ]
                )
            )->persist();

        return app(AccountRepository::class)->findByUuid($aggregate->uuid());
    }

    /**
     * @return void
     */
    protected function createRoles(): void
    {
        collect(UserRoles::cases())->each(
            fn ($role) => Role::factory()->withRole($role)->create()
        );
    }
}
