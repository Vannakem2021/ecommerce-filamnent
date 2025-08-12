<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class ValidateStockConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:validate-stock-config {--fix : Automatically fix invalid configurations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate and optionally fix stock tracking configurations for all products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Validating stock configurations for all products...');
        
        $products = Product::with('variants')->get();
        $invalidProducts = [];
        $fixedCount = 0;
        
        foreach ($products as $product) {
            $errors = $product->validateStockConfiguration();
            
            if (!empty($errors)) {
                $invalidProducts[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'has_variants' => $product->has_variants,
                    'track_inventory' => $product->track_inventory,
                    'stock_quantity' => $product->stock_quantity,
                    'errors' => $errors
                ];
                
                // Auto-fix if requested
                if ($this->option('fix')) {
                    $this->fixProductConfiguration($product);
                    $fixedCount++;
                }
            }
        }
        
        if (empty($invalidProducts)) {
            $this->info('✅ All products have valid stock configurations!');
            return Command::SUCCESS;
        }
        
        $this->warn("Found " . count($invalidProducts) . " products with invalid stock configurations:");
        
        foreach ($invalidProducts as $product) {
            $this->line("");
            $this->line("Product ID: {$product['id']} - {$product['name']}");
            $this->line("Has Variants: " . ($product['has_variants'] ? 'Yes' : 'No'));
            $this->line("Track Inventory: " . ($product['track_inventory'] ? 'Yes' : 'No'));
            $this->line("Stock Quantity: {$product['stock_quantity']}");
            $this->line("Errors:");
            foreach ($product['errors'] as $error) {
                $this->line("  - {$error}");
            }
        }
        
        if ($this->option('fix')) {
            $this->info("\n✅ Fixed {$fixedCount} products automatically.");
        } else {
            $this->line("\nRun with --fix option to automatically correct these issues.");
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Fix stock configuration for a product
     */
    private function fixProductConfiguration(Product $product): void
    {
        if ($product->has_variants) {
            // Products with variants should not track inventory at product level
            $product->update([
                'track_inventory' => false,
                'stock_quantity' => 0
            ]);
            
            // Ensure all active variants track inventory
            $product->variants()
                ->where('is_active', true)
                ->update(['track_inventory' => true]);
                
        } else {
            // Products without variants should track inventory at product level
            $product->update(['track_inventory' => true]);
        }
        
        $this->line("  ✅ Fixed: {$product->name}");
    }
}
