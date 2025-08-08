<?php

namespace Tests\Feature;

use App\Models\Category;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CategoryResourceFixTest extends TestCase
{
    use DatabaseTransactions;

    public function test_category_model_basic_functionality()
    {
        // Test basic category creation and filtering
        $activeCategory = Category::create([
            'name' => 'Test Active Category',
            'slug' => 'test-active-category',
            'is_active' => true
        ]);

        $inactiveCategory = Category::create([
            'name' => 'Test Inactive Category', 
            'slug' => 'test-inactive-category',
            'is_active' => false
        ]);

        // Test filtering works at model level
        $this->assertEquals(1, Category::where('is_active', true)->where('name', 'LIKE', '%Test%')->count());
        $this->assertEquals(1, Category::where('is_active', false)->where('name', 'LIKE', '%Test%')->count());
        
        // Test search functionality at model level
        $searchResults = Category::where('name', 'LIKE', '%Active%')->get();
        $this->assertGreaterThanOrEqual(1, $searchResults->count());
    }

    public function test_category_routes_are_accessible()
    {
        // Test that the main routes exist and are properly named
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
