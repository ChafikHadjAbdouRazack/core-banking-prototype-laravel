<?php

namespace Tests\Unit\Listeners;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateAccountForNewUserTest extends TestCase
{
    private AccountService $accountService;

    private CreateAccountForNewUser $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountService = Mockery::mock(AccountService::class);
        $this->listener = new CreateAccountForNewUser($this->accountService);
    }

    #[Test]
    public function test_creates_account_for_new_user(): void
    {
        $user = new User([
            'uuid'  => 'user-123',
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $event = new Registered($user);

        $this->accountService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($account) use ($user) {
                return $account instanceof Account &&
                    $account->name === "John Doe's Account" &&
                    $account->userUuid === 'user-123';
            }));

        Log::shouldReceive('info')
            ->once()
            ->with('Created default account for new user', [
                'user_uuid'  => 'user-123',
                'user_email' => 'john@example.com',
            ]);

        $this->listener->handle($event);
    }

    #[Test]
    public function test_logs_error_when_account_creation_fails(): void
    {
        $user = new User([
            'uuid'  => 'user-456',
            'name'  => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $event = new Registered($user);
        $exception = new \Exception('Database connection failed');

        $this->accountService->shouldReceive('create')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to create account for new user', Mockery::on(function ($context) {
                return $context['user_uuid'] === 'user-456' &&
                    $context['error'] === 'Database connection failed' &&
                    isset($context['trace']);
            }));

        // Should not throw exception - gracefully handles the error
        $this->listener->handle($event);
    }

    #[Test]
    public function test_does_not_throw_exception_on_failure(): void
    {
        $user = new User([
            'uuid'  => 'user-789',
            'name'  => 'Error User',
            'email' => 'error@example.com',
        ]);

        $event = new Registered($user);

        $this->accountService->shouldReceive('create')
            ->once()
            ->andThrow(new \RuntimeException('Service unavailable'));

        Log::shouldReceive('error')->once();

        // This should not throw an exception
        try {
            $this->listener->handle($event);
            $this->assertTrue(true); // If we reach here, no exception was thrown
        } catch (\Exception $e) {
            $this->fail('Listener should not throw exceptions: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
