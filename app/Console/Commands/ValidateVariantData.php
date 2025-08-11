<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ValidateVariantData extends Command
{
    protected $errors = [];
    protected $warnings = [];
    protected $stats = [];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'validate:variant-data 
                           {--detailed : Show detailed validation results}
                           {--fix-orphans : Attempt to fix orphaned records}
                           {--export=report.json : Export validation results to file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate variant data integrity before migration to JSON system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Starting variant data validation...');
        
        // Check if tables exist
        if (!$this->checkTablesExist()) {
            $this->error('Required tables are missing. Cannot proceed with validation.');
            return 1;
        }
        
        // Run validation checks
        $this->validateProductVariantRelationships();
        $this->validatePricingConsistency();
        $this->validateSkuUniqueness();
        $this->collectStatistics();
        
        // Display results
        $this->displayValidationResults();
        
        // Export results if requested
        if ($this->option('export')) {
            $this->exportResults();
        }
        
        return count($this->errors) === 0 ? 0 : 1;
    }
    
    protected function checkTablesExist(): bool
    {
        $requiredTables = ['products', 'product_variants'];
        
        $this->info('📋 Checking table existence...');
        
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $this->error("Required table '{$table}' does not exist");
                return false;
            }
        }
        
        $this->info('✅ Table check completed');
        return true;
    }
    
    protected function validateProductVariantRelationships()
    {
        $this->info('🔗 Validating product-variant relationships...');
        
        // Check for variants without valid products
        $orphanedVariants = ProductVariant::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('products')
                  ->whereColumn('products.id', 'product_variants.product_id');
        })->get();
        
        if ($orphanedVariants->count() > 0) {
            $this->errors[] = [
                'type' => 'orphaned_variants',
                'message' => "Found {$orphanedVariants->count()} variants with invalid product references",
                'details' => $orphanedVariants->pluck('id')->toArray()
            ];
        }
        
        // Check for products marked as having variants but with no variants
        $productsWithoutVariants = Product::where('has_variants', true)
            ->whereDoesntHave('variants')
            ->get();
            
        if ($productsWithoutVariants->count() > 0) {
            $this->warnings[] = [
                'type' => 'products_without_variants',
                'message' => "Found {$productsWithoutVariants->count()} products marked as having variants but with no variants",
                'details' => $productsWithoutVariants->pluck('id')->toArray()
            ];
        }
    }
    
    protected function validatePricingConsistency()
    {
        $this->info('💰 Validating pricing consistency...');
        
        // Check for products with null or zero prices
        $productsWithInvalidPrices = Product::where(function ($query) {
            $query->whereNull('price')
                  ->orWhere('price', '<=', 0)
                  ->orWhereNull('price_cents')
                  ->orWhere('price_cents', '<=', 0);
        })->get();
        
        if ($productsWithInvalidPrices->count() > 0) {
            $this->warnings[] = [
                'type' => 'invalid_product_prices',
                'message' => "Found {$productsWithInvalidPrices->count()} products with invalid prices",
                'details' => $productsWithInvalidPrices->pluck('id')->toArray()
            ];
        }
    }
    
    protected function validateSkuUniqueness()
    {
        $this->info('🏷️ Validating SKU uniqueness...');
        
        // Check for duplicate variant SKUs
        $duplicateVariantSkus = ProductVariant::select('sku', DB::raw('COUNT(*) as count'))
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->groupBy('sku')
            ->having('count', '>', 1)
            ->get();
            
        if ($duplicateVariantSkus->count() > 0) {
            $this->errors[] = [
                'type' => 'duplicate_variant_skus',
                'message' => "Found {$duplicateVariantSkus->count()} duplicate variant SKUs",
                'details' => $duplicateVariantSkus->pluck('sku')->toArray()
            ];
        }
    }
    
    protected function collectStatistics()
    {
        $this->info('📊 Collecting statistics...');
        
        $this->stats['total_products'] = Product::count();
        $this->stats['products_with_variants'] = Product::where('has_variants', true)->count();
        $this->stats['total_variants'] = ProductVariant::count();
        $this->stats['active_variants'] = ProductVariant::where('is_active', true)->count();
        $this->stats['variants_with_json_options'] = ProductVariant::whereNotNull('options')->count();
        $this->stats['variants_with_override_price'] = ProductVariant::whereNotNull('override_price')->count();
        
        // Phase 2 specific stats
        $this->stats['products_migrated_to_json'] = Product::where('migrated_to_json', true)->count();
        $this->stats['variants_migrated_to_json'] = ProductVariant::where('migrated_to_json', true)->count();
        $this->stats['products_with_variant_config'] = Product::whereNotNull('variant_config')->count();
    }
    
    protected function displayValidationResults()
    {
        $this->info('');
        $this->info('📊 VALIDATION RESULTS');
        $this->info('================================');
        
        // Display statistics
        $this->info('📈 Statistics:');
        foreach ($this->stats as $key => $value) {
            $label = str_replace('_', ' ', ucfirst($key));
            $this->line("  {$label}: {$value}");
        }
        
        // Display errors
        if (count($this->errors) > 0) {
            $this->error('');
            $this->error('❌ ERRORS FOUND (' . count($this->errors) . ')');
            foreach ($this->errors as $error) {
                $this->error("• {$error['message']}");
            }
        } else {
            $this->info('✅ No errors found!');
        }
        
        // Display warnings
        if (count($this->warnings) > 0) {
            $this->warn('');
            $this->warn('⚠️ WARNINGS (' . count($this->warnings) . ')');
            foreach ($this->warnings as $warning) {
                $this->warn("• {$warning['message']}");
            }
        } else {
            $this->info('✅ No warnings!');
        }
        
        $this->info('');
        if (count($this->errors) > 0) {
            $this->error('💥 Validation failed with ' . count($this->errors) . ' errors');
        } else {
            $this->info('🎉 Validation passed! System is ready for migration.');
        }
    }
    
    protected function exportResults()
    {
        $filename = $this->option('export');
        
        $results = [
            'timestamp' => now()->toISOString(),
            'validation_summary' => [
                'errors_count' => count($this->errors),
                'warnings_count' => count($this->warnings),
                'status' => count($this->errors) === 0 ? 'passed' : 'failed'
            ],
            'statistics' => $this->stats,
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
        
        file_put_contents($filename, json_encode($results, JSON_PRETTY_PRINT));
        $this->info("📄 Validation results exported to: {$filename}");
    }
}
