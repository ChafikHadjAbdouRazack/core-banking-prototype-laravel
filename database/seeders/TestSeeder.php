<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds for test environment.
     */
    public function run(): void
    {
        // Only seed essential data needed for tests
        $this->call([
            RolesSeeder::class,
            AssetSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
