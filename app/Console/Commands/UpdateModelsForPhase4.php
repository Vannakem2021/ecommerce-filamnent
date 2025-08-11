<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateModelsForPhase4 extends Command
{
    protected $signature = 'update:models-for-phase4 
                           {--dry-run : Show what would be updated without making changes}
                           {--backup : Create backup of model files}';

    protected $description = 'Update model helper methods for Phase 4 frontend integration';

    public function handle()
    {
        $this->info('🔄 Updating models for Phase 4 frontend integration...');
        
        if ($this->option('backup')) {
            $this->createBackups();
        }
        
        $this->updateProductVariantModel();
        $this->updateProductModel();
        
        $this->info('✅ Model updates completed!');
        return 0;
    }
    
    protected function createBackups(): void
    {
        if ($this->option('dry-run')) {
            $this->line('[DRY RUN] Would create model backups');
            return;
        }
        
        $backupDir = 'storage/model-backups/phase4_' . now()->format('Y-m-d_H-i-s');
        File::makeDirectory($backupDir, 0755, true);
        
        $models = ['app/Models/Product.php', 'app/Models/ProductVariant.php'];
        
        foreach ($models as $model) {
            if (File::exists($model)) {
                File::copy($model, $backupDir . '/' . basename($model));
                $this->line("  ✅ Backed up: {$model}");
            }
        }
        
        $this->info("Model backups created in: {$backupDir}");
    }
    
    protected function updateProductVariantModel(): void
    {
        $this->info('📝 Updating ProductVariant model...');
        
        $modelPath = 'app/Models/ProductVariant.php';
        
        if (!File::exists($modelPath)) {
            $this->warn("ProductVariant model not found");
            return;
        }
        
        $content = File::get($modelPath);
        
        // Check if helper methods already exist
        if (str_contains($content, 'getVariantOptions')) {
            $this->line('  ℹ️ ProductVariant model already has helper methods');
            return;
        }
        
        // Add helper methods before the closing brace
        $helperMethods = '
    // ========================================
    // JSON VARIANT HELPER METHODS (Phase 4)
    // ========================================
    
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
        return isset($options[$key][\'value\']) ? $options[$key][\'value\'] : 
               (isset($options[$key]) && !is_array($options[$key]) ? $options[$key] : $default);
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
     * Get effective price in dollars
     */
    public function getEffectivePriceInDollars(): float
    {
        return $this->getEffectivePrice() / 100;
    }
    
    /**
     * Check if variant has been migrated to JSON system
     */
    public function isMigratedToJson(): bool
    {
        return $this->migrated_to_json === true;
    }
    
    /**
     * Get variant image URL or fallback to product images
     */
    public function getImageUrl(): ?string
    {
        if ($this->image_url) {
            return $this->image_url;
        }
        
        // Fallback to first product image
        $productImages = $this->product->images ?? [];
        return !empty($productImages) ? $productImages[0] : null;
    }
    
    /**
     * Check if variant is in stock
     */
    public function isInStock(): bool
    {
        return $this->stock_quantity > 0 && $this->is_active;
    }
    
    /**
     * Get variant display name based on options
     */
    public function getDisplayName(): string
    {
        if ($this->name) {
            return $this->name;
        }
        
        $options = $this->getVariantOptions();
        if (empty($options)) {
            return "Variant #{$this->id}";
        }
        
        $displayParts = [];
        foreach ($options as $key => $value) {
            $displayValue = is_array($value) ? ($value[\'value\'] ?? $value) : $value;
            $displayParts[] = $displayValue;
        }
        
        return implode(\' - \', $displayParts);
    }
';
        
        if ($this->option('dry-run')) {
            $this->line('  [DRY RUN] Would add helper methods to ProductVariant model');
            return;
        }
        
        // Insert before the closing brace of the class
        $content = preg_replace('/(\n\s*}\s*$)/', $helperMethods . '$1', $content);
        
        File::put($modelPath, $content);
        $this->line('  ✅ ProductVariant model updated with helper methods');
    }
    
    protected function updateProductModel(): void
    {
        $this->info('📝 Updating Product model...');
        
        $modelPath = 'app/Models/Product.php';
        
        if (!File::exists($modelPath)) {
            $this->warn("Product model not found");
            return;
        }
        
        $content = File::get($modelPath);
        
        // Check if we need to add variant_config to casts
        if (!str_contains($content, '\'variant_config\' => \'array\'')) {
            // Add variant_config to casts
            $content = preg_replace(
                '/(\'attributes\' => \'array\',.*?\n)/s',
                '$1        \'variant_config\' => \'array\',
',
                $content
            );
            
            if ($this->option('dry-run')) {
                $this->line('  [DRY RUN] Would add variant_config to casts');
            } else {
                $this->line('  ✅ Added variant_config to casts');
            }
        }
        
        // Check if we need to add migrated_to_json to fillable
        if (!str_contains($content, '\'migrated_to_json\'')) {
            $content = preg_replace(
                '/(\'attributes\',.*?\n)/s',
                '$1        \'variant_config\',
        \'migrated_to_json\',
',
                $content
            );
            
            if ($this->option('dry-run')) {
                $this->line('  [DRY RUN] Would add migration fields to fillable');
            } else {
                $this->line('  ✅ Added migration fields to fillable');
            }
        }
        
        // Check if helper methods already exist
        if (str_contains($content, 'getVariantConfiguration')) {
            $this->line('  ℹ️ Product model already has helper methods');
        } else {
            // Add helper methods
            $helperMethods = '
    // ========================================
    // JSON VARIANT HELPER METHODS (Phase 4)
    // ========================================
    
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
    
    /**
     * Get cheapest variant for price display
     */
    public function getCheapestVariant()
    {
        return $this->variants()
            ->where(\'is_active\', true)
            ->orderBy(\'price_cents\')
            ->first();
    }
    
    /**
     * Get price range for variants
     */
    public function getPriceRange(): ?array
    {
        if (!$this->has_variants) {
            return null;
        }
        
        $variants = $this->variants()->where(\'is_active\', true)->get();
        
        if ($variants->isEmpty()) {
            return null;
        }
        
        $prices = $variants->map(function ($variant) {
            return $variant->getEffectivePrice();
        });
        
        $min = $prices->min() / 100;
        $max = $prices->max() / 100;
        
        return [
            \'min\' => $min,
            \'max\' => $max,
            \'same\' => $min === $max
        ];
    }
    
    /**
     * Check if product has stock (any variant in stock)
     */
    public function hasStock(): bool
    {
        if (!$this->has_variants) {
            return $this->stock_quantity > 0;
        }
        
        return $this->variants()->where(\'is_active\', true)->where(\'stock_quantity\', \'>\', 0)->exists();
    }
    
    /**
     * Get total stock across all variants
     */
    public function getTotalStock(): int
    {
        if (!$this->has_variants) {
            return $this->stock_quantity;
        }
        
        return $this->variants()->where(\'is_active\', true)->sum(\'stock_quantity\');
    }
';
            
            if ($this->option('dry-run')) {
                $this->line('  [DRY RUN] Would add helper methods to Product model');
            } else {
                // Insert before the closing brace of the class
                $content = preg_replace('/(\n\s*}\s*$)/', $helperMethods . '$1', $content);
                $this->line('  ✅ Added helper methods to Product model');
            }
        }
        
        if (!$this->option('dry-run')) {
            File::put($modelPath, $content);
        }
    }
}