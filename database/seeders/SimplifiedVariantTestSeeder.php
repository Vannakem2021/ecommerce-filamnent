<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariant;

class SimplifiedVariantTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding simplified variant test data...');

        // Create categories
        $categories = $this->createCategories();
        
        // Create brands
        $brands = $this->createBrands();
        
        // Create products with variants
        $this->createSmartphones($categories['Electronics'], $brands);
        $this->createLaptops($categories['Electronics'], $brands);
        $this->createTablets($categories['Electronics'], $brands);
        $this->createHeadphones($categories['Electronics'], $brands);
        
        // Create some products without variants
        $this->createSimpleProducts($categories, $brands);

        $this->command->info('✅ Simplified variant test data seeded successfully!');
    }

    private function createCategories(): array
    {
        $this->command->info('📁 Creating categories...');
        
        return [
            'Electronics' => Category::firstOrCreate(
                ['slug' => 'electronics'],
                [
                    'name' => 'Electronics',
                    'is_active' => true,
                ]
            ),
            'Accessories' => Category::firstOrCreate(
                ['slug' => 'accessories'],
                [
                    'name' => 'Accessories',
                    'is_active' => true,
                ]
            ),
        ];
    }

    private function createBrands(): array
    {
        $this->command->info('🏷️ Creating brands...');
        
        $brandNames = ['Apple', 'Samsung', 'Google', 'Dell', 'HP', 'Sony'];

        $brands = [];
        foreach ($brandNames as $name) {
            $brands[$name] = Brand::firstOrCreate(
                ['slug' => strtolower($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                ]
            );
        }

        return $brands;
    }

    private function createSmartphones($category, $brands): void
    {
        $this->command->info('📱 Creating smartphones with variants...');

        // iPhone 15 Series
        $iphone15 = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brands['Apple']->id,
            'name' => 'iPhone 15',
            'slug' => 'iphone-15',
            'description' => 'The latest iPhone with advanced camera system and A17 chip.',
            'short_description' => 'Latest iPhone with A17 chip',
            'price_cents' => 79900, // $799 base price
            'sku' => 'IPHONE15',
            'has_variants' => true,
            'is_active' => true,
            'is_featured' => true,
            'images' => [
                'https://via.placeholder.com/800x600/000000/FFFFFF?text=iPhone+15',
            ],
        ]);

        // iPhone 15 Variants
        $colors = ['Black', 'Blue', 'Green', 'Yellow', 'Pink'];
        $storages = ['128GB', '256GB', '512GB'];
        $storagePrices = ['128GB' => 0, '256GB' => 10000, '512GB' => 30000]; // Price modifiers

        foreach ($colors as $color) {
            foreach ($storages as $storage) {
                $overridePrice = $iphone15->price_cents + $storagePrices[$storage];
                
                ProductVariant::create([
                    'product_id' => $iphone15->id,
                    'sku' => "IPHONE15-" . strtoupper(substr($color, 0, 3)) . "-" . str_replace('GB', '', $storage),
                    'options' => [
                        'Color' => $color,
                        'Storage' => $storage,
                    ],
                    'override_price' => $overridePrice,
                    'stock_quantity' => rand(5, 25),
                    'is_active' => true,
                    'is_default' => $color === 'Black' && $storage === '128GB',
                ]);
            }
        }

        // Samsung Galaxy S24
        $galaxyS24 = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brands['Samsung']->id,
            'name' => 'Samsung Galaxy S24',
            'slug' => 'samsung-galaxy-s24',
            'description' => 'Premium Android smartphone with AI-powered camera and S Pen support.',
            'short_description' => 'Premium Android with AI camera',
            'price_cents' => 69900, // $699 base price
            'sku' => 'GALAXY-S24',
            'has_variants' => true,
            'is_active' => true,
            'is_featured' => true,
            'images' => [
                'https://via.placeholder.com/800x600/1f1f1f/FFFFFF?text=Galaxy+S24',
            ],
        ]);

        // Galaxy S24 Variants
        $galaxyColors = ['Phantom Black', 'Cream', 'Lavender', 'Mint'];
        $galaxyStorages = ['128GB', '256GB', '512GB'];
        $galaxyStoragePrices = ['128GB' => 0, '256GB' => 8000, '512GB' => 25000];

        foreach ($galaxyColors as $color) {
            foreach ($galaxyStorages as $storage) {
                $overridePrice = $galaxyS24->price_cents + $galaxyStoragePrices[$storage];
                
                ProductVariant::create([
                    'product_id' => $galaxyS24->id,
                    'sku' => "GALAXY-S24-" . strtoupper(str_replace(' ', '', substr($color, 0, 3))) . "-" . str_replace('GB', '', $storage),
                    'options' => [
                        'Color' => $color,
                        'Storage' => $storage,
                    ],
                    'override_price' => $overridePrice,
                    'stock_quantity' => rand(8, 20),
                    'is_active' => true,
                    'is_default' => $color === 'Phantom Black' && $storage === '128GB',
                ]);
            }
        }
    }

    private function createLaptops($category, $brands): void
    {
        $this->command->info('💻 Creating laptops with variants...');

        // MacBook Air M3
        $macbookAir = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brands['Apple']->id,
            'name' => 'MacBook Air M3',
            'slug' => 'macbook-air-m3',
            'description' => 'Ultra-thin laptop with M3 chip, perfect for everyday computing and creative work.',
            'short_description' => 'Ultra-thin laptop with M3 chip',
            'price_cents' => 109900, // $1099 base price
            'sku' => 'MBA-M3',
            'has_variants' => true,
            'is_active' => true,
            'is_featured' => true,
            'images' => [
                'https://via.placeholder.com/800x600/c0c0c0/000000?text=MacBook+Air+M3',
            ],
        ]);

        // MacBook Air Variants
        $laptopColors = ['Space Gray', 'Silver', 'Starlight', 'Midnight'];
        $laptopStorages = ['256GB', '512GB', '1TB', '2TB'];
        $laptopStoragePrices = ['256GB' => 0, '512GB' => 20000, '1TB' => 40000, '2TB' => 80000];

        foreach ($laptopColors as $color) {
            foreach ($laptopStorages as $storage) {
                $overridePrice = $macbookAir->price_cents + $laptopStoragePrices[$storage];
                
                ProductVariant::create([
                    'product_id' => $macbookAir->id,
                    'sku' => "MBA-M3-" . strtoupper(str_replace(' ', '', substr($color, 0, 3))) . "-" . str_replace(['GB', 'TB'], ['', 'T'], $storage),
                    'options' => [
                        'Color' => $color,
                        'Storage' => $storage,
                    ],
                    'override_price' => $overridePrice,
                    'stock_quantity' => rand(3, 15),
                    'is_active' => true,
                    'is_default' => $color === 'Space Gray' && $storage === '256GB',
                ]);
            }
        }

        // Dell XPS 13
        $dellXPS = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brands['Dell']->id,
            'name' => 'Dell XPS 13',
            'slug' => 'dell-xps-13',
            'description' => 'Premium Windows laptop with InfinityEdge display and powerful performance.',
            'short_description' => 'Premium Windows laptop',
            'price_cents' => 99900, // $999 base price
            'sku' => 'XPS-13',
            'has_variants' => true,
            'is_active' => true,
            'images' => [
                'https://via.placeholder.com/800x600/2f2f2f/FFFFFF?text=Dell+XPS+13',
            ],
        ]);

        // Dell XPS Variants
        $dellColors = ['Platinum Silver', 'Graphite'];
        $dellStorages = ['256GB', '512GB', '1TB'];
        $dellStoragePrices = ['256GB' => 0, '512GB' => 15000, '1TB' => 35000];

        foreach ($dellColors as $color) {
            foreach ($dellStorages as $storage) {
                $overridePrice = $dellXPS->price_cents + $dellStoragePrices[$storage];
                
                ProductVariant::create([
                    'product_id' => $dellXPS->id,
                    'sku' => "XPS-13-" . strtoupper(str_replace(' ', '', substr($color, 0, 3))) . "-" . str_replace('GB', '', $storage),
                    'options' => [
                        'Color' => $color,
                        'Storage' => $storage,
                    ],
                    'override_price' => $overridePrice,
                    'stock_quantity' => rand(5, 12),
                    'is_active' => true,
                    'is_default' => $color === 'Platinum Silver' && $storage === '256GB',
                ]);
            }
        }
    }

    private function createTablets($category, $brands): void
    {
        $this->command->info('📱 Creating tablets with variants...');

        // iPad Air
        $ipadAir = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brands['Apple']->id,
            'name' => 'iPad Air',
            'slug' => 'ipad-air',
            'description' => 'Powerful tablet with M2 chip, perfect for creativity and productivity.',
            'short_description' => 'Powerful tablet with M2 chip',
            'price_cents' => 59900, // $599 base price
            'sku' => 'IPAD-AIR',
            'has_variants' => true,
            'is_active' => true,
            'is_featured' => true,
            'images' => [
                'https://via.placeholder.com/800x600/f0f0f0/000000?text=iPad+Air',
            ],
        ]);

        // iPad Air Variants
        $tabletColors = ['Space Gray', 'Starlight', 'Pink', 'Purple', 'Blue'];
        $tabletStorages = ['64GB', '256GB', '512GB'];
        $tabletStoragePrices = ['64GB' => 0, '256GB' => 15000, '512GB' => 35000];

        foreach ($tabletColors as $color) {
            foreach ($tabletStorages as $storage) {
                $overridePrice = $ipadAir->price_cents + $tabletStoragePrices[$storage];

                ProductVariant::create([
                    'product_id' => $ipadAir->id,
                    'sku' => "IPAD-AIR-" . strtoupper(str_replace(' ', '', substr($color, 0, 3))) . "-" . str_replace('GB', '', $storage),
                    'options' => [
                        'Color' => $color,
                        'Storage' => $storage,
                    ],
                    'override_price' => $overridePrice,
                    'stock_quantity' => rand(8, 18),
                    'is_active' => true,
                    'is_default' => $color === 'Space Gray' && $storage === '64GB',
                ]);
            }
        }
    }

    private function createHeadphones($category, $brands): void
    {
        $this->command->info('🎧 Creating headphones with variants...');

        // AirPods Pro
        $airpodsPro = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brands['Apple']->id,
            'name' => 'AirPods Pro (2nd generation)',
            'slug' => 'airpods-pro-2nd-gen',
            'description' => 'Premium wireless earbuds with active noise cancellation and spatial audio.',
            'short_description' => 'Premium wireless earbuds with ANC',
            'price_cents' => 24900, // $249 base price
            'sku' => 'AIRPODS-PRO-2',
            'has_variants' => true,
            'is_active' => true,
            'is_featured' => true,
            'images' => [
                'https://via.placeholder.com/800x600/ffffff/000000?text=AirPods+Pro',
            ],
        ]);

        // AirPods Pro Variants (Color only, no storage)
        $headphoneColors = ['White'];
        $headphoneStorages = ['Standard']; // Not really storage, but using for consistency

        foreach ($headphoneColors as $color) {
            foreach ($headphoneStorages as $storage) {
                ProductVariant::create([
                    'product_id' => $airpodsPro->id,
                    'sku' => "AIRPODS-PRO-2-" . strtoupper(substr($color, 0, 3)) . "-STD",
                    'options' => [
                        'Color' => $color,
                        'Storage' => $storage,
                    ],
                    'override_price' => $airpodsPro->price_cents, // No price modifier
                    'stock_quantity' => rand(15, 30),
                    'is_active' => true,
                    'is_default' => true,
                ]);
            }
        }

        // Sony WH-1000XM5
        $sonyHeadphones = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brands['Sony']->id,
            'name' => 'Sony WH-1000XM5',
            'slug' => 'sony-wh-1000xm5',
            'description' => 'Industry-leading noise canceling headphones with exceptional sound quality.',
            'short_description' => 'Industry-leading noise canceling headphones',
            'price_cents' => 39900, // $399 base price
            'sku' => 'SONY-WH1000XM5',
            'has_variants' => true,
            'is_active' => true,
            'images' => [
                'https://via.placeholder.com/800x600/000000/FFFFFF?text=Sony+WH-1000XM5',
            ],
        ]);

        // Sony Headphones Variants
        $sonyColors = ['Black', 'Silver'];
        $sonyStorages = ['Standard'];

        foreach ($sonyColors as $color) {
            foreach ($sonyStorages as $storage) {
                ProductVariant::create([
                    'product_id' => $sonyHeadphones->id,
                    'sku' => "SONY-WH1000XM5-" . strtoupper(substr($color, 0, 3)) . "-STD",
                    'options' => [
                        'Color' => $color,
                        'Storage' => $storage,
                    ],
                    'override_price' => $sonyHeadphones->price_cents,
                    'stock_quantity' => rand(10, 25),
                    'is_active' => true,
                    'is_default' => $color === 'Black',
                ]);
            }
        }
    }

    private function createSimpleProducts($categories, $brands): void
    {
        $this->command->info('📦 Creating simple products (no variants)...');

        // USB-C Cable (no variants)
        Product::create([
            'category_id' => $categories['Accessories']->id,
            'brand_id' => $brands['Apple']->id,
            'name' => 'USB-C to Lightning Cable',
            'slug' => 'usb-c-lightning-cable',
            'description' => 'High-quality cable for fast charging and data transfer.',
            'short_description' => 'Fast charging cable',
            'price_cents' => 1900, // $19
            'sku' => 'CABLE-USBC-LIGHTNING',
            'stock_quantity' => 100,
            'has_variants' => false,
            'is_active' => true,
            'images' => [
                'https://via.placeholder.com/800x600/ffffff/000000?text=USB-C+Cable',
            ],
        ]);

        // Wireless Charger (no variants)
        Product::create([
            'category_id' => $categories['Accessories']->id,
            'brand_id' => $brands['Samsung']->id,
            'name' => 'Wireless Charging Pad',
            'slug' => 'wireless-charging-pad',
            'description' => 'Fast wireless charging pad compatible with Qi-enabled devices.',
            'short_description' => 'Fast wireless charging pad',
            'price_cents' => 4900, // $49
            'sku' => 'CHARGER-WIRELESS-PAD',
            'stock_quantity' => 50,
            'has_variants' => false,
            'is_active' => true,
            'images' => [
                'https://via.placeholder.com/800x600/000000/FFFFFF?text=Wireless+Charger',
            ],
        ]);

        // Phone Case (no variants - simple product)
        Product::create([
            'category_id' => $categories['Accessories']->id,
            'brand_id' => $brands['Apple']->id,
            'name' => 'iPhone 15 Silicone Case',
            'slug' => 'iphone-15-silicone-case',
            'description' => 'Premium silicone case designed specifically for iPhone 15.',
            'short_description' => 'Premium silicone case',
            'price_cents' => 4900, // $49
            'sku' => 'CASE-IPHONE15-SILICONE',
            'stock_quantity' => 75,
            'has_variants' => false,
            'is_active' => true,
            'images' => [
                'https://via.placeholder.com/800x600/ff6b6b/FFFFFF?text=iPhone+Case',
            ],
        ]);
    }
}
