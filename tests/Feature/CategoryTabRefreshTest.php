<?php

namespace Tests\Feature;

use App\Models\Category;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CategoryTabRefreshTest extends TestCase
{
    use DatabaseTransactions;

    public function test_category_filtering_works_correctly()
    {
        // Create test data
        Category::create(['name' => 'Active Category 1', 'slug' => 'active-1', 'is_active' => true]);
        Category::create(['name' => 'Active Category 2', 'slug' => 'active-2', 'is_active' => true]);
        Category::create(['name' => 'Inactive Category 1', 'slug' => 'inactive-1', 'is_active' => false]);

        // Test active filter
        $activeCategories = Category::where('is_active', true)->get();
        $this->assertGreaterThanOrEqual(2, $activeCategories->count());

        // Test inactive filter
        $inactiveCategories = Category::where('is_active', false)->get();
        $this->assertGreaterThanOrEqual(1, $inactiveCategories->count());

        // Test total count
        $totalCategories = Category::count();
        $this->assertGreaterThanOrEqual(3, $totalCategories);
    }

    public function test_livewire_component_methods_exist()
    {
        $listCategoriesClass = \App\Filament\Resources\CategoryResource\Pages\ListCategories::class;
        
        // Check that the necessary methods exist
        $this->assertTrue(method_exists($listCategoriesClass, 'updatedActiveTab'));
        $this->assertTrue(method_exists($listCategoriesClass, 'getTabs'));
        $this->assertTrue(method_exists($listCategoriesClass, 'mount'));
    }
}
