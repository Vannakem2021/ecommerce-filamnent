<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // WARNING: This migration will permanently delete legacy variant tables
        // Only run this after successful migration to JSON system and thorough testing
        
        $legacyTables = [
            'product_variant_attributes',
            'product_attribute_values', 
            'product_attributes',
            'variant_specification_values',
            'product_specification_values',
            'specification_attribute_options',
            'specification_attributes'
        ];
        
        foreach ($legacyTables as $table) {
            if (Schema::hasTable($table)) {
                $rowCount = DB::table($table)->count();
                Log::info("Dropping legacy table: {$table} ({$rowCount} rows)");
                Schema::dropIfExists($table);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // WARNING: Cannot restore dropped tables without backup
        // This migration is not reversible - tables and data will be permanently lost
        throw new \Exception(
            'Cannot reverse legacy table deletion. ' .
            'Restore from backup if needed: ' .
            'mysql -u username -p database < backup_file.sql'
        );
    }
};
