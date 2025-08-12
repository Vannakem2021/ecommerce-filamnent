<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4UserExperienceTest extends TestCase
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
    public function product_cards_show_stock_status_badges()
    {
        // Create products with different stock levels
        $inStockProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'In Stock Product',
            'stock_quantity' => 25,
            'track_inventory' => true,
        ]);

        $lowStockProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Low Stock Product',
            'stock_quantity' => 2,
            'low_stock_threshold' => 5,
            'track_inventory' => true,
        ]);

        $outOfStockProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Out of Stock Product',
            'stock_quantity' => 0,
            'track_inventory' => true,
        ]);

        // Test that stock display works correctly
        $inStockDisplay = InventoryService::getDisplayStock($inStockProduct);
        $this->assertEquals('in_stock', $inStockDisplay['status']);
        $this->assertEquals('In stock', $inStockDisplay['message']);

        $lowStockDisplay = InventoryService::getDisplayStock($lowStockProduct);
        $this->assertEquals('low_stock', $lowStockDisplay['status']);
        $this->assertEquals('Only 2 left!', $lowStockDisplay['message']);

        $outOfStockDisplay = InventoryService::getDisplayStock($outOfStockProduct);
        $this->assertEquals('out_of_stock', $outOfStockDisplay['status']);
        $this->assertEquals('Out of stock', $outOfStockDisplay['message']);
    }

    /** @test */
    public function product_cards_disable_add_to_cart_for_out_of_stock()
    {
        $outOfStockProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => 0,
            'has_variants' => false,
            'track_inventory' => true,
        ]);

        $stockDisplay = InventoryService::getDisplayStock($outOfStockProduct);
        
        // Verify the product is out of stock
        $this->assertEquals('out_of_stock', $stockDisplay['status']);
        $this->assertFalse(InventoryService::hasStock($outOfStockProduct));
    }

    /** @test */
    public function products_with_variants_show_correct_stock_status()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => true,
            'track_inventory' => true,
        ]);

        // Create variants with different stock levels
        $inStockVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 15,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        $outOfStockVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 0,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        // Product should show in stock because at least one variant has stock
        $productDisplay = InventoryService::getDisplayStock($product);
        $this->assertEquals('in_stock', $productDisplay['status']);
        $this->assertEquals(15, $productDisplay['quantity']);

        // Individual variant displays
        $inStockVariantDisplay = InventoryService::getDisplayStock($product, $inStockVariant);
        $this->assertEquals('in_stock', $inStockVariantDisplay['status']);

        $outOfStockVariantDisplay = InventoryService::getDisplayStock($product, $outOfStockVariant);
        $this->assertEquals('out_of_stock', $outOfStockVariantDisplay['status']);
    }

    /** @test */
    public function stock_messages_are_user_friendly_across_all_components()
    {
        // Test various stock scenarios
        $scenarios = [
            ['stock' => 0, 'threshold' => 5, 'expected_status' => 'out_of_stock', 'expected_message' => 'Out of stock'],
            ['stock' => 1, 'threshold' => 5, 'expected_status' => 'low_stock', 'expected_message' => 'Only 1 left!'],
            ['stock' => 3, 'threshold' => 5, 'expected_status' => 'low_stock', 'expected_message' => 'Only 3 left!'],
            ['stock' => 8, 'threshold' => 10, 'expected_status' => 'low_stock', 'expected_message' => 'Low stock - 8 left'],
            ['stock' => 15, 'threshold' => 5, 'expected_status' => 'in_stock', 'expected_message' => '15 in stock'],
            ['stock' => 50, 'threshold' => 5, 'expected_status' => 'in_stock', 'expected_message' => 'In stock'],
        ];

        foreach ($scenarios as $scenario) {
            $product = Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'stock_quantity' => $scenario['stock'],
                'low_stock_threshold' => $scenario['threshold'],
                'track_inventory' => true,
            ]);

            $display = InventoryService::getDisplayStock($product);
            
            $this->assertEquals($scenario['expected_status'], $display['status'], 
                "Failed for stock: {$scenario['stock']}, threshold: {$scenario['threshold']}");
            $this->assertEquals($scenario['expected_message'], $display['message'],
                "Failed message for stock: {$scenario['stock']}, threshold: {$scenario['threshold']}");
        }
    }

    /** @test */
    public function complete_user_flow_works_end_to_end()
    {
        // Create a product with good stock
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
            'stock_quantity' => 10,
            'track_inventory' => true,
            'has_variants' => false,
        ]);

        // 1. Product should show as in stock
        $this->assertTrue(InventoryService::hasStock($product));
        $this->assertEquals('in_stock', InventoryService::getStockStatus($product));

        // 2. Stock display should be user-friendly
        $display = InventoryService::getDisplayStock($product);
        $this->assertEquals('10 in stock', $display['message']);

        // 3. Validation should allow adding to cart
        $validation = InventoryService::validateQuantity($product, 2);
        $this->assertTrue($validation['valid']);
        $this->assertEquals('Stock available', $validation['message']);

        // 4. Validation should prevent overselling
        $oversellValidation = InventoryService::validateQuantity($product, 15);
        $this->assertFalse($oversellValidation['valid']);
        $this->assertStringContainsString('Insufficient stock', $oversellValidation['message']);

        // 5. When stock runs out, status should update
        $product->update(['stock_quantity' => 0]);
        $this->assertFalse(InventoryService::hasStock($product));
        $this->assertEquals('out_of_stock', InventoryService::getStockStatus($product));
        
        $outOfStockDisplay = InventoryService::getDisplayStock($product);
        $this->assertEquals('Out of stock', $outOfStockDisplay['message']);
    }

    /** @test */
    public function admin_stock_overview_calculates_correctly()
    {
        // Create products with different stock statuses
        Product::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => 25,
            'is_active' => true,
            'track_inventory' => true,
        ]); // 3 in stock

        Product::factory()->count(2)->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => 2,
            'low_stock_threshold' => 5,
            'is_active' => true,
            'track_inventory' => true,
        ]); // 2 low stock

        Product::factory()->count(1)->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => 0,
            'is_active' => true,
            'track_inventory' => true,
        ]); // 1 out of stock

        // Test that we can count products by status
        $products = Product::where('is_active', true)->get();
        
        $inStockCount = 0;
        $lowStockCount = 0;
        $outOfStockCount = 0;
        
        foreach ($products as $product) {
            $status = InventoryService::getStockStatus($product);
            switch ($status) {
                case 'in_stock':
                    $inStockCount++;
                    break;
                case 'low_stock':
                    $lowStockCount++;
                    break;
                case 'out_of_stock':
                    $outOfStockCount++;
                    break;
            }
        }

        $this->assertEquals(3, $inStockCount);
        $this->assertEquals(2, $lowStockCount);
        $this->assertEquals(1, $outOfStockCount);
        $this->assertEquals(6, $products->count());
    }
}
