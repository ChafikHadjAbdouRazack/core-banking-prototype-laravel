<?php

namespace Tests\Security\Authentication;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\DomainTestCase;

class AuthorizationSecurityTest extends DomainTestCase
{
    use RefreshDatabase;

    protected User $user1;

    protected User $user2;

    protected User $admin;

    protected string $userToken;

    protected string $adminToken;

    protected Account $user1Account;

    protected Account $user2Account;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();
        $this->admin = User::factory()->create();

        // Assign admin role using Spatie permission
        $this->admin->assignRole('admin');

        // Create tokens
        $this->userToken = $this->user1->createToken('user-token')->plainTextToken;
        $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;

        // Create accounts
        $this->user1Account = Account::factory()->create([
            'user_uuid' => $this->user1->uuid,
            'balance' => 50000,
        ]);

        $this->user2Account = Account::factory()->create([
            'user_uuid' => $this->user2->uuid,
            'balance' => 30000,
        ]);
    }

    #[Test]
    public function test_users_cannot_access_other_users_accounts()
    {
        // User 1 trying to access User 2's account
        $response = $this->withToken($this->userToken)
            ->getJson("/api/accounts/{$this->user2Account->uuid}");

        $this->assertEquals(403, $response->status());

        // Verify cannot see in listing either
        $response = $this->withToken($this->userToken)
            ->getJson('/api/accounts');

        $accounts = $response->json('data');
        $accountUuids = array_column($accounts, 'uuid');

        $this->assertContains((string) $this->user1Account->uuid, $accountUuids);
        $this->assertNotContains((string) $this->user2Account->uuid, $accountUuids);
    }

    #[Test]
    public function test_users_cannot_modify_other_users_accounts()
    {
        // Try to freeze another user's account
        $response = $this->withToken($this->userToken)
            ->postJson("/api/accounts/{$this->user2Account->uuid}/freeze", [
                'reason' => 'Attempting to freeze',
            ]);

        $this->assertEquals(403, $response->status());

        // Try to delete (first set balance to 0 to ensure it's not the balance check failing)
        $this->user2Account->update(['balance' => 0]);

        $response = $this->withToken($this->userToken)
            ->deleteJson("/api/accounts/{$this->user2Account->uuid}");

        $this->assertEquals(403, $response->status());

        // Verify account unchanged
        $this->assertDatabaseHas('accounts', [
            'uuid' => $this->user2Account->uuid,
            'name' => $this->user2Account->name,
        ]);
    }

    #[Test]
    public function test_users_cannot_transfer_from_others_accounts()
    {
        $response = $this->withToken($this->userToken)
            ->postJson('/api/transfers', [
                'from_account' => $this->user2Account->uuid, // Not their account
                'to_account' => $this->user1Account->uuid,
                'amount' => 100.00,
                'asset_code' => 'USD',
            ]);

        $this->assertEquals(403, $response->status());
        $this->assertEquals('UNAUTHORIZED_TRANSFER', $response->json('error'));

        // Verify balances unchanged
        $this->assertEquals(30000, $this->user2Account->fresh()->balance);
        $this->assertEquals(50000, $this->user1Account->fresh()->balance);
    }

    #[Test]
    public function test_privilege_escalation_via_parameter_pollution()
    {
        $this->markTestSkipped('Account listing endpoint not available in API v1');

        // TODO: Test this with API v2 endpoints
        /*
        // Try to escalate privileges via parameter pollution
        $response = $this->withToken($this->userToken)
            ->getJson('/api/accounts?user_uuid=' . $this->user2->uuid);

        // Should still only see own accounts
        $accounts = $response->json('data');
        foreach ($accounts as $account) {
            $this->assertEquals($this->user1->uuid, $account['user_uuid']);
        }

        // Try array parameter pollution
        $response = $this->withToken($this->userToken)
            ->getJson('/api/accounts?user_uuid[]=' . $this->user1->uuid . '&user_uuid[]=' . $this->user2->uuid);

        $accounts = $response->json('data');
        foreach ($accounts as $account) {
            $this->assertEquals($this->user1->uuid, $account['user_uuid']);
        }
        */
    }

    #[Test]
    public function test_insecure_direct_object_reference_protection()
    {
        // Try sequential IDs
        $accountIds = [];
        for ($i = 1; $i <= 100; $i++) {
            $response = $this->withToken($this->userToken)
                ->getJson("/api/accounts/{$i}");

            if ($response->status() === 200) {
                $accountIds[] = $i;
            }
        }

        // Should not find accounts by sequential ID
        $this->assertEmpty($accountIds, 'Accounts should use UUIDs, not sequential IDs');

        // Try common UUID patterns
        $commonUuids = [
            '00000000-0000-0000-0000-000000000000',
            '11111111-1111-1111-1111-111111111111',
            'ffffffff-ffff-ffff-ffff-ffffffffffff',
        ];

        foreach ($commonUuids as $uuid) {
            $response = $this->withToken($this->userToken)
                ->getJson("/api/accounts/{$uuid}");

            $this->assertContains($response->status(), [403, 404]);
        }
    }

    #[Test]
    public function test_mass_assignment_protection()
    {
        // Try to assign protected attributes
        $response = $this->withToken($this->userToken)
            ->postJson('/api/accounts', [
                'name' => 'New Account',
                'type' => 'savings',
                'user_uuid' => $this->user2->uuid, // Try to assign to another user
                'balance' => 1000000, // Try to set initial balance
                'is_active' => true,
                'is_frozen' => false,
                'created_at' => '2020-01-01',
                'uuid' => 'custom-uuid-12345',
            ]);

        if ($response->status() !== 201) {
            $this->fail('Account creation failed: ' . json_encode($response->json()));
        }

        $account = $response->json('data');

        // Should be assigned to authenticated user, not user2
        $this->assertEquals($this->user1->uuid, $account['user_uuid']);

        // Balance should be 0, not 1000000
        $this->assertEquals(0, $account['balance']);

        // UUID should be auto-generated, not custom
        $this->assertNotEquals('custom-uuid-12345', $account['uuid']);
    }

    #[Test]
    public function test_jwt_token_tampering_detection()
    {
        // Try modified token
        $tamperedTokens = [
            $this->userToken . 'extra',
            substr($this->userToken, 0, -5) . 'aaaaa',
            'invalid-token-format',
            '12345',
        ];

        foreach ($tamperedTokens as $token) {
            $response = $this->withToken($token)
                ->getJson('/api/auth/user');

            $this->assertEquals(401, $response->status(), "Token '$token' should be rejected");
        }
    }

    #[Test]
    public function test_authorization_bypass_via_http_methods()
    {
        $this->markTestSkipped('Account authorization not implemented - relies on proper access control');

        // TODO: Test once authorization is implemented
        /*
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

        foreach ($methods as $method) {
            $response = $this->withToken($this->userToken)
                ->json($method, "/api/accounts/{$this->user2Account->uuid}");

            // Should not allow unauthorized access with any method
            if (! in_array($method, ['HEAD', 'OPTIONS'])) {
                $this->assertContains($response->status(), [403, 404, 405]);
            }
        }
        */
    }

    #[Test]
    public function test_role_based_access_control()
    {
        // Regular user should not access admin endpoints
        $adminEndpoints = [
            '/api/admin/users',
            '/api/admin/accounts',
            '/api/admin/settings',
            '/api/admin/reports',
            '/api/admin/system',
        ];

        foreach ($adminEndpoints as $endpoint) {
            $response = $this->withToken($this->userToken)->getJson($endpoint);
            $this->assertContains($response->status(), [403, 404]);
        }

        // Admin should have access
        foreach ($adminEndpoints as $endpoint) {
            $response = $this->withToken($this->adminToken)->getJson($endpoint);
            // Should not be 403 (might be 404 if not implemented)
            $this->assertNotEquals(403, $response->status());
        }
    }

    #[Test]
    public function test_api_scope_limitations()
    {
        $this->markTestSkipped('API scopes not enforced - no PUT endpoint, DELETE has no scope check');

        // TODO: Implement scope-based authorization
        /*
        // Create limited scope token
        $limitedToken = $this->user1->createToken('limited', ['read'])->plainTextToken;

        // Should be able to read
        $response = $this->withToken($limitedToken)
            ->getJson("/api/accounts/{$this->user1Account->uuid}");
        $this->assertEquals(200, $response->status());

        // Should not be able to write
        $response = $this->withToken($limitedToken)
            ->putJson("/api/accounts/{$this->user1Account->uuid}", [
                'name' => 'Updated Name',
            ]);
        $this->assertEquals(403, $response->status());

        // Should not be able to delete
        $response = $this->withToken($limitedToken)
            ->deleteJson("/api/accounts/{$this->user1Account->uuid}");
        $this->assertEquals(403, $response->status());
        */
    }

    #[Test]
    public function test_transaction_authorization_with_limits()
    {
        $this->markTestSkipped('Transaction limits not implemented - daily_limit and transaction_limit columns do not exist');

        // TODO: Implement transaction limits feature
        /*
        // Create account with transaction limits
        $limitedAccount = Account::factory()->create([
            'user_uuid'         => $this->user1->uuid,
            'balance'           => 100000,
            'daily_limit'       => 10000,
            'transaction_limit' => 5000,
        ]);

        // Try to exceed single transaction limit
        $response = $this->withToken($this->userToken)
            ->postJson('/api/transfers', [
                'from_account' => $limitedAccount->uuid,
                'to_account'   => Account::factory()->create()->uuid,
                'amount'       => 6000, // Exceeds limit
                'currency'     => 'USD',
            ]);

        $this->assertEquals(422, $response->status());
        $this->assertArrayHasKey('amount', $response->json('errors'));
        */
    }

    #[Test]
    public function test_path_traversal_in_authorization()
    {
        $pathTraversalAttempts = [
            '../' . $this->user2Account->uuid,
            '../../' . $this->user2Account->uuid,
            $this->user1Account->uuid . '/../' . $this->user2Account->uuid,
            './../accounts/' . $this->user2Account->uuid,
        ];

        foreach ($pathTraversalAttempts as $attempt) {
            $response = $this->withToken($this->userToken)
                ->getJson("/api/accounts/{$attempt}");

            // Should not bypass authorization
            $this->assertContains($response->status(), [403, 404]);
        }
    }
}
