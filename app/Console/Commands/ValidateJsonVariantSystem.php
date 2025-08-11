<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ValidateJsonVariantSystem extends Command
{
    protected $issues = [];
    protected $warnings = [];
    protected $stats = [];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'validate:json-variant-system 
                           {--export=storage/migration-docs/json-system-validation.json : Export results to file}
                           {--detailed : Show detailed validation results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate the JSON-only variant system after migration completion';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Validating JSON-only variant system...');
        
        $this->validateSystemStructure();
        $this->validateDataIntegrity();
        $this->validateJsonStructures();
        $this->performBasicTests();
        
        $this->displayResults();
        
        if ($this->option('export')) {
            $this->exportResults();
        }
        
        return empty($this->issues) ? 0 : 1;
    }
    
    protected function validateSystemStructure(): void
    {
        $this->info('🏗️ Validating system structure...');
        
        // Check that legacy tables are gone
        $legacyTables = ['product_attributes', 'product_attribute_values', 'product_variant_attributes'];
        $remainingTables = [];
        
        foreach ($legacyTables as $table) {
            if (Schema::hasTable($table)) {
                $remainingTables[] = $table;
            }
        }
        
        if (!empty($remainingTables)) {
            $this->issues[] = [
                'type' => 'legacy_tables_exist',
                'message' => 'Legacy tables still exist: ' . implode(', ', $remainingTables),
                'tables' => $remainingTables
            ];
        }
        
        // Check JSON columns exist
        if (!Schema::hasColumn('products', 'variant_config')) {
            $this->issues[] = [
                'type' => 'missing_column',
                'message' => 'Missing variant_config column in products table'
            ];
        }
        
        if (!Schema::hasColumn('product_variants', 'options')) {
            $this->issues[] = [
                'type' => 'missing_column',
                'message' => 'Missing options column in product_variants table'
            ];
        }
    }
    
    protected function validateDataIntegrity(): void
    {
        $this->info('🔗 Validating data integrity...');
        
        // Check migration completion
        $totalProducts = Product::where('has_variants', true)->count();
        $migratedProducts = Product::where('has_variants', true)
            ->where('migrated_to_json', true)
            ->count();
        
        $totalVariants = ProductVariant::count();
        $migratedVariants = ProductVariant::where('migrated_to_json', true)->count();
        
        $this->stats['products_total'] = $totalProducts;
        $this->stats['products_migrated'] = $migratedProducts;
        $this->stats['variants_total'] = $totalVariants;
        $this->stats['variants_migrated'] = $migratedVariants;
        
        if ($migratedProducts < $totalProducts) {
            $this->issues[] = [
                'type' => 'incomplete_migration',
                'message' => "Not all products migrated: {$migratedProducts}/{$totalProducts}"
            ];
        }
        
        if ($migratedVariants < $totalVariants) {
            $this->issues[] = [
                'type' => 'incomplete_migration',
                'message' => "Not all variants migrated: {$migratedVariants}/{$totalVariants}"
            ];
        }
    }
    
    protected function validateJsonStructures(): void
    {
        $this->info('📋 Validating JSON structures...');
        
        // Check product JSON
        $productsWithConfig = Product::whereNotNull('variant_config')->count();
        $this->stats['products_with_config'] = $productsWithConfig;
        
        // Check variant JSON
        $variantsWithOptions = ProductVariant::whereNotNull('options')->count();
        $this->stats['variants_with_options'] = $variantsWithOptions;
        
        // Validate JSON structure integrity
        $invalidProductJson = Product::whereNotNull('variant_config')
            ->get()
            ->filter(function ($product) {
                return !is_array($product->variant_config);
            });
            
        if ($invalidProductJson->count() > 0) {
            $this->issues[] = [
                'type' => 'invalid_json',
                'message' => "Found {$invalidProductJson->count()} products with invalid JSON config"
            ];
        }
        
        $invalidVariantJson = ProductVariant::whereNotNull('options')
            ->get()
            ->filter(function ($variant) {
                return !is_array($variant->options);
            });
            
        if ($invalidVariantJson->count() > 0) {
            $this->issues[] = [
                'type' => 'invalid_json',
                'message' => "Found {$invalidVariantJson->count()} variants with invalid JSON options"
            ];
        }
    }
    
    protected function performBasicTests(): void
    {
        $this->info('🧪 Performing basic functionality tests...');
        
        // Test product loading
        try {
            $sampleProduct = Product::where('has_variants', true)->with('variants')->first();
            if ($sampleProduct) {
                $this->stats['sample_product_loaded'] = true;
                $this->stats['sample_variant_count'] = $sampleProduct->variants ? $sampleProduct->variants->count() : 0;
            } else {
                $this->warnings[] = [
                    'type' => 'no_test_data',
                    'message' => 'No products with variants found for testing'
                ];
            }
        } catch (\Exception $e) {
            $this->issues[] = [
                'type' => 'functionality_error',
                'message' => 'Cannot load products: ' . $e->getMessage()
            ];
        }
        
        // Test JSON access
        try {
            $variantWithOptions = ProductVariant::whereNotNull('options')->first();
            if ($variantWithOptions) {
                $options = $variantWithOptions->options;
                $this->stats['json_access_test'] = is_array($options) ? 'passed' : 'failed';
            }
        } catch (\Exception $e) {
            $this->issues[] = [
                'type' => 'json_access_error',
                'message' => 'Cannot access variant options: ' . $e->getMessage()
            ];
        }
    }
    
    protected function displayResults(): void
    {
        $this->info('');
        $this->info('📊 VALIDATION RESULTS');
        $this->info('====================');
        
        // Summary
        $totalIssues = count($this->issues);
        $totalWarnings = count($this->warnings);
        
        if ($totalIssues === 0 && $totalWarnings === 0) {
            $this->info('✅ All validations passed! JSON variant system is healthy.');
        } else {
            if ($totalIssues > 0) {
                $this->error("❌ Found {$totalIssues} issues");
            }
            if ($totalWarnings > 0) {
                $this->warn("⚠️ Found {$totalWarnings} warnings");
            }
        }
        
        // Display issues
        if (!empty($this->issues)) {
            $this->error('');
            $this->error('ISSUES:');
            foreach ($this->issues as $issue) {
                $this->error("  • {$issue['message']}");
            }
        }
        
        // Display warnings
        if (!empty($this->warnings)) {
            $this->warn('');
            $this->warn('WARNINGS:');
            foreach ($this->warnings as $warning) {
                $this->warn("  • {$warning['message']}");
            }
        }
        
        // Display statistics
        $this->info('');
        $this->info('📈 System Statistics:');
        foreach ($this->stats as $key => $value) {
            $label = str_replace('_', ' ', ucfirst($key));
            $this->line("  {$label}: {$value}");
        }
    }
    
    protected function exportResults(): void
    {
        $filename = $this->option('export');
        
        $results = [
            'timestamp' => now()->toISOString(),
            'validation_summary' => [
                'total_issues' => count($this->issues),
                'total_warnings' => count($this->warnings),
                'status' => empty($this->issues) ? 'healthy' : 'issues_found'
            ],
            'statistics' => $this->stats,
            'issues' => $this->issues,
            'warnings' => $this->warnings
        ];
        
        $directory = dirname($filename);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        file_put_contents($filename, json_encode($results, JSON_PRETTY_PRINT));
        $this->info("📄 Results exported to: {$filename}");
    }
}
