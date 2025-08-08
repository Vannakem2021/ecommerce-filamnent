<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

class EcommerceSeeder extends Seeder
{
    public function run(): void
    {
        // Create Categories
        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true],
            ['name' => 'Clothing', 'slug' => 'clothing', 'is_active' => true],
            ['name' => 'Books', 'slug' => 'books', 'is_active' => true],
            ['name' => 'Home & Garden', 'slug' => 'home-garden', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Create Brands
        $brands = [
            ['name' => 'Apple', 'slug' => 'apple', 'is_active' => true],
            ['name' => 'Samsung', 'slug' => 'samsung', 'is_active' => true],
            ['name' => 'Nike', 'slug' => 'nike', 'is_active' => true],
            ['name' => 'Adidas', 'slug' => 'adidas', 'is_active' => true],
            ['name' => 'Sony', 'slug' => 'sony', 'is_active' => true],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }

        // Create Products
        $products = [
            [
                'category_id' => 1,
                'brand_id' => 1,
                'name' => 'iPhone 15 Pro',
                'slug' => 'iphone-15-pro',
                'description' => 'Latest iPhone with advanced features',
                'short_description' => 'Premium smartphone with Pro camera system',
                'price' => 999.99,
                'sku' => 'IPH15PRO001',
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'on_sale' => false,
                'images' => ['products/iphone-15-pro.jpg'],
            ],
            [
                'category_id' => 1,
                'brand_id' => 2,
                'name' => 'Samsung Galaxy S24',
                'slug' => 'samsung-galaxy-s24',
                'description' => 'Flagship Android smartphone',
                'short_description' => 'Advanced Android phone with AI features',
                'price' => 899.99,
                'sku' => 'SAM24001',
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'on_sale' => true,
                'images' => ['products/galaxy-s24.jpg'],
            ],
            [
                'category_id' => 2,
                'brand_id' => 3,
                'name' => 'Nike Air Max 270',
                'slug' => 'nike-air-max-270',
                'description' => 'Comfortable running shoes',
                'short_description' => 'Lightweight running shoes with Air Max technology',
                'price' => 150.00,
                'sku' => 'NIKE270001',
                'is_active' => true,
                'is_featured' => false,
                'in_stock' => true,
                'on_sale' => false,
                'images' => ['products/nike-air-max.jpg'],
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        // Create a sample order
        $user = User::first();
        if ($user) {
            $order = Order::create([
                'user_id' => $user->id,
                'grand_total' => 1149.98,
                'payment_method' => 'stripe',
                'payment_status' => 'paid',
                'status' => 'processing',
                'currency' => 'USD',
                'shipping_amount' => 0.00,
                'shipping_method' => 'standard',
                'notes' => 'Sample order for testing',
            ]);

            // Create order items
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => 1,
                'quantity' => 1,
                'unit_amount' => 999.99,
                'total_amount' => 999.99,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => 3,
                'quantity' => 1,
                'unit_amount' => 150.00,
                'total_amount' => 150.00,
            ]);

            // Create address
            Address::create([
                'order_id' => $order->id,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'phone' => '+1234567890',
                'street_address' => '123 Main Street',
                'city' => 'New York',
                'state' => 'NY',
                'zip_code' => '10001',
            ]);
        }
    }
}
