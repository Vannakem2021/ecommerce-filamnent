<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;

class ProductAttributeSeeder extends Seeder
{
    /**
     * Run the database seeder.
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
            ['value' => 'Black', 'color_code' => '#000000', 'sort_order' => 1],
            ['value' => 'White', 'color_code' => '#FFFFFF', 'sort_order' => 2],
            ['value' => 'Red', 'color_code' => '#EF4444', 'sort_order' => 3],
            ['value' => 'Blue', 'color_code' => '#3B82F6', 'sort_order' => 4],
            ['value' => 'Green', 'color_code' => '#10B981', 'sort_order' => 5],
        ];

        foreach ($colors as $color) {
            ProductAttributeValue::create([
                'product_attribute_id' => $colorAttribute->id,
                'value' => $color['value'],
                'slug' => strtolower($color['value']),
                'color_code' => $color['color_code'],
                'is_active' => true,
                'sort_order' => $color['sort_order'],
            ]);
        }

        // Create Size attribute
        $sizeAttribute = ProductAttribute::create([
            'name' => 'Size',
            'slug' => 'size',
            'type' => 'select',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Create size values
        $sizes = [
            ['value' => 'XS', 'sort_order' => 1],
            ['value' => 'S', 'sort_order' => 2],
            ['value' => 'M', 'sort_order' => 3],
            ['value' => 'L', 'sort_order' => 4],
            ['value' => 'XL', 'sort_order' => 5],
            ['value' => 'XXL', 'sort_order' => 6],
        ];

        foreach ($sizes as $size) {
            ProductAttributeValue::create([
                'product_attribute_id' => $sizeAttribute->id,
                'value' => $size['value'],
                'slug' => strtolower($size['value']),
                'is_active' => true,
                'sort_order' => $size['sort_order'],
            ]);
        }

        // Create Storage attribute (for electronics)
        $storageAttribute = ProductAttribute::create([
            'name' => 'Storage',
            'slug' => 'storage',
            'type' => 'select',
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Create storage values
        $storages = [
            ['value' => '64GB', 'sort_order' => 1],
            ['value' => '128GB', 'sort_order' => 2],
            ['value' => '256GB', 'sort_order' => 3],
            ['value' => '512GB', 'sort_order' => 4],
            ['value' => '1TB', 'sort_order' => 5],
        ];

        foreach ($storages as $storage) {
            ProductAttributeValue::create([
                'product_attribute_id' => $storageAttribute->id,
                'value' => $storage['value'],
                'slug' => strtolower(str_replace(['GB', 'TB'], ['gb', 'tb'], $storage['value'])),
                'is_active' => true,
                'sort_order' => $storage['sort_order'],
            ]);
        }

        echo "Created product attributes and values:\n";
        echo "- Color attribute with " . $colorAttribute->values()->count() . " values\n";
        echo "- Size attribute with " . $sizeAttribute->values()->count() . " values\n";
        echo "- Storage attribute with " . $storageAttribute->values()->count() . " values\n";
    }
}
