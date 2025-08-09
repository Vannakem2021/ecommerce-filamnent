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
        Schema::create('specification_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // e.g., "CPU", "GPU", "Screen Size", "RAM"
            $table->string('code')->unique(); // e.g., "cpu", "gpu", "screen_size", "ram"
            $table->enum('data_type', ['text', 'number', 'boolean', 'enum'])->default('text');
            $table->string('unit', 50)->nullable(); // e.g., "GHz", "inch", "W", "GB", "hours"
            $table->enum('scope', ['product', 'variant'])->default('product')
                ->comment('product=same for all variants, variant=changes per variant');
            $table->boolean('is_filterable')->default(true)
                ->comment('Whether this attribute can be used for filtering products');
            $table->boolean('is_comparable')->default(true)
                ->comment('Whether this attribute should appear in product comparisons');
            $table->boolean('is_required')->default(false)
                ->comment('Whether this attribute is required for products');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0)->index();
            $table->text('description')->nullable()
                ->comment('Description of what this attribute represents');
            $table->timestamps();

            // Indexes for performance
            $table->index(['scope', 'is_active']);
            $table->index(['is_filterable', 'is_active']);
            $table->index(['sort_order', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specification_attributes');
    }
};
