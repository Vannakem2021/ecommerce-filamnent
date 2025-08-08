<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UpdateUserPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-user-permissions {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user permissions and assign admin role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }

        // Create all permissions
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

        // Create admin role and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($permissions);

        // Assign admin role to user
        $user->syncRoles(['admin']);

        $this->info("User {$email} has been updated with admin permissions!");
        $this->info("Roles: " . $user->roles->pluck('name')->join(', '));

        return 0;
    }
}
