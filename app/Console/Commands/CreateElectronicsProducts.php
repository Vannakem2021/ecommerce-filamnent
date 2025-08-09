<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateElectronicsProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'electronics:create-products {--force : Force creation even if products exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create sample electronic products with variants using electronics attributes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📱 Creating Sample Electronics Products with Variants...');
        
        $force = $this->option('force');
        
        // First run the electronics seeder to ensure categories and brands exist
        $this->call('db:seed', ['--class' => 'ElectronicsSeeder']);
        
        // Create sample products with variants
        $this->createiPhoneWithVariants();
        $this->createSamsungGalaxyWithVariants();
        $this->createMacBookWithVariants();
        $this->createSonyHeadphonesWithVariants();
        $this->createDellLaptopWithVariants();

        $this->info('✅ Sample electronics products with variants created successfully!');
        $this->newLine();
        $this->info('🛍️ You can now browse these products in your store:');
        $this->table(
            ['Product', 'Category', 'Brand', 'Variants', 'Price Range'],
            [
                ['iPhone 15 Pro', 'Smartphones', 'Apple', 'Storage + Color', '$999 - $1499'],
                ['Galaxy S24 Ultra', 'Smartphones', 'Samsung', 'Storage + Color', '$1199 - $1699'],
                ['MacBook Pro 14"', 'Laptops', 'Apple', 'RAM + Storage + Color', '$1999 - $3499'],
                ['Sony WH-1000XM5', 'Audio', 'Sony', 'Color', '$399'],
                ['Dell XPS 13', 'Laptops', 'Dell', 'RAM + Storage', '$1299 - $2299'],
            ]
        );
    }

    private function createiPhoneWithVariants()
    {
        $this->info('Creating iPhone 15 Pro with variants...');
        
        $category = Category::where('slug', 'smartphones')->first();
        $brand = Brand::where('slug', 'apple')->first();
        
        $product = Product::updateOrCreate(
            ['sku' => 'IPHONE-15-PRO'],
            [
                'name' => 'iPhone 15 Pro',
                'slug' => 'iphone-15-pro',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'description' => 'The most advanced iPhone with titanium design, A17 Pro chip, and pro camera system. Features include Dynamic Island, Always-On display, and advanced computational photography.',
                'short_description' => 'Premium smartphone with A17 Pro chip and titanium design',
                'price' => 999.00,
                'price_cents' => 99900,
                'stock_quantity' => 100,
                'stock_status' => 'in_stock',
                'track_inventory' => true,
                'low_stock_threshold' => 10,
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'has_variants' => true,
                'variant_type' => 'multiple',
                'images' => ['iphone-15-pro-natural.jpg', 'iphone-15-pro-blue.jpg', 'iphone-15-pro-white.jpg', 'iphone-15-pro-black.jpg'],
            ]
        );

        // Get attributes
        $storageAttr = ProductAttribute::where('slug', 'storage-capacity')->first();
        $colorAttr = ProductAttribute::where('slug', 'device-color')->first();
        $brandAttr = ProductAttribute::where('slug', 'electronics-brand')->first();
        $processorAttr = ProductAttribute::where('slug', 'processor')->first();
        $osAttr = ProductAttribute::where('slug', 'operating-system')->first();
        $cameraAttr = ProductAttribute::where('slug', 'camera-resolution')->first();

        // Create variants
        $storageOptions = ['128GB', '256GB', '512GB', '1TB'];
        $colorOptions = ['Space Gray', 'Silver', 'Gold', 'Blue'];
        $prices = [999, 1099, 1299, 1499];

        foreach ($storageOptions as $storageIndex => $storage) {
            foreach ($colorOptions as $colorIndex => $color) {
                $storageValue = ProductAttributeValue::where('product_attribute_id', $storageAttr->id)
                    ->where('value', $storage)->first();
                $colorValue = ProductAttributeValue::where('product_attribute_id', $colorAttr->id)
                    ->where('value', $color)->first();

                if ($storageValue && $colorValue) {
                    $variant = ProductVariant::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'sku' => "IPHONE-15-PRO-{$storage}-" . strtoupper(str_replace(' ', '-', $color))
                        ],
                        [
                            'name' => "iPhone 15 Pro {$storage} {$color}",
                            'price_cents' => $prices[$storageIndex] * 100,
                            'stock_quantity' => rand(10, 50),
                            'stock_status' => 'in_stock',
                            'track_inventory' => true,
                            'low_stock_threshold' => 5,
                            'is_active' => true,
                            'is_default' => $storageIndex === 0 && $colorIndex === 0,
                        ]
                    );

                    // Attach attribute values to variant with proper pivot data
                    $variant->attributeValues()->sync([
                        $storageValue->id => ['product_attribute_id' => $storageAttr->id],
                        $colorValue->id => ['product_attribute_id' => $colorAttr->id],
                    ]);
                }
            }
        }
    }

    private function createSamsungGalaxyWithVariants()
    {
        $this->info('Creating Samsung Galaxy S24 Ultra with variants...');
        
        $category = Category::where('slug', 'smartphones')->first();
        $brand = Brand::where('slug', 'samsung')->first();
        
        $product = Product::updateOrCreate(
            ['sku' => 'GALAXY-S24-ULTRA'],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'slug' => 'samsung-galaxy-s24-ultra',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'description' => 'Ultimate Android flagship with built-in S Pen, 200MP camera system, and Galaxy AI features. Features titanium frame and advanced night photography.',
                'short_description' => 'Premium Android smartphone with S Pen and 200MP camera',
                'price' => 1199.00,
                'price_cents' => 119900,
                'stock_quantity' => 80,
                'stock_status' => 'in_stock',
                'track_inventory' => true,
                'low_stock_threshold' => 8,
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'has_variants' => true,
                'variant_type' => 'multiple',
                'images' => ['galaxy-s24-ultra-titanium.jpg', 'galaxy-s24-ultra-black.jpg', 'galaxy-s24-ultra-violet.jpg'],
            ]
        );

        // Get attributes
        $storageAttr = ProductAttribute::where('slug', 'storage-capacity')->first();
        $colorAttr = ProductAttribute::where('slug', 'device-color')->first();

        // Create variants
        $storageOptions = ['256GB', '512GB', '1TB'];
        $colorOptions = ['Black', 'Purple', 'Silver'];
        $prices = [1199, 1419, 1659];

        foreach ($storageOptions as $storageIndex => $storage) {
            foreach ($colorOptions as $colorIndex => $color) {
                $storageValue = ProductAttributeValue::where('product_attribute_id', $storageAttr->id)
                    ->where('value', $storage)->first();
                $colorValue = ProductAttributeValue::where('product_attribute_id', $colorAttr->id)
                    ->where('value', $color)->first();

                if ($storageValue && $colorValue) {
                    $variant = ProductVariant::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'sku' => "GALAXY-S24-ULTRA-{$storage}-" . strtoupper($color)
                        ],
                        [
                            'name' => "Galaxy S24 Ultra {$storage} {$color}",
                            'price_cents' => $prices[$storageIndex] * 100,
                            'stock_quantity' => rand(8, 30),
                            'stock_status' => 'in_stock',
                            'track_inventory' => true,
                            'low_stock_threshold' => 3,
                            'is_active' => true,
                            'is_default' => $storageIndex === 0 && $colorIndex === 0,
                        ]
                    );

                    // Attach attribute values to variant with proper pivot data
                    $variant->attributeValues()->sync([
                        $storageValue->id => ['product_attribute_id' => $storageAttr->id],
                        $colorValue->id => ['product_attribute_id' => $colorAttr->id],
                    ]);
                }
            }
        }
    }

    private function createMacBookWithVariants()
    {
        $this->info('Creating MacBook Pro 14-inch with variants...');
        
        $category = Category::where('slug', 'laptops-computers')->first();
        $brand = Brand::where('slug', 'apple')->first();
        
        $product = Product::updateOrCreate(
            ['sku' => 'MBP-14-M3'],
            [
                'name' => 'MacBook Pro 14-inch M3',
                'slug' => 'macbook-pro-14-inch-m3',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'description' => 'Professional laptop with M3 chip, Liquid Retina XDR display, and all-day battery life. Perfect for creative professionals and developers.',
                'short_description' => 'Professional laptop with M3 chip and XDR display',
                'price' => 1999.00,
                'price_cents' => 199900,
                'stock_quantity' => 50,
                'stock_status' => 'in_stock',
                'track_inventory' => true,
                'low_stock_threshold' => 5,
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'has_variants' => true,
                'variant_type' => 'multiple',
                'images' => ['macbook-pro-14-space-gray.jpg', 'macbook-pro-14-silver.jpg'],
            ]
        );

        // Get attributes
        $ramAttr = ProductAttribute::where('slug', 'ram-memory')->first();
        $storageAttr = ProductAttribute::where('slug', 'storage-capacity')->first();
        $colorAttr = ProductAttribute::where('slug', 'device-color')->first();

        // Create variants
        $ramOptions = ['8GB', '16GB', '32GB'];
        $storageOptions = ['512GB', '1TB', '2TB'];
        $colorOptions = ['Space Gray', 'Silver'];
        $basePrices = [
            '8GB' => ['512GB' => 1999, '1TB' => 2199, '2TB' => 2599],
            '16GB' => ['512GB' => 2399, '1TB' => 2599, '2TB' => 2999],
            '32GB' => ['512GB' => 2999, '1TB' => 3199, '2TB' => 3599],
        ];

        foreach ($ramOptions as $ram) {
            foreach ($storageOptions as $storage) {
                foreach ($colorOptions as $color) {
                    $ramValue = ProductAttributeValue::where('product_attribute_id', $ramAttr->id)
                        ->where('value', $ram)->first();
                    $storageValue = ProductAttributeValue::where('product_attribute_id', $storageAttr->id)
                        ->where('value', $storage)->first();
                    $colorValue = ProductAttributeValue::where('product_attribute_id', $colorAttr->id)
                        ->where('value', $color)->first();

                    if ($ramValue && $storageValue && $colorValue) {
                        $variant = ProductVariant::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'sku' => "MBP-14-M3-{$ram}-{$storage}-" . strtoupper(str_replace(' ', '-', $color))
                            ],
                            [
                                'name' => "MacBook Pro 14\" M3 {$ram} {$storage} {$color}",
                                'price_cents' => $basePrices[$ram][$storage] * 100,
                                'stock_quantity' => rand(5, 20),
                                'stock_status' => 'in_stock',
                                'track_inventory' => true,
                                'low_stock_threshold' => 2,
                                'is_active' => true,
                                'is_default' => $ram === '8GB' && $storage === '512GB' && $color === 'Space Gray',
                            ]
                        );

                        // Attach attribute values to variant with proper pivot data
                        $variant->attributeValues()->sync([
                            $ramValue->id => ['product_attribute_id' => $ramAttr->id],
                            $storageValue->id => ['product_attribute_id' => $storageAttr->id],
                            $colorValue->id => ['product_attribute_id' => $colorAttr->id],
                        ]);
                    }
                }
            }
        }
    }

    private function createSonyHeadphonesWithVariants()
    {
        $this->info('Creating Sony WH-1000XM5 with variants...');

        $category = Category::where('slug', 'audio-headphones')->first();
        $brand = Brand::where('slug', 'sony')->first();

        $product = Product::updateOrCreate(
            ['sku' => 'SONY-WH1000XM5'],
            [
                'name' => 'Sony WH-1000XM5',
                'slug' => 'sony-wh-1000xm5',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'description' => 'Industry-leading noise canceling headphones with exceptional sound quality, 30-hour battery life, and crystal-clear call quality.',
                'short_description' => 'Premium noise-canceling wireless headphones',
                'price' => 399.00,
                'price_cents' => 39900,
                'stock_quantity' => 75,
                'stock_status' => 'in_stock',
                'track_inventory' => true,
                'low_stock_threshold' => 10,
                'is_active' => true,
                'is_featured' => true,
                'in_stock' => true,
                'has_variants' => true,
                'variant_type' => 'single',
                'images' => ['sony-wh1000xm5-black.jpg', 'sony-wh1000xm5-silver.jpg'],
            ]
        );

        // Get attributes
        $colorAttr = ProductAttribute::where('slug', 'device-color')->first();

        // Create variants
        $colorOptions = ['Black', 'Silver'];

        foreach ($colorOptions as $colorIndex => $color) {
            $colorValue = ProductAttributeValue::where('product_attribute_id', $colorAttr->id)
                ->where('value', $color)->first();

            if ($colorValue) {
                $variant = ProductVariant::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'sku' => "SONY-WH1000XM5-" . strtoupper($color)
                    ],
                    [
                        'name' => "Sony WH-1000XM5 {$color}",
                        'price_cents' => 39900,
                        'stock_quantity' => rand(20, 40),
                        'stock_status' => 'in_stock',
                        'track_inventory' => true,
                        'low_stock_threshold' => 5,
                        'is_active' => true,
                        'is_default' => $colorIndex === 0,
                    ]
                );

                // Attach attribute values to variant with proper pivot data
                $variant->attributeValues()->sync([
                    $colorValue->id => ['product_attribute_id' => $colorAttr->id],
                ]);
            }
        }
    }

    private function createDellLaptopWithVariants()
    {
        $this->info('Creating Dell XPS 13 with variants...');

        $category = Category::where('slug', 'laptops-computers')->first();
        $brand = Brand::where('slug', 'dell')->first();

        $product = Product::updateOrCreate(
            ['sku' => 'DELL-XPS-13'],
            [
                'name' => 'Dell XPS 13',
                'slug' => 'dell-xps-13',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'description' => 'Ultra-portable laptop with InfinityEdge display, premium build quality, and exceptional performance in a compact form factor.',
                'short_description' => 'Ultra-portable premium laptop with InfinityEdge display',
                'price' => 1299.00,
                'price_cents' => 129900,
                'stock_quantity' => 60,
                'stock_status' => 'in_stock',
                'track_inventory' => true,
                'low_stock_threshold' => 8,
                'is_active' => true,
                'is_featured' => false,
                'in_stock' => true,
                'has_variants' => true,
                'variant_type' => 'multiple',
                'images' => ['dell-xps-13-platinum.jpg', 'dell-xps-13-frost.jpg'],
            ]
        );

        // Get attributes
        $ramAttr = ProductAttribute::where('slug', 'ram-memory')->first();
        $storageAttr = ProductAttribute::where('slug', 'storage-capacity')->first();

        // Create variants
        $ramOptions = ['8GB', '16GB', '32GB'];
        $storageOptions = ['256GB', '512GB', '1TB'];
        $basePrices = [
            '8GB' => ['256GB' => 1299, '512GB' => 1499, '1TB' => 1799],
            '16GB' => ['256GB' => 1599, '512GB' => 1799, '1TB' => 2099],
            '32GB' => ['256GB' => 1999, '512GB' => 2199, '1TB' => 2499],
        ];

        foreach ($ramOptions as $ram) {
            foreach ($storageOptions as $storage) {
                $ramValue = ProductAttributeValue::where('product_attribute_id', $ramAttr->id)
                    ->where('value', $ram)->first();
                $storageValue = ProductAttributeValue::where('product_attribute_id', $storageAttr->id)
                    ->where('value', $storage)->first();

                if ($ramValue && $storageValue) {
                    $variant = ProductVariant::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'sku' => "DELL-XPS-13-{$ram}-{$storage}"
                        ],
                        [
                            'name' => "Dell XPS 13 {$ram} {$storage}",
                            'price_cents' => $basePrices[$ram][$storage] * 100,
                            'stock_quantity' => rand(8, 25),
                            'stock_status' => 'in_stock',
                            'track_inventory' => true,
                            'low_stock_threshold' => 3,
                            'is_active' => true,
                            'is_default' => $ram === '8GB' && $storage === '256GB',
                        ]
                    );

                    // Attach attribute values to variant with proper pivot data
                    $variant->attributeValues()->sync([
                        $ramValue->id => ['product_attribute_id' => $ramAttr->id],
                        $storageValue->id => ['product_attribute_id' => $storageAttr->id],
                    ]);
                }
            }
        }
    }
}
