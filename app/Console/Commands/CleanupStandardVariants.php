<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantTransitionService;
use Illuminate\Console\Command;

class CleanupStandardVariants extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'products:cleanup-standard-variants {--dry-run : Show what would be cleaned without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up confusing "Standard" variants and convert products appropriately';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('🔍 Scanning for "Standard" variants...');
        
        // Find all variants with "Standard" in their options
        $standardVariants = ProductVariant::whereJsonContains('options->Color', 'Standard')
            ->orWhereJsonContains('options->Storage', 'Standard')
            ->with('product')
            ->get();
        
        if ($standardVariants->isEmpty()) {
            $this->info('✅ No "Standard" variants found!');
            return Command::SUCCESS;
        }
        
        $this->warn("Found {$standardVariants->count()} products with 'Standard' variants:");
        
        $cleanedCount = 0;
        $convertedToSimple = 0;
        
        // Group variants by product
        $productGroups = $standardVariants->groupBy('product_id');
        
        foreach ($productGroups as $productId => $variants) {
            $product = $variants->first()->product;
            $this->line("\n📦 Product: {$product->name} (ID: {$product->id})");
            
            // Check if product has ONLY standard variants
            $allVariants = $product->variants;
            $hasOnlyStandardVariants = $allVariants->every(function ($variant) {
                return ($variant->options['Color'] ?? '') === 'Standard' && 
                       ($variant->options['Storage'] ?? '') === 'Standard';
            });
            
            if ($hasOnlyStandardVariants) {
                $this->line("   • Has only 'Standard' variants ({$allVariants->count()})");
                
                if (!$isDryRun) {
                    // Convert to simple product
                    ProductVariantTransitionService::convertToSimpleProduct($product);
                    $convertedToSimple++;
                    $this->info("   ✅ Converted to simple product");
                } else {
                    $this->info("   🔧 Would convert to simple product");
                }
            } else {
                $this->line("   • Has mixed variants (some standard, some real)");
                $standardCount = $variants->count();
                $realCount = $allVariants->count() - $standardCount;
                $this->line("   • Standard variants: {$standardCount}, Real variants: {$realCount}");
                
                if (!$isDryRun) {
                    // Delete only the standard variants
                    foreach ($variants as $variant) {
                        $this->line("   • Deleting standard variant: {$variant->sku}");
                        $variant->delete();
                    }
                    $cleanedCount++;
                    $this->info("   ✅ Removed standard variants, kept real variants");
                } else {
                    $this->info("   🔧 Would remove standard variants, keep real variants");
                }
            }
        }
        
        if (!$isDryRun) {
            $this->info("\n🎉 Cleanup completed!");
            $this->line("   • Products converted to simple: {$convertedToSimple}");
            $this->line("   • Products with standard variants removed: {$cleanedCount}");
        } else {
            $this->info("\n📋 Dry run completed. Run without --dry-run to apply changes.");
        }
        
        // Show final statistics
        $this->line('');
        $this->info('📊 Final Statistics:');
        $totalProducts = Product::count();
        $variantProducts = Product::where('has_variants', true)->count();
        $simpleProducts = $totalProducts - $variantProducts;
        
        $this->line("   • Total products: {$totalProducts}");
        $this->line("   • Products with variants: {$variantProducts}");
        $this->line("   • Simple products: {$simpleProducts}");
        
        $totalVariants = ProductVariant::count();
        $remainingStandardVariants = ProductVariant::whereJsonContains('options->Color', 'Standard')
            ->orWhereJsonContains('options->Storage', 'Standard')
            ->count();
            
        $this->line("   • Total variants: {$totalVariants}");
        $this->line("   • Remaining 'Standard' variants: {$remainingStandardVariants}");
        
        if ($remainingStandardVariants > 0) {
            $this->warn("   ⚠️ Some 'Standard' variants remain (mixed with real variants)");
        } else {
            $this->info("   ✅ No 'Standard' variants remaining!");
        }
        
        return Command::SUCCESS;
    }
}
