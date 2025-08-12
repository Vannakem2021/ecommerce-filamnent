<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2InventoryConsistencyTest extends TestCase
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
    public function in_stock_field_has_been_removed_from_database()
    {
        // Create a product and verify in_stock field doesn't exist
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        // Check that in_stock is not in the fillable array
        $this->assertNotContains('in_stock', $product->getFillable());

        // Verify we can't set in_stock field
        $product->fill(['in_stock' => true]);
        $this->assertArrayNotHasKey('in_stock', $product->getAttributes());
    }

    /** @test */
    public function stock_status_is_calculated_automatically()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => 10,
            'track_inventory' => true,
        ]);

        // Stock status should be calculated, not stored
        $this->assertEquals('in_stock', $product->stock_status);

        // Change stock quantity and verify status updates
        $product->stock_quantity = 0;
        $this->assertEquals('out_of_stock', $product->stock_status);

        // Test low stock
        $product->stock_quantity = 3;
        $product->low_stock_threshold = 5;
        $this->assertEquals('low_stock', $product->stock_status);
    }

    /** @test */
    public function variant_stock_status_is_calculated_automatically()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => true,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 15,
            'track_inventory' => true,
        ]);

        // Variant stock status should be calculated
        $this->assertEquals('in_stock', $variant->stock_status);

        // Change variant stock and verify status updates
        $variant->stock_quantity = 0;
        $this->assertEquals('out_of_stock', $variant->stock_status);

        // Test low stock for variant
        $variant->stock_quantity = 2;
        $variant->low_stock_threshold = 5;
        $this->assertEquals('low_stock', $variant->stock_status);
    }

    /** @test */
    public function stock_status_not_in_fillable_arrays()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        // stock_status should not be in fillable arrays
        $this->assertNotContains('stock_status', $product->getFillable());
        $this->assertNotContains('stock_status', $variant->getFillable());
    }

    /** @test */
    public function products_with_variants_use_variant_stock_only()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => true,
            'stock_quantity' => 100, // This should be ignored
            'track_inventory' => true,
        ]);

        // Create variants with different stock levels
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 10,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 5,
            'is_active' => true,
            'track_inventory' => true,
        ]);

        // Product stock should come from variants, not product stock_quantity
        $this->assertEquals(15, InventoryService::getTotalStock($product));
        $this->assertTrue(InventoryService::hasStock($product));
        $this->assertEquals('in_stock', InventoryService::getStockStatus($product));
    }

    /** @test */
    public function products_without_variants_use_product_stock_only()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => false,
            'stock_quantity' => 25,
            'track_inventory' => true,
        ]);

        // Product stock should come from product stock_quantity
        $this->assertEquals(25, InventoryService::getTotalStock($product));
        $this->assertTrue(InventoryService::hasStock($product));
        $this->assertEquals('in_stock', InventoryService::getStockStatus($product));
    }

    /** @test */
    public function inventory_service_and_model_methods_are_consistent()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => 8,
            'low_stock_threshold' => 10,
        ]);

        // All methods should return the same results
        $this->assertEquals(
            InventoryService::hasStock($product),
            $product->hasStock()
        );

        $this->assertEquals(
            InventoryService::getTotalStock($product),
            $product->getTotalStock()
        );

        $this->assertEquals(
            InventoryService::getStockStatus($product),
            $product->getStockStatus()
        );

        $this->assertEquals(
            InventoryService::isLowStock($product),
            $product->isLowStock()
        );
    }

    /** @test */
    public function factory_methods_work_without_in_stock_field()
    {
        // Test inStock factory method
        $inStockProduct = Product::factory()->inStock()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->assertTrue($inStockProduct->stock_quantity > 0);
        $this->assertEquals('in_stock', $inStockProduct->stock_status);

        // Test outOfStock factory method
        $outOfStockProduct = Product::factory()->outOfStock()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'track_inventory' => true,
        ]);

        $this->assertEquals(0, $outOfStockProduct->stock_quantity);
        $this->assertEquals('out_of_stock', $outOfStockProduct->stock_status);
    }

    /** @test */
    public function calculated_stock_status_handles_edge_cases()
    {
        // Test product without inventory tracking
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'stock_quantity' => 0,
            'track_inventory' => false,
        ]);

        $this->assertEquals('in_stock', $product->stock_status);

        // Test product with variants but no active variants
        $productWithVariants = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => true,
            'track_inventory' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $productWithVariants->id,
            'stock_quantity' => 10,
            'is_active' => false, // Inactive variant
            'track_inventory' => true,
        ]);

        // Refresh the product to ensure relationships are loaded
        $productWithVariants->refresh();
        $this->assertEquals('out_of_stock', $productWithVariants->stock_status);
    }

    /** @test */
    public function bulk_stock_checking_works_correctly()
    {
        $products = collect([
            Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'stock_quantity' => 10,
                'low_stock_threshold' => 5,
                'track_inventory' => true,
            ]),
            Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'stock_quantity' => 0,
                'track_inventory' => true,
            ]),
            Product::factory()->create([
                'category_id' => $this->category->id,
                'brand_id' => $this->brand->id,
                'stock_quantity' => 3,
                'low_stock_threshold' => 5,
                'track_inventory' => true,
            ]),
        ]);

        $bulkResults = InventoryService::bulkCheckStock($products);

        $this->assertTrue($bulkResults[$products[0]->id]['has_stock']);
        $this->assertEquals('in_stock', $bulkResults[$products[0]->id]['status']);

        $this->assertFalse($bulkResults[$products[1]->id]['has_stock']);
        $this->assertEquals('out_of_stock', $bulkResults[$products[1]->id]['status']);

        $this->assertTrue($bulkResults[$products[2]->id]['has_stock']);
        $this->assertEquals('low_stock', $bulkResults[$products[2]->id]['status']);
    }
}
