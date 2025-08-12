<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $category;
    protected $brand;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->category = Category::factory()->create();
        $this->brand = Brand::factory()->create();
    }

    /** @test */
    public function it_correctly_calculates_stock_for_product_without_variants()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => false,
            'stock_quantity' => 50,
            'track_inventory' => true,
        ]);

        $this->assertTrue(InventoryService::hasStock($product));
        $this->assertEquals(50, InventoryService::getTotalStock($product));
        $this->assertEquals('in_stock', InventoryService::getStockStatus($product));
    }

    /** @test */
    public function it_correctly_calculates_stock_for_product_with_variants()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => true,
            'stock_quantity' => 0, // Should be ignored
            'track_inventory' => true,
        ]);

        // Create variants with stock
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 20,
            'is_active' => true,
        ]);

        // Create inactive variant (should be ignored)
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 100,
            'is_active' => false,
        ]);

        $this->assertTrue(InventoryService::hasStock($product));
        $this->assertEquals(30, InventoryService::getTotalStock($product)); // 10 + 20, ignoring inactive
        $this->assertEquals('in_stock', InventoryService::getStockStatus($product));
    }

    /** @test */
    public function it_correctly_handles_out_of_stock_products()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => false,
            'stock_quantity' => 0,
            'track_inventory' => true,
        ]);

        $this->assertFalse(InventoryService::hasStock($product));
        $this->assertEquals(0, InventoryService::getTotalStock($product));
        $this->assertEquals('out_of_stock', InventoryService::getStockStatus($product));
    }

    /** @test */
    public function it_correctly_handles_low_stock_products()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => false,
            'stock_quantity' => 3,
            'low_stock_threshold' => 5,
            'track_inventory' => true,
        ]);

        $this->assertTrue(InventoryService::hasStock($product));
        $this->assertTrue(InventoryService::isLowStock($product));
        $this->assertEquals(3, InventoryService::getTotalStock($product));
        $this->assertEquals('low_stock', InventoryService::getStockStatus($product));
    }

    /** @test */
    public function it_validates_quantity_correctly()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => false,
            'stock_quantity' => 10,
            'track_inventory' => true,
        ]);

        // Valid quantity
        $result = InventoryService::validateQuantity($product, 5);
        $this->assertTrue($result['valid']);
        $this->assertEquals('Stock available', $result['message']);
        $this->assertEquals(10, $result['available']);

        // Invalid quantity (too much)
        $result = InventoryService::validateQuantity($product, 15);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Insufficient stock', $result['message']);
        $this->assertEquals(10, $result['available']);

        // Invalid quantity (zero or negative)
        $result = InventoryService::validateQuantity($product, 0);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Quantity must be greater than 0', $result['message']);
    }

    /** @test */
    public function it_handles_products_without_inventory_tracking()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => false,
            'stock_quantity' => 0,
            'track_inventory' => false,
        ]);

        $this->assertTrue(InventoryService::hasStock($product));
        $this->assertEquals(999999, InventoryService::getTotalStock($product));
        $this->assertEquals('in_stock', InventoryService::getStockStatus($product));
    }

    /** @test */
    public function it_provides_correct_display_stock_information()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => false,
            'stock_quantity' => 25,
            'track_inventory' => true,
        ]);

        $displayStock = InventoryService::getDisplayStock($product);
        
        $this->assertEquals(25, $displayStock['quantity']);
        $this->assertEquals('in_stock', $displayStock['status']);
        $this->assertEquals('In stock', $displayStock['message']); // Updated to new user-friendly format
    }

    /** @test */
    public function model_methods_use_inventory_service()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => false,
            'stock_quantity' => 15,
            'track_inventory' => true,
        ]);

        // Test that model methods delegate to InventoryService
        $this->assertEquals(InventoryService::hasStock($product), $product->hasStock());
        $this->assertEquals(InventoryService::getTotalStock($product), $product->getTotalStock());
        $this->assertEquals(InventoryService::getStockStatus($product), $product->getStockStatus());
        $this->assertEquals(InventoryService::isLowStock($product), $product->isLowStock());
    }
}
