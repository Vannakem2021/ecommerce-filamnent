<?php

namespace Tests\Feature;

use App\Livewire\ProductDetailPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SimpleVariantSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;
    protected Brand $brand;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->category = Category::factory()->create(['name' => 'Smartphones']);
        $this->brand = Brand::factory()->create(['name' => 'Apple', 'slug' => 'apple']);
        
        $this->product = Product::factory()->create([
            'name' => 'iPhone 15 Pro',
            'slug' => 'iphone-15-pro',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'has_variants' => true,
            'price_cents' => 99900, // $999 base price
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_simple_variants()
    {
        // Create variants using simple method
        $black128 = $this->product->addVariant('Black', '128GB', 0, 10);
        $black256 = $this->product->addVariant('Black', '256GB', 10000, 8);
        $white128 = $this->product->addVariant('White', '128GB', 0, 15);
        
        $black128->update(['is_default' => true]);

        // Test variant creation
        $this->assertEquals('Black', $black128->getColor());
        $this->assertEquals('128GB', $black128->getStorage());
        $this->assertEquals(999, $black128->getFinalPrice());
        $this->assertEquals('Black - 128GB', $black128->getDisplayName());

        $this->assertEquals('Black', $black256->getColor());
        $this->assertEquals('256GB', $black256->getStorage());
        $this->assertEquals(1099, $black256->getFinalPrice());

        $this->assertEquals('White', $white128->getColor());
        $this->assertEquals('128GB', $white128->getStorage());
        $this->assertEquals(999, $white128->getFinalPrice());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_get_available_options()
    {
        // Create variants
        $this->product->addVariant('Black', '128GB', 0, 10);
        $this->product->addVariant('Black', '256GB', 10000, 8);
        $this->product->addVariant('White', '128GB', 0, 15);
        $this->product->addVariant('White', '512GB', 20000, 5);

        // Test available colors
        $colors = $this->product->getAvailableColors();
        $this->assertContains('Black', $colors);
        $this->assertContains('White', $colors);
        $this->assertCount(2, $colors);

        // Test available storage
        $storage = $this->product->getAvailableStorage();
        $this->assertContains('128GB', $storage);
        $this->assertContains('256GB', $storage);
        $this->assertContains('512GB', $storage);
        $this->assertCount(3, $storage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_find_variants_by_color_and_storage()
    {
        // Create variants
        $black128 = $this->product->addVariant('Black', '128GB', 0, 10);
        $black256 = $this->product->addVariant('Black', '256GB', 10000, 8);
        $white128 = $this->product->addVariant('White', '128GB', 0, 15);

        // Test finding existing variants
        $found = $this->product->findVariant('Black', '128GB');
        $this->assertNotNull($found);
        $this->assertEquals($black128->id, $found->id);

        $found2 = $this->product->findVariant('Black', '256GB');
        $this->assertNotNull($found2);
        $this->assertEquals($black256->id, $found2->id);

        $found3 = $this->product->findVariant('White', '128GB');
        $this->assertNotNull($found3);
        $this->assertEquals($white128->id, $found3->id);

        // Test non-existent variant
        $notFound = $this->product->findVariant('Gold', '128GB');
        $this->assertNull($notFound);

        $notFound2 = $this->product->findVariant('Black', '1TB');
        $this->assertNull($notFound2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function livewire_component_loads_with_variants()
    {
        // Create variants
        $black128 = $this->product->addVariant('Black', '128GB', 0, 10);
        $white256 = $this->product->addVariant('White', '256GB', 10000, 8);
        $black128->update(['is_default' => true]);

        // Test with product model directly (bypasses slug lookup issues)
        $component = Livewire::test(ProductDetailPage::class, ['slug' => $this->product]);

        // Check that product is loaded
        $component->assertSet('product.name', 'iPhone 15 Pro');

        // Check that available options are loaded
        $this->assertContains('Black', $component->get('availableColors'));
        $this->assertContains('White', $component->get('availableColors'));
        $this->assertContains('128GB', $component->get('availableStorage'));
        $this->assertContains('256GB', $component->get('availableStorage'));

        // Check that default variant is selected
        $this->assertEquals('Black', $component->get('selectedColor'));
        $this->assertEquals('128GB', $component->get('selectedStorage'));
        $this->assertEquals($black128->id, $component->get('selectedVariant')->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function livewire_component_can_select_variants()
    {
        // Create variants
        $black128 = $this->product->addVariant('Black', '128GB', 0, 10);
        $white256 = $this->product->addVariant('White', '256GB', 10000, 8);
        $black128->update(['is_default' => true]);

        $component = Livewire::test(ProductDetailPage::class, ['slug' => $this->product]);

        // Initially should have black 128GB selected
        $this->assertEquals('Black', $component->get('selectedColor'));
        $this->assertEquals('128GB', $component->get('selectedStorage'));
        $this->assertEquals($black128->id, $component->get('selectedVariant')->id);

        // Select white color
        $component->set('selectedColor', 'White');
        $this->assertEquals('White', $component->get('selectedColor'));
        $this->assertNull($component->get('selectedVariant')); // No White 128GB variant

        // Select 256GB storage
        $component->set('selectedStorage', '256GB');
        $this->assertEquals('White', $component->get('selectedColor'));
        $this->assertEquals('256GB', $component->get('selectedStorage'));
        $this->assertEquals($white256->id, $component->get('selectedVariant')->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function livewire_component_shows_correct_prices()
    {
        // Create variants
        $black128 = $this->product->addVariant('Black', '128GB', 0, 10);        // $999
        $black256 = $this->product->addVariant('Black', '256GB', 10000, 8);     // $1099
        $black128->update(['is_default' => true]);

        $component = Livewire::test(ProductDetailPage::class, ['slug' => $this->product]);

        // Initially should show $999 for Black 128GB
        $this->assertEquals(999, $component->call('getCurrentPrice'));
        $this->assertEquals('₹999.00', $component->call('getCurrentPriceFormatted'));

        // Select 256GB storage
        $component->set('selectedStorage', '256GB');
        $this->assertEquals(1099, $component->call('getCurrentPrice'));
        $this->assertEquals('₹1,099.00', $component->call('getCurrentPriceFormatted'));
    }
}
