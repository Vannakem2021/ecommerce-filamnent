<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSlugTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_find_product_by_slug()
    {
        // Create test data
        $category = Category::factory()->create(['name' => 'Electronics']);
        $brand = Brand::factory()->create(['name' => 'TestBrand', 'slug' => 'testbrand']);
        
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'has_variants' => true,
            'price_cents' => 100000,
        ]);

        // Test direct lookup
        $foundProduct = Product::where('slug', 'test-product')->first();
        $this->assertNotNull($foundProduct);
        $this->assertEquals('Test Product', $foundProduct->name);

        // Test with relationships
        $foundProductWithRelations = Product::with(['category', 'brand', 'variants'])
            ->where('slug', 'test-product')
            ->first();
        
        $this->assertNotNull($foundProductWithRelations);
        $this->assertEquals('Test Product', $foundProductWithRelations->name);
        $this->assertNotNull($foundProductWithRelations->category);
        $this->assertNotNull($foundProductWithRelations->brand);
        $this->assertEquals('Electronics', $foundProductWithRelations->category->name);
        $this->assertEquals('TestBrand', $foundProductWithRelations->brand->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_for_non_existent_slug()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        
        Product::where('slug', 'non-existent-slug')->firstOrFail();
    }
}
