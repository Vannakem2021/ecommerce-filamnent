<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductVariantTransitionService;
use Illuminate\Console\Command;

class FixProductVariantConflicts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'products:fix-variant-conflicts {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Fix pricing and inventory conflicts between products and variants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('🔍 Scanning for product variant conflicts...');
        
        $products = Product::with('variants')->get();
        $conflictCount = 0;
        $fixedCount = 0;
        
        foreach ($products as $product) {
            $conflicts = ProductVariantTransitionService::validateAndFixConflicts($product);
            
            if ($conflicts['has_conflicts']) {
                $conflictCount++;
                
                $this->warn("⚠️ Product: {$product->name} (ID: {$product->id})");
                foreach ($conflicts['issues'] as $issue) {
                    $this->line("   • {$issue}");
                }
                
                if (!$isDryRun) {
                    $fixed = ProductVariantTransitionService::autoFixConflicts($product);
                    if (!empty($fixed)) {
                        $fixedCount++;
                        $this->info("✅ Fixed:");
                        foreach ($fixed as $fix) {
                            $this->line("   • {$fix}");
                        }
                    }
                } else {
                    $this->info("🔧 Would fix:");
                    foreach ($conflicts['fixes'] as $fix) {
                        $this->line("   • {$fix}");
                    }
                }
                
                $this->line('');
            }
        }
        
        if ($conflictCount === 0) {
            $this->info('✅ No conflicts found! All products are properly configured.');
        } else {
            if ($isDryRun) {
                $this->warn("Found {$conflictCount} products with conflicts.");
                $this->info("Run without --dry-run to fix these issues.");
            } else {
                $this->info("✅ Fixed {$fixedCount} out of {$conflictCount} products with conflicts.");
            }
        }
        
        // Additional statistics
        $this->line('');
        $this->info('📊 Product Statistics:');
        $totalProducts = $products->count();
        $variantProducts = $products->where('has_variants', true)->count();
        $simpleProducts = $totalProducts - $variantProducts;
        
        $this->line("   • Total products: {$totalProducts}");
        $this->line("   • Products with variants: {$variantProducts}");
        $this->line("   • Simple products: {$simpleProducts}");
        
        $totalVariants = $products->sum(fn($p) => $p->variants()->count());
        $this->line("   • Total variants: {$totalVariants}");
        
        return Command::SUCCESS;
    }
}
