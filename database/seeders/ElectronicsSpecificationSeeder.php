<?php

namespace Database\Seeders;

use App\Models\SpecificationAttribute;
use App\Models\SpecificationAttributeOption;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ElectronicsSpecificationSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Smartphone specifications
        $this->createSmartphoneSpecs();
        
        // Tablet specifications
        $this->createTabletSpecs();
        
        // Headphones/Audio specifications
        $this->createAudioSpecs();
        
        // Gaming specifications
        $this->createGamingSpecs();
        
        // Camera specifications
        $this->createCameraSpecs();
    }

    private function createSmartphoneSpecs()
    {
        // Display Type
        $displayTypeAttr = SpecificationAttribute::create([
            'name' => 'Display Type',
            'code' => 'display_type',
            'data_type' => 'enum',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 25,
            'description' => 'Type of display technology',
        ]);

        $displayTypes = ['AMOLED', 'Super AMOLED', 'IPS LCD', 'OLED', 'Dynamic AMOLED', 'Retina'];
        foreach ($displayTypes as $index => $type) {
            SpecificationAttributeOption::create([
                'specification_attribute_id' => $displayTypeAttr->id,
                'value' => $type,
                'slug' => strtolower(str_replace(' ', '-', $type)),
                'sort_order' => $index + 1,
            ]);
        }

        // Refresh Rate
        SpecificationAttribute::create([
            'name' => 'Refresh Rate',
            'code' => 'refresh_rate',
            'data_type' => 'number',
            'unit' => 'Hz',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 35,
            'description' => 'Display refresh rate in Hz',
        ]);

        // Camera Resolution (Main)
        SpecificationAttribute::create([
            'name' => 'Main Camera',
            'code' => 'main_camera',
            'data_type' => 'number',
            'unit' => 'MP',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 140,
            'description' => 'Main camera resolution in megapixels',
        ]);

        // Front Camera
        SpecificationAttribute::create([
            'name' => 'Front Camera',
            'code' => 'front_camera',
            'data_type' => 'number',
            'unit' => 'MP',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 150,
            'description' => 'Front camera resolution in megapixels',
        ]);

        // Battery Capacity
        SpecificationAttribute::create([
            'name' => 'Battery Capacity',
            'code' => 'battery_capacity',
            'data_type' => 'number',
            'unit' => 'mAh',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 160,
            'description' => 'Battery capacity in milliampere-hours',
        ]);

        // 5G Support
        SpecificationAttribute::create([
            'name' => '5G Support',
            'code' => '5g_support',
            'data_type' => 'boolean',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 170,
            'description' => 'Whether the device supports 5G connectivity',
        ]);
    }

    private function createTabletSpecs()
    {
        // Stylus Support
        SpecificationAttribute::create([
            'name' => 'Stylus Support',
            'code' => 'stylus_support',
            'data_type' => 'boolean',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 180,
            'description' => 'Whether the tablet supports stylus input',
        ]);

        // Keyboard Support
        SpecificationAttribute::create([
            'name' => 'Keyboard Support',
            'code' => 'keyboard_support',
            'data_type' => 'boolean',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 190,
            'description' => 'Whether the tablet supports detachable keyboard',
        ]);
    }

    private function createAudioSpecs()
    {
        // Audio Type
        $audioTypeAttr = SpecificationAttribute::create([
            'name' => 'Audio Type',
            'code' => 'audio_type',
            'data_type' => 'enum',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 200,
            'description' => 'Type of audio device',
        ]);

        $audioTypes = ['Over-ear', 'On-ear', 'In-ear', 'Earbuds', 'Gaming Headset'];
        foreach ($audioTypes as $index => $type) {
            SpecificationAttributeOption::create([
                'specification_attribute_id' => $audioTypeAttr->id,
                'value' => $type,
                'slug' => strtolower(str_replace('-', '', $type)),
                'sort_order' => $index + 1,
            ]);
        }

        // Noise Cancellation
        SpecificationAttribute::create([
            'name' => 'Noise Cancellation',
            'code' => 'noise_cancellation',
            'data_type' => 'boolean',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 210,
            'description' => 'Active noise cancellation support',
        ]);

        // Wireless
        SpecificationAttribute::create([
            'name' => 'Wireless',
            'code' => 'wireless',
            'data_type' => 'boolean',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 220,
            'description' => 'Wireless connectivity support',
        ]);

        // Driver Size
        SpecificationAttribute::create([
            'name' => 'Driver Size',
            'code' => 'driver_size',
            'data_type' => 'number',
            'unit' => 'mm',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 230,
            'description' => 'Audio driver size in millimeters',
        ]);
    }

    private function createGamingSpecs()
    {
        // Gaming Platform
        $platformAttr = SpecificationAttribute::create([
            'name' => 'Gaming Platform',
            'code' => 'gaming_platform',
            'data_type' => 'enum',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 240,
            'description' => 'Compatible gaming platform',
        ]);

        $platforms = ['PC', 'PlayStation', 'Xbox', 'Nintendo Switch', 'Mobile', 'Multi-platform'];
        foreach ($platforms as $index => $platform) {
            SpecificationAttributeOption::create([
                'specification_attribute_id' => $platformAttr->id,
                'value' => $platform,
                'slug' => strtolower(str_replace(' ', '-', $platform)),
                'sort_order' => $index + 1,
            ]);
        }

        // RGB Lighting
        SpecificationAttribute::create([
            'name' => 'RGB Lighting',
            'code' => 'rgb_lighting',
            'data_type' => 'boolean',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 250,
            'description' => 'RGB lighting support',
        ]);
    }

    private function createCameraSpecs()
    {
        // Camera Type
        $cameraTypeAttr = SpecificationAttribute::create([
            'name' => 'Camera Type',
            'code' => 'camera_type',
            'data_type' => 'enum',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 260,
            'description' => 'Type of camera',
        ]);

        $cameraTypes = ['DSLR', 'Mirrorless', 'Point & Shoot', 'Action Camera', 'Instant Camera'];
        foreach ($cameraTypes as $index => $type) {
            SpecificationAttributeOption::create([
                'specification_attribute_id' => $cameraTypeAttr->id,
                'value' => $type,
                'slug' => strtolower(str_replace([' ', '&'], ['-', 'and'], $type)),
                'sort_order' => $index + 1,
            ]);
        }

        // Sensor Size
        $sensorSizeAttr = SpecificationAttribute::create([
            'name' => 'Sensor Size',
            'code' => 'sensor_size',
            'data_type' => 'enum',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 270,
            'description' => 'Camera sensor size',
        ]);

        $sensorSizes = ['Full Frame', 'APS-C', 'Micro Four Thirds', '1-inch', '1/2.3-inch'];
        foreach ($sensorSizes as $index => $size) {
            SpecificationAttributeOption::create([
                'specification_attribute_id' => $sensorSizeAttr->id,
                'value' => $size,
                'slug' => strtolower(str_replace(['/', ' '], ['-', '-'], $size)),
                'sort_order' => $index + 1,
            ]);
        }

        // Video Recording
        $videoRecordingAttr = SpecificationAttribute::create([
            'name' => 'Video Recording',
            'code' => 'video_recording',
            'data_type' => 'enum',
            'scope' => 'product',
            'is_filterable' => true,
            
            'sort_order' => 280,
            'description' => 'Maximum video recording resolution',
        ]);

        $videoResolutions = ['1080p', '4K', '6K', '8K'];
        foreach ($videoResolutions as $index => $resolution) {
            SpecificationAttributeOption::create([
                'specification_attribute_id' => $videoRecordingAttr->id,
                'value' => $resolution,
                'slug' => strtolower($resolution),
                'sort_order' => $index + 1,
            ]);
        }
    }
}
