<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleVariantTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_variants_and_get_available_options()
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
            'price_cents' => 100000, // $1000 base price
        ]);

        // Verify product has variants flag
        $this->assertTrue($product->has_variants);

        // Create variants
        $blackVariant = $product->createSimpleVariant(['Color' => 'Black'], null, 10);
        $whiteVariant = $product->createSimpleVariant(['Color' => 'White'], null, 15);

        // Verify variants were created
        $this->assertNotNull($blackVariant);
        $this->assertNotNull($whiteVariant);
        $this->assertEquals(2, $product->variants()->count());

        // Test getAvailableOptions
        $availableOptions = $product->getAvailableOptions();
        
        $this->assertNotNull($availableOptions);
        $this->assertIsArray($availableOptions);
        $this->assertArrayHasKey('Color', $availableOptions);
        $this->assertContains('Black', $availableOptions['Color']);
        $this->assertContains('White', $availableOptions['Color']);

        // Test variant options
        $this->assertEquals(['Color' => 'Black'], $blackVariant->options);
        $this->assertEquals(['Color' => 'White'], $whiteVariant->options);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_find_variants_by_options()
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

        // Test finding variants by options
        $foundBlack = $product->findVariantByOptions(['Color' => 'Black']);
        $foundWhite = $product->findVariantByOptions(['Color' => 'White']);

        $this->assertNotNull($foundBlack);
        $this->assertNotNull($foundWhite);
        $this->assertEquals($blackVariant->id, $foundBlack->id);
        $this->assertEquals($whiteVariant->id, $foundWhite->id);

        // Test non-existent variant
        $notFound = $product->findVariantByOptions(['Color' => 'Red']);
        $this->assertNull($notFound);
    }
}
