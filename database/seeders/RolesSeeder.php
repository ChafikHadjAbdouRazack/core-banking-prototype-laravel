<?php

use App\Values\UserRoles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run()
    {
        Role::create(['name' => UserRoles::PRIVATE]);
        Role::create(['name' => UserRoles::BUSINESS]);
        Role::create(['name' => UserRoles::ADMIN]);
    }
}
