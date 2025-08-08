<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ElectronicsDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🧹 Cleaning existing data...');
        $this->cleanExistingData();

        $this->command->info('📱 Creating Electronics categories...');
        $categories = $this->createElectronicsCategories();

        $this->command->info('🏢 Creating Electronics brands...');
        $brands = $this->createElectronicsBrands();

        $this->command->info('🏷️ Creating Electronics attributes...');
        $attributes = $this->createElectronicsAttributes();

        $this->command->info('📦 Creating Electronics products with variants...');
        $this->createElectronicsProducts($categories, $brands, $attributes);

        $this->command->info('✅ Electronics data seeding completed successfully!');
    }

    /**
     * Clean existing data
     */
    private function cleanExistingData(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear in correct order to avoid foreign key constraints
        DB::table('product_variant_attributes')->truncate();
        DB::table('product_variants')->truncate();
        DB::table('product_attribute_values')->truncate();
        DB::table('product_attributes')->truncate();
        DB::table('products')->truncate();
        DB::table('categories')->truncate();
        DB::table('brands')->truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('   ✓ Existing data cleaned');
    }

    /**
     * Create Electronics categories
     */
    private function createElectronicsCategories(): array
    {
        $categories = [
            [
                'name' => 'Smartphones',
                'slug' => 'smartphones',
                'is_active' => true,
            ],
            [
                'name' => 'Laptops & Computers',
                'slug' => 'laptops-computers',
                'is_active' => true,
            ],
            [
                'name' => 'Audio & Headphones',
                'slug' => 'audio-headphones',
                'is_active' => true,
            ],
            [
                'name' => 'Tablets',
                'slug' => 'tablets',
                'is_active' => true,
            ],
            [
                'name' => 'Smart Watches',
                'slug' => 'smart-watches',
                'is_active' => true,
            ],
            [
                'name' => 'Gaming',
                'slug' => 'gaming',
                'is_active' => true,
            ],
            [
                'name' => 'Cameras',
                'slug' => 'cameras',
                'is_active' => true,
            ],
            [
                'name' => 'Home Electronics',
                'slug' => 'home-electronics',
                'is_active' => true,
            ],
        ];

        $createdCategories = [];
        foreach ($categories as $categoryData) {
            $createdCategories[] = Category::create($categoryData);
        }

        $this->command->info('   ✓ Created ' . count($createdCategories) . ' electronics categories');
        return $createdCategories;
    }

    /**
     * Create Electronics brands
     */
    private function createElectronicsBrands(): array
    {
        $brands = [
            ['name' => 'Apple', 'slug' => 'apple', 'is_active' => true],
            ['name' => 'Samsung', 'slug' => 'samsung', 'is_active' => true],
            ['name' => 'Google', 'slug' => 'google', 'is_active' => true],
            ['name' => 'OnePlus', 'slug' => 'oneplus', 'is_active' => true],
            ['name' => 'Xiaomi', 'slug' => 'xiaomi', 'is_active' => true],
            ['name' => 'Dell', 'slug' => 'dell', 'is_active' => true],
            ['name' => 'HP', 'slug' => 'hp', 'is_active' => true],
            ['name' => 'Lenovo', 'slug' => 'lenovo', 'is_active' => true],
            ['name' => 'ASUS', 'slug' => 'asus', 'is_active' => true],
            ['name' => 'Sony', 'slug' => 'sony', 'is_active' => true],
            ['name' => 'Bose', 'slug' => 'bose', 'is_active' => true],
            ['name' => 'JBL', 'slug' => 'jbl', 'is_active' => true],
            ['name' => 'Sennheiser', 'slug' => 'sennheiser', 'is_active' => true],
            ['name' => 'Canon', 'slug' => 'canon', 'is_active' => true],
            ['name' => 'Nikon', 'slug' => 'nikon', 'is_active' => true],
            ['name' => 'Nintendo', 'slug' => 'nintendo', 'is_active' => true],
            ['name' => 'Microsoft', 'slug' => 'microsoft', 'is_active' => true],
            ['name' => 'Logitech', 'slug' => 'logitech', 'is_active' => true],
        ];

        $createdBrands = [];
        foreach ($brands as $brandData) {
            $createdBrands[] = Brand::create($brandData);
        }

        $this->command->info('   ✓ Created ' . count($createdBrands) . ' electronics brands');
        return $createdBrands;
    }

    /**
     * Create Electronics attributes
     */
    private function createElectronicsAttributes(): array
    {
        $attributes = [
            // Color attribute
            [
                'name' => 'Color',
                'slug' => 'color',
                'type' => 'color',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 1,
                'values' => [
                    ['value' => 'Space Black', 'color_code' => '#1C1C1E'],
                    ['value' => 'Silver', 'color_code' => '#C7C7CC'],
                    ['value' => 'Gold', 'color_code' => '#FFEAA7'],
                    ['value' => 'Rose Gold', 'color_code' => '#E17B93'],
                    ['value' => 'Blue', 'color_code' => '#007AFF'],
                    ['value' => 'Green', 'color_code' => '#34C759'],
                    ['value' => 'Purple', 'color_code' => '#AF52DE'],
                    ['value' => 'Red', 'color_code' => '#FF3B30'],
                    ['value' => 'White', 'color_code' => '#FFFFFF'],
                    ['value' => 'Midnight', 'color_code' => '#000000'],
                    ['value' => 'Starlight', 'color_code' => '#F5F5DC'],
                    ['value' => 'Pink', 'color_code' => '#FF69B4'],
                ]
            ],
            // Storage attribute
            [
                'name' => 'Storage',
                'slug' => 'storage',
                'type' => 'select',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 2,
                'values' => [
                    ['value' => '64GB'],
                    ['value' => '128GB'],
                    ['value' => '256GB'],
                    ['value' => '512GB'],
                    ['value' => '1TB'],
                    ['value' => '2TB'],
                    ['value' => '4TB'],
                    ['value' => '8TB'],
                ]
            ],
            // RAM attribute
            [
                'name' => 'RAM',
                'slug' => 'ram',
                'type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 3,
                'values' => [
                    ['value' => '4GB'],
                    ['value' => '6GB'],
                    ['value' => '8GB'],
                    ['value' => '12GB'],
                    ['value' => '16GB'],
                    ['value' => '32GB'],
                    ['value' => '64GB'],
                ]
            ],
            // Screen Size attribute
            [
                'name' => 'Screen Size',
                'slug' => 'screen-size',
                'type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 4,
                'values' => [
                    ['value' => '6.1"'],
                    ['value' => '6.7"'],
                    ['value' => '11"'],
                    ['value' => '12.9"'],
                    ['value' => '13"'],
                    ['value' => '14"'],
                    ['value' => '15"'],
                    ['value' => '16"'],
                    ['value' => '17"'],
                    ['value' => '24"'],
                    ['value' => '27"'],
                    ['value' => '32"'],
                ]
            ],
            // Connectivity attribute
            [
                'name' => 'Connectivity',
                'slug' => 'connectivity',
                'type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 5,
                'values' => [
                    ['value' => 'Wi-Fi'],
                    ['value' => 'Wi-Fi + Cellular'],
                    ['value' => 'Bluetooth'],
                    ['value' => 'Wired'],
                    ['value' => 'Wireless'],
                    ['value' => 'USB-C'],
                    ['value' => 'Lightning'],
                ]
            ],
        ];

        $createdAttributes = [];
        foreach ($attributes as $attributeData) {
            $values = $attributeData['values'];
            unset($attributeData['values']);

            $attribute = ProductAttribute::create($attributeData);
            $createdAttributes[] = $attribute;

            foreach ($values as $index => $valueData) {
                ProductAttributeValue::create([
                    'product_attribute_id' => $attribute->id,
                    'value' => $valueData['value'],
                    'slug' => \Illuminate\Support\Str::slug($valueData['value']),
                    'color_code' => $valueData['color_code'] ?? null,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]);
            }
        }

        $this->command->info('   ✓ Created ' . count($createdAttributes) . ' electronics attributes');
        return $createdAttributes;
    }

    /**
     * Create Electronics products with variants
     */
    private function createElectronicsProducts($categories, $brands, $attributes): void
    {
        // Get attributes by slug for easy reference
        $colorAttr = collect($attributes)->firstWhere('slug', 'color');
        $storageAttr = collect($attributes)->firstWhere('slug', 'storage');
        $ramAttr = collect($attributes)->firstWhere('slug', 'ram');
        $screenAttr = collect($attributes)->firstWhere('slug', 'screen-size');
        $connectivityAttr = collect($attributes)->firstWhere('slug', 'connectivity');

        // Get categories by slug
        $smartphonesCat = collect($categories)->firstWhere('slug', 'smartphones');
        $laptopsCat = collect($categories)->firstWhere('slug', 'laptops-computers');
        $audioCat = collect($categories)->firstWhere('slug', 'audio-headphones');
        $tabletsCat = collect($categories)->firstWhere('slug', 'tablets');
        $watchesCat = collect($categories)->firstWhere('slug', 'smart-watches');

        // Get brands by slug
        $apple = collect($brands)->firstWhere('slug', 'apple');
        $samsung = collect($brands)->firstWhere('slug', 'samsung');
        $google = collect($brands)->firstWhere('slug', 'google');
        $dell = collect($brands)->firstWhere('slug', 'dell');
        $sony = collect($brands)->firstWhere('slug', 'sony');

        // Create iPhone 15 Pro with Color + Storage variants
        $this->createIPhone15Pro($smartphonesCat, $apple, $colorAttr, $storageAttr);

        // Create Samsung Galaxy S24 with Color + Storage + RAM variants
        $this->createSamsungGalaxyS24($smartphonesCat, $samsung, $colorAttr, $storageAttr, $ramAttr);

        // Create Google Pixel 8 with Color + Storage variants
        $this->createGooglePixel8($smartphonesCat, $google, $colorAttr, $storageAttr);

        // Create MacBook Pro with Color + Storage + RAM variants
        $this->createMacBookPro($laptopsCat, $apple, $colorAttr, $storageAttr, $ramAttr);

        // Create Dell XPS 13 with Color + Storage + RAM variants
        $this->createDellXPS13($laptopsCat, $dell, $colorAttr, $storageAttr, $ramAttr);

        // Create iPad Pro with Color + Storage + Connectivity variants
        $this->createIPadPro($tabletsCat, $apple, $colorAttr, $storageAttr, $connectivityAttr);

        // Create Apple Watch with Color variants only
        $this->createAppleWatch($watchesCat, $apple, $colorAttr);

        // Create Sony WH-1000XM5 with Color variants only
        $this->createSonyHeadphones($audioCat, $sony, $colorAttr);
    }

    private function createIPhone15Pro($category, $brand, $colorAttr, $storageAttr): void
    {
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'iPhone 15 Pro',
            'slug' => 'iphone-15-pro',
            'sku' => 'IPHONE15PRO',
            'description' => 'The most advanced iPhone ever with titanium design, A17 Pro chip, and Pro camera system.',
            'short_description' => 'Premium smartphone with titanium design and Pro camera system.',
            'price_cents' => 119999, // Base price $1199.99
            'compare_price_cents' => 129999,
            'cost_price_cents' => 80000,
            'has_variants' => true,
            'variant_type' => 'multiple',
            'variant_attributes' => [$colorAttr->id, $storageAttr->id],
            'is_active' => true,
            'is_featured' => true,
            'images' => ['products/iphone-15-pro.jpg'],
        ]);

        // Get specific color and storage values
        $colors = $colorAttr->activeValues()->whereIn('slug', ['space-black', 'silver', 'gold', 'blue'])->get();
        $storages = $storageAttr->activeValues()->whereIn('slug', ['128gb', '256gb', '512gb', '1tb'])->get();

        $this->createVariantsForProduct($product, [
            ['attribute' => $colorAttr, 'values' => $colors],
            ['attribute' => $storageAttr, 'values' => $storages],
        ], [
            '128gb' => ['price_add' => 0, 'cost_add' => 0],
            '256gb' => ['price_add' => 10000, 'cost_add' => 6000],
            '512gb' => ['price_add' => 20000, 'cost_add' => 12000],
            '1tb' => ['price_add' => 30000, 'cost_add' => 18000],
        ]);

        $this->command->info('   ✓ Created iPhone 15 Pro with ' . $product->variants()->count() . ' variants');
    }

    private function createSamsungGalaxyS24($category, $brand, $colorAttr, $storageAttr, $ramAttr): void
    {
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Samsung Galaxy S24 Ultra',
            'slug' => 'samsung-galaxy-s24-ultra',
            'sku' => 'GALAXYS24ULTRA',
            'description' => 'The ultimate Android flagship with S Pen, 200MP camera, and AI features.',
            'short_description' => 'Premium Android smartphone with S Pen and AI features.',
            'price_cents' => 109999, // Base price $1099.99
            'compare_price_cents' => 119999,
            'cost_price_cents' => 75000,
            'has_variants' => true,
            'variant_type' => 'multiple',
            'variant_attributes' => [$colorAttr->id, $storageAttr->id, $ramAttr->id],
            'is_active' => true,
            'is_featured' => true,
            'images' => ['products/galaxy-s24-ultra.jpg'],
        ]);

        $colors = $colorAttr->activeValues()->whereIn('slug', ['midnight', 'silver', 'green', 'purple'])->get();
        $storages = $storageAttr->activeValues()->whereIn('slug', ['256gb', '512gb', '1tb'])->get();
        $rams = $ramAttr->activeValues()->whereIn('slug', ['12gb', '16gb'])->get();

        $this->createVariantsForProduct($product, [
            ['attribute' => $colorAttr, 'values' => $colors],
            ['attribute' => $storageAttr, 'values' => $storages],
            ['attribute' => $ramAttr, 'values' => $rams],
        ], [
            '256gb' => ['price_add' => 0, 'cost_add' => 0],
            '512gb' => ['price_add' => 15000, 'cost_add' => 9000],
            '1tb' => ['price_add' => 25000, 'cost_add' => 15000],
            '12gb' => ['price_add' => 0, 'cost_add' => 0],
            '16gb' => ['price_add' => 8000, 'cost_add' => 5000],
        ]);

        $this->command->info('   ✓ Created Samsung Galaxy S24 Ultra with ' . $product->variants()->count() . ' variants');
    }

    private function createGooglePixel8($category, $brand, $colorAttr, $storageAttr): void
    {
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Google Pixel 8 Pro',
            'slug' => 'google-pixel-8-pro',
            'sku' => 'PIXEL8PRO',
            'description' => 'The smartest Pixel phone with Google AI, Magic Eraser, and incredible camera.',
            'short_description' => 'AI-powered smartphone with advanced computational photography.',
            'price_cents' => 89999, // Base price $899.99
            'cost_price_cents' => 60000,
            'has_variants' => true,
            'variant_type' => 'multiple',
            'variant_attributes' => [$colorAttr->id, $storageAttr->id],
            'is_active' => true,
            'is_featured' => true,
            'images' => ['products/pixel-8-pro.jpg'],
        ]);

        $colors = $colorAttr->activeValues()->whereIn('slug', ['midnight', 'white', 'blue'])->get();
        $storages = $storageAttr->activeValues()->whereIn('slug', ['128gb', '256gb', '512gb'])->get();

        $this->createVariantsForProduct($product, [
            ['attribute' => $colorAttr, 'values' => $colors],
            ['attribute' => $storageAttr, 'values' => $storages],
        ], [
            '128gb' => ['price_add' => 0, 'cost_add' => 0],
            '256gb' => ['price_add' => 10000, 'cost_add' => 6000],
            '512gb' => ['price_add' => 20000, 'cost_add' => 12000],
        ]);

        $this->command->info('   ✓ Created Google Pixel 8 Pro with ' . $product->variants()->count() . ' variants');
    }

    private function createMacBookPro($category, $brand, $colorAttr, $storageAttr, $ramAttr): void
    {
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'MacBook Pro 14"',
            'slug' => 'macbook-pro-14',
            'sku' => 'MACBOOKPRO14',
            'description' => 'Supercharged by M3 Pro chip with incredible performance and battery life.',
            'short_description' => 'Professional laptop with M3 Pro chip and Liquid Retina XDR display.',
            'price_cents' => 199999, // Base price $1999.99
            'cost_price_cents' => 140000,
            'has_variants' => true,
            'variant_type' => 'multiple',
            'variant_attributes' => [$colorAttr->id, $storageAttr->id, $ramAttr->id],
            'is_active' => true,
            'is_featured' => true,
            'images' => ['products/macbook-pro-14.jpg'],
        ]);

        $colors = $colorAttr->activeValues()->whereIn('slug', ['space-black', 'silver'])->get();
        $storages = $storageAttr->activeValues()->whereIn('slug', ['512gb', '1tb', '2tb'])->get();
        $rams = $ramAttr->activeValues()->whereIn('slug', ['16gb', '32gb', '64gb'])->get();

        $this->createVariantsForProduct($product, [
            ['attribute' => $colorAttr, 'values' => $colors],
            ['attribute' => $storageAttr, 'values' => $storages],
            ['attribute' => $ramAttr, 'values' => $rams],
        ], [
            '512gb' => ['price_add' => 0, 'cost_add' => 0],
            '1tb' => ['price_add' => 20000, 'cost_add' => 12000],
            '2tb' => ['price_add' => 40000, 'cost_add' => 24000],
            '16gb' => ['price_add' => 0, 'cost_add' => 0],
            '32gb' => ['price_add' => 40000, 'cost_add' => 24000],
            '64gb' => ['price_add' => 80000, 'cost_add' => 48000],
        ]);

        $this->command->info('   ✓ Created MacBook Pro 14" with ' . $product->variants()->count() . ' variants');
    }

    private function createDellXPS13($category, $brand, $colorAttr, $storageAttr, $ramAttr): void
    {
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Dell XPS 13',
            'slug' => 'dell-xps-13',
            'sku' => 'DELLXPS13',
            'description' => 'Ultra-portable laptop with InfinityEdge display and premium build quality.',
            'short_description' => 'Premium ultrabook with stunning display and portability.',
            'price_cents' => 129999, // Base price $1299.99
            'cost_price_cents' => 90000,
            'has_variants' => true,
            'variant_type' => 'multiple',
            'variant_attributes' => [$colorAttr->id, $storageAttr->id, $ramAttr->id],
            'is_active' => true,
            'is_featured' => false,
            'images' => ['products/dell-xps-13.jpg'],
        ]);

        $colors = $colorAttr->activeValues()->whereIn('slug', ['silver', 'white'])->get();
        $storages = $storageAttr->activeValues()->whereIn('slug', ['256gb', '512gb', '1tb'])->get();
        $rams = $ramAttr->activeValues()->whereIn('slug', ['8gb', '16gb', '32gb'])->get();

        $this->createVariantsForProduct($product, [
            ['attribute' => $colorAttr, 'values' => $colors],
            ['attribute' => $storageAttr, 'values' => $storages],
            ['attribute' => $ramAttr, 'values' => $rams],
        ], [
            '256gb' => ['price_add' => 0, 'cost_add' => 0],
            '512gb' => ['price_add' => 15000, 'cost_add' => 9000],
            '1tb' => ['price_add' => 30000, 'cost_add' => 18000],
            '8gb' => ['price_add' => 0, 'cost_add' => 0],
            '16gb' => ['price_add' => 20000, 'cost_add' => 12000],
            '32gb' => ['price_add' => 40000, 'cost_add' => 24000],
        ]);

        $this->command->info('   ✓ Created Dell XPS 13 with ' . $product->variants()->count() . ' variants');
    }

    private function createIPadPro($category, $brand, $colorAttr, $storageAttr, $connectivityAttr): void
    {
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'iPad Pro 12.9"',
            'slug' => 'ipad-pro-12-9',
            'sku' => 'IPADPRO129',
            'description' => 'The ultimate iPad experience with M2 chip, Liquid Retina XDR display, and Apple Pencil support.',
            'short_description' => 'Professional tablet with M2 chip and stunning display.',
            'price_cents' => 109999, // Base price $1099.99
            'cost_price_cents' => 75000,
            'has_variants' => true,
            'variant_type' => 'multiple',
            'variant_attributes' => [$colorAttr->id, $storageAttr->id, $connectivityAttr->id],
            'is_active' => true,
            'is_featured' => true,
            'images' => ['products/ipad-pro-12-9.jpg'],
        ]);

        $colors = $colorAttr->activeValues()->whereIn('slug', ['silver', 'space-black'])->get();
        $storages = $storageAttr->activeValues()->whereIn('slug', ['128gb', '256gb', '512gb', '1tb', '2tb'])->get();
        $connectivity = $connectivityAttr->activeValues()->whereIn('slug', ['wi-fi', 'wi-fi-cellular'])->get();

        $this->createVariantsForProduct($product, [
            ['attribute' => $colorAttr, 'values' => $colors],
            ['attribute' => $storageAttr, 'values' => $storages],
            ['attribute' => $connectivityAttr, 'values' => $connectivity],
        ], [
            '128gb' => ['price_add' => 0, 'cost_add' => 0],
            '256gb' => ['price_add' => 10000, 'cost_add' => 6000],
            '512gb' => ['price_add' => 20000, 'cost_add' => 12000],
            '1tb' => ['price_add' => 40000, 'cost_add' => 24000],
            '2tb' => ['price_add' => 60000, 'cost_add' => 36000],
            'wi-fi' => ['price_add' => 0, 'cost_add' => 0],
            'wi-fi-cellular' => ['price_add' => 15000, 'cost_add' => 9000],
        ]);

        $this->command->info('   ✓ Created iPad Pro 12.9" with ' . $product->variants()->count() . ' variants');
    }

    private function createAppleWatch($category, $brand, $colorAttr): void
    {
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Apple Watch Series 9',
            'slug' => 'apple-watch-series-9',
            'sku' => 'WATCHSERIES9',
            'description' => 'The most advanced Apple Watch with Double Tap, S9 chip, and comprehensive health features.',
            'short_description' => 'Advanced smartwatch with health monitoring and fitness tracking.',
            'price_cents' => 39999, // Base price $399.99
            'cost_price_cents' => 25000,
            'has_variants' => true,
            'variant_type' => 'multiple',
            'variant_attributes' => [$colorAttr->id],
            'is_active' => true,
            'is_featured' => true,
            'images' => ['products/apple-watch-series-9.jpg'],
        ]);

        $colors = $colorAttr->activeValues()->whereIn('slug', ['midnight', 'starlight', 'silver', 'pink', 'red'])->get();

        $this->createVariantsForProduct($product, [
            ['attribute' => $colorAttr, 'values' => $colors],
        ]);

        $this->command->info('   ✓ Created Apple Watch Series 9 with ' . $product->variants()->count() . ' variants');
    }

    private function createSonyHeadphones($category, $brand, $colorAttr): void
    {
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Sony WH-1000XM5',
            'slug' => 'sony-wh-1000xm5',
            'sku' => 'SONYWH1000XM5',
            'description' => 'Industry-leading noise canceling headphones with exceptional sound quality and 30-hour battery life.',
            'short_description' => 'Premium noise-canceling headphones with superior audio quality.',
            'price_cents' => 39999, // Base price $399.99
            'cost_price_cents' => 25000,
            'has_variants' => true,
            'variant_type' => 'multiple',
            'variant_attributes' => [$colorAttr->id],
            'is_active' => true,
            'is_featured' => false,
            'images' => ['products/sony-wh-1000xm5.jpg'],
        ]);

        $colors = $colorAttr->activeValues()->whereIn('slug', ['midnight', 'silver'])->get();

        $this->createVariantsForProduct($product, [
            ['attribute' => $colorAttr, 'values' => $colors],
        ]);

        $this->command->info('   ✓ Created Sony WH-1000XM5 with ' . $product->variants()->count() . ' variants');
    }

    /**
     * Helper method to create variants for a product
     */
    private function createVariantsForProduct($product, $attributeGroups, $pricingRules = []): void
    {
        $combinations = $this->generateAttributeCombinations($attributeGroups);
        $isFirst = true;

        foreach ($combinations as $combination) {
            $priceAdd = 0;
            $costAdd = 0;

            // Calculate price additions based on attribute values
            foreach ($combination as $valueId) {
                $value = ProductAttributeValue::find($valueId);
                if (isset($pricingRules[$value->slug])) {
                    $priceAdd += $pricingRules[$value->slug]['price_add'] ?? 0;
                    $costAdd += $pricingRules[$value->slug]['cost_add'] ?? 0;
                }
            }

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'price_cents' => $product->price_cents + $priceAdd,
                'compare_price_cents' => $product->compare_price_cents ? $product->compare_price_cents + $priceAdd : null,
                'cost_price_cents' => $product->cost_price_cents + $costAdd,
                'stock_quantity' => rand(5, 50),
                'stock_status' => 'in_stock',
                'low_stock_threshold' => 5,
                'track_inventory' => true,
                'is_active' => true,
                'is_default' => $isFirst,
            ]);

            // Attach attribute values
            foreach ($combination as $attributeId => $valueId) {
                $variant->attributeValues()->attach($valueId, [
                    'product_attribute_id' => $attributeId
                ]);
            }

            // Generate SKU
            $variant->sku = $variant->generateSku();
            $variant->save();

            $isFirst = false;
        }
    }

    /**
     * Generate all possible combinations of attribute values
     */
    private function generateAttributeCombinations($attributeGroups): array
    {
        $combinations = [[]];

        foreach ($attributeGroups as $group) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($group['values'] as $value) {
                    $newCombination = $combination;
                    $newCombination[$group['attribute']->id] = $value->id;
                    $newCombinations[] = $newCombination;
                }
            }
            $combinations = $newCombinations;
        }

        return $combinations;
    }
}
