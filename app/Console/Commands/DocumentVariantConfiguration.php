<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentVariantConfiguration extends Command
{
    protected $signature = 'document:variant-config 
                           {--output=storage/migration-docs/ : Output directory for documentation}
                           {--format=json : Output format (json, markdown)}';

    protected $description = 'Document current variant system configuration for migration planning';

    public function handle()
    {
        $this->info('📝 Starting variant system documentation...');
        
        $outputDir = $this->option('output');
        $format = $this->option('format');
        
        // Create output directory
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Gather system information
        $documentation = [
            'generated_at' => now()->toISOString(),
            'system_info' => $this->gatherSystemInfo(),
            'product_analysis' => $this->gatherProductInfo(),
            'variant_analysis' => $this->gatherVariantInfo(),
            'recommendations' => $this->generateRecommendations()
        ];
        
        // Save documentation
        $this->saveDocumentation($documentation, $outputDir, $format);
        $this->displaySummary($documentation);
        
        $this->info('✅ Variant system documentation completed!');
        return 0;
    }
    
    protected function gatherSystemInfo(): array
    {
        $this->info('🔍 Analyzing system configuration...');
        
        $tables = ['products', 'product_variants', 'product_attributes', 'product_attribute_values'];
        $existingTables = [];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $existingTables[$table] = [
                    'exists' => true,
                    'row_count' => DB::table($table)->count(),
                    'columns' => Schema::getColumnListing($table)
                ];
            } else {
                $existingTables[$table] = ['exists' => false];
            }
        }
        
        return [
            'database_tables' => $existingTables,
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION
        ];
    }
    
    protected function gatherProductInfo(): array
    {
        $this->info('📦 Analyzing products...');
        
        $totalProducts = Product::count();
        $productsWithVariants = Product::where('has_variants', true)->count();
        $productsWithJsonAttributes = Product::whereNotNull('attributes')->count();
        
        return [
            'total_products' => $totalProducts,
            'products_with_variants' => $productsWithVariants,
            'products_with_json_attributes' => $productsWithJsonAttributes,
            'percentage_with_variants' => $totalProducts > 0 ? round(($productsWithVariants / $totalProducts) * 100, 2) : 0
        ];
    }
    
    protected function gatherVariantInfo(): array
    {
        $this->info('🔄 Analyzing variants...');
        
        $totalVariants = ProductVariant::count();
        $activeVariants = ProductVariant::where('is_active', true)->count();
        $variantsWithJsonOptions = ProductVariant::whereNotNull('options')->count();
        
        return [
            'total_variants' => $totalVariants,
            'active_variants' => $activeVariants,
            'variants_with_json_options' => $variantsWithJsonOptions
        ];
    }
    
    protected function generateRecommendations(): array
    {
        $recommendations = [];
        
        $productInfo = $this->gatherProductInfo();
        $variantInfo = $this->gatherVariantInfo();
        
        $complexity = 'low';
        if ($productInfo['products_with_variants'] > 100 || $variantInfo['total_variants'] > 500) {
            $complexity = 'high';
        } elseif ($productInfo['products_with_variants'] > 20 || $variantInfo['total_variants'] > 100) {
            $complexity = 'medium';
        }
        
        $recommendations[] = [
            'type' => 'complexity',
            'level' => $complexity,
            'message' => "Migration complexity assessed as {$complexity}"
        ];
        
        return $recommendations;
    }
    
    protected function saveDocumentation(array $documentation, string $outputDir, string $format): void
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "{$outputDir}/variant-config-{$timestamp}.{$format}";
        
        if ($format === 'json') {
            file_put_contents($filename, json_encode($documentation, JSON_PRETTY_PRINT));
        } else {
            $md = $this->generateMarkdownReport($documentation);
            file_put_contents($filename, $md);
        }
        
        $this->info("📄 Documentation saved to: {$filename}");
    }
    
    protected function generateMarkdownReport(array $documentation): string
    {
        $md = "# Variant System Configuration Report\n\n";
        $md .= "Generated: {$documentation['generated_at']}\n\n";
        
        $md .= "## System Overview\n\n";
        $md .= "- Total Products: {$documentation['product_analysis']['total_products']}\n";
        $md .= "- Products with Variants: {$documentation['product_analysis']['products_with_variants']}\n";
        $md .= "- Total Variants: {$documentation['variant_analysis']['total_variants']}\n";
        
        return $md;
    }
    
    protected function displaySummary(array $documentation): void
    {
        $this->info('');
        $this->info('📊 DOCUMENTATION SUMMARY');
        $this->info('================================');
        
        $productInfo = $documentation['product_analysis'];
        $variantInfo = $documentation['variant_analysis'];
        
        $this->line("Products: {$productInfo['total_products']} total, {$productInfo['products_with_variants']} with variants");
        $this->line("Variants: {$variantInfo['total_variants']} total, {$variantInfo['active_variants']} active");
        $this->line("JSON Ready: {$variantInfo['variants_with_json_options']} variants with JSON options");
    }
}
