<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed roles and permissions first
        $this->call(RolePermissionSeeder::class);

        // Create admin user
        $this->call(AdminUserSeeder::class);

        // Seed e-commerce data
        $this->call(EcommerceSeeder::class);

        // Seed simplified variant test data
        $this->call(SimplifiedVariantTestSeeder::class);
    }
}
