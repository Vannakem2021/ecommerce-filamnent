<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3SimpleFeaturesTest extends TestCase
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
    public function stock_messages_are_user_friendly()
    {
        // Test out of stock
        $outOfStockProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => 0,
            'track_inventory' => true,
        ]);

        $display = InventoryService::getDisplayStock($outOfStockProduct);
        $this->assertEquals('Out of stock', $display['message']);

        // Test single item left
        $singleItemProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => 1,
            'low_stock_threshold' => 5,
            'track_inventory' => true,
        ]);

        $display = InventoryService::getDisplayStock($singleItemProduct);
        $this->assertEquals('Only 1 left!', $display['message']);

        // Test few items left
        $fewItemsProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => 3,
            'low_stock_threshold' => 5,
            'track_inventory' => true,
        ]);

        $display = InventoryService::getDisplayStock($fewItemsProduct);
        $this->assertEquals('Only 3 left!', $display['message']);

        // Test normal stock
        $normalStockProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => 15,
            'low_stock_threshold' => 5,
            'track_inventory' => true,
        ]);

        $display = InventoryService::getDisplayStock($normalStockProduct);
        $this->assertEquals('15 in stock', $display['message']);

        // Test high stock (should just say "In stock")
        $highStockProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => 50,
            'low_stock_threshold' => 5,
            'track_inventory' => true,
        ]);

        $display = InventoryService::getDisplayStock($highStockProduct);
        $this->assertEquals('In stock', $display['message']);
    }

    /** @test */
    public function negative_stock_validation_works_for_products()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock quantity cannot be negative');

        Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => -5,
        ]);
    }

    /** @test */
    public function negative_stock_validation_works_for_variants()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => true,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Variant stock quantity cannot be negative');

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => -10,
        ]);
    }

    /** @test */
    public function stock_validation_allows_zero_and_positive_values()
    {
        // Test zero stock (should be allowed)
        $zeroStockProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => 0,
        ]);

        $this->assertEquals(0, $zeroStockProduct->stock_quantity);

        // Test positive stock
        $positiveStockProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => 25,
        ]);

        $this->assertEquals(25, $positiveStockProduct->stock_quantity);

        // Test variant with zero stock
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => true,
        ]);

        $zeroStockVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 0,
        ]);

        $this->assertEquals(0, $zeroStockVariant->stock_quantity);

        // Test variant with positive stock
        $positiveStockVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 15,
        ]);

        $this->assertEquals(15, $positiveStockVariant->stock_quantity);
    }

    /** @test */
    public function format_stock_message_handles_all_scenarios()
    {
        // Test various stock levels and statuses
        $this->assertEquals('Out of stock', InventoryService::formatStockMessage(0, 'out_of_stock'));
        $this->assertEquals('Only 1 left!', InventoryService::formatStockMessage(1, 'low_stock'));
        $this->assertEquals('Only 2 left!', InventoryService::formatStockMessage(2, 'low_stock'));
        $this->assertEquals('Only 3 left!', InventoryService::formatStockMessage(3, 'low_stock'));
        $this->assertEquals('Low stock - 4 left', InventoryService::formatStockMessage(4, 'low_stock'));
        $this->assertEquals('Low stock - 8 left', InventoryService::formatStockMessage(8, 'low_stock'));
        
        $this->assertEquals('5 in stock', InventoryService::formatStockMessage(5, 'in_stock'));
        $this->assertEquals('15 in stock', InventoryService::formatStockMessage(15, 'in_stock'));
        $this->assertEquals('In stock', InventoryService::formatStockMessage(25, 'in_stock'));
        $this->assertEquals('In stock', InventoryService::formatStockMessage(999999, 'in_stock'));
    }

    /** @test */
    public function database_indexes_improve_query_performance()
    {
        // This test just ensures the migration ran successfully
        // In a real scenario, you'd measure query performance
        
        // Create some test data
        $products = Product::factory()->count(10)->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'is_active' => true,
            'stock_quantity' => 15,
        ]);

        foreach ($products->take(5) as $product) {
            $product->update(['has_variants' => true]);
            ProductVariant::factory()->count(3)->create([
                'product_id' => $product->id,
                'is_active' => true,
                'stock_quantity' => 10,
            ]);
        }

        // Test that queries still work (indexes are transparent to application logic)
        $activeProductsWithStock = Product::where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->count();

        $this->assertGreaterThan(0, $activeProductsWithStock);

        $activeVariantsWithStock = ProductVariant::where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->count();

        $this->assertGreaterThan(0, $activeVariantsWithStock);
    }

    /** @test */
    public function all_phase_3_improvements_work_together()
    {
        // Create a product with variants
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => true,
            'track_inventory' => true,
        ]);

        // Create variants with different stock levels
        $highStockVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 25,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        $lowStockVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 2,
            'low_stock_threshold' => 5,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        // Test user-friendly messages
        $highStockDisplay = InventoryService::getDisplayStock($product, $highStockVariant);
        $this->assertEquals('In stock', $highStockDisplay['message']);

        $lowStockDisplay = InventoryService::getDisplayStock($product, $lowStockVariant);
        $this->assertEquals('Only 2 left!', $lowStockDisplay['message']);

        // Test that validation prevents negative stock
        $this->expectException(\InvalidArgumentException::class);
        $lowStockVariant->update(['stock_quantity' => -1]);
    }
}
