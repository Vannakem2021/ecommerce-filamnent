<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Database\Seeder;

class EnhancedProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing categories and brands
        $categories = Category::all();
        $brands = Brand::all();

        if ($categories->isEmpty() || $brands->isEmpty()) {
            $this->command->info('Please run the main seeder first to create categories and brands.');
            return;
        }

        // Create products with enhanced pricing and inventory
        $products = [
            [
                'category_id' => $categories->first()->id,
                'brand_id' => $brands->first()->id,
                'name' => 'Premium Smartphone Pro Max',
                'slug' => 'premium-smartphone-pro-max',
                'sku' => 'PHONE001',
                'description' => 'Latest flagship smartphone with advanced features and premium build quality.',
                'short_description' => 'Premium smartphone with Pro camera system and advanced features.',
                'price_cents' => 119999, // $1199.99
                'compare_price_cents' => 139999, // $1399.99 (showing discount)
                'cost_price_cents' => 80000, // $800.00
                'stock_quantity' => 25,
                'stock_status' => 'in_stock',
                'low_stock_threshold' => 5,
                'track_inventory' => true,
                'is_active' => true,
                'is_featured' => true,
                'on_sale' => true,
                'images' => ['products/smartphone-pro.jpg'],
            ],
            [
                'category_id' => $categories->skip(1)->first()->id ?? $categories->first()->id,
                'brand_id' => $brands->skip(1)->first()->id ?? $brands->first()->id,
                'name' => 'Wireless Bluetooth Headphones',
                'slug' => 'wireless-bluetooth-headphones',
                'sku' => 'AUDIO001',
                'description' => 'High-quality wireless headphones with noise cancellation and premium sound.',
                'short_description' => 'Premium wireless headphones with noise cancellation.',
                'price_cents' => 29999, // $299.99
                'compare_price_cents' => 39999, // $399.99
                'cost_price_cents' => 15000, // $150.00
                'stock_quantity' => 50,
                'stock_status' => 'in_stock',
                'low_stock_threshold' => 10,
                'track_inventory' => true,
                'is_active' => true,
                'is_featured' => false,
                'in_stock' => true,
                'on_sale' => true,
                'images' => ['products/headphones.jpg'],
            ],
            [
                'category_id' => $categories->first()->id,
                'brand_id' => $brands->first()->id,
                'name' => 'Gaming Laptop Ultra',
                'slug' => 'gaming-laptop-ultra',
                'sku' => 'LAPTOP001',
                'description' => 'High-performance gaming laptop with latest GPU and fast processor.',
                'short_description' => 'High-performance gaming laptop for enthusiasts.',
                'price_cents' => 199999, // $1999.99
                'compare_price_cents' => null, // No compare price
                'cost_price_cents' => 140000, // $1400.00
                'stock_quantity' => 3, // Low stock
                'stock_status' => 'in_stock',
                'low_stock_threshold' => 5,
                'track_inventory' => true,
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'on_sale' => false,
                'images' => ['products/gaming-laptop.jpg'],
            ],
            [
                'category_id' => $categories->first()->id,
                'brand_id' => $brands->first()->id,
                'name' => 'Smart Watch Series X',
                'slug' => 'smart-watch-series-x',
                'sku' => 'WATCH001',
                'description' => 'Advanced smartwatch with health monitoring and fitness tracking.',
                'short_description' => 'Advanced smartwatch with comprehensive health features.',
                'price_cents' => 49999, // $499.99
                'compare_price_cents' => 59999, // $599.99
                'cost_price_cents' => 25000, // $250.00
                'stock_quantity' => 0, // Out of stock
                'stock_status' => 'out_of_stock',
                'low_stock_threshold' => 5,
                'track_inventory' => true,
                'is_active' => true,
                'is_featured' => false,
                'in_stock' => false,
                'on_sale' => true,
                'images' => ['products/smartwatch.jpg'],
            ],
            [
                'category_id' => $categories->first()->id,
                'brand_id' => $brands->first()->id,
                'name' => 'Digital Camera Pro',
                'slug' => 'digital-camera-pro',
                'sku' => 'CAM001',
                'description' => 'Professional digital camera with high-resolution sensor and advanced features.',
                'short_description' => 'Professional camera for photography enthusiasts.',
                'price_cents' => 89999, // $899.99
                'compare_price_cents' => null,
                'cost_price_cents' => 60000, // $600.00
                'stock_quantity' => 15,
                'stock_status' => 'in_stock',
                'low_stock_threshold' => 3,
                'track_inventory' => false, // Not tracking inventory
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'on_sale' => false,
                'images' => ['products/camera-pro.jpg'],
            ],
        ];

        foreach ($products as $productData) {
            Product::create($productData);
        }

        $this->command->info('Enhanced products with pricing and inventory data created successfully!');
    }
}
