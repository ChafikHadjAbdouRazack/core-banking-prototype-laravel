<?php

namespace Tests\Unit\Actions\Fortify;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateNewUserTest extends TestCase
{
    private CreateNewUser $action;

    protected function shouldCreateDefaultAccountsInSetup(): bool
    {
        return false;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new CreateNewUser();
    }

    #[Test]
    public function test_creates_new_private_user_successfully(): void
    {
        $input = [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'SecureP@ssw0rd#2024!',
            'password_confirmation' => 'SecureP@ssw0rd#2024!',
            'is_business_customer'  => false,
            'terms'                 => true,
        ];

        $user = $this->action->create($input);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue(Hash::check('SecureP@ssw0rd#2024!', $user->password));
        $this->assertTrue($user->hasRole('customer_private'));

        // Check team was created
        $this->assertCount(1, $user->ownedTeams);
        $team = $user->ownedTeams->first();
        $this->assertEquals("John's Team", $team->name);
        $this->assertTrue($team->personal_team);
    }

    #[Test]
    public function test_creates_new_business_user_successfully(): void
    {
        $input = [
            'name'                  => 'Business User',
            'email'                 => 'business@example.com',
            'password'              => 'SecureP@ssw0rd#2024!',
            'password_confirmation' => 'SecureP@ssw0rd#2024!',
            'is_business_customer'  => true,
            'terms'                 => true,
        ];

        $user = $this->action->create($input);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Business User', $user->name);
        $this->assertEquals('business@example.com', $user->email);
        $this->assertTrue($user->hasRole('customer_business'));

        // Check business team configuration
        $team = $user->ownedTeams->first();
        $this->assertTrue($team->is_business_organization);
        $this->assertEquals('business', $team->organization_type);
        $this->assertEquals(10, $team->max_users);
        $this->assertContains('compliance_officer', $team->allowed_roles);
        $this->assertContains('risk_manager', $team->allowed_roles);
    }

    #[Test]
    public function test_validates_required_fields(): void
    {
        $this->expectException(ValidationException::class);

        $this->action->create([]);
    }

    #[Test]
    public function test_validates_name_is_required(): void
    {
        $this->expectException(ValidationException::class);

        $this->action->create([
            'email'                 => 'test@example.com',
            'password'              => 'SecureP@ssw0rd#2024!',
            'password_confirmation' => 'SecureP@ssw0rd#2024!',
            'terms'                 => true,
        ]);
    }

    #[Test]
    public function test_validates_email_is_required(): void
    {
        $this->expectException(ValidationException::class);

        $this->action->create([
            'name'                  => 'Test User',
            'password'              => 'SecureP@ssw0rd#2024!',
            'password_confirmation' => 'SecureP@ssw0rd#2024!',
            'terms'                 => true,
        ]);
    }

    #[Test]
    public function test_validates_email_format(): void
    {
        $this->expectException(ValidationException::class);

        $this->action->create([
            'name'                  => 'Test User',
            'email'                 => 'invalid-email',
            'password'              => 'SecureP@ssw0rd#2024!',
            'password_confirmation' => 'SecureP@ssw0rd#2024!',
            'terms'                 => true,
        ]);
    }

    #[Test]
    public function test_validates_email_is_unique(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->expectException(ValidationException::class);

        $this->action->create([
            'name'                  => 'Test User',
            'email'                 => 'existing@example.com',
            'password'              => 'SecureP@ssw0rd#2024!',
            'password_confirmation' => 'SecureP@ssw0rd#2024!',
            'terms'                 => true,
        ]);
    }

    #[Test]
    public function test_validates_password_is_required(): void
    {
        $this->expectException(ValidationException::class);

        $this->action->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
            'terms' => true,
        ]);
    }

    #[Test]
    public function test_validates_terms_acceptance_when_feature_enabled(): void
    {
        // Terms and privacy policy feature is enabled in config/jetstream.php
        $this->expectException(ValidationException::class);

        $this->action->create([
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'SecureP@ssw0rd#2024!',
            'password_confirmation' => 'SecureP@ssw0rd#2024!',
            // terms is missing, should fail validation
        ]);
    }

    #[Test]
    public function test_creates_user_with_terms_accepted(): void
    {
        // Terms and privacy policy feature is enabled in config/jetstream.php
        $input = [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'SecureP@ssw0rd#2024!',
            'password_confirmation' => 'SecureP@ssw0rd#2024!',
            'terms'                 => true,
        ];

        $user = $this->action->create($input);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
    }

    #[Test]
    public function test_handles_single_name_correctly(): void
    {
        $input = [
            'name'                  => 'Madonna',
            'email'                 => 'madonna@example.com',
            'password'              => 'SecureP@ssw0rd#2024!',
            'password_confirmation' => 'SecureP@ssw0rd#2024!',
            'terms'                 => true,
        ];

        $user = $this->action->create($input);

        $team = $user->ownedTeams->first();
        $this->assertEquals("Madonna's Team", $team->name);
    }

    #[Test]
    public function test_handles_multi_word_name_correctly(): void
    {
        $input = [
            'name'                  => 'Mary Jane Watson Parker',
            'email'                 => 'mj@example.com',
            'password'              => 'SecureP@ssw0rd#2024!',
            'password_confirmation' => 'SecureP@ssw0rd#2024!',
            'terms'                 => true,
        ];

        $user = $this->action->create($input);

        $team = $user->ownedTeams->first();
        $this->assertEquals("Mary's Team", $team->name);
    }

    #[Test]
    public function test_business_user_gets_owner_role_in_team(): void
    {
        $input = [
            'name'                  => 'Business Owner',
            'email'                 => 'owner@business.com',
            'password'              => 'SecureP@ssw0rd#2024!',
            'password_confirmation' => 'SecureP@ssw0rd#2024!',
            'is_business_customer'  => true,
            'terms'                 => true,
        ];

        $user = $this->action->create($input);
        $team = $user->ownedTeams->first();

        // Verify the user has owner role in the team
        $teamRole = $team->getUserTeamRole($user);
        $this->assertNotNull($teamRole);
        $this->assertEquals('owner', $teamRole->role);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
