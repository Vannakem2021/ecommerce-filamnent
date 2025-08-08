<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdvancedRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create additional specialized admin roles
        
        // Product Manager - can manage products, categories, brands
        $productManagerRole = Role::create(['name' => 'product-manager']);
        $productManagerRole->givePermissionTo([
            'admin.access',
            'dashboard.view',
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',
            'brands.view',
            'brands.create',
            'brands.edit',
            'brands.delete',
        ]);

        // Order Manager - can manage orders and view customers
        $orderManagerRole = Role::create(['name' => 'order-manager']);
        $orderManagerRole->givePermissionTo([
            'admin.access',
            'dashboard.view',
            'orders.view',
            'orders.edit',
            'orders.update-status',
            'users.view',
        ]);

        // Analytics Viewer - read-only access to reports and analytics
        $analyticsRole = Role::create(['name' => 'analytics-viewer']);
        $analyticsRole->givePermissionTo([
            'admin.access',
            'dashboard.view',
            'analytics.view',
            'orders.view',
            'products.view',
            'users.view',
        ]);

        // Create specialized admin users
        $productManager = User::create([
            'name' => 'Product Manager',
            'email' => 'products@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);
        $productManager->assignRole('product-manager');

        $orderManager = User::create([
            'name' => 'Order Manager',
            'email' => 'orders@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);
        $orderManager->assignRole('order-manager');

        $analyticsViewer = User::create([
            'name' => 'Analytics Viewer',
            'email' => 'analytics@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);
        $analyticsViewer->assignRole('analytics-viewer');

        $this->command->info('Advanced roles created successfully!');
        $this->command->info('Product Manager: products@example.com / password');
        $this->command->info('Order Manager: orders@example.com / password');
        $this->command->info('Analytics Viewer: analytics@example.com / password');
    }
}
