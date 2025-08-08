<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'image' => 'categories/electronics.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Clothing & Fashion',
                'slug' => 'clothing-fashion',
                'image' => 'categories/clothing.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Home & Garden',
                'slug' => 'home-garden',
                'image' => 'categories/home-garden.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Sports & Outdoors',
                'slug' => 'sports-outdoors',
                'image' => 'categories/sports.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Books & Media',
                'slug' => 'books-media',
                'image' => 'categories/books.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Health & Beauty',
                'slug' => 'health-beauty',
                'image' => 'categories/health-beauty.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Toys & Games',
                'slug' => 'toys-games',
                'image' => 'categories/toys.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Automotive',
                'slug' => 'automotive',
                'image' => 'categories/automotive.jpg',
                'is_active' => true,
            ],
            [
                'name' => 'Food & Beverages',
                'slug' => 'food-beverages',
                'image' => 'categories/food.jpg',
                'is_active' => false, // Test inactive category
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
