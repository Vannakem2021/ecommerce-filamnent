<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $permissions = [
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create admin role and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($permissions);

        // Create admin user
        $this->adminUser = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->adminUser->assignRole('admin');
    }

    /** @test */
    public function admin_can_view_categories_list()
    {
        // Create some test categories
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/categories');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_view_create_category_page()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/categories/create');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_view_single_category()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->get("/admin/categories/{$category->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_view_edit_category_page()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->get("/admin/categories/{$category->id}/edit");

        $response->assertStatus(200);
    }

    /** @test */
    public function category_routes_are_properly_registered()
    {
        $this->assertTrue(
            collect(\Route::getRoutes())->contains(function ($route) {
                return $route->getName() === 'filament.admin.resources.categories.view';
            })
        );

        $this->assertTrue(
            collect(\Route::getRoutes())->contains(function ($route) {
                return $route->getName() === 'filament.admin.resources.categories.edit';
            })
        );

        $this->assertTrue(
            collect(\Route::getRoutes())->contains(function ($route) {
                return $route->getName() === 'filament.admin.resources.categories.index';
            })
        );

        $this->assertTrue(
            collect(\Route::getRoutes())->contains(function ($route) {
                return $route->getName() === 'filament.admin.resources.categories.create';
            })
        );
    }
}
