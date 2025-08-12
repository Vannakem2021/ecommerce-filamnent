<?php

namespace Tests\Feature;

use App\Livewire\ProductDetailPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductDetailPageTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_load_product_detail_page_without_variants()
    {
        // Create test data
        $category = Category::factory()->create(['name' => 'Electronics']);
        $brand = Brand::factory()->create(['name' => 'TestBrand', 'slug' => 'testbrand']);
        
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'has_variants' => false,
            'price_cents' => 100000,
        ]);

        $component = Livewire::test(ProductDetailPage::class, ['slug' => $product->slug]);

        $component->assertSet('product.name', 'Test Product');
        $component->assertSet('availableOptions', []);
        $component->assertSet('selectedOptions', []);
        $component->assertSet('selectedVariant', null);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_load_product_detail_page_with_variants()
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

        $component = Livewire::test(ProductDetailPage::class, ['slug' => $product->slug]);

        $component->assertSet('product.name', 'Test Product');
        
        // Check available options
        $availableOptions = $component->get('availableOptions');
        $this->assertNotNull($availableOptions);
        $this->assertArrayHasKey('Color', $availableOptions);
        $this->assertContains('Black', $availableOptions['Color']);
        $this->assertContains('White', $availableOptions['Color']);

        // Check default variant selection
        $selectedOptions = $component->get('selectedOptions');
        $this->assertNotNull($selectedOptions);
        $this->assertEquals('Black', $selectedOptions['Color']);

        $selectedVariant = $component->get('selectedVariant');
        $this->assertNotNull($selectedVariant);
        $this->assertEquals($blackVariant->id, $selectedVariant->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_select_variant_options()
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

        $component = Livewire::test(ProductDetailPage::class, ['slug' => $product->slug]);

        // Initially should have black selected
        $this->assertEquals('Black', $component->get('selectedOptions')['Color']);
        $this->assertEquals($blackVariant->id, $component->get('selectedVariant')->id);

        // Select white color
        $component->call('selectOption', 'Color', 'White');

        // Should now have white selected
        $this->assertEquals('White', $component->get('selectedOptions')['Color']);
        $this->assertEquals($whiteVariant->id, $component->get('selectedVariant')->id);
    }
}
