<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true);
        $price = $this->faker->randomFloat(2, 10, 500);
        
        return [
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'images' => $this->faker->optional()->randomElement([
                ['product1.jpg', 'product2.jpg'],
                ['product3.jpg'],
                null
            ]),
            'description' => $this->faker->optional()->paragraphs(3, true),
            'short_description' => $this->faker->optional()->sentence(),
            'price' => $price,
            'price_cents' => round($price * 100),
            'compare_price_cents' => $this->faker->optional()->numberBetween(
                round($price * 110), 
                round($price * 150)
            ),
            'cost_price_cents' => $this->faker->optional()->numberBetween(
                round($price * 50), 
                round($price * 80)
            ),
            'sku' => $this->faker->unique()->regexify('[A-Z]{3}-[0-9]{4}'),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'stock_status' => $this->faker->randomElement(['in_stock', 'out_of_stock', 'back_order']),
            'low_stock_threshold' => $this->faker->numberBetween(1, 10),
            'track_inventory' => $this->faker->boolean(80), // 80% chance of tracking inventory
            'has_variants' => false, // Default to no variants
            'variant_type' => 'none', // Default to no variants
            'variant_attributes' => null,
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'is_featured' => $this->faker->boolean(20), // 20% chance of being featured
            'in_stock' => $this->faker->boolean(80), // 80% chance of being in stock
            'on_sale' => $this->faker->boolean(25), // 25% chance of being on sale
            'meta_title' => $this->faker->optional()->sentence(),
            'meta_description' => $this->faker->optional()->sentence(),
            'meta_keywords' => $this->faker->optional()->words(5, true),
        ];
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the product is in stock.
     */
    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'in_stock' => true,
            'stock_status' => 'in_stock',
            'stock_quantity' => $this->faker->numberBetween(10, 100),
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'in_stock' => false,
            'stock_status' => 'out_of_stock',
            'stock_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the product is on sale.
     */
    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'on_sale' => true,
            'compare_price_cents' => $this->faker->numberBetween(
                round($attributes['price'] * 120), 
                round($attributes['price'] * 180)
            ),
        ]);
    }

    /**
     * Indicate that the product has variants.
     */
    public function withVariants(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_variants' => true,
            'variant_type' => 'multiple',
            'variant_attributes' => json_encode([
                'size' => ['S', 'M', 'L', 'XL'],
                'color' => ['Red', 'Blue', 'Green', 'Black']
            ]),
        ]);
    }
}
