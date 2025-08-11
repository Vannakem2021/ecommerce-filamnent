<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MigrateVariantDataToJson extends Command
{
    protected $migratedCount = 0;
    protected $errorCount = 0;
    protected $batchId;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:variant-data-to-json 
                           {--batch-size=100 : Number of records to process per batch}
                           {--dry-run : Show what would be migrated without executing}
                           {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate variant data from normalized tables to JSON format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->batchId = Str::uuid()->toString();
        
        $this->info('🚀 Starting variant data migration to JSON format...');
        $this->info("Batch ID: {$this->batchId}");
        
        if (!$this->validatePreconditions()) {
            return 1;
        }
        
        if (!$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('Proceed with migration?')) {
                $this->info('Migration cancelled.');
                return 0;
            }
        }
        
        if ($this->option('dry-run')) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }
        
        $this->migrateProductsToJson();
        $this->displayMigrationSummary();
        
        if ($this->errorCount > 0) {
            $this->warn("⚠️ Migration completed with {$this->errorCount} errors.");
            return 1;
        }
        
        $this->info('✅ Migration completed successfully!');
        return 0;
    }
    
    protected function validatePreconditions(): bool
    {
        $this->info('🔍 Validating migration preconditions...');
        
        $requiredTables = ['products', 'product_variants'];
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $this->error("Required table '{$table}' does not exist");
                return false;
            }
        }
        
        if (!Schema::hasColumn('products', 'attributes') || !Schema::hasColumn('product_variants', 'options')) {
            $this->error('JSON columns not found. Please run Phase 1 migrations first.');
            return false;
        }
        
        $this->info('✅ Preconditions validated');
        return true;
    }
    
    protected function migrateProductsToJson(): void
    {
        $batchSize = $this->option('batch-size');
        
        $query = Product::with(['variants'])
            ->where('has_variants', true)
            ->orderBy('id');
            
        $totalProducts = $query->count();
        $this->info("📦 Found {$totalProducts} products with variants to migrate");
        
        if ($totalProducts === 0) {
            $this->info('No products to migrate.');
            return;
        }
        
        $this->output->progressStart($totalProducts);
        
        $query->chunk($batchSize, function ($products) {
            foreach ($products as $product) {
                $this->migrateProduct($product);
                $this->output->progressAdvance();
            }
        });
        
        $this->output->progressFinish();
    }
    
    protected function migrateProduct(Product $product): void
    {
        try {
            if ($this->option('dry-run')) {
                $this->line("\n  [DRY RUN] Would migrate product: {$product->id} - {$product->name}");
                $this->line("    Variants: {$product->variants->count()}");
                return;
            }
            
            DB::transaction(function () use ($product) {
                // Migrate product-level attributes
                $variantConfig = [
                    'has_variants' => true,
                    'variant_count' => $product->variants->count(),
                    'migrated_at' => now()->toISOString(),
                    'migration_batch_id' => $this->batchId
                ];
                
                $product->update([
                    'variant_config' => $variantConfig,
                    'migrated_to_json' => true
                ]);
                
                // Migrate each variant
                foreach ($product->variants as $variant) {
                    $this->migrateVariant($variant);
                }
            });
            
            $this->migratedCount++;
            
        } catch (\Exception $e) {
            $this->error("Failed to migrate product {$product->id}: {$e->getMessage()}");
            $this->errorCount++;
        }
    }
    
    protected function migrateVariant(ProductVariant $variant): void
    {
        $options = [];
        
        // Preserve existing JSON options if they exist
        if ($variant->options) {
            $options = is_string($variant->options) 
                ? json_decode($variant->options, true) 
                : $variant->options;
        }
        
        // Extract legacy attributes if they exist
        $legacyOptions = $this->extractLegacyVariantAttributes($variant);
        if ($legacyOptions) {
            $options = array_merge($options, $legacyOptions);
        }
        
        // Add basic variant information if still empty
        if (empty($options)) {
            $options = [
                'name' => $variant->name,
                'sku' => $variant->sku,
                'migrated_from_legacy' => true
            ];
        }
        
        // Normalize options
        $options = $this->normalizeVariantOptions($options);
        
        // Calculate override price if needed
        $overridePrice = $this->calculateOverridePrice($variant);
        
        $variant->update([
            'options' => $options ?: null,
            'override_price' => $overridePrice,
            'migrated_to_json' => true
        ]);
    }
    
    protected function extractLegacyVariantAttributes(ProductVariant $variant): array
    {
        if (!Schema::hasTable('product_variant_attributes') || 
            !Schema::hasTable('product_attribute_values') || 
            !Schema::hasTable('product_attributes')) {
            return [];
        }
        
        try {
            $attributes = DB::table('product_variant_attributes as pva')
                ->join('product_attribute_values as pav', 'pva.product_attribute_value_id', '=', 'pav.id')
                ->join('product_attributes as pa', 'pav.product_attribute_id', '=', 'pa.id')
                ->where('pva.product_variant_id', $variant->id)
                ->select('pa.name as attribute_name', 'pa.type', 'pav.value', 'pav.price_modifier')
                ->get();
                
            $options = [];
            foreach ($attributes as $attr) {
                $key = $this->normalizeAttributeName($attr->attribute_name);
                $options[$key] = [
                    'value' => $attr->value,
                    'type' => $attr->type ?? 'string',
                ];
                
                if ($attr->price_modifier !== null && $attr->price_modifier != 0) {
                    $options[$key]['price_modifier'] = (int) $attr->price_modifier;
                }
            }
            
            return $options;
            
        } catch (\Exception $e) {
            $this->warn("Could not extract legacy attributes for variant {$variant->id}: {$e->getMessage()}");
            return [];
        }
    }
    
    protected function normalizeVariantOptions(array $options): array
    {
        $normalized = [];
        
        foreach ($options as $key => $value) {
            // Normalize key names
            $normalizedKey = $this->normalizeAttributeName($key);
            
            if (is_array($value)) {
                $normalized[$normalizedKey] = $value;
            } else {
                $normalized[$normalizedKey] = [
                    'value' => $value,
                    'type' => 'string'
                ];
            }
        }
        
        return $normalized;
    }
    
    protected function normalizeAttributeName(string $name): string
    {
        // Convert to snake_case and clean up
        return Str::snake(trim($name));
    }
    
    protected function calculateOverridePrice(ProductVariant $variant): ?int
    {
        // If variant already has override price, keep it
        if ($variant->override_price !== null) {
            return $variant->override_price;
        }
        
        // If variant price differs from product base price, set override
        $product = $variant->product;
        if ($product && $product->price_cents && $variant->price_cents != $product->price_cents) {
            return $variant->price_cents;
        }
        
        return null;
    }
    
    protected function displayMigrationSummary(): void
    {
        $this->info('');
        $this->info('📊 MIGRATION SUMMARY');
        $this->info('===================');
        $this->line("Batch ID: {$this->batchId}");
        $this->line("Products migrated: {$this->migratedCount}");
        $this->line("Errors encountered: {$this->errorCount}");
        
        if (!$this->option('dry-run')) {
            $this->info('');
            $this->info('Next steps:');
            $this->line('1. Validate migrated data: php artisan validate:variant-data');
            $this->line('2. Test frontend functionality');
            $this->line('3. Proceed to Phase 3 when ready');
        }
    }
}
