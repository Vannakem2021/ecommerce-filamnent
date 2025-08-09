<?php

namespace Database\Seeders;

use App\Models\SpecificationAttribute;
use App\Models\SpecificationAttributeOption;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecificationAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Product-level specifications (same for all variants)
        $this->createProductLevelSpecs();

        // Variant-level specifications (changes per variant)
        $this->createVariantLevelSpecs();
    }

    private function createProductLevelSpecs()
    {
        // CPU specifications
        $cpuAttribute = SpecificationAttribute::create([
            'name' => 'CPU',
            'code' => 'cpu',
            'data_type' => 'enum',
            'scope' => 'product',
            'is_filterable' => true,
            'sort_order' => 10,
            'description' => 'Central Processing Unit model',
        ]);

        $cpuOptions = [
            'Intel Core i3-1215U', 'Intel Core i5-1235U', 'Intel Core i7-1255U', 'Intel Core i9-1285H',
            'AMD Ryzen 5 5500U', 'AMD Ryzen 7 5700U', 'AMD Ryzen 9 5900HX',
            'Apple M1', 'Apple M2', 'Apple M2 Pro', 'Apple M2 Max',
        ];

        foreach ($cpuOptions as $index => $option) {
            SpecificationAttributeOption::create([
                'specification_attribute_id' => $cpuAttribute->id,
                'value' => $option,
                'slug' => str_replace([' ', '.'], ['-', '-'], strtolower($option)),
                'sort_order' => $index + 1,
            ]);
        }

        // GPU specifications
        $gpuAttribute = SpecificationAttribute::create([
            'name' => 'GPU',
            'code' => 'gpu',
            'data_type' => 'enum',
            'scope' => 'product',
            'is_filterable' => true,
            'sort_order' => 20,
            'description' => 'Graphics Processing Unit',
        ]);

        $gpuOptions = [
            'Intel Iris Xe Graphics', 'Intel UHD Graphics', 'AMD Radeon Graphics',
            'NVIDIA GeForce RTX 3050', 'NVIDIA GeForce RTX 3060', 'NVIDIA GeForce RTX 4050',
            'NVIDIA GeForce RTX 4060', 'NVIDIA GeForce RTX 4070', 'Apple GPU (8-core)',
            'Apple GPU (10-core)', 'Apple GPU (16-core)', 'Apple GPU (19-core)',
        ];

        foreach ($gpuOptions as $index => $option) {
            SpecificationAttributeOption::create([
                'specification_attribute_id' => $gpuAttribute->id,
                'value' => $option,
                'slug' => str_replace([' ', '(', ')'], ['-', '', ''], strtolower($option)),
                'sort_order' => $index + 1,
            ]);
        }

        // Screen Size
        SpecificationAttribute::create([
            'name' => 'Screen Size',
            'code' => 'screen_size',
            'data_type' => 'number',
            'unit' => 'inch',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 30,
            'description' => 'Display diagonal size in inches',
        ]);

        // Screen Resolution
        $resolutionAttribute = SpecificationAttribute::create([
            'name' => 'Screen Resolution',
            'code' => 'screen_resolution',
            'data_type' => 'enum',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 40,
            'description' => 'Display resolution',
        ]);

        $resolutionOptions = [
            '1366x768 (HD)', '1920x1080 (Full HD)', '2560x1440 (QHD)',
            '3840x2160 (4K UHD)', '2560x1600 (WQXGA)', '3456x2234 (3.5K)',
        ];

        foreach ($resolutionOptions as $index => $option) {
            SpecificationAttributeOption::create([
                'specification_attribute_id' => $resolutionAttribute->id,
                'value' => $option,
                'slug' => str_replace(['x', '(', ')', ' '], ['-', '', '', '-'], strtolower($option)),
                'sort_order' => $index + 1,
            ]);
        }

        // Panel Type
        $panelAttribute = SpecificationAttribute::create([
            'name' => 'Panel Type',
            'code' => 'panel_type',
            'data_type' => 'enum',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 50,
            'description' => 'Display panel technology',
        ]);

        $panelOptions = ['IPS', 'OLED', 'TN', 'VA', 'Mini-LED', 'Retina'];
        foreach ($panelOptions as $index => $option) {
            SpecificationAttributeOption::create([
                'specification_attribute_id' => $panelAttribute->id,
                'value' => $option,
                'slug' => strtolower($option),
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function createVariantLevelSpecs()
    {
        // RAM (varies by variant)
        SpecificationAttribute::create([
            'name' => 'RAM',
            'code' => 'ram',
            'data_type' => 'number',
            'unit' => 'GB',
            'scope' => 'variant',
            'is_filterable' => true,
            
            'sort_order' => 100,
            'description' => 'System memory in gigabytes',
        ]);

        // Storage (varies by variant)
        SpecificationAttribute::create([
            'name' => 'Storage',
            'code' => 'storage',
            'data_type' => 'number',
            'unit' => 'GB',
            'scope' => 'variant',
            'is_filterable' => true,
            
            'sort_order' => 110,
            'description' => 'Storage capacity in gigabytes',
        ]);

        // Storage Type
        $storageTypeAttribute = SpecificationAttribute::create([
            'name' => 'Storage Type',
            'code' => 'storage_type',
            'data_type' => 'enum',
            'scope' => 'variant',
            'is_filterable' => true,
            
            'sort_order' => 120,
            'description' => 'Type of storage technology',
        ]);

        $storageTypes = ['SSD', 'HDD', 'eMMC', 'NVMe SSD', 'SATA SSD'];
        foreach ($storageTypes as $index => $type) {
            SpecificationAttributeOption::create([
                'specification_attribute_id' => $storageTypeAttribute->id,
                'value' => $type,
                'slug' => strtolower(str_replace(' ', '-', $type)),
                'sort_order' => $index + 1,
            ]);
        }

        // Weight (can vary by configuration)
        SpecificationAttribute::create([
            'name' => 'Weight',
            'code' => 'weight',
            'data_type' => 'number',
            'unit' => 'kg',
            'scope' => 'variant',
            'is_filterable' => true,
            
            'sort_order' => 130,
            'description' => 'Device weight in kilograms',
        ]);

        // Battery Life
        SpecificationAttribute::create([
            'name' => 'Battery Life',
            'code' => 'battery_life',
            'data_type' => 'number',
            'unit' => 'hours',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 60,
            'description' => 'Typical battery life in hours',
        ]);

        // Operating System
        $osAttribute = SpecificationAttribute::create([
            'name' => 'Operating System',
            'code' => 'operating_system',
            'data_type' => 'enum',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 70,
            'description' => 'Pre-installed operating system',
        ]);

        $osOptions = [
            'Windows 11 Home', 'Windows 11 Pro', 'macOS Ventura', 'macOS Sonoma',
            'Chrome OS', 'Ubuntu Linux', 'No OS',
        ];

        foreach ($osOptions as $index => $os) {
            SpecificationAttributeOption::create([
                'specification_attribute_id' => $osAttribute->id,
                'value' => $os,
                'slug' => strtolower(str_replace([' ', '.'], ['-', ''], $os)),
                'sort_order' => $index + 1,
            ]);
        }

        // Connectivity
        $connectivityAttribute = SpecificationAttribute::create([
            'name' => 'Wireless Connectivity',
            'code' => 'wireless_connectivity',
            'data_type' => 'enum',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 80,
            'description' => 'Wireless connectivity options',
        ]);

        $connectivityOptions = [
            'Wi-Fi 6 (802.11ax)', 'Wi-Fi 6E', 'Wi-Fi 5 (802.11ac)',
            'Bluetooth 5.0', 'Bluetooth 5.1', 'Bluetooth 5.2', 'Bluetooth 5.3',
        ];

        foreach ($connectivityOptions as $index => $connectivity) {
            SpecificationAttributeOption::create([
                'specification_attribute_id' => $connectivityAttribute->id,
                'value' => $connectivity,
                'slug' => strtolower(str_replace([' ', '(', ')', '.'], ['-', '', '', ''], $connectivity)),
                'sort_order' => $index + 1,
            ]);
        }

        // Warranty
        SpecificationAttribute::create([
            'name' => 'Warranty',
            'code' => 'warranty',
            'data_type' => 'number',
            'unit' => 'years',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 90,
            'description' => 'Manufacturer warranty period in years',
        ]);
    }
}
