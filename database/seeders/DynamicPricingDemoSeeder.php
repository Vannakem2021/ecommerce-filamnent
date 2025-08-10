<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;

class DynamicPricingDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a demo category and brand if they don't exist
        $category = Category::firstOrCreate([
            'name' => 'Smartphones',
            'slug' => 'smartphones'
        ]);

        $brand = Brand::firstOrCreate([
            'name' => 'TechDemo',
            'slug' => 'techdemo'
        ]);

        // Create a demo product with dynamic pricing
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'TechDemo Pro Max',
            'slug' => 'techdemo-pro-max',
            'description' => 'A premium smartphone with customizable storage and color options. Prices adjust based on your selections.',
            'short_description' => 'Premium smartphone with dynamic pricing based on storage and color options.',
            'price_cents' => 79900, // Base price: ₹799
            'compare_price_cents' => 89900, // Compare price: ₹899
            'cost_price_cents' => 60000, // Cost: ₹600
            'sku' => 'TDPM-BASE',
            'stock_quantity' => 100,
            'stock_status' => 'in_stock',
            'has_variants' => true,
            'variant_type' => 'options',
            'is_active' => true,
            'is_featured' => true,
            'images' => [
                'products/demo-phone-1.jpg',
                'products/demo-phone-2.jpg'
            ]
        ]);

        // Create Storage attribute with price modifiers
        $storageAttribute = ProductAttribute::create([
            'name' => 'Storage',
            'slug' => 'storage',
            'type' => 'select',
            'purpose' => 'variant',
            'data_type' => 'text',
            'is_filterable' => true,
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 1,
            'description' => 'Internal storage capacity'
        ]);

        // Storage options with price modifiers
        $storageOptions = [
            ['value' => '128GB', 'modifier' => 0], // Base storage, no extra cost
            ['value' => '256GB', 'modifier' => 6000], // +₹60
            ['value' => '512GB', 'modifier' => 15000], // +₹150
            ['value' => '1TB', 'modifier' => 30000], // +₹300
        ];

        foreach ($storageOptions as $option) {
            ProductAttributeValue::create([
                'product_attribute_id' => $storageAttribute->id,
                'value' => $option['value'],
                'slug' => strtolower(str_replace(['GB', 'TB'], ['gb', 'tb'], $option['value'])),
                'price_modifier_cents' => $option['modifier'],
                'is_active' => true,
                'sort_order' => array_search($option, $storageOptions) + 1
            ]);
        }

        // Create Color attribute with price modifiers
        $colorAttribute = ProductAttribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'color',
            'purpose' => 'variant',
            'data_type' => 'text',
            'is_filterable' => true,
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 2,
            'description' => 'Device color'
        ]);

        // Color options with price modifiers
        $colorOptions = [
            ['value' => 'Space Gray', 'color' => '#2D3748', 'modifier' => 0], // Base color
            ['value' => 'Silver', 'color' => '#E2E8F0', 'modifier' => 0], // Same price
            ['value' => 'Gold', 'color' => '#D69E2E', 'modifier' => 2000], // +₹20 premium
            ['value' => 'Deep Purple', 'color' => '#553C9A', 'modifier' => 2000], // +₹20 premium
        ];

        foreach ($colorOptions as $option) {
            ProductAttributeValue::create([
                'product_attribute_id' => $colorAttribute->id,
                'value' => $option['value'],
                'slug' => str_replace(' ', '-', strtolower($option['value'])),
                'color_code' => $option['color'],
                'price_modifier_cents' => $option['modifier'],
                'is_active' => true,
                'sort_order' => array_search($option, $colorOptions) + 1
            ]);
        }

        // Generate some sample variants (not all combinations, just a few examples)
        $sampleVariants = [
            ['Storage' => '128GB', 'Color' => 'Space Gray'],
            ['Storage' => '256GB', 'Color' => 'Silver'],
            ['Storage' => '512GB', 'Color' => 'Gold'],
            ['Storage' => '1TB', 'Color' => 'Deep Purple'],
        ];

        foreach ($sampleVariants as $variantOptions) {
            // Calculate price based on modifiers
            $variantPrice = $product->price_cents;
            $variantComparePrice = $product->compare_price_cents;

            foreach ($variantOptions as $attributeName => $value) {
                $attribute = ProductAttribute::where('name', $attributeName)->first();
                $attributeValue = $attribute->values()->where('value', $value)->first();
                
                if ($attributeValue && $attributeValue->price_modifier_cents) {
                    $variantPrice += $attributeValue->price_modifier_cents;
                    $variantComparePrice += $attributeValue->price_modifier_cents;
                }
            }

            // Create variant
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => 'TDPM-' . strtoupper(substr($variantOptions['Color'], 0, 2)) . '-' . $variantOptions['Storage'],
                'price_cents' => $variantPrice,
                'compare_price_cents' => $variantComparePrice,
                'cost_price_cents' => $product->cost_price_cents,
                'stock_quantity' => rand(5, 50),
                'stock_status' => 'in_stock',
                'is_active' => true,
                'is_default' => $variantOptions['Storage'] === '128GB' && $variantOptions['Color'] === 'Space Gray',
                'options' => $variantOptions
            ]);

            // Attach attribute values to variant
            foreach ($variantOptions as $attributeName => $value) {
                $attribute = ProductAttribute::where('name', $attributeName)->first();
                $attributeValue = $attribute->values()->where('value', $value)->first();
                
                if ($attributeValue) {
                    $variant->attributeValues()->attach($attributeValue->id, [
                        'product_attribute_id' => $attribute->id
                    ]);
                }
            }
        }

        $this->command->info('Dynamic pricing demo product created successfully!');
        $this->command->info('Product: ' . $product->name);
        $this->command->info('Base Price: ₹' . ($product->price_cents / 100));
        $this->command->info('Storage options: 128GB (+₹0), 256GB (+₹60), 512GB (+₹150), 1TB (+₹300)');
        $this->command->info('Color options: Space Gray/Silver (+₹0), Gold/Deep Purple (+₹20)');
        $this->command->info('Visit: /products/' . $product->slug . ' to see dynamic pricing in action!');
    }
}
