<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPricingInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test category and brand
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
    public function it_can_create_product_with_advanced_pricing()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST001',
            'price_cents' => 9999,
            'compare_price_cents' => 12999,
            'cost_price_cents' => 5000,
            'stock_quantity' => 100,
            'stock_status' => 'in_stock',
            'low_stock_threshold' => 10,
            'track_inventory' => true,
        ]);

        $this->assertEquals(9999, $product->price_cents);
        $this->assertEquals(12999, $product->compare_price_cents);
        $this->assertEquals(5000, $product->cost_price_cents);
        $this->assertEquals(99.99, $product->price);
        $this->assertEquals(129.99, $product->compare_price);
        $this->assertEquals(50.00, $product->cost_price);
    }

    /** @test */
    public function it_calculates_profit_margin_correctly()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST001',
            'price_cents' => 10000,
            'cost_price_cents' => 6000,
            'stock_quantity' => 50,
        ]);

        $this->assertEquals(40.0, $product->profit_margin);
        $this->assertEquals(40.00, $product->profit_amount);
    }

    /** @test */
    public function it_calculates_discount_percentage_correctly()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST001',
            'price_cents' => 8000,
            'compare_price_cents' => 10000,
            'stock_quantity' => 50,
        ]);

        $this->assertEquals(20.0, $product->discount_percentage);
        $this->assertTrue($product->is_on_sale);
    }

    /** @test */
    public function it_detects_low_stock_correctly()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST001',
            'price_cents' => 5000,
            'stock_quantity' => 5,
            'low_stock_threshold' => 10,
            'track_inventory' => true,
        ]);

        $this->assertTrue($product->is_low_stock);
    }

    /** @test */
    public function it_can_reduce_stock_quantity()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST001',
            'price_cents' => 5000,
            'stock_quantity' => 100,
            'track_inventory' => true,
        ]);

        $result = $product->reduceStock(10);

        $this->assertTrue($result);
        $this->assertEquals(90, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function it_prevents_reducing_stock_below_zero()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST001',
            'price_cents' => 5000,
            'stock_quantity' => 5,
            'track_inventory' => true,
        ]);

        $result = $product->reduceStock(10);

        $this->assertFalse($result);
        $this->assertEquals(5, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function it_updates_stock_status_automatically()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST001',
            'price_cents' => 5000,
            'stock_quantity' => 1,
            'track_inventory' => true,
        ]);

        $product->reduceStock(1);

        $this->assertEquals('out_of_stock', $product->fresh()->stock_status);
    }
}
