<?php

namespace Tests\Feature;

use App\Livewire\ProductDetailPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductVariantDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;
    protected Brand $brand;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->category = Category::factory()->create(['name' => 'Electronics']);
        $this->brand = Brand::factory()->create(['name' => 'TestBrand', 'slug' => 'testbrand']);

        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => true,
            'price_cents' => 100000, // $1000 base price
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_displays_color_variants_correctly()
    {
        // Create variants with different colors
        $blackVariant = $this->product->createSimpleVariant(['Color' => 'Black'], null, 10);
        $whiteVariant = $this->product->createSimpleVariant(['Color' => 'White'], null, 15);

        $blackVariant->update(['is_default' => true]);

        // Refresh the product to ensure relationships are loaded
        $this->product->refresh();

        $component = Livewire::test(ProductDetailPage::class, ['slug' => $this->product->slug]);

        // Debug: Check what we actually have
        $availableOptions = $component->get('availableOptions');
        $this->assertNotNull($availableOptions, 'Available options should not be null');

        // Check that available options are loaded
        $this->assertArrayHasKey('Color', $availableOptions);
        $this->assertContains('Black', $availableOptions['Color']);
        $this->assertContains('White', $availableOptions['Color']);

        // Check that default variant is selected
        $selectedOptions = $component->get('selectedOptions');
        $this->assertNotNull($selectedOptions, 'Selected options should not be null');
        $this->assertEquals('Black', $selectedOptions['Color']);

        $selectedVariant = $component->get('selectedVariant');
        $this->assertNotNull($selectedVariant, 'Selected variant should not be null');
        $this->assertEquals($blackVariant->id, $selectedVariant->id);
    }

    /** @test */
    public function it_displays_storage_variants_with_pricing()
    {
        // Create variants with different storage and pricing
        $variant128 = $this->product->createSimpleVariant(['Storage' => '128GB'], null, 10);
        $variant256 = $this->product->createSimpleVariant(['Storage' => '256GB'], 120000, 8); // $1200
        
        $variant128->update(['is_default' => true]);

        $component = Livewire::test(ProductDetailPage::class, ['slug' => $this->product->slug]);

        // Check that storage options are available
        $this->assertArrayHasKey('Storage', $component->get('availableOptions'));
        $this->assertContains('128GB', $component->get('availableOptions')['Storage']);
        $this->assertContains('256GB', $component->get('availableOptions')['Storage']);

        // Check pricing for different variants
        $this->assertEquals(1000, $variant128->final_price_in_dollars); // Uses base price
        $this->assertEquals(1200, $variant256->final_price_in_dollars); // Uses override price
    }

    /** @test */
    public function it_handles_variant_selection_correctly()
    {
        // Create variants with both color and storage
        $blackSmall = $this->product->createSimpleVariant(['Color' => 'Black', 'Storage' => '128GB'], null, 10);
        $blackLarge = $this->product->createSimpleVariant(['Color' => 'Black', 'Storage' => '256GB'], 120000, 8);
        $whiteSmall = $this->product->createSimpleVariant(['Color' => 'White', 'Storage' => '128GB'], null, 15);
        
        $blackSmall->update(['is_default' => true]);

        $component = Livewire::test(ProductDetailPage::class, ['slug' => $this->product->slug]);

        // Initially should have black small selected
        $this->assertEquals('Black', $component->get('selectedOptions')['Color']);
        $this->assertEquals('128GB', $component->get('selectedOptions')['Storage']);
        $this->assertEquals($blackSmall->id, $component->get('selectedVariant')->id);

        // Select white color
        $component->call('selectOption', 'Color', 'White');
        
        // Should now have white small selected
        $this->assertEquals('White', $component->get('selectedOptions')['Color']);
        $this->assertEquals('128GB', $component->get('selectedOptions')['Storage']);
        $this->assertEquals($whiteSmall->id, $component->get('selectedVariant')->id);

        // Select 256GB storage
        $component->call('selectOption', 'Storage', '256GB');
        
        // Should now try to find white 256GB variant (doesn't exist), so no variant selected
        $this->assertEquals('White', $component->get('selectedOptions')['Color']);
        $this->assertEquals('256GB', $component->get('selectedOptions')['Storage']);
        $this->assertNull($component->get('selectedVariant'));
    }

    /** @test */
    public function it_normalizes_option_keys_correctly()
    {
        // Create a variant with trailing space in key (simulating data issue)
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'options' => ['Color ' => 'Black'], // Note the trailing space
            'is_default' => true,
        ]);

        $component = Livewire::test(ProductDetailPage::class, ['slug' => $this->product->slug]);

        // Should normalize the key and make it available
        $this->assertArrayHasKey('Color', $component->get('availableOptions'));
        $this->assertContains('Black', $component->get('availableOptions')['Color']);
        
        // Should be able to select the option
        $component->call('selectOption', 'Color', 'Black');
        $this->assertEquals('Black', $component->get('selectedOptions')['Color']);
        $this->assertEquals($variant->id, $component->get('selectedVariant')->id);
    }

    /** @test */
    public function it_displays_price_range_when_no_variant_selected()
    {
        // Create variants with different prices
        $cheapVariant = $this->product->createSimpleVariant(['Color' => 'Black'], null, 10); // $1000
        $expensiveVariant = $this->product->createSimpleVariant(['Color' => 'Gold'], 150000, 5); // $1500

        $component = Livewire::test(ProductDetailPage::class, ['slug' => $this->product->slug]);

        // Clear any selected variant
        $component->set('selectedVariant', null);
        $component->set('selectedOptions', []);

        $priceRange = $component->call('getCurrentPriceRange');
        
        $this->assertNotNull($priceRange);
        $this->assertEquals(1000, $priceRange['min']);
        $this->assertEquals(1500, $priceRange['max']);
    }
}
