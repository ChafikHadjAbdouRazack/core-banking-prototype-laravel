<?php

namespace Tests\Unit\Helpers;

use App\Domain\Account\DataObjects\Account;
use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use JustSteveKing\DataObjects\Contracts\DataObjectContract;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ObjectsHelperTest extends TestCase
{
    #[Test]
    public function test_helper_functions_exist(): void
    {
        $this->assertTrue(function_exists('hydrate'));
        $this->assertTrue(function_exists('__account'));
        $this->assertTrue(function_exists('__money'));
        $this->assertTrue(function_exists('__account_uuid'));
        $this->assertTrue(function_exists('__account__uuid'));
    }

    #[Test]
    public function test_hydrate_function_signature(): void
    {
        $reflection = new \ReflectionFunction('hydrate');

        $this->assertEquals(2, $reflection->getNumberOfParameters());

        $parameters = $reflection->getParameters();
        $this->assertEquals('class', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        $this->assertEquals('properties', $parameters[1]->getName());
        $this->assertEquals('array', $parameters[1]->getType()->getName());

        $this->assertEquals(DataObjectContract::class, $reflection->getReturnType()->getName());
    }

    #[Test]
    public function test_hydrate_has_proper_documentation(): void
    {
        $reflection = new \ReflectionFunction('hydrate');
        $docComment = $reflection->getDocComment();

        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Hydrate and return a specific Data Object class instance', $docComment);
        $this->assertStringContainsString('@template T of DataObjectContract', $docComment);
        $this->assertStringContainsString('@param class-string<T> $class', $docComment);
        $this->assertStringContainsString('@return T', $docComment);
    }

    #[Test]
    public function test_account_function_signature(): void
    {
        $reflection = new \ReflectionFunction('__account');

        $this->assertEquals(1, $reflection->getNumberOfParameters());

        $parameter = $reflection->getParameters()[0];
        $this->assertEquals('account', $parameter->getName());

        $type = $parameter->getType();
        $this->assertInstanceOf(\ReflectionUnionType::class, $type);
        $this->assertEquals('App\Domain\Account\DataObjects\Account|array', (string) $type);

        $this->assertEquals(Account::class, $reflection->getReturnType()->getName());
    }

    #[Test]
    public function test_money_function_signature(): void
    {
        $reflection = new \ReflectionFunction('__money');

        $this->assertEquals(1, $reflection->getNumberOfParameters());

        $parameter = $reflection->getParameters()[0];
        $this->assertEquals('amount', $parameter->getName());

        $type = $parameter->getType();
        $this->assertInstanceOf(\ReflectionUnionType::class, $type);
        $this->assertEquals('App\Domain\Account\DataObjects\Money|int', (string) $type);

        $this->assertEquals(Money::class, $reflection->getReturnType()->getName());
    }

    #[Test]
    public function test_account_uuid_function_signature(): void
    {
        $reflection = new \ReflectionFunction('__account_uuid');

        $this->assertEquals(1, $reflection->getNumberOfParameters());

        $parameter = $reflection->getParameters()[0];
        $this->assertEquals('uuid', $parameter->getName());

        $type = $parameter->getType();
        $this->assertInstanceOf(\ReflectionUnionType::class, $type);
        $types = array_map(fn ($t) => $t->getName(), $type->getTypes());
        $this->assertContains(Account::class, $types);
        $this->assertContains(AccountModel::class, $types);
        $this->assertContains(AccountUuid::class, $types);
        $this->assertContains('string', $types);

        $this->assertEquals(AccountUuid::class, $reflection->getReturnType()->getName());
    }

    #[Test]
    public function test_account_double_uuid_function_signature(): void
    {
        $reflection = new \ReflectionFunction('__account__uuid');

        $this->assertEquals(1, $reflection->getNumberOfParameters());

        $parameter = $reflection->getParameters()[0];
        $this->assertEquals('uuid', $parameter->getName());

        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    #[Test]
    public function test_objects_helper_file_structure(): void
    {
        $helperFile = base_path('app/Domain/Account/Helpers/objects.php');

        $this->assertFileExists($helperFile);

        $content = file_get_contents($helperFile);

        // Check imports
        $this->assertStringContainsString('use App\Domain\Account\DataObjects\Account;', $content);
        $this->assertStringContainsString('use App\Domain\Account\DataObjects\AccountUuid;', $content);
        $this->assertStringContainsString('use App\Domain\Account\DataObjects\Money;', $content);
        $this->assertStringContainsString('use App\Models\Account as AccountModel;', $content);
        $this->assertStringContainsString('use JustSteveKing\DataObjects\Contracts\DataObjectContract;', $content);
        $this->assertStringContainsString('use JustSteveKing\DataObjects\Facades\Hydrator;', $content);
    }

    #[Test]
    public function test_hydrate_handles_unit_enums(): void
    {
        $helperFile = base_path('app/Domain/Account/Helpers/objects.php');
        $content = file_get_contents($helperFile);

        // Check that hydrate function handles UnitEnum
        $this->assertStringContainsString('$value instanceof UnitEnum', $content);
        $this->assertStringContainsString('? $value->value : $value', $content);
    }

    #[Test]
    public function test_account_function_returns_same_if_already_account(): void
    {
        $helperFile = base_path('app/Domain/Account/Helpers/objects.php');
        $content = file_get_contents($helperFile);

        // Check early return
        $this->assertStringContainsString('if ($account instanceof Account) {', $content);
        $this->assertStringContainsString('return $account;', $content);
    }

    #[Test]
    public function test_money_function_returns_same_if_already_money(): void
    {
        $helperFile = base_path('app/Domain/Account/Helpers/objects.php');
        $content = file_get_contents($helperFile);

        // Check early return
        $this->assertStringContainsString('if ($amount instanceof Money) {', $content);
        $this->assertStringContainsString('return $amount;', $content);
    }

    #[Test]
    public function test_account_uuid_handles_different_types(): void
    {
        $helperFile = base_path('app/Domain/Account/Helpers/objects.php');
        $content = file_get_contents($helperFile);

        // Check different type handling
        $this->assertStringContainsString('if ($uuid instanceof AccountUuid) {', $content);
        $this->assertStringContainsString('if ($uuid instanceof Account) {', $content);
        $this->assertStringContainsString('$uuid = $uuid->getUuid();', $content);
        $this->assertStringContainsString('if ($uuid instanceof AccountModel) {', $content);
        $this->assertStringContainsString('$uuid = $uuid->uuid;', $content);
    }

    #[Test]
    public function test_account_double_uuid_returns_string(): void
    {
        $helperFile = base_path('app/Domain/Account/Helpers/objects.php');
        $content = file_get_contents($helperFile);

        // Check different return paths
        $this->assertStringContainsString('return $uuid->getUuid();', $content);
        $this->assertStringContainsString('return $uuid->uuid;', $content);
        $this->assertStringContainsString('return $uuid;', $content);
    }

    #[Test]
    public function test_all_functions_use_hydrate(): void
    {
        $helperFile = base_path('app/Domain/Account/Helpers/objects.php');
        $content = file_get_contents($helperFile);

        // Count hydrate calls (should be at least 3: in __account, __money, __account_uuid)
        $hydrateCount = substr_count($content, 'hydrate(');
        $this->assertGreaterThanOrEqual(3, $hydrateCount);
    }

    #[Test]
    public function test_functions_have_proper_type_hints(): void
    {
        $functions = ['__account', '__money', '__account_uuid', '__account__uuid'];

        foreach ($functions as $function) {
            $reflection = new \ReflectionFunction($function);

            // All should have return types
            $this->assertTrue($reflection->hasReturnType());

            // All should have typed parameters
            $parameters = $reflection->getParameters();
            foreach ($parameters as $parameter) {
                $this->assertTrue($parameter->hasType());
            }
        }
    }

    #[Test]
    public function test_money_function_creates_with_amount_property(): void
    {
        $helperFile = base_path('app/Domain/Account/Helpers/objects.php');
        $content = file_get_contents($helperFile);

        // Check that money is created with amount property
        $this->assertStringContainsString("'amount' => \$amount", $content);
    }

    #[Test]
    public function test_account_uuid_function_creates_with_uuid_property(): void
    {
        $helperFile = base_path('app/Domain/Account/Helpers/objects.php');
        $content = file_get_contents($helperFile);

        // Check that AccountUuid is created with uuid property
        $this->assertStringContainsString("'uuid' => \$uuid", $content);
    }
}
