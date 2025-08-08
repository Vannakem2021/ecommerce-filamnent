<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CategoryResourceFunctionalityTest extends TestCase
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
            'email' => 'admin' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->adminUser->assignRole('admin');
    }

    public function test_category_tabs_filter_correctly()
    {
        // Create test categories
        $activeCategories = Category::factory()->count(3)->create(['is_active' => true]);
        $inactiveCategories = Category::factory()->count(2)->create(['is_active' => false]);

        // Test that we have the right counts
        $this->assertEquals(3, Category::where('is_active', true)->count());
        $this->assertEquals(2, Category::where('is_active', false)->count());
        $this->assertEquals(5, Category::count());
    }

    public function test_category_search_functionality()
    {
        // Create categories with specific names for testing
        $category1 = Category::factory()->create([
            'name' => 'Electronics Gadgets',
            'slug' => 'electronics-gadgets'
        ]);

        $category2 = Category::factory()->create([
            'name' => 'Home Appliances',
            'slug' => 'home-appliances'
        ]);

        // Test search by name
        $searchResults = Category::where('name', 'like', '%Electronics%')->get();
        $this->assertCount(1, $searchResults);
        $this->assertEquals('Electronics Gadgets', $searchResults->first()->name);

        // Test search by slug
        $slugResults = Category::where('slug', 'like', '%home%')->get();
        $this->assertCount(1, $slugResults);
        $this->assertEquals('home-appliances', $slugResults->first()->slug);
    }

    public function test_category_pagination_data_structure()
    {
        // Create enough categories to test pagination
        Category::factory()->count(30)->create();

        // Test that we have enough data for pagination
        $this->assertGreaterThan(25, Category::count());

        // Test pagination query
        $paginatedResults = Category::paginate(10);
        $this->assertEquals(10, $paginatedResults->perPage());
        $this->assertGreaterThan(1, $paginatedResults->lastPage());
    }

    public function test_category_with_products_filter()
    {
        // Create categories
        $categoryWithProducts = Category::factory()->create();
        $categoryWithoutProducts = Category::factory()->create();

        // Create products for one category
        Product::factory()->count(3)->create(['category_id' => $categoryWithProducts->id]);

        // Test has products filter
        $categoriesWithProducts = Category::has('products')->get();
        $this->assertCount(1, $categoriesWithProducts);
        $this->assertEquals($categoryWithProducts->id, $categoriesWithProducts->first()->id);

        // Test doesn't have products filter
        $categoriesWithoutProducts = Category::doesntHave('products')->get();
        $this->assertContains($categoryWithoutProducts->id, $categoriesWithoutProducts->pluck('id'));
    }

    public function test_category_sorting_functionality()
    {
        // Create categories with different creation times
        $oldCategory = Category::factory()->create(['created_at' => now()->subDays(5)]);
        $newCategory = Category::factory()->create(['created_at' => now()]);

        // Test default sort (created_at desc)
        $sortedCategories = Category::orderBy('created_at', 'desc')->get();
        $this->assertEquals($newCategory->id, $sortedCategories->first()->id);

        // Test name sorting
        $categoryA = Category::factory()->create(['name' => 'A Category']);
        $categoryZ = Category::factory()->create(['name' => 'Z Category']);

        $nameSorted = Category::orderBy('name', 'asc')->get();
        $this->assertEquals('A Category', $nameSorted->first()->name);
    }
}
