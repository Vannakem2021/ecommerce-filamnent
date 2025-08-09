<?php

namespace App\Console\Commands;

use App\Models\ProductVariant;
use Illuminate\Console\Command;

class ConvertVariantsToJsonOptions extends Command
{
    protected $signature = 'variants:convert-to-json';
    protected $description = 'Convert existing variants from attribute system to JSON options';

    public function handle()
    {
        $this->info('Converting variants to JSON options system...');

        $variants = ProductVariant::with('attributeValues.attribute')->get();
        $converted = 0;
        $skipped = 0;

        foreach ($variants as $variant) {
            if (!empty($variant->options)) {
                $skipped++;
                continue; // Already has options
            }

            $options = [];
            
            foreach ($variant->attributeValues as $attributeValue) {
                if ($attributeValue->attribute) {
                    $options[$attributeValue->attribute->name] = $attributeValue->value;
                }
            }

            if (!empty($options)) {
                $variant->options = $options;
                $variant->save();
                $converted++;
                
                $this->line("✅ Converted {$variant->sku}: " . json_encode($options));
            }
        }

        $this->info("Conversion complete!");
        $this->info("- Converted: {$converted} variants");
        $this->info("- Skipped: {$skipped} variants (already had options)");

        return 0;
    }
}
