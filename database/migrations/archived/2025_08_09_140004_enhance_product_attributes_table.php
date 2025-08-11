<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_attributes', function (Blueprint $table) {
            // Add purpose to distinguish between variant and specification attributes
            $table->enum('purpose', ['variant', 'specification'])->default('variant')->after('type')
                ->comment('variant=creates selectable options, specification=descriptive only');
            
            // Add data type support for better attribute handling
            $table->enum('data_type', ['text', 'number', 'boolean', 'enum'])->default('enum')->after('purpose')
                ->comment('Data type for proper value storage and filtering');
            
            // Add unit support for numeric attributes
            $table->string('unit', 50)->nullable()->after('data_type')
                ->comment('Unit of measurement (GB, GHz, inch, etc.)');
            
            // Add filterable flag
            $table->boolean('is_filterable')->default(true)->after('unit')
                ->comment('Whether this attribute can be used for filtering');
            
            // Add comparable flag
            $table->boolean('is_comparable')->default(true)->after('is_filterable')
                ->comment('Whether this attribute should appear in comparisons');
            
            // Add description field
            $table->text('description')->nullable()->after('is_comparable')
                ->comment('Description of what this attribute represents');

            // Add indexes for performance
            $table->index(['purpose', 'is_active']);
            $table->index(['is_filterable', 'is_active']);
        });

        // Update existing attributes to be variant attributes by default
        DB::statement("UPDATE product_attributes SET purpose = 'variant', data_type = 'enum' WHERE purpose IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_attributes', function (Blueprint $table) {
            $table->dropIndex(['purpose', 'is_active']);
            $table->dropIndex(['is_filterable', 'is_active']);
            
            $table->dropColumn([
                'purpose',
                'data_type',
                'unit',
                'is_filterable',
                'is_comparable',
                'description'
            ]);
        });
    }
};
