<?php

namespace Database\Seeders;

use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Seeder;

class ProductAttributesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Color attribute
        $colorAttribute = ProductAttribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'color',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Create color values
        $colors = [
            ['value' => 'Red', 'color_code' => '#FF0000'],
            ['value' => 'Blue', 'color_code' => '#0000FF'],
            ['value' => 'Green', 'color_code' => '#00FF00'],
            ['value' => 'Black', 'color_code' => '#000000'],
            ['value' => 'White', 'color_code' => '#FFFFFF'],
            ['value' => 'Silver', 'color_code' => '#C0C0C0'],
            ['value' => 'Gold', 'color_code' => '#FFD700'],
        ];

        foreach ($colors as $index => $color) {
            ProductAttributeValue::create([
                'product_attribute_id' => $colorAttribute->id,
                'value' => $color['value'],
                'slug' => \Illuminate\Support\Str::slug($color['value']),
                'color_code' => $color['color_code'],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        // Create Storage attribute
        $storageAttribute = ProductAttribute::create([
            'name' => 'Storage',
            'slug' => 'storage',
            'type' => 'select',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Create storage values
        $storageOptions = ['16GB', '32GB', '64GB', '128GB', '256GB', '512GB', '1TB'];
        foreach ($storageOptions as $index => $storage) {
            ProductAttributeValue::create([
                'product_attribute_id' => $storageAttribute->id,
                'value' => $storage,
                'slug' => \Illuminate\Support\Str::slug($storage),
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        // Create Size attribute
        $sizeAttribute = ProductAttribute::create([
            'name' => 'Size',
            'slug' => 'size',
            'type' => 'select',
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Create size values
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        foreach ($sizes as $index => $size) {
            ProductAttributeValue::create([
                'product_attribute_id' => $sizeAttribute->id,
                'value' => $size,
                'slug' => \Illuminate\Support\Str::slug($size),
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        // Create Material attribute
        $materialAttribute = ProductAttribute::create([
            'name' => 'Material',
            'slug' => 'material',
            'type' => 'select',
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // Create material values
        $materials = [
            'Cotton',
            'Polyester',
            'Leather',
            'Metal',
            'Plastic',
            'Glass',
            'Wood',
            'Ceramic'
        ];
        
        foreach ($materials as $index => $material) {
            ProductAttributeValue::create([
                'product_attribute_id' => $materialAttribute->id,
                'value' => $material,
                'slug' => \Illuminate\Support\Str::slug($material),
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        $this->command->info('Product attributes and values created successfully!');
    }
}
