<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CleanupLegacyVariantSystem extends Command
{
    protected $signature = 'cleanup:legacy-variant-system 
                           {--step= : Specific cleanup step (validate, backup, cleanup, verify)}
                           {--table= : Specific table to cleanup}
                           {--dry-run : Show what would be done without executing}
                           {--force : Skip confirmation prompts}
                           {--keep-backup : Keep backup tables after cleanup}';

    protected $description = 'Clean up legacy variant system tables after successful migration';

    protected $cleanupBatchId;
    protected $legacyTables = [
        'product_attributes',
        'product_attribute_values', 
        'product_variant_attributes',
        'specification_attributes',
        'specification_attribute_options',
        'product_specification_values',
        'variant_specification_values'
    ];

    public function handle()
    {
        $this->cleanupBatchId = Str::uuid()->toString();
        
        $this->warn('⚠️  LEGACY SYSTEM CLEANUP ⚠️');
        $this->info('This will remove legacy variant system tables and data.');
        $this->info("Cleanup Batch ID: {$this->cleanupBatchId}");
        
        $step = $this->option('step');
        
        if ($step) {
            return $this->runSpecificStep($step);
        }
        
        // Run full cleanup process
        return $this->runFullCleanup();
    }
    
    protected function runFullCleanup(): int
    {
        $this->info('🚀 Starting full legacy system cleanup...');
        
        // Step 1: Validate migration completion
        if (!$this->validateMigrationCompletion()) {
            $this->error('Migration validation failed. Cannot proceed with cleanup.');
            return 1;
        }
        
        // Step 2: Create backup of legacy tables
        if (!$this->createLegacyBackup()) {
            $this->error('Backup creation failed. Cannot proceed with cleanup.');
            return 1;
        }
        
        // Step 3: Perform cleanup
        if (!$this->performCleanup()) {
            $this->error('Cleanup failed. Please check logs and consider rollback.');
            return 1;
        }
        
        // Step 4: Verify cleanup
        if (!$this->verifyCleanup()) {
            $this->warn('Cleanup verification found issues. Please review manually.');
            return 1;
        }
        
        $this->info('✅ Legacy system cleanup completed successfully!');
        return 0;
    }
    
    protected function runSpecificStep(string $step): int
    {
        switch ($step) {
            case 'validate':
                return $this->validateMigrationCompletion() ? 0 : 1;
            case 'backup':
                return $this->createLegacyBackup() ? 0 : 1;
            case 'cleanup':
                return $this->performCleanup() ? 0 : 1;
            case 'verify':
                return $this->verifyCleanup() ? 0 : 1;
            default:
                $this->error("Unknown step: {$step}");
                return 1;
        }
    }
    
    protected function validateMigrationCompletion(): bool
    {
        $this->info('🔍 Validating migration completion...');
        
        // Check if migration columns exist
        if (!Schema::hasColumn('products', 'migrated_to_json') || 
            !Schema::hasColumn('product_variants', 'migrated_to_json')) {
            $this->error('Migration tracking columns not found. Run Phase 1 first.');
            return false;
        }
        
        // Check migration completion status
        $totalProductsWithVariants = Product::where('has_variants', true)->count();
        $migratedProducts = Product::where('has_variants', true)
            ->where('migrated_to_json', true)
            ->count();
        
        $totalVariants = ProductVariant::count();
        $migratedVariants = ProductVariant::where('migrated_to_json', true)->count();
        
        $this->line("Products with variants: {$totalProductsWithVariants}");
        $this->line("Migrated products: {$migratedProducts}");
        $this->line("Total variants: {$totalVariants}");
        $this->line("Migrated variants: {$migratedVariants}");
        
        if ($migratedProducts < $totalProductsWithVariants) {
            $this->error("Not all products have been migrated ({$migratedProducts}/{$totalProductsWithVariants})");
            return false;
        }
        
        if ($migratedVariants < $totalVariants) {
            $this->error("Not all variants have been migrated ({$migratedVariants}/{$totalVariants})");
            return false;
        }
        
        // Validate JSON data exists
        $productsWithJsonConfig = Product::whereNotNull('variant_config')->count();
        $variantsWithJsonOptions = ProductVariant::whereNotNull('options')->count();
        
        $this->line("Products with JSON config: {$productsWithJsonConfig}");
        $this->line("Variants with JSON options: {$variantsWithJsonOptions}");
        
        if ($productsWithJsonConfig === 0 && $variantsWithJsonOptions === 0) {
            $this->warn('No JSON data found. This might be expected if no variants had attributes.');
        }
        
        $this->info('✅ Migration completion validated');
        return true;
    }
    
    protected function createLegacyBackup(): bool
    {
        $this->info('💾 Creating backup of legacy tables...');
        
        if ($this->option('dry-run')) {
            $this->line('[DRY RUN] Would create backup of legacy tables');
            return true;
        }
        
        $backupSuffix = '_backup_' . now()->format('Y_m_d_H_i_s');
        $backedUpTables = [];
        
        foreach ($this->legacyTables as $table) {
            if (!Schema::hasTable($table)) {
                $this->line("  Skipping {$table} (does not exist)");
                continue;
            }
            
            $rowCount = DB::table($table)->count();
            if ($rowCount === 0) {
                $this->line("  Skipping {$table} (empty table)");
                continue;
            }
            
            $backupTable = $table . $backupSuffix;
            
            try {
                // Create backup table with same structure
                DB::statement("CREATE TABLE {$backupTable} LIKE {$table}");
                
                // Copy data
                DB::statement("INSERT INTO {$backupTable} SELECT * FROM {$table}");
                
                $this->line("  ✅ Backed up {$table} -> {$backupTable} ({$rowCount} rows)");
                $backedUpTables[] = $backupTable;
                
            } catch (\Exception $e) {
                $this->error("Failed to backup {$table}: {$e->getMessage()}");
                
                // Cleanup partial backups
                foreach ($backedUpTables as $cleanup_table) {
                    try {
                        DB::statement("DROP TABLE IF EXISTS {$cleanup_table}");
                    } catch (\Exception $cleanup_e) {
                        // Silent cleanup failure
                    }
                }
                
                return false;
            }
        }
        
        if (empty($backedUpTables)) {
            $this->info('  No tables required backup (all empty or non-existent)');
        } else {
            $this->info("✅ Created backup for " . count($backedUpTables) . " tables");
        }
        
        // Log backup information
        $this->logCleanupAction('backup_created', [
            'backed_up_tables' => $backedUpTables,
            'backup_suffix' => $backupSuffix
        ]);
        
        return true;
    }
    
    protected function performCleanup(): bool
    {
        $this->info('🧹 Performing legacy system cleanup...');
        
        if (!$this->option('force') && !$this->option('dry-run')) {
            $this->warn('This will permanently delete legacy tables and data.');
            
            if (!$this->confirm('Are you absolutely sure you want to proceed?')) {
                $this->info('Cleanup cancelled by user.');
                return false;
            }
        }
        
        $specificTable = $this->option('table');
        $tablesToClean = $specificTable ? [$specificTable] : $this->legacyTables;
        
        $cleanedTables = [];
        $skippedTables = [];
        
        foreach ($tablesToClean as $table) {
            if (!Schema::hasTable($table)) {
                $skippedTables[] = $table . ' (does not exist)';
                continue;
            }
            
            $rowCount = DB::table($table)->count();
            
            if ($this->option('dry-run')) {
                $this->line("  [DRY RUN] Would drop table: {$table} ({$rowCount} rows)");
                continue;
            }
            
            try {
                Schema::dropIfExists($table);
                $this->line("  ✅ Dropped {$table} ({$rowCount} rows)");
                $cleanedTables[] = $table;
                
                $this->logCleanupAction('table_dropped', [
                    'table' => $table,
                    'row_count' => $rowCount
                ]);
                
            } catch (\Exception $e) {
                $this->error("  Failed to drop {$table}: {$e->getMessage()}");
                return false;
            }
        }
        
        // Report results
        if (!empty($cleanedTables)) {
            $this->info("✅ Successfully cleaned up " . count($cleanedTables) . " tables");
        }
        
        if (!empty($skippedTables)) {
            $this->line("Skipped tables: " . implode(', ', $skippedTables));
        }
        
        return true;
    }
    
    protected function verifyCleanup(): bool
    {
        $this->info('🔍 Verifying cleanup completion...');
        
        $remainingTables = [];
        
        foreach ($this->legacyTables as $table) {
            if (Schema::hasTable($table)) {
                $remainingTables[] = $table;
            }
        }
        
        if (!empty($remainingTables)) {
            $this->warn("Legacy tables still exist: " . implode(', ', $remainingTables));
            
            if (!$this->option('table')) { // Only fail if we tried to clean all tables
                return false;
            }
        }
        
        // Verify migration data integrity
        $this->validatePostCleanupState();
        
        $this->info('✅ Cleanup verification completed');
        return true;
    }
    
    protected function validatePostCleanupState(): void
    {
        $this->info('🔍 Validating post-cleanup state...');
        
        // Check JSON data is still intact
        $productsWithJsonConfig = Product::whereNotNull('variant_config')->count();
        $variantsWithJsonOptions = ProductVariant::whereNotNull('options')->count();
        
        $this->line("Products with JSON config: {$productsWithJsonConfig}");
        $this->line("Variants with JSON options: {$variantsWithJsonOptions}");
        
        // Check migration flags are still set
        $migratedProducts = Product::where('migrated_to_json', true)->count();
        $migratedVariants = ProductVariant::where('migrated_to_json', true)->count();
        
        $this->line("Products marked as migrated: {$migratedProducts}");
        $this->line("Variants marked as migrated: {$migratedVariants}");
        
        // Sample data validation
        $sampleProduct = Product::where('has_variants', true)
            ->where('migrated_to_json', true)
            ->with('variants')
            ->first();
            
        if ($sampleProduct) {
            $this->line("Sample product verification:");
            $this->line("  Product {$sampleProduct->id}: " . 
                ($sampleProduct->variant_config ? 'Has JSON config' : 'No JSON config'));
            
            foreach ($sampleProduct->variants->take(2) as $variant) {
                $this->line("  Variant {$variant->id}: " . 
                    ($variant->options ? 'Has JSON options' : 'No JSON options'));
            }
        }
    }
    
    protected function logCleanupAction(string $action, array $data = []): void
    {
        if (!Schema::hasTable('variant_migration_audit')) {
            return;
        }
        
        try {
            DB::table('variant_migration_audit')->insert([
                'phase' => 'phase_3',
                'step' => 'cleanup',
                'entity_type' => 'system',
                'entity_id' => null,
                'old_data' => json_encode($data),
                'new_data' => json_encode(['action' => $action]),
                'status' => 'completed',
                'batch_id' => $this->cleanupBatchId,
                'started_at' => now(),
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            // Silent failure for audit logging
        }
    }
}