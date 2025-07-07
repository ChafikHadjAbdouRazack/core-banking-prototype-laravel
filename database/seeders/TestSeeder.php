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
        $seeders = [
            AssetSeeder::class,
            SettingSeeder::class,
        ];
        
        // Only seed roles if the permission tables exist
        if (\Schema::hasTable('roles')) {
            array_unshift($seeders, RolesSeeder::class);
        }
        
        $this->call($seeders);
    }
}
