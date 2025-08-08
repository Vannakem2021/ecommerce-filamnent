<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Livewire\Livewire;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user for testing
        $this->adminUser = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        // Assign admin role if using Spatie permissions
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
            $this->adminUser->assignRole($adminRole);
        }
    }

    /** @test */
    public function it_can_create_a_category()
    {
        $categoryData = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ];

        $category = Category::create($categoryData);

        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        $this->assertEquals('Test Category', $category->name);
        $this->assertEquals('test-category', $category->slug);
        $this->assertTrue($category->is_active);
    }

    /** @test */
    public function it_can_update_a_category()
    {
        $category = Category::factory()->create([
            'name' => 'Original Name',
            'slug' => 'original-name',
        ]);

        $category->update([
            'name' => 'Updated Name',
            'slug' => 'updated-name',
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'slug' => 'updated-name',
        ]);
    }

    /** @test */
    public function it_can_delete_a_category()
    {
        $category = Category::factory()->create();

        $category->delete();

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    /** @test */
    public function it_can_list_categories()
    {
        Category::factory()->count(5)->create();

        $categories = Category::all();

        $this->assertCount(5, $categories);
    }

    /** @test */
    public function it_can_filter_active_categories()
    {
        Category::factory()->count(3)->create(['is_active' => true]);
        Category::factory()->count(2)->create(['is_active' => false]);

        $activeCategories = Category::where('is_active', true)->get();
        $inactiveCategories = Category::where('is_active', false)->get();

        $this->assertCount(3, $activeCategories);
        $this->assertCount(2, $inactiveCategories);
    }

    /** @test */
    public function frontend_displays_only_active_categories()
    {
        // Create active and inactive categories
        $activeCategory = Category::factory()->create([
            'name' => 'Active Category',
            'is_active' => true,
        ]);

        $inactiveCategory = Category::factory()->create([
            'name' => 'Inactive Category',
            'is_active' => false,
        ]);

        // Test the CategoriesPage component
        Livewire::test(\App\Livewire\CategoriesPage::class)
            ->assertSee('Active Category')
            ->assertDontSee('Inactive Category');
    }

    /** @test */
    public function category_slug_must_be_unique()
    {
        Category::factory()->create(['slug' => 'unique-slug']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Category::factory()->create(['slug' => 'unique-slug']);
    }

    /** @test */
    public function category_name_is_required()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Category::create([
            'slug' => 'test-slug',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function category_slug_is_auto_generated_from_name()
    {
        $category = Category::create([
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        $this->assertEquals('test-category', $category->slug);
    }

    /** @test */
    public function category_has_default_active_status()
    {
        $category = Category::factory()->create();

        $this->assertTrue($category->is_active);
    }
}
