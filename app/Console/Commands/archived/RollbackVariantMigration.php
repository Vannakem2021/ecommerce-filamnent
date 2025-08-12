<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RollbackVariantMigration extends Command
{
    protected $signature = 'rollback:variant-migration 
                           {--phase=all : Migration phase to rollback (phase_1, phase_2, all)}
                           {--confirm : Skip confirmation prompts}
                           {--dry-run : Show what would be done without executing}';

    protected $description = 'Rollback variant migration to previous state';

    public function handle()
    {
        $phase = $this->option('phase');
        $confirm = $this->option('confirm');
        $dryRun = $this->option('dry-run');
        
        $this->warn('⚠️  VARIANT MIGRATION ROLLBACK ⚠️');
        $this->line('This will undo migration changes and restore the previous system state.');
        
        if (!$confirm && !$this->confirm('Are you sure you want to proceed with rollback?')) {
            $this->info('Rollback cancelled.');
            return 0;
        }
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }
        
        $this->info("🔄 Starting rollback for phase: {$phase}");
        
        switch ($phase) {
            case 'phase_1':
                $this->rollbackPhase1($dryRun);
                break;
            case 'phase_2':
                $this->rollbackPhase2($dryRun);
                break;
            case 'all':
                $this->rollbackPhase2($dryRun);
                $this->rollbackPhase1($dryRun);
                break;
            default:
                $this->error("Unknown phase: {$phase}");
                return 1;
        }
        
        if (!$dryRun) {
            $this->info('✅ Rollback completed!');
            $this->info('💡 Please verify your system and run validation checks.');
        }
        
        return 0;
    }
    
    protected function rollbackPhase1(bool $dryRun): void
    {
        $this->info('📦 Rolling back Phase 1 - Pre-migration setup...');
        
        // Reset migration flags
        if ($this->shouldExecute('Reset product migration flags', $dryRun)) {
            if (!$dryRun) {
                DB::table('products')
                    ->where('migrated_to_json', true)
                    ->update(['migrated_to_json' => false]);
            }
        }
        
        if ($this->shouldExecute('Reset variant migration flags', $dryRun)) {
            if (!$dryRun) {
                DB::table('product_variants')
                    ->where('migrated_to_json', true)
                    ->update(['migrated_to_json' => false]);
            }
        }
        
        // Clear migration audit logs
        if ($this->shouldExecute('Clear Phase 1 audit logs', $dryRun)) {
            if (!$dryRun && Schema::hasTable('variant_migration_audit')) {
                DB::table('variant_migration_audit')
                    ->where('phase', 'phase_1')
                    ->delete();
            }
        }
    }
    
    protected function rollbackPhase2(bool $dryRun): void
    {
        $this->info('🔄 Rolling back Phase 2 - Data migration...');
        
        // Clear JSON data from products
        if ($this->shouldExecute('Clear product JSON attributes', $dryRun)) {
            if (!$dryRun) {
                DB::table('products')
                    ->whereNotNull('attributes')
                    ->update(['attributes' => null]);
                    
                DB::table('products')
                    ->whereNotNull('variant_config')
                    ->update(['variant_config' => null]);
            }
        }
        
        // Clear JSON data from variants
        if ($this->shouldExecute('Clear variant JSON options', $dryRun)) {
            if (!$dryRun) {
                DB::table('product_variants')
                    ->whereNotNull('options')
                    ->update(['options' => null]);
            }
        }
        
        // Reset override prices
        if ($this->shouldExecute('Reset variant override prices', $dryRun)) {
            if (!$dryRun) {
                DB::table('product_variants')
                    ->whereNotNull('override_price')
                    ->update(['override_price' => null]);
            }
        }
        
        // Clear Phase 2 audit logs
        if ($this->shouldExecute('Clear Phase 2 audit logs', $dryRun)) {
            if (!$dryRun && Schema::hasTable('variant_migration_audit')) {
                DB::table('variant_migration_audit')
                    ->where('phase', 'phase_2')
                    ->delete();
            }
        }
    }
    
    protected function shouldExecute(string $action, bool $dryRun): bool
    {
        if ($dryRun) {
            $this->line("  [DRY RUN] Would execute: {$action}");
            return false;
        } else {
            $this->line("  Executing: {$action}");
            return true;
        }
    }
}