<?php

namespace Database\Factories;

use App\Models\Role;
use App\Domain\User\Values\UserRoles;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Role::class;

    /**
     * @return array[]
     */
    public function definition(): array
    {
        return [
            'name'       => fake()->randomElement(array_column(UserRoles::cases(), 'value')),
            'guard_name' => 'web',
        ];
    }

    /**
     * @param UserRoles $role
     *
     * @return $this
     */
    public function withRole(UserRoles $role): static
    {
        return $this->state([
            'name' => $role->value,
        ]);
    }
}
