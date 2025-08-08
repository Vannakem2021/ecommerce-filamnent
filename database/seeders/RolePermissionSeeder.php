<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Product permissions
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            
            // Category permissions
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',
            
            // Brand permissions
            'brands.view',
            'brands.create',
            'brands.edit',
            'brands.delete',
            
            // Order permissions
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.delete',
            'orders.update-status',
            
            // User permissions
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            
            // Admin panel access
            'admin.access',
            
            // Dashboard permissions
            'dashboard.view',
            'analytics.view',
            
            // System permissions
            'system.settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Admin role - full access
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // User role - limited access (frontend only)
        $userRole = Role::create(['name' => 'user']);
        // Users don't need any special permissions for frontend access
        
        // Create default admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('admin123'),
        ]);
        $admin->assignRole('admin');

        // Update existing test user to have user role
        $testUser = User::where('email', 'test@example.com')->first();
        if ($testUser) {
            $testUser->assignRole('user');
        }

        // Create additional test users
        $regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);
        $regularUser->assignRole('user');

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Admin user: admin@example.com / admin123');
        $this->command->info('Regular user: user@example.com / password');
        $this->command->info('Test user: test@example.com / password (updated to user role)');
    }
}
