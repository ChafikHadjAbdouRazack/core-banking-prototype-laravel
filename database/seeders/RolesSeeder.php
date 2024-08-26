<?php

namespace Database\Seeders;

use App\Values\UserRoles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            UserRoles::BUSINESS->value,
            UserRoles::PRIVATE->value,
            UserRoles::ADMIN->value,
        ];

        // Seed each role into the database
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}
