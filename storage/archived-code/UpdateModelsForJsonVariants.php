<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateModelsForJsonVariants extends Command
{
    protected $signature = 'update:models-for-json-variants 
                           {--dry-run : Show what would be updated without making changes}
                           {--backup : Create backup of original model files}';

    protected $description = 'Update model relationships to use JSON-only variant system';

    protected $modelUpdates = [];

    public function handle()
    {
        $this->info('🔄 Updating models for JSON-only variant system...');
        
        if ($this->option('backup')) {
            $this->createModelBackups();
        }
        
        $this->updateProductModel();
        $this->updateProductVariantModel();
        $this->removeObsoleteModels();
        
        $this->displayUpdateSummary();
        
        if (!$this->option('dry-run')) {
            $this->info('✅ Model updates completed!');
            $this->warn('🔄 Please clear your application cache: php artisan optimize:clear');
        }
        
        return 0;
    }
    
    protected function createModelBackups(): void
    {
        if ($this->option('dry-run')) {
            $this->line('[DRY RUN] Would create model backups');
            return;
        }
        
        $this->info('💾 Creating model backups...');
        
        $modelsToBackup = [
            'app/Models/Product.php',
            'app/Models/ProductVariant.php',
            'app/Models/ProductAttribute.php',
            'app/Models/ProductAttributeValue.php'
        ];
        
        $backupDir = 'storage/model-backups/' . now()->format('Y-m-d_H-i-s');
        File::makeDirectory($backupDir, 0755, true);
        
        foreach ($modelsToBackup as $modelPath) {
            if (File::exists($modelPath)) {
                $backupPath = $backupDir . '/' . basename($modelPath);
                File::copy($modelPath, $backupPath);
                $this->line("  ✅ Backed up: {$modelPath}");
            }
        }
        
        $this->info("Model backups created in: {$backupDir}");
    }
    
    protected function updateProductModel(): void
    {
        $this->info('📝 Updating Product model...');
        
        $modelPath = 'app/Models/Product.php';
        
        if (!File::exists($modelPath)) {
            $this->warn("Product model not found at {$modelPath}");
            return;
        }
        
        $content = File::get($modelPath);
        $originalContent = $content;
        
        // Add JSON casting for new columns
        $content = $this->addJsonCasting($content, ['attributes', 'variant_config']);
        
        // Add helper methods for JSON variant handling
        $content = $this->addProductHelperMethods($content);
        
        // Remove legacy relationship methods if they exist
        $content = $this->removeLegacyRelationships($content, [
            'public function attributes()',
            'public function productAttributes()'
        ]);
        
        if ($content !== $originalContent) {
            $this->modelUpdates['Product'] = 'Updated JSON casting and helper methods';
            
            if (!$this->option('dry-run')) {
                File::put($modelPath, $content);
                $this->line('  ✅ Product model updated');
            } else {
                $this->line('  [DRY RUN] Would update Product model');
            }
        } else {
            $this->line('  ℹ️ Product model already up to date');
        }
    }
    
    protected function updateProductVariantModel(): void
    {
        $this->info('📝 Updating ProductVariant model...');
        
        $modelPath = 'app/Models/ProductVariant.php';
        
        if (!File::exists($modelPath)) {
            $this->warn("ProductVariant model not found at {$modelPath}");
            return;
        }
        
        $content = File::get($modelPath);
        $originalContent = $content;
        
        // Add JSON casting for options
        $content = $this->addJsonCasting($content, ['options']);
        
        // Add helper methods for variant options
        $content = $this->addVariantHelperMethods($content);
        
        // Remove legacy relationship methods
        $content = $this->removeLegacyRelationships($content, [
            'public function attributeValues()',
            'public function variantAttributes()'
        ]);
        
        if ($content !== $originalContent) {
            $this->modelUpdates['ProductVariant'] = 'Updated JSON casting and helper methods';
            
            if (!$this->option('dry-run')) {
                File::put($modelPath, $content);
                $this->line('  ✅ ProductVariant model updated');
            } else {
                $this->line('  [DRY RUN] Would update ProductVariant model');
            }
        } else {
            $this->line('  ℹ️ ProductVariant model already up to date');
        }
    }
    
    protected function removeObsoleteModels(): void
    {
        $this->info('🗑️ Checking for obsolete models...');
        
        $obsoleteModels = [
            'app/Models/ProductAttribute.php',
            'app/Models/ProductAttributeValue.php',
            'app/Models/SpecificationAttribute.php',
            'app/Models/SpecificationAttributeOption.php',
            'app/Models/ProductSpecificationValue.php',
            'app/Models/VariantSpecificationValue.php'
        ];
        
        foreach ($obsoleteModels as $modelPath) {
            if (File::exists($modelPath)) {
                if ($this->option('dry-run')) {
                    $this->line("  [DRY RUN] Would mark {$modelPath} as obsolete");
                } else {
                    // Don't delete, just comment out or rename to indicate obsolete
                    $this->markModelObsolete($modelPath);
                    $this->line("  ⚠️ Marked {$modelPath} as obsolete");
                }
                
                $this->modelUpdates[basename($modelPath, '.php')] = 'Marked as obsolete';
            }
        }
    }
    
    protected function addJsonCasting(string $content, array $columns): string
    {
        // Check if casts property exists
        if (!preg_match('/protected\s+\$casts\s*=\s*\[([^\]]*)\];/s', $content, $matches)) {
            // Add casts property if it doesn't exist
            $castsArray = implode(",\n        ", array_map(fn($col) => "'{$col}' => 'array'", $columns));
            $castsProperty = "\n    protected \$casts = [\n        {$castsArray}\n    ];\n";
            
            // Insert after fillable property
            $content = preg_replace(
                '/(protected\s+\$fillable\s*=\s*\[[^\]]*\];)/s',
                "$1{$castsProperty}",
                $content
            );
        } else {
            // Update existing casts
            $existingCasts = $matches[1];
            
            foreach ($columns as $column) {
                if (!str_contains($existingCasts, "'{$column}'")) {
                    $existingCasts .= ",\n        '{$column}' => 'array'";
                }
            }
            
            $content = preg_replace(
                '/protected\s+\$casts\s*=\s*\[([^\]]*)\];/s',
                "protected \$casts = [{$existingCasts}\n    ];",
                $content
            );
        }
        
        return $content;
    }
    
    protected function addProductHelperMethods(string $content): string
    {
        // Check if helper methods already exist
        if (str_contains($content, 'getVariantConfiguration')) {
            return $content;
        }
        
        $helperMethods = '
    /**
     * Get variant configuration from JSON
     */
    public function getVariantConfiguration(): array
    {
        return $this->variant_config ?? [];
    }
    
    /**
     * Get product attributes from JSON
     */
    public function getProductAttributes(): array
    {
        return $this->attributes ?? [];
    }
    
    /**
     * Check if product has been migrated to JSON system
     */
    public function isMigratedToJson(): bool
    {
        return $this->migrated_to_json === true;
    }
    
    /**
     * Get variant count from configuration
     */
    public function getVariantCount(): int
    {
        $config = $this->getVariantConfiguration();
        return $config[\'variant_count\'] ?? $this->variants()->count();
    }
';
        
        // Insert before the closing brace of the class
        $content = preg_replace('/(\n\s*}\s*$)/', $helperMethods . '$1', $content);
        
        return $content;
    }
    
    protected function addVariantHelperMethods(string $content): string
    {
        // Check if helper methods already exist
        if (str_contains($content, 'getVariantOptions')) {
            return $content;
        }
        
        $helperMethods = '
    /**
     * Get variant options from JSON
     */
    public function getVariantOptions(): array
    {
        return $this->options ?? [];
    }
    
    /**
     * Get specific option value
     */
    public function getOptionValue(string $key, $default = null)
    {
        $options = $this->getVariantOptions();
        return $options[$key][\'value\'] ?? $default;
    }
    
    /**
     * Get option with full details
     */
    public function getOption(string $key): ?array
    {
        $options = $this->getVariantOptions();
        return $options[$key] ?? null;
    }
    
    /**
     * Check if variant has specific option
     */
    public function hasOption(string $key): bool
    {
        $options = $this->getVariantOptions();
        return isset($options[$key]);
    }
    
    /**
     * Get effective price (with override if set)
     */
    public function getEffectivePrice(): int
    {
        if ($this->override_price !== null) {
            return $this->override_price;
        }
        
        return $this->price_cents ?? $this->product->price_cents ?? 0;
    }
    
    /**
     * Check if variant has been migrated to JSON system
     */
    public function isMigratedToJson(): bool
    {
        return $this->migrated_to_json === true;
    }
';
        
        // Insert before the closing brace of the class
        $content = preg_replace('/(\n\s*}\s*$)/', $helperMethods . '$1', $content);
        
        return $content;
    }
    
    protected function removeLegacyRelationships(string $content, array $methodNames): string
    {
        foreach ($methodNames as $methodName) {
            // Remove entire method definition
            $pattern = '/\s*\/\*\*.*?\*\/\s*' . preg_quote($methodName, '/') . '.*?\n\s*\}/s';
            $content = preg_replace($pattern, '', $content);
            
            // Also remove simple method definitions without docblocks
            $pattern = '/\s*' . preg_quote($methodName, '/') . '.*?\n\s*\}/s';
            $content = preg_replace($pattern, '', $content);
        }
        
        return $content;
    }
    
    protected function markModelObsolete(string $modelPath): void
    {
        $content = File::get($modelPath);
        
        // Add obsolete comment at the top of the class
        $obsoleteComment = '
/**
 * @deprecated This model is obsolete after variant system migration to JSON.
 * Use JSON-based variant system instead.
 * This file is kept for reference and potential rollback purposes.
 */';
        
        $content = preg_replace(
            '/(class\s+\w+)/',
            $obsoleteComment . "\n$1",
            $content
        );
        
        File::put($modelPath, $content);
    }
    
    protected function displayUpdateSummary(): void
    {
        $this->info('');
        $this->info('📊 MODEL UPDATE SUMMARY');
        $this->info('======================');
        
        if (empty($this->modelUpdates)) {
            $this->line('No model updates were needed.');
            return;
        }
        
        foreach ($this->modelUpdates as $model => $change) {
            $this->line("✅ {$model}: {$change}");
        }
        
        $this->info('');
        $this->info('🔧 Next steps:');
        $this->line('1. Clear application cache: php artisan optimize:clear');
        $this->line('2. Update any custom code that references legacy model relationships');
        $this->line('3. Test your application to ensure everything works correctly');
        $this->line('4. Consider removing obsolete model files after thorough testing');
    }
}