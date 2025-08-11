<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => $this->faker->unique()->regexify('[A-Z]{3}-[0-9]{4}-[A-Z]{2}'),
            'name' => $this->faker->words(3, true),
            
            // JSON options for simplified variant system
            'options' => [
                'Color' => $this->faker->randomElement(['Black', 'White', 'Blue', 'Red', 'Green']),
                'Storage' => $this->faker->randomElement(['64GB', '128GB', '256GB', '512GB', '1TB']),
                'RAM' => $this->faker->randomElement(['8GB', '12GB', '16GB', '32GB']),
            ],
            
            // Simplified pricing - use override_price for variant-specific pricing
            'override_price' => $this->faker->optional(0.3)->numberBetween(50000, 200000), // 30% chance of override
            'image_url' => $this->faker->optional()->imageUrl(400, 400, 'products'),
            
            // Legacy fields (kept for compatibility)
            'price_cents' => $this->faker->numberBetween(1000, 50000), // Deprecated
            'compare_price_cents' => $this->faker->optional()->numberBetween(1500, 60000),
            'cost_price_cents' => $this->faker->numberBetween(500, 30000),
            
            // Inventory
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'stock_status' => $this->faker->randomElement(['in_stock', 'out_of_stock', 'back_order']),
            'low_stock_threshold' => $this->faker->numberBetween(1, 10),
            'track_inventory' => $this->faker->boolean(80),
            
            // Physical attributes
            'weight' => $this->faker->optional()->randomFloat(2, 0.1, 10.0),
            'dimensions' => $this->faker->optional()->randomElement([
                ['length' => 10, 'width' => 5, 'height' => 2],
                ['length' => 15, 'width' => 10, 'height' => 3],
                ['length' => 20, 'width' => 15, 'height' => 5],
            ]),
            'barcode' => $this->faker->optional()->ean13(),
            
            // Status
            'is_active' => $this->faker->boolean(90),
            'is_default' => false, // Will be set explicitly when needed
        ];
    }

    /**
     * Indicate that the variant is the default for its product.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the variant is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the variant is in stock.
     */
    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_status' => 'in_stock',
            'stock_quantity' => $this->faker->numberBetween(10, 100),
        ]);
    }

    /**
     * Indicate that the variant is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_status' => 'out_of_stock',
            'stock_quantity' => 0,
        ]);
    }

    /**
     * Create a variant with specific JSON options.
     */
    public function withOptions(array $options): static
    {
        return $this->state(fn (array $attributes) => [
            'options' => $options,
        ]);
    }

    /**
     * Create a variant with override pricing.
     */
    public function withOverridePrice(int $priceCents): static
    {
        return $this->state(fn (array $attributes) => [
            'override_price' => $priceCents,
        ]);
    }

    /**
     * Create iPhone-style variants.
     */
    public function iphone(): static
    {
        return $this->state(fn (array $attributes) => [
            'options' => [
                'Color' => $this->faker->randomElement(['Black', 'White', 'Blue', 'Pink']),
                'Storage' => $this->faker->randomElement(['128GB', '256GB', '512GB', '1TB']),
            ],
            'override_price' => $this->faker->randomElement([110000, 120000, 140000, 160000]), // $1100-$1600
        ]);
    }
}
