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
            'price_cents' => $this->faker->numberBetween(1000, 50000), // $10 to $500
            'compare_price_cents' => $this->faker->optional()->numberBetween(1500, 60000),
            'cost_price_cents' => $this->faker->numberBetween(500, 30000),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'stock_status' => $this->faker->randomElement(['in_stock', 'out_of_stock', 'back_order']),
            'low_stock_threshold' => $this->faker->numberBetween(1, 10),
            'track_inventory' => $this->faker->boolean(80), // 80% chance of tracking inventory
            'weight' => $this->faker->optional()->randomFloat(2, 0.1, 10.0),
            'dimensions' => $this->faker->optional()->randomElement([
                ['length' => 10, 'width' => 5, 'height' => 2],
                ['length' => 15, 'width' => 10, 'height' => 3],
                ['length' => 20, 'width' => 15, 'height' => 5],
            ]),
            'barcode' => $this->faker->optional()->ean13(),
            'images' => $this->faker->optional()->randomElement([
                ['variant1.jpg', 'variant2.jpg'],
                ['variant3.jpg'],
                null
            ]),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
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
}
