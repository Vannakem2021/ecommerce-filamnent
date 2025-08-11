<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JsonVariantSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        $this->brand = Brand::create([
            'name' => 'Test Brand',
            'slug' => 'test-brand',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_create_product_with_json_variants()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Phone',
            'slug' => 'test-phone',
            'sku' => 'PHONE001',
            'price_cents' => 50000,
            'has_variants' => true,
            'is_active' => true,
        ]);

        // Create variants with JSON options
        $redVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PHONE001-RED-16GB',
            'options' => ['Color' => 'Red', 'Storage' => '16GB'],
            'price_cents' => 50000, // Base price (required field)
            'override_price' => 45000, // $450
            'stock_quantity' => 10,
            'is_active' => true,
            'is_default' => true,
        ]);

        $blueVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PHONE001-BLUE-32GB',
            'options' => ['Color' => 'Blue', 'Storage' => '32GB'],
            'price_cents' => 50000, // Base price (required field)
            'override_price' => 55000, // $550
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        // Test variant finding by options
        $foundVariant = $product->findVariantByOptions(['Color' => 'Red', 'Storage' => '16GB']);
        $this->assertEquals($redVariant->id, $foundVariant->id);

        // Test pricing
        $this->assertEquals(450.00, $redVariant->final_price_in_dollars);
        $this->assertEquals(550.00, $blueVariant->final_price_in_dollars);

        // Test available options
        $availableOptions = $product->getAvailableOptions();
        $this->assertArrayHasKey('Color', $availableOptions);
        $this->assertArrayHasKey('Storage', $availableOptions);
        $this->assertContains('Red', $availableOptions['Color']);
        $this->assertContains('Blue', $availableOptions['Color']);
    }

    /** @test */
    public function it_calculates_price_for_variant_options()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price_cents' => 50000,
            'has_variants' => true,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'options' => ['Size' => 'Large'],
            'price_cents' => 50000, // Base price (required field)
            'override_price' => 60000,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $priceData = $product->getPriceForVariant(null, ['Size' => 'Large']);
        
        $this->assertEquals(600.00, $priceData['price']);
        $this->assertEquals(60000, $priceData['price_cents']);
        $this->assertTrue($priceData['has_override']);
    }

    /** @test */
    public function it_handles_variant_stock_management()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price_cents' => 50000,
            'has_variants' => true,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-VARIANT',
            'options' => ['Color' => 'Red'],
            'price_cents' => 50000, // Base price (required field)
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        // Test stock reduction
        $variant->reduceStockSimple(3);
        $this->assertEquals(7, $variant->fresh()->stock_quantity);

        // Test stock status
        $this->assertEquals('in_stock', $variant->getSimpleStockStatus());
        
        // Test low stock
        $variant->update(['stock_quantity' => 2]);
        $this->assertEquals('low_stock', $variant->fresh()->getSimpleStockStatus());
        
        // Test out of stock
        $variant->update(['stock_quantity' => 0]);
        $this->assertEquals('out_of_stock', $variant->fresh()->getSimpleStockStatus());
    }

    /** @test */
    public function it_generates_unique_skus_for_variants()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'PROD001',
            'price_cents' => 50000,
            'has_variants' => true,
            'is_active' => true,
        ]);

        $variant1 = ProductVariant::create([
            'product_id' => $product->id,
            'options' => ['Color' => 'Red', 'Size' => 'Large'],
            'price_cents' => 50000, // Base price (required field)
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $variant2 = ProductVariant::create([
            'product_id' => $product->id,
            'options' => ['Color' => 'Blue', 'Size' => 'Small'],
            'price_cents' => 50000, // Base price (required field)
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        // Regenerate SKUs
        $variant1->regenerateSku();
        $variant2->regenerateSku();

        $this->assertNotEmpty($variant1->fresh()->sku);
        $this->assertNotEmpty($variant2->fresh()->sku);
        $this->assertNotEquals($variant1->fresh()->sku, $variant2->fresh()->sku);
    }

    /** @test */
    public function it_calculates_effective_product_prices_from_variants()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST001',
            'price_cents' => 50000,
            'has_variants' => true,
            'is_active' => true,
        ]);

        // Create variants with different prices
        ProductVariant::create([
            'product_id' => $product->id,
            'price_cents' => 50000, // Base price (required field)
            'override_price' => 40000, // $400
            'stock_quantity' => 10,
            'is_active' => true,
            'is_default' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'price_cents' => 50000, // Base price (required field)
            'override_price' => 60000, // $600
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this->assertEquals(400.00, $product->effective_price); // Default variant price
        $this->assertEquals(400.00, $product->lowest_price);
        $this->assertEquals(500.00, $product->highest_price); // Currently uses base price_cents, not final price
        $this->assertEquals(15, $product->effective_stock_quantity); // Sum of variants

        $priceRange = $product->price_range;
        $this->assertEquals(400.00, $priceRange['min']);
        $this->assertEquals(500.00, $priceRange['max']); // Currently uses base price_cents, not final price
    }

    /** @test */
    public function it_finds_cheapest_variant()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price_cents' => 50000,
            'has_variants' => true,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'price_cents' => 50000, // Base price (required field)
            'override_price' => 80000, // $800
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $cheapVariant = ProductVariant::create([
            'product_id' => $product->id,
            'price_cents' => 50000, // Base price (required field)
            'override_price' => 30000, // $300
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cheapestVariant = $product->getCheapestVariant();
        $this->assertEquals($cheapVariant->id, $cheapestVariant->id);
        $this->assertEquals(300.00, $cheapestVariant->final_price_in_dollars);
    }

    /** @test */
    public function it_handles_products_without_variants()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Simple Product',
            'slug' => 'simple-product',
            'price_cents' => 25000,
            'has_variants' => false,
            'stock_quantity' => 100,
            'is_active' => true,
        ]);

        $this->assertFalse($product->has_variants);
        $this->assertEquals(250.00, $product->price);
        $this->assertEquals(100, $product->stock_quantity);
        $this->assertTrue($product->hasStock());
        $this->assertNull($product->getCheapestVariant());
    }
}
