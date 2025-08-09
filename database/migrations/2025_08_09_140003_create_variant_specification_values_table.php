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
        Schema::create('variant_specification_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();
            $table->foreignId('specification_attribute_id')
                ->constrained('specification_attributes')
                ->cascadeOnDelete();
            
            // Store values based on data type
            $table->text('value_text')->nullable()
                ->comment('For text and enum data types');
            $table->decimal('value_number', 15, 4)->nullable()
                ->comment('For number data type');
            $table->boolean('value_boolean')->nullable()
                ->comment('For boolean data type');
            
            // For enum types, optionally reference the option
            $table->foreignId('specification_attribute_option_id')->nullable()
                ->constrained('specification_attribute_options')
                ->nullOnDelete()
                ->comment('For enum types, reference to the selected option');
            
            $table->timestamps();

            // Ensure unique attribute per variant
            $table->unique(['product_variant_id', 'specification_attribute_id'], 'variant_spec_unique');

            // Indexes for performance and filtering
            $table->index(['specification_attribute_id', 'value_text']);
            $table->index(['specification_attribute_id', 'value_number']);
            $table->index(['specification_attribute_id', 'value_boolean']);
            $table->index(['product_variant_id', 'specification_attribute_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_specification_values');
    }
};
