<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Database\Seeder;

class ProductVariantsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing categories, brands, and attributes
        $categories = Category::all();
        $brands = Brand::all();
        $colorAttribute = ProductAttribute::where('slug', 'color')->first();
        $storageAttribute = ProductAttribute::where('slug', 'storage')->first();

        if ($categories->isEmpty() || $brands->isEmpty() || !$colorAttribute || !$storageAttribute) {
            $this->command->error('Please run the main seeders first to create categories, brands, and attributes.');
            return;
        }

        // Create iPhone with Color and Storage variants
        $iphone = Product::create([
            'category_id' => $categories->first()->id,
            'brand_id' => $brands->first()->id,
            'name' => 'iPhone 15 Pro',
            'slug' => 'iphone-15-pro',
            'sku' => 'IPHONE15PRO',
            'description' => 'Latest iPhone with Pro camera system and titanium design.',
            'short_description' => 'Premium smartphone with advanced features.',
            'price_cents' => 119999, // Base price $1199.99
            'compare_price_cents' => 129999, // Compare price $1299.99
            'cost_price_cents' => 80000, // Cost $800.00
            'stock_quantity' => 0, // Will be managed by variants
            'has_variants' => true,
            'variant_type' => 'multiple',
            'variant_attributes' => [$colorAttribute->id, $storageAttribute->id],
            'is_active' => true,
            'is_featured' => true,
            'images' => ['products/iphone-15-pro.jpg'],
        ]);

        // Get color and storage values
        $colors = $colorAttribute->activeValues()->whereIn('slug', ['black', 'white', 'blue'])->get();
        $storages = $storageAttribute->activeValues()->whereIn('slug', ['128gb', '256gb', '512gb'])->get();

        // Create variants for each combination
        $isFirst = true;
        foreach ($colors as $color) {
            foreach ($storages as $storage) {
                // Calculate variant price based on storage
                $basePriceCents = 119999;
                $storagePriceCents = match($storage->slug) {
                    '128gb' => 0,
                    '256gb' => 10000, // +$100
                    '512gb' => 20000, // +$200
                    default => 0
                };

                $variant = ProductVariant::create([
                    'product_id' => $iphone->id,
                    'price_cents' => $basePriceCents + $storagePriceCents,
                    'compare_price_cents' => 129999 + $storagePriceCents,
                    'cost_price_cents' => 80000 + ($storagePriceCents * 0.6),
                    'stock_quantity' => rand(5, 25),
                    'stock_status' => 'in_stock',
                    'low_stock_threshold' => 5,
                    'track_inventory' => true,
                    'is_active' => true,
                    'is_default' => $isFirst,
                ]);

                // Attach attributes
                $variant->attributeValues()->attach($color->id, [
                    'product_attribute_id' => $colorAttribute->id
                ]);
                $variant->attributeValues()->attach($storage->id, [
                    'product_attribute_id' => $storageAttribute->id
                ]);

                // Generate SKU
                $variant->sku = $variant->generateSku();
                $variant->save();

                $isFirst = false;
            }
        }

        // Create MacBook with Color variants only
        $macbook = Product::create([
            'category_id' => $categories->skip(1)->first()->id ?? $categories->first()->id,
            'brand_id' => $brands->first()->id,
            'name' => 'MacBook Air M3',
            'slug' => 'macbook-air-m3',
            'sku' => 'MACBOOK-AIR-M3',
            'description' => 'Powerful laptop with M3 chip and all-day battery life.',
            'short_description' => 'Ultra-thin laptop with M3 performance.',
            'price_cents' => 149999, // Base price $1499.99
            'cost_price_cents' => 100000, // Cost $1000.00
            'stock_quantity' => 0, // Will be managed by variants
            'has_variants' => true,
            'variant_type' => 'multiple',
            'variant_attributes' => [$colorAttribute->id],
            'is_active' => true,
            'is_featured' => true,
            'images' => ['products/macbook-air-m3.jpg'],
        ]);

        // Create MacBook variants (Color only)
        $macbookColors = $colorAttribute->activeValues()->whereIn('slug', ['silver', 'gold', 'black'])->get();
        $isFirst = true;
        foreach ($macbookColors as $color) {
            $variant = ProductVariant::create([
                'product_id' => $macbook->id,
                'price_cents' => 149999,
                'cost_price_cents' => 100000,
                'stock_quantity' => rand(3, 15),
                'stock_status' => 'in_stock',
                'low_stock_threshold' => 3,
                'track_inventory' => true,
                'is_active' => true,
                'is_default' => $isFirst,
            ]);

            // Attach color attribute
            $variant->attributeValues()->attach($color->id, [
                'product_attribute_id' => $colorAttribute->id
            ]);

            // Generate SKU
            $variant->sku = $variant->generateSku();
            $variant->save();

            $isFirst = false;
        }

        // Create a simple product without variants for comparison
        Product::create([
            'category_id' => $categories->first()->id,
            'brand_id' => $brands->first()->id,
            'name' => 'AirPods Pro',
            'slug' => 'airpods-pro',
            'sku' => 'AIRPODS-PRO',
            'description' => 'Premium wireless earbuds with active noise cancellation.',
            'short_description' => 'Wireless earbuds with ANC.',
            'price_cents' => 24999, // $249.99
            'compare_price_cents' => 29999, // $299.99
            'cost_price_cents' => 15000, // $150.00
            'stock_quantity' => 50,
            'stock_status' => 'in_stock',
            'low_stock_threshold' => 10,
            'track_inventory' => true,
            'has_variants' => false,
            'variant_type' => 'none',
            'is_active' => true,
            'is_featured' => false,
            'images' => ['products/airpods-pro.jpg'],
        ]);

        $this->command->info('Products with variants created successfully!');
        $this->command->info('iPhone 15 Pro: ' . $iphone->variants()->count() . ' variants created');
        $this->command->info('MacBook Air M3: ' . $macbook->variants()->count() . ' variants created');
    }
}
