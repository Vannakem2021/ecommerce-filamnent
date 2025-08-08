<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantsTest extends TestCase
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
    public function it_can_create_product_attributes_and_values()
    {
        // Create Color attribute
        $colorAttribute = ProductAttribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'color',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Create color values
        $redValue = ProductAttributeValue::create([
            'product_attribute_id' => $colorAttribute->id,
            'value' => 'Red',
            'slug' => 'red',
            'color_code' => '#FF0000',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $blueValue = ProductAttributeValue::create([
            'product_attribute_id' => $colorAttribute->id,
            'value' => 'Blue',
            'slug' => 'blue',
            'color_code' => '#0000FF',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Create Storage attribute
        $storageAttribute = ProductAttribute::create([
            'name' => 'Storage',
            'slug' => 'storage',
            'type' => 'select',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Create storage values
        $storage16gb = ProductAttributeValue::create([
            'product_attribute_id' => $storageAttribute->id,
            'value' => '16GB',
            'slug' => '16gb',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $storage32gb = ProductAttributeValue::create([
            'product_attribute_id' => $storageAttribute->id,
            'value' => '32GB',
            'slug' => '32gb',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->assertEquals('Color', $colorAttribute->name);
        $this->assertEquals('color', $colorAttribute->slug);
        $this->assertEquals(2, $colorAttribute->values()->count());
        $this->assertEquals(2, $storageAttribute->values()->count());
    }

    /** @test */
    public function it_can_create_product_with_variants()
    {
        // Create attributes
        $colorAttribute = ProductAttribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'color',
            'is_active' => true,
        ]);

        $redValue = ProductAttributeValue::create([
            'product_attribute_id' => $colorAttribute->id,
            'value' => 'Red',
            'slug' => 'red',
            'is_active' => true,
        ]);

        $blueValue = ProductAttributeValue::create([
            'product_attribute_id' => $colorAttribute->id,
            'value' => 'Blue',
            'slug' => 'blue',
            'is_active' => true,
        ]);

        // Create product with variants
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Smartphone',
            'slug' => 'test-smartphone',
            'sku' => 'PHONE001',
            'price_cents' => 99999,
            'has_variants' => true,
            'variant_type' => 'multiple',
            'variant_attributes' => [$colorAttribute->id],
            'is_active' => true,
        ]);

        // Create variants
        $redVariant = ProductVariant::create([
            'product_id' => $product->id,
            'price_cents' => 99999,
            'stock_quantity' => 10,
            'is_active' => true,
            'is_default' => true,
        ]);

        $blueVariant = ProductVariant::create([
            'product_id' => $product->id,
            'price_cents' => 99999,
            'stock_quantity' => 5,
            'is_active' => true,
            'is_default' => false,
        ]);

        // Attach attribute values to variants
        $redVariant->attributeValues()->attach($redValue->id, [
            'product_attribute_id' => $colorAttribute->id
        ]);

        $blueVariant->attributeValues()->attach($blueValue->id, [
            'product_attribute_id' => $colorAttribute->id
        ]);

        // Regenerate SKUs
        $redVariant->sku = $redVariant->generateSku();
        $redVariant->save();

        $blueVariant->sku = $blueVariant->generateSku();
        $blueVariant->save();

        $this->assertTrue($product->has_variants);
        $this->assertEquals(2, $product->variants()->count());
        $this->assertEquals('PHONE001-RED', $redVariant->fresh()->sku);
        $this->assertEquals('PHONE001-BLUE', $blueVariant->fresh()->sku);
        $this->assertTrue($redVariant->fresh()->is_default);
        $this->assertFalse($blueVariant->fresh()->is_default);
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
            'variant_type' => 'multiple',
            'is_active' => true,
        ]);

        // Create variants with different prices
        $variant1 = ProductVariant::create([
            'product_id' => $product->id,
            'price_cents' => 40000, // $400
            'stock_quantity' => 10,
            'is_active' => true,
            'is_default' => true,
        ]);

        $variant2 = ProductVariant::create([
            'product_id' => $product->id,
            'price_cents' => 60000, // $600
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this->assertEquals(400.00, $product->effective_price); // Default variant price
        $this->assertEquals(400.00, $product->lowest_price);
        $this->assertEquals(600.00, $product->highest_price);
        $this->assertEquals(15, $product->effective_stock_quantity); // Sum of variants

        $priceRange = $product->price_range;
        $this->assertEquals(400.00, $priceRange['min']);
        $this->assertEquals(600.00, $priceRange['max']);
    }

    /** @test */
    public function it_handles_variant_stock_management()
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

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'price_cents' => 50000,
            'stock_quantity' => 10,
            'low_stock_threshold' => 5,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        // Test stock reduction
        $result = $variant->reduceStock(3);
        $this->assertTrue($result);
        $this->assertEquals(7, $variant->fresh()->stock_quantity);

        // Test low stock detection
        $variant->reduceStock(3); // Now at 4, below threshold of 5
        $this->assertTrue($variant->fresh()->is_low_stock);

        // Test stock depletion
        $variant->reduceStock(4); // Now at 0
        $this->assertEquals('out_of_stock', $variant->fresh()->stock_status);

        // Test insufficient stock
        $result = $variant->reduceStock(1);
        $this->assertFalse($result);
        $this->assertEquals(0, $variant->fresh()->stock_quantity);
    }

    /** @test */
    public function it_generates_unique_skus_for_variants()
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST001',
            'price_cents' => 50000,
            'is_active' => true,
        ]);

        $variant1 = ProductVariant::create([
            'product_id' => $product->id,
            'price_cents' => 50000,
            'is_active' => true,
        ]);

        $variant2 = ProductVariant::create([
            'product_id' => $product->id,
            'price_cents' => 50000,
            'is_active' => true,
        ]);

        // SKUs should be unique
        $this->assertNotEquals($variant1->sku, $variant2->sku);
        $this->assertStringStartsWith('TEST001', $variant1->sku);
        $this->assertStringStartsWith('TEST001', $variant2->sku);
    }
}
