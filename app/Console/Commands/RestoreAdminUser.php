<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RestoreAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:restore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore the admin user after tests or database issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Restoring admin user...');

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

        $this->info('✅ Admin user restored successfully!');
        $this->info('📧 Email: admin@test.com');
        $this->info('🔑 Password: admin123');
        $this->info('🌐 Login URL: http://localhost:8000/admin');
        
        return 0;
    }
}
