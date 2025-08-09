<?php

namespace App\Console\Commands;

use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Console\Command;

class CreateElectronicsAttributes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'electronics:create-attributes {--force : Force creation even if attributes exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create product attributes specifically for electronics products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔌 Creating Electronics Product Attributes...');
        
        $force = $this->option('force');
        
        // Check if attributes already exist
        if (!$force && ProductAttribute::where('slug', 'like', '%electronics%')->exists()) {
            if (!$this->confirm('Electronics attributes may already exist. Continue anyway?')) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        $this->createBrandAttribute();
        $this->createStorageAttribute();
        $this->createRamAttribute();
        $this->createScreenSizeAttribute();
        $this->createColorAttribute();
        $this->createConnectivityAttribute();
        $this->createBatteryLifeAttribute();
        $this->createProcessorAttribute();
        $this->createOperatingSystemAttribute();
        $this->createCameraAttribute();
        $this->createWarrantyAttribute();
        $this->createConditionAttribute();

        $this->info('✅ Electronics product attributes created successfully!');
        $this->newLine();
        $this->info('📱 You can now use these attributes when creating electronic products:');
        $this->table(
            ['Attribute', 'Type', 'Sample Values'],
            [
                ['Brand', 'select', 'Apple, Samsung, Sony, LG, etc.'],
                ['Storage', 'select', '64GB, 128GB, 256GB, 512GB, 1TB'],
                ['RAM', 'select', '4GB, 8GB, 16GB, 32GB, 64GB'],
                ['Screen Size', 'select', '5.5", 6.1", 6.7", 13", 15.6"'],
                ['Color', 'color', 'Space Gray, Silver, Gold, etc.'],
                ['Connectivity', 'select', 'WiFi, Bluetooth, 5G, etc.'],
                ['Battery Life', 'select', '8 hours, 12 hours, 24 hours'],
                ['Processor', 'select', 'A17 Pro, Snapdragon 8, Intel i7'],
                ['Operating System', 'select', 'iOS, Android, Windows, macOS'],
                ['Camera', 'select', '12MP, 48MP, 108MP'],
                ['Warranty', 'select', '1 Year, 2 Years, 3 Years'],
                ['Condition', 'select', 'New, Refurbished, Used'],
            ]
        );
    }

    private function createBrandAttribute()
    {
        $this->info('Creating Brand attribute...');
        
        $attribute = ProductAttribute::updateOrCreate(
            ['slug' => 'electronics-brand'],
            [
                'name' => 'Brand',
                'type' => 'select',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $brands = [
            'Apple', 'Samsung', 'Sony', 'LG', 'Dell', 'HP', 'Lenovo', 'ASUS', 
            'Acer', 'Microsoft', 'Google', 'OnePlus', 'Xiaomi', 'Huawei', 
            'Canon', 'Nikon', 'Panasonic', 'JBL', 'Bose', 'Beats'
        ];

        foreach ($brands as $index => $brand) {
            ProductAttributeValue::updateOrCreate(
                [
                    'product_attribute_id' => $attribute->id,
                    'slug' => strtolower(str_replace(' ', '-', $brand))
                ],
                [
                    'value' => $brand,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function createStorageAttribute()
    {
        $this->info('Creating Storage attribute...');
        
        $attribute = ProductAttribute::updateOrCreate(
            ['slug' => 'storage-capacity'],
            [
                'name' => 'Storage Capacity',
                'type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        $storages = [
            '16GB', '32GB', '64GB', '128GB', '256GB', '512GB', '1TB', '2TB', '4TB', '8TB'
        ];

        foreach ($storages as $index => $storage) {
            ProductAttributeValue::updateOrCreate(
                [
                    'product_attribute_id' => $attribute->id,
                    'slug' => strtolower($storage)
                ],
                [
                    'value' => $storage,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function createRamAttribute()
    {
        $this->info('Creating RAM attribute...');
        
        $attribute = ProductAttribute::updateOrCreate(
            ['slug' => 'ram-memory'],
            [
                'name' => 'RAM Memory',
                'type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 3,
            ]
        );

        $ramOptions = ['2GB', '4GB', '6GB', '8GB', '12GB', '16GB', '32GB', '64GB', '128GB'];

        foreach ($ramOptions as $index => $ram) {
            ProductAttributeValue::updateOrCreate(
                [
                    'product_attribute_id' => $attribute->id,
                    'slug' => strtolower($ram)
                ],
                [
                    'value' => $ram,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function createScreenSizeAttribute()
    {
        $this->info('Creating Screen Size attribute...');
        
        $attribute = ProductAttribute::updateOrCreate(
            ['slug' => 'screen-size'],
            [
                'name' => 'Screen Size',
                'type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 4,
            ]
        );

        $screenSizes = [
            '4.7"', '5.4"', '5.5"', '6.1"', '6.7"', '7"', '8"', '9.7"', '10.2"', 
            '10.9"', '11"', '12.9"', '13"', '13.3"', '14"', '15.6"', '17"', '21.5"', 
            '24"', '27"', '32"', '43"', '55"', '65"', '75"'
        ];

        foreach ($screenSizes as $index => $size) {
            ProductAttributeValue::updateOrCreate(
                [
                    'product_attribute_id' => $attribute->id,
                    'slug' => str_replace('"', '-inch', strtolower($size))
                ],
                [
                    'value' => $size,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function createColorAttribute()
    {
        $this->info('Creating Color attribute...');
        
        $attribute = ProductAttribute::updateOrCreate(
            ['slug' => 'device-color'],
            [
                'name' => 'Color',
                'type' => 'color',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 5,
            ]
        );

        $colors = [
            ['value' => 'Space Gray', 'color_code' => '#5C5C5C'],
            ['value' => 'Silver', 'color_code' => '#C0C0C0'],
            ['value' => 'Gold', 'color_code' => '#FFD700'],
            ['value' => 'Rose Gold', 'color_code' => '#E8B4B8'],
            ['value' => 'Midnight', 'color_code' => '#1C1C1E'],
            ['value' => 'Blue', 'color_code' => '#007AFF'],
            ['value' => 'Purple', 'color_code' => '#AF52DE'],
            ['value' => 'Pink', 'color_code' => '#FF2D92'],
            ['value' => 'Green', 'color_code' => '#30D158'],
            ['value' => 'Red', 'color_code' => '#FF3B30'],
            ['value' => 'White', 'color_code' => '#FFFFFF'],
            ['value' => 'Black', 'color_code' => '#000000'],
        ];

        foreach ($colors as $index => $color) {
            ProductAttributeValue::updateOrCreate(
                [
                    'product_attribute_id' => $attribute->id,
                    'slug' => strtolower(str_replace(' ', '-', $color['value']))
                ],
                [
                    'value' => $color['value'],
                    'color_code' => $color['color_code'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function createConnectivityAttribute()
    {
        $this->info('Creating Connectivity attribute...');

        $attribute = ProductAttribute::updateOrCreate(
            ['slug' => 'connectivity'],
            [
                'name' => 'Connectivity',
                'type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 6,
            ]
        );

        $connectivity = [
            'WiFi 6', 'WiFi 6E', 'WiFi 7', 'Bluetooth 5.0', 'Bluetooth 5.1',
            'Bluetooth 5.2', 'Bluetooth 5.3', '5G', '4G LTE', 'NFC',
            'USB-C', 'Lightning', 'Thunderbolt 4', 'HDMI', 'Ethernet'
        ];

        foreach ($connectivity as $index => $conn) {
            ProductAttributeValue::updateOrCreate(
                [
                    'product_attribute_id' => $attribute->id,
                    'slug' => strtolower(str_replace([' ', '.'], ['-', '-'], $conn))
                ],
                [
                    'value' => $conn,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function createBatteryLifeAttribute()
    {
        $this->info('Creating Battery Life attribute...');

        $attribute = ProductAttribute::updateOrCreate(
            ['slug' => 'battery-life'],
            [
                'name' => 'Battery Life',
                'type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 7,
            ]
        );

        $batteryLife = [
            '4 hours', '6 hours', '8 hours', '10 hours', '12 hours',
            '15 hours', '18 hours', '20 hours', '24 hours', '30 hours',
            '40 hours', '50 hours', '100 hours'
        ];

        foreach ($batteryLife as $index => $battery) {
            ProductAttributeValue::updateOrCreate(
                [
                    'product_attribute_id' => $attribute->id,
                    'slug' => str_replace(' ', '-', strtolower($battery))
                ],
                [
                    'value' => $battery,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function createProcessorAttribute()
    {
        $this->info('Creating Processor attribute...');

        $attribute = ProductAttribute::updateOrCreate(
            ['slug' => 'processor'],
            [
                'name' => 'Processor',
                'type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 8,
            ]
        );

        $processors = [
            'A17 Pro', 'A16 Bionic', 'A15 Bionic', 'M3', 'M2', 'M1',
            'Snapdragon 8 Gen 3', 'Snapdragon 8 Gen 2', 'Snapdragon 7 Gen 3',
            'Intel Core i3', 'Intel Core i5', 'Intel Core i7', 'Intel Core i9',
            'AMD Ryzen 5', 'AMD Ryzen 7', 'AMD Ryzen 9',
            'MediaTek Dimensity 9300', 'MediaTek Dimensity 8300',
            'Google Tensor G3', 'Exynos 2400'
        ];

        foreach ($processors as $index => $processor) {
            ProductAttributeValue::updateOrCreate(
                [
                    'product_attribute_id' => $attribute->id,
                    'slug' => strtolower(str_replace([' ', '.'], ['-', '-'], $processor))
                ],
                [
                    'value' => $processor,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function createOperatingSystemAttribute()
    {
        $this->info('Creating Operating System attribute...');

        $attribute = ProductAttribute::updateOrCreate(
            ['slug' => 'operating-system'],
            [
                'name' => 'Operating System',
                'type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 9,
            ]
        );

        $operatingSystems = [
            'iOS 17', 'iOS 16', 'iOS 15',
            'Android 14', 'Android 13', 'Android 12',
            'Windows 11', 'Windows 10',
            'macOS Sonoma', 'macOS Ventura', 'macOS Monterey',
            'iPadOS 17', 'iPadOS 16',
            'watchOS 10', 'tvOS 17',
            'Chrome OS', 'Linux Ubuntu', 'Linux Mint'
        ];

        foreach ($operatingSystems as $index => $os) {
            ProductAttributeValue::updateOrCreate(
                [
                    'product_attribute_id' => $attribute->id,
                    'slug' => strtolower(str_replace([' ', '.'], ['-', '-'], $os))
                ],
                [
                    'value' => $os,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function createCameraAttribute()
    {
        $this->info('Creating Camera attribute...');

        $attribute = ProductAttribute::updateOrCreate(
            ['slug' => 'camera-resolution'],
            [
                'name' => 'Camera Resolution',
                'type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 10,
            ]
        );

        $cameras = [
            '8MP', '12MP', '16MP', '20MP', '24MP', '32MP', '48MP', '50MP',
            '64MP', '108MP', '200MP', 'Dual 12MP', 'Triple 48MP',
            'Quad 108MP', '4K Video', '8K Video'
        ];

        foreach ($cameras as $index => $camera) {
            ProductAttributeValue::updateOrCreate(
                [
                    'product_attribute_id' => $attribute->id,
                    'slug' => strtolower(str_replace([' ', '.'], ['-', '-'], $camera))
                ],
                [
                    'value' => $camera,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function createWarrantyAttribute()
    {
        $this->info('Creating Warranty attribute...');

        $attribute = ProductAttribute::updateOrCreate(
            ['slug' => 'warranty-period'],
            [
                'name' => 'Warranty Period',
                'type' => 'select',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 11,
            ]
        );

        $warranties = [
            '6 Months', '1 Year', '2 Years', '3 Years', '5 Years',
            'Limited Lifetime', 'No Warranty'
        ];

        foreach ($warranties as $index => $warranty) {
            ProductAttributeValue::updateOrCreate(
                [
                    'product_attribute_id' => $attribute->id,
                    'slug' => strtolower(str_replace(' ', '-', $warranty))
                ],
                [
                    'value' => $warranty,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function createConditionAttribute()
    {
        $this->info('Creating Condition attribute...');

        $attribute = ProductAttribute::updateOrCreate(
            ['slug' => 'product-condition'],
            [
                'name' => 'Condition',
                'type' => 'select',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 12,
            ]
        );

        $conditions = [
            ['value' => 'New', 'description' => 'Brand new, unopened'],
            ['value' => 'Open Box', 'description' => 'New but box opened'],
            ['value' => 'Refurbished', 'description' => 'Professionally restored'],
            ['value' => 'Used - Like New', 'description' => 'Minimal signs of use'],
            ['value' => 'Used - Good', 'description' => 'Some signs of use'],
            ['value' => 'Used - Fair', 'description' => 'Noticeable wear but functional'],
        ];

        foreach ($conditions as $index => $condition) {
            ProductAttributeValue::updateOrCreate(
                [
                    'product_attribute_id' => $attribute->id,
                    'slug' => strtolower(str_replace([' ', '-'], ['-', ''], $condition['value']))
                ],
                [
                    'value' => $condition['value'],
                    'description' => $condition['description'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}