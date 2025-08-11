<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;

class TestJsonVariantFrontend extends Command
{
    protected $tests = [];
    protected $failures = [];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:json-variant-frontend 
                           {--detailed : Show detailed test results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test JSON variant system frontend functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing JSON variant frontend functionality...');
        
        $this->testProductMethods();
        $this->testVariantMethods();
        $this->testLivewireComponent();
        
        $this->displayResults();
        
        return empty($this->failures) ? 0 : 1;
    }
    
    protected function testProductMethods(): void
    {
        $this->info('🔍 Testing Product model methods...');
        
        $product = Product::where('has_variants', true)->with('variants')->first();
        
        if (!$product) {
            $this->failures[] = 'No products with variants found';
            return;
        }
        
        // Test getAvailableOptions
        try {
            $options = $product->getAvailableOptions();
            $this->tests['product_getAvailableOptions'] = is_array($options) ? 'passed' : 'failed';
            $this->line('  ✅ getAvailableOptions');
        } catch (\Exception $e) {
            $this->tests['product_getAvailableOptions'] = 'error';
            $this->failures[] = "getAvailableOptions failed: {$e->getMessage()}";
            $this->line('  ❌ getAvailableOptions');
        }
        
        // Test findVariantByOptions
        try {
            $options = $product->getAvailableOptions();
            if (!empty($options)) {
                $firstOption = array_slice($options, 0, 1, true);
                $testOptions = [];
                foreach ($firstOption as $key => $values) {
                    $testOptions[$key] = $values[0];
                }
                
                $variant = $product->findVariantByOptions($testOptions);
                $this->tests['product_findVariantByOptions'] = $variant instanceof ProductVariant ? 'passed' : 'failed';
                $this->line('  ✅ findVariantByOptions');
            } else {
                $this->tests['product_findVariantByOptions'] = 'skipped';
                $this->line('  ⏭️ findVariantByOptions (no options available)');
            }
        } catch (\Exception $e) {
            $this->tests['product_findVariantByOptions'] = 'error';
            $this->failures[] = "findVariantByOptions failed: {$e->getMessage()}";
            $this->line('  ❌ findVariantByOptions');
        }
        
        // Test hasStock
        try {
            $hasStock = $product->hasStock();
            $this->tests['product_hasStock'] = is_bool($hasStock) ? 'passed' : 'failed';
            $this->line('  ✅ hasStock');
        } catch (\Exception $e) {
            $this->tests['product_hasStock'] = 'error';
            $this->failures[] = "hasStock failed: {$e->getMessage()}";
            $this->line('  ❌ hasStock');
        }
    }
    
    protected function testVariantMethods(): void
    {
        $this->info('🔍 Testing ProductVariant model methods...');
        
        $variant = ProductVariant::whereNotNull('options')->first();
        
        if (!$variant) {
            $this->warn('No variants with JSON options found - skipping variant tests');
            return;
        }
        
        // Test getVariantOptions
        try {
            $options = $variant->getVariantOptions();
            $this->tests['variant_getVariantOptions'] = is_array($options) ? 'passed' : 'failed';
            $this->line('  ✅ getVariantOptions');
        } catch (\Exception $e) {
            $this->tests['variant_getVariantOptions'] = 'error';
            $this->failures[] = "getVariantOptions failed: {$e->getMessage()}";
            $this->line('  ❌ getVariantOptions');
        }
        
        // Test getEffectivePrice
        try {
            $price = $variant->getEffectivePrice();
            $this->tests['variant_getEffectivePrice'] = is_numeric($price) ? 'passed' : 'failed';
            $this->line('  ✅ getEffectivePrice');
        } catch (\Exception $e) {
            $this->tests['variant_getEffectivePrice'] = 'error';
            $this->failures[] = "getEffectivePrice failed: {$e->getMessage()}";
            $this->line('  ❌ getEffectivePrice');
        }
        
        // Test getDisplayName
        try {
            $name = $variant->getDisplayName();
            $this->tests['variant_getDisplayName'] = is_string($name) && !empty($name) ? 'passed' : 'failed';
            $this->line('  ✅ getDisplayName');
        } catch (\Exception $e) {
            $this->tests['variant_getDisplayName'] = 'error';
            $this->failures[] = "getDisplayName failed: {$e->getMessage()}";
            $this->line('  ❌ getDisplayName');
        }
    }
    
    protected function testLivewireComponent(): void
    {
        $this->info('🔍 Testing Livewire component...');
        
        $product = Product::where('has_variants', true)->with('variants')->first();
        
        if (!$product) {
            $this->failures[] = 'No products for Livewire testing';
            return;
        }
        
        try {
            $component = new \App\Livewire\ProductDetailPage();
            $component->mount($product);
            
            $this->tests['livewire_mount'] = 'passed';
            $this->line('  ✅ Component mount');
            
            // Test availableOptions
            $hasOptions = is_array($component->availableOptions);
            $this->tests['livewire_availableOptions'] = $hasOptions ? 'passed' : 'failed';
            $this->line($hasOptions ? '  ✅ availableOptions' : '  ❌ availableOptions');
            
        } catch (\Exception $e) {
            $this->tests['livewire_mount'] = 'error';
            $this->failures[] = "Livewire component failed: {$e->getMessage()}";
            $this->line('  ❌ Component mount');
        }
    }
    
    protected function displayResults(): void
    {
        $this->info('');
        $this->info('📊 TEST RESULTS');
        $this->info('===============');
        
        $passed = 0;
        $total = count($this->tests);
        
        foreach ($this->tests as $test => $result) {
            if ($result === 'passed') {
                $passed++;
            }
        }
        
        $this->line("Passed: {$passed}/{$total}");
        
        if (empty($this->failures)) {
            $this->info('🎉 All tests passed!');
        } else {
            $this->error('⚠️ Some tests failed:');
            foreach ($this->failures as $failure) {
                $this->line("  • {$failure}");
            }
        }
    }
}
