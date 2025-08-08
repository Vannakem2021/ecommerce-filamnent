<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create permissions if they don't exist
        $permissions = [
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'orders.view', 'orders.create', 'orders.edit', 'orders.delete',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'brands.view', 'brands.create', 'brands.edit', 'brands.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($permissions);

        // Create or update admin user
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Test Admin',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role
        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
        }

        $this->command->info('✅ Admin user created/updated successfully!');
        $this->command->info('📧 Email: admin@test.com');
        $this->command->info('🔑 Password: admin123');
        $this->command->info('🌐 Login URL: http://localhost:8000/admin');
    }
}
