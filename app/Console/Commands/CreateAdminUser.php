<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin-user {--email=admin@example.com} {--password=password} {--name=Admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin user with proper permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists!");
            return 1;
        }

        // Create the user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Ensure admin role has all permissions
        $permissions = [
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'orders.view', 'orders.create', 'orders.edit', 'orders.delete',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'brands.view', 'brands.create', 'brands.edit', 'brands.delete',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole->syncPermissions($permissions);

        // Assign admin role to user
        $user->assignRole($adminRole);

        $this->info("Admin user created successfully!");
        $this->info("Email: {$email}");
        $this->info("Password: {$password}");
        $this->info("You can now login to the admin panel.");

        return 0;
    }
}
