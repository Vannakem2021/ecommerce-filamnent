<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create permissions for all resources
        $permissions = [
            // Categories
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',
            
            // Products
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            
            // Orders
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.delete',
            
            // Users
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            
            // Brands
            'brands.view',
            'brands.create',
            'brands.edit',
            'brands.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create admin role and assign all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo($permissions);

        // Create other roles
        $productManagerRole = Role::firstOrCreate(['name' => 'product-manager']);
        $productManagerRole->givePermissionTo([
            'categories.view',
            'categories.create',
            'categories.edit',
            'products.view',
            'products.create',
            'products.edit',
            'brands.view',
            'brands.create',
            'brands.edit',
        ]);

        $orderManagerRole = Role::firstOrCreate(['name' => 'order-manager']);
        $orderManagerRole->givePermissionTo([
            'orders.view',
            'orders.edit',
            'users.view',
        ]);

        echo "Permissions and roles created successfully!\n";
    }
}
