<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyzeLegacyVariantSystem extends Command
{
    protected $signature = 'analyze:legacy-variant-system 
                           {--export=storage/migration-docs/legacy-analysis.json : Export results to file}
                           {--detailed : Show detailed analysis}';

    protected $description = 'Analyze legacy variant system for migration planning';

    public function handle()
    {
        $this->info('🔍 Analyzing legacy variant system...');
        
        $analysis = [
            'generated_at' => now()->toISOString(),
            'table_analysis' => $this->analyzeTableStructure(),
            'data_analysis' => $this->analyzeDataStructure(),
            'relationship_analysis' => $this->analyzeRelationships(),
            'migration_complexity' => $this->assessMigrationComplexity()
        ];
        
        $this->displayAnalysis($analysis);
        
        if ($this->option('export')) {
            $this->exportAnalysis($analysis);
        }
        
        return 0;
    }
    
    protected function analyzeTableStructure(): array
    {
        $this->info('📋 Analyzing table structure...');
        
        $legacyTables = [
            'product_attributes',
            'product_attribute_values',
            'product_variant_attributes',
            'specification_attributes',
            'specification_attribute_options',
            'product_specification_values',
            'variant_specification_values'
        ];
        
        $tableAnalysis = [];
        
        foreach ($legacyTables as $table) {
            if (Schema::hasTable($table)) {
                $tableAnalysis[$table] = [
                    'exists' => true,
                    'row_count' => DB::table($table)->count(),
                    'columns' => Schema::getColumnListing($table),
                    'sample_data' => $this->getSampleData($table)
                ];
            } else {
                $tableAnalysis[$table] = ['exists' => false];
            }
        }
        
        return $tableAnalysis;
    }
    
    protected function analyzeDataStructure(): array
    {
        $this->info('📊 Analyzing data structure...');
        
        $analysis = [];
        
        // Analyze product attributes
        if (Schema::hasTable('product_attributes')) {
            $analysis['product_attributes'] = $this->analyzeProductAttributes();
        }
        
        // Analyze variant attributes
        if (Schema::hasTable('product_variant_attributes')) {
            $analysis['variant_attributes'] = $this->analyzeVariantAttributes();
        }
        
        // Analyze specifications
        if (Schema::hasTable('specification_attributes')) {
            $analysis['specifications'] = $this->analyzeSpecifications();
        }
        
        return $analysis;
    }
    
    protected function analyzeProductAttributes(): array
    {
        try {
            $totalAttributes = DB::table('product_attributes')->count();
            
            // Attribute types distribution
            $typeDistribution = DB::table('product_attributes')
                ->select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->get()
                ->keyBy('type')
                ->map(fn($item) => $item->count)
                ->toArray();
            
            // Most used attributes
            $mostUsed = DB::table('product_attributes as pa')
                ->leftJoin('product_attribute_values as pav', 'pa.id', '=', 'pav.product_attribute_id')
                ->select('pa.name', 'pa.type', DB::raw('COUNT(pav.id) as usage_count'))
                ->groupBy('pa.id', 'pa.name', 'pa.type')
                ->orderBy('usage_count', 'desc')
                ->limit(10)
                ->get()
                ->toArray();
            
            return [
                'total_count' => $totalAttributes,
                'type_distribution' => $typeDistribution,
                'most_used_attributes' => $mostUsed
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    protected function analyzeVariantAttributes(): array
    {
        try {
            $totalLinks = DB::table('product_variant_attributes')->count();
            
            // Variants with most attributes
            $variantsWithMostAttrs = DB::table('product_variant_attributes as pva')
                ->join('product_variants as pv', 'pva.product_variant_id', '=', 'pv.id')
                ->select('pv.id', 'pv.name', DB::raw('COUNT(*) as attr_count'))
                ->groupBy('pv.id', 'pv.name')
                ->orderBy('attr_count', 'desc')
                ->limit(10)
                ->get()
                ->toArray();
            
            // Attribute usage in variants
            $attrUsageInVariants = DB::table('product_variant_attributes as pva')
                ->join('product_attribute_values as pav', 'pva.product_attribute_value_id', '=', 'pav.id')
                ->join('product_attributes as pa', 'pav.product_attribute_id', '=', 'pa.id')
                ->select('pa.name', DB::raw('COUNT(DISTINCT pva.product_variant_id) as variant_count'))
                ->groupBy('pa.id', 'pa.name')
                ->orderBy('variant_count', 'desc')
                ->limit(10)
                ->get()
                ->toArray();
            
            return [
                'total_variant_attribute_links' => $totalLinks,
                'variants_with_most_attributes' => $variantsWithMostAttrs,
                'attribute_usage_in_variants' => $attrUsageInVariants
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    protected function analyzeSpecifications(): array
    {
        try {
            $totalSpecs = DB::table('specification_attributes')->count();
            
            $specTypes = DB::table('specification_attributes')
                ->select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->get()
                ->keyBy('type')
                ->map(fn($item) => $item->count)
                ->toArray();
            
            return [
                'total_specifications' => $totalSpecs,
                'type_distribution' => $specTypes
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    protected function analyzeRelationships(): array
    {
        $this->info('🔗 Analyzing relationships...');
        
        $relationships = [];
        
        // Orphaned records analysis
        $relationships['orphaned_records'] = $this->findOrphanedRecords();
        
        // Data integrity issues
        $relationships['integrity_issues'] = $this->findIntegrityIssues();
        
        return $relationships;
    }
    
    protected function findOrphanedRecords(): array
    {
        $orphaned = [];
        
        // Orphaned attribute values
        if (Schema::hasTable('product_attribute_values') && Schema::hasTable('product_attributes')) {
            try {
                $orphanedValues = DB::table('product_attribute_values as pav')
                    ->leftJoin('product_attributes as pa', 'pav.product_attribute_id', '=', 'pa.id')
                    ->whereNull('pa.id')
                    ->count();
                $orphaned['attribute_values'] = $orphanedValues;
            } catch (\Exception $e) {
                $orphaned['attribute_values'] = 'error: ' . $e->getMessage();
            }
        }
        
        // Orphaned variant attributes
        if (Schema::hasTable('product_variant_attributes') && Schema::hasTable('product_variants')) {
            try {
                $orphanedVariantAttrs = DB::table('product_variant_attributes as pva')
                    ->leftJoin('product_variants as pv', 'pva.product_variant_id', '=', 'pv.id')
                    ->whereNull('pv.id')
                    ->count();
                $orphaned['variant_attributes'] = $orphanedVariantAttrs;
            } catch (\Exception $e) {
                $orphaned['variant_attributes'] = 'error: ' . $e->getMessage();
            }
        }
        
        return $orphaned;
    }
    
    protected function findIntegrityIssues(): array
    {
        $issues = [];
        
        // Check for duplicate attribute names
        if (Schema::hasTable('product_attributes')) {
            try {
                $duplicateNames = DB::table('product_attributes')
                    ->select('name', DB::raw('COUNT(*) as count'))
                    ->groupBy('name')
                    ->having('count', '>', 1)
                    ->count();
                $issues['duplicate_attribute_names'] = $duplicateNames;
            } catch (\Exception $e) {
                $issues['duplicate_attribute_names'] = 'error: ' . $e->getMessage();
            }
        }
        
        return $issues;
    }
    
    protected function assessMigrationComplexity(): array
    {
        $this->info('🎯 Assessing migration complexity...');
        
        $complexity = [
            'overall_score' => 0,
            'factors' => [],
            'recommendations' => []
        ];
        
        // Factor 1: Number of legacy tables with data
        $tablesWithData = 0;
        $legacyTables = ['product_attributes', 'product_attribute_values', 'product_variant_attributes'];
        
        foreach ($legacyTables as $table) {
            if (Schema::hasTable($table) && DB::table($table)->count() > 0) {
                $tablesWithData++;
            }
        }
        
        $complexity['factors']['legacy_tables_with_data'] = $tablesWithData;
        
        // Factor 2: Data volume
        $totalRecords = 0;
        foreach ($legacyTables as $table) {
            if (Schema::hasTable($table)) {
                $totalRecords += DB::table($table)->count();
            }
        }
        
        $complexity['factors']['total_legacy_records'] = $totalRecords;
        
        // Calculate complexity score
        $score = 0;
        
        if ($tablesWithData == 0) {
            $score = 1; // Very low complexity
            $complexity['recommendations'][] = 'No legacy data to migrate - very simple migration';
        } elseif ($tablesWithData <= 2 && $totalRecords < 1000) {
            $score = 2; // Low complexity
            $complexity['recommendations'][] = 'Low complexity - can migrate in single batch';
        } elseif ($tablesWithData <= 3 && $totalRecords < 10000) {
            $score = 3; // Medium complexity
            $complexity['recommendations'][] = 'Medium complexity - use batch processing';
        } else {
            $score = 4; // High complexity
            $complexity['recommendations'][] = 'High complexity - use small batches and monitor closely';
        }
        
        $complexity['overall_score'] = $score;
        $complexity['complexity_level'] = ['', 'Very Low', 'Low', 'Medium', 'High'][$score];
        
        return $complexity;
    }
    
    protected function getSampleData(string $table, int $limit = 3): array
    {
        try {
            if (!$this->option('detailed')) {
                return [];
            }
            
            return DB::table($table)->limit($limit)->get()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    protected function displayAnalysis(array $analysis): void
    {
        $this->info('');
        $this->info('📊 LEGACY SYSTEM ANALYSIS RESULTS');
        $this->info('================================');
        
        // Table structure summary
        $this->line('📋 Legacy Tables:');
        foreach ($analysis['table_analysis'] as $table => $info) {
            $status = $info['exists'] ? '✅' : '❌';
            $count = $info['exists'] ? " ({$info['row_count']} rows)" : '';
            $this->line("  {$status} {$table}{$count}");
        }
        
        // Migration complexity
        $complexity = $analysis['migration_complexity'];
        $this->info('');
        $this->line("🎯 Migration Complexity: {$complexity['complexity_level']} (Score: {$complexity['overall_score']}/4)");
        
        foreach ($complexity['recommendations'] as $recommendation) {
            $this->line("  💡 {$recommendation}");
        }
        
        // Data insights
        if (isset($analysis['data_analysis']['product_attributes'])) {
            $attrs = $analysis['data_analysis']['product_attributes'];
            $this->info('');
            $this->line("🏷️ Product Attributes: {$attrs['total_count']} total");
            
            if (!empty($attrs['type_distribution'])) {
                $this->line('  Types: ' . implode(', ', array_map(
                    fn($type, $count) => "{$type}({$count})",
                    array_keys($attrs['type_distribution']),
                    $attrs['type_distribution']
                )));
            }
        }
        
        if (isset($analysis['data_analysis']['variant_attributes'])) {
            $varAttrs = $analysis['data_analysis']['variant_attributes'];
            $this->line("🔗 Variant-Attribute Links: {$varAttrs['total_variant_attribute_links']} total");
        }
        
        // Orphaned records
        $orphaned = $analysis['relationship_analysis']['orphaned_records'] ?? [];
        if (!empty($orphaned)) {
            $this->warn('');
            $this->warn('⚠️ Orphaned Records Found:');
            foreach ($orphaned as $type => $count) {
                if (is_numeric($count) && $count > 0) {
                    $this->warn("  {$type}: {$count}");
                }
            }
        }
    }
    
    protected function exportAnalysis(array $analysis): void
    {
        $filename = $this->option('export');
        
        // Ensure directory exists
        $directory = dirname($filename);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        file_put_contents($filename, json_encode($analysis, JSON_PRETTY_PRINT));
        $this->info("📄 Analysis exported to: {$filename}");
    }
}