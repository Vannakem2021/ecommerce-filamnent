<?php

namespace Tests\Feature;

use App\Livewire\ProductDetailPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivewireComponentTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_instantiate_component_directly()
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

        // Create variants
        $blackVariant = $product->createSimpleVariant(['Color' => 'Black'], null, 10);
        $whiteVariant = $product->createSimpleVariant(['Color' => 'White'], null, 15);
        $blackVariant->update(['is_default' => true]);

        // Test direct instantiation
        $component = new ProductDetailPage();
        $component->mount('test-product');

        $this->assertNotNull($component->product);
        $this->assertEquals('Test Product', $component->product->name);
        $this->assertNotNull($component->availableOptions);
        $this->assertArrayHasKey('Color', $component->availableOptions);
        $this->assertContains('Black', $component->availableOptions['Color']);
        $this->assertContains('White', $component->availableOptions['Color']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_instantiate_component_with_product_model()
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

        // Create variants
        $blackVariant = $product->createSimpleVariant(['Color' => 'Black'], null, 10);
        $whiteVariant = $product->createSimpleVariant(['Color' => 'White'], null, 15);
        $blackVariant->update(['is_default' => true]);

        // Test with product model
        $component = new ProductDetailPage();
        $component->mount($product);

        $this->assertNotNull($component->product);
        $this->assertEquals('Test Product', $component->product->name);
        $this->assertNotNull($component->availableOptions);
        $this->assertArrayHasKey('Color', $component->availableOptions);
        $this->assertContains('Black', $component->availableOptions['Color']);
        $this->assertContains('White', $component->availableOptions['Color']);
    }
}
