<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ElectronicsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->info('🔌 Creating Electronics Categories and Products...');

        // Create Electronics Categories
        $this->createElectronicsCategories();
        
        // Create Electronics Brands
        $this->createElectronicsBrands();
        
        // Create Sample Electronic Products
        $this->createSampleProducts();

        $this->info('✅ Electronics data seeded successfully!');
    }

    private function createElectronicsCategories()
    {
        $categories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'All electronic devices and gadgets',
                'is_active' => true,
                'children' => [
                    [
                        'name' => 'Smartphones',
                        'slug' => 'smartphones',
                        'description' => 'Mobile phones and accessories',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Laptops & Computers',
                        'slug' => 'laptops-computers',
                        'description' => 'Laptops, desktops, and computer accessories',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Tablets',
                        'slug' => 'tablets',
                        'description' => 'Tablets and e-readers',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Audio & Headphones',
                        'slug' => 'audio-headphones',
                        'description' => 'Headphones, speakers, and audio equipment',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Smart Home',
                        'slug' => 'smart-home',
                        'description' => 'Smart home devices and IoT products',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Gaming',
                        'slug' => 'gaming',
                        'description' => 'Gaming consoles, accessories, and peripherals',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Cameras & Photography',
                        'slug' => 'cameras-photography',
                        'description' => 'Digital cameras, lenses, and photography equipment',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Wearables',
                        'slug' => 'wearables',
                        'description' => 'Smartwatches, fitness trackers, and wearable tech',
                        'is_active' => true,
                    ],
                ]
            ]
        ];

        foreach ($categories as $categoryData) {
            $parentCategory = Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    'name' => $categoryData['name'],
                    'is_active' => $categoryData['is_active'],
                ]
            );

            if (isset($categoryData['children'])) {
                foreach ($categoryData['children'] as $childData) {
                    Category::updateOrCreate(
                        ['slug' => $childData['slug']],
                        [
                            'name' => $childData['name'],
                            'is_active' => $childData['is_active'],
                        ]
                    );
                }
            }
        }
    }

    private function createElectronicsBrands()
    {
        $brands = [
            'Apple', 'Samsung', 'Sony', 'LG', 'Dell', 'HP', 'Lenovo', 'ASUS',
            'Microsoft', 'Google', 'OnePlus', 'Xiaomi', 'Canon', 'Nikon',
            'JBL', 'Bose', 'Beats', 'Nintendo', 'PlayStation', 'Xbox'
        ];

        foreach ($brands as $brandName) {
            Brand::updateOrCreate(
                ['slug' => Str::slug($brandName)],
                [
                    'name' => $brandName,
                    'is_active' => true,
                ]
            );
        }
    }

    private function createSampleProducts()
    {
        // Get categories and brands
        $smartphoneCategory = Category::where('slug', 'smartphones')->first();
        $laptopCategory = Category::where('slug', 'laptops-computers')->first();
        $tabletCategory = Category::where('slug', 'tablets')->first();
        $audioCategory = Category::where('slug', 'audio-headphones')->first();

        $appleBrand = Brand::where('slug', 'apple')->first();
        $samsungBrand = Brand::where('slug', 'samsung')->first();
        $sonyBrand = Brand::where('slug', 'sony')->first();
        $dellBrand = Brand::where('slug', 'dell')->first();

        // Sample Products
        $products = [
            [
                'name' => 'iPhone 15 Pro',
                'category_id' => $smartphoneCategory->id,
                'brand_id' => $appleBrand->id,
                'description' => 'The most advanced iPhone with titanium design, A17 Pro chip, and pro camera system.',
                'short_description' => 'Premium smartphone with advanced features',
                'price' => 999.00,
                'sku' => 'IPHONE-15-PRO',
                'stock_quantity' => 50,
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'has_variants' => true,
                'variant_type' => 'multiple',
                'images' => ['iphone-15-pro-1.jpg', 'iphone-15-pro-2.jpg'],
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'category_id' => $smartphoneCategory->id,
                'brand_id' => $samsungBrand->id,
                'description' => 'Ultimate Android flagship with S Pen, 200MP camera, and AI features.',
                'short_description' => 'Premium Android smartphone with S Pen',
                'price' => 1199.00,
                'sku' => 'GALAXY-S24-ULTRA',
                'stock_quantity' => 30,
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'has_variants' => true,
                'variant_type' => 'multiple',
                'images' => ['galaxy-s24-ultra-1.jpg', 'galaxy-s24-ultra-2.jpg'],
            ],
            [
                'name' => 'MacBook Pro 14-inch M3',
                'category_id' => $laptopCategory->id,
                'brand_id' => $appleBrand->id,
                'description' => 'Professional laptop with M3 chip, Liquid Retina XDR display, and all-day battery life.',
                'short_description' => 'Professional laptop with M3 chip',
                'price' => 1999.00,
                'sku' => 'MBP-14-M3',
                'stock_quantity' => 25,
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'has_variants' => true,
                'variant_type' => 'multiple',
                'images' => ['macbook-pro-14-1.jpg', 'macbook-pro-14-2.jpg'],
            ],
            [
                'name' => 'iPad Pro 12.9-inch',
                'category_id' => $tabletCategory->id,
                'brand_id' => $appleBrand->id,
                'description' => 'Most advanced iPad with M2 chip, Liquid Retina XDR display, and Apple Pencil support.',
                'short_description' => 'Professional tablet with M2 chip',
                'price' => 1099.00,
                'sku' => 'IPAD-PRO-129',
                'stock_quantity' => 40,
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'has_variants' => true,
                'variant_type' => 'multiple',
                'images' => ['ipad-pro-129-1.jpg', 'ipad-pro-129-2.jpg'],
            ],
            [
                'name' => 'Sony WH-1000XM5',
                'category_id' => $audioCategory->id,
                'brand_id' => $sonyBrand->id,
                'description' => 'Industry-leading noise canceling headphones with exceptional sound quality.',
                'short_description' => 'Premium noise-canceling headphones',
                'price' => 399.00,
                'sku' => 'SONY-WH1000XM5',
                'stock_quantity' => 60,
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'has_variants' => true,
                'variant_type' => 'single',
                'images' => ['sony-wh1000xm5-1.jpg', 'sony-wh1000xm5-2.jpg'],
            ],
            [
                'name' => 'Dell XPS 13',
                'category_id' => $laptopCategory->id,
                'brand_id' => $dellBrand->id,
                'description' => 'Ultra-portable laptop with InfinityEdge display and premium build quality.',
                'short_description' => 'Ultra-portable premium laptop',
                'price' => 1299.00,
                'sku' => 'DELL-XPS-13',
                'stock_quantity' => 35,
                'is_active' => true,
                'is_featured' => false,
                'in_stock' => true,
                'has_variants' => true,
                'variant_type' => 'multiple',
                'images' => ['dell-xps-13-1.jpg', 'dell-xps-13-2.jpg'],
            ],
        ];

        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['sku' => $productData['sku']],
                array_merge($productData, [
                    'slug' => Str::slug($productData['name']),
                    'price_cents' => $productData['price'] * 100,
                    'stock_status' => 'in_stock',
                    'track_inventory' => true,
                    'low_stock_threshold' => 5,
                ])
            );
        }
    }

    private function info($message)
    {
        if (app()->runningInConsole()) {
            echo $message . "\n";
        }
    }
}
