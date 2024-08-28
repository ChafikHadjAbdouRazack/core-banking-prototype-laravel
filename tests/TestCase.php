<?php

namespace Tests;

use App\Domain\Account\Repositories\AccountRepository;
use App\Domain\Account\Services\LedgerService;
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

    protected User $business_user;

    protected Account $account;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createRoles();

        $this->user = User::factory()->create();
        $this->business_user = User::factory()->withBusinessRole()->create();
        $this->account = $this->createAccount($this->business_user);
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
     * @param User $user
     *
     * @return Account
     */
    protected function createAccount(User $user): Account
    {
        $uuid = Str::uuid();

        $aggregate = LedgerService::retrieve($uuid)
            ->createAccount(
                account: hydrate(
                    class: \App\Domain\Account\DataObjects\Account::class,
                    properties: [
                        'name'      => DefaultAccountNames::default(),
                        'user_uuid' => $user->uuid,
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
